<?php


namespace App\Analytics;


use App\Analytics\Analyzer\AnalyzerInterface;
use App\Entity\Article;
use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;

class JournalAnalyticsMaker
{

    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function analyticsForJournal(Journal $journal, array $yearPeriods, array $dateAnalyzers): array
    {
        $stat = [];

        $stat['common'] = $this->makeCommonStat($journal);
        $stat['periods'] = $this->makePeriodStat($journal, $yearPeriods, $dateAnalyzers);

        return $stat;
    }

    private function makeCommonStat(Journal $journal): array {
        $stat = [];

        $articlesCount = (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $stat['count'] = $articlesCount;

        $minYear = (int) $this->em->createQueryBuilder()
            ->select('MIN(entity.year)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $stat['min'] = $minYear;

        $maxYear = (int) $this->em->createQueryBuilder()
            ->select('MAX(entity.year)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $stat['max'] = $maxYear;

        return $stat;
    }

    private function makePeriodStat(Journal $journal, array $yearPeriods, array $dateAnalyzers): array {
        $stat = [];

        /** @var YearPeriod $yearPeriod */
        foreach ($yearPeriods as $yearPeriod) {
            $yearPeriodKey = sprintf('%d_%d%s',
                $yearPeriod->getStart(), $yearPeriod->getEnd(), $yearPeriod->isOpenAccess() === true ? '_OA' : '');

            $stat[$yearPeriodKey] = [
                'start' => $yearPeriod->getStart(),
                'end' => $yearPeriod->getEnd(),
                'openAccess' => $yearPeriod->isOpenAccess()
            ];

            $totalArticles = (int)$this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(Article::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->andWhere('entity.year >= :startYear')
                ->andWhere('entity.year <= :endYear')
                ->andWhere($yearPeriod->isOpenAccess() ? 'entity.openAccess = true' : '1=1' )
                ->setParameter('journal', $journal)
                ->setParameter('startYear', $yearPeriod->getStart())
                ->setParameter('endYear', $yearPeriod->getEnd())
                ->getQuery()
                ->getSingleScalarResult();
            $stat[$yearPeriodKey]['articles'] = $totalArticles;

            /** @var AnalyzerInterface $analyzer */
            foreach ($dateAnalyzers as $analyzer) {
                $articlesIteratorQb = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from(Article::class, 'entity')
                    ->andWhere('entity.journal = :journal')
                    ->andWhere($yearPeriod->isOpenAccess() ? 'entity.openAccess = true' : '1=1' )
                    ->andWhere('entity.year >= :startYear')
                    ->andWhere('entity.year <= :endYear')
                    ->setParameter('journal', $journal)
                    ->setParameter('startYear', $yearPeriod->getStart())
                    ->setParameter('endYear', $yearPeriod->getEnd());
                $analyzer->limitArticles($articlesIteratorQb);

                $articlesIterator = $articlesIteratorQb->getQuery()
                    ->iterate();

                $dateSeries = $this->buildDateSeries($articlesIterator, $analyzer);
                $dateSeriesHistogram = $this->buildHistogram($dateSeries);

                if (count($dateSeries) > 0) {
                    $medianIdx = $this->median($dateSeries);

                    $analyzerResult = [
                        'count' => count($dateSeries),
                        'min' => $dateSeries[0],
                        'min_count' => $dateSeriesHistogram[$dateSeries[0]],
                        'max' => $dateSeries[count($dateSeries) - 1],
                        'max_count' => $dateSeriesHistogram[$dateSeries[count($dateSeries) - 1]],
                        'avg' => $this->avg($dateSeries),
                        'median' => $dateSeries[$medianIdx],
                        'median_count' => $medianIdx + 1,
                        'histogram' => $dateSeriesHistogram,
                        'quartiles' => $this->quartiles($dateSeries)
                    ];
                } else {
                    $analyzerResult = [
                        'count' => 0,
                        'min' => null,
                        'min_count' => null,
                        'max' => null,
                        'max_count' => null,
                        'avg' => null,
                        'median' => null,
                        'median_count' => null,
                        'histogram' => [],
                        'quartiles' => []
                    ];
                }

                $stat[$yearPeriodKey]['analyzers'][$analyzer->getName()] = $analyzerResult;

            }
        }

        return $stat;
    }

   private function avg(array $data): int
    {
        if(count($data) === 0) {
            return 0;
        }

        $sum = 0;
        foreach ($data as $value) {
            $sum += $value;
        }

        return (int) ($sum / count($data));
    }

    private function median(array $data): int
    {
        if(count($data) === 0) {
            return 0;
        }
        return floor(count($data) / 2);
    }

    private function buildHistogram(array $dateSeries): array
    {
        $result = [];
        foreach($dateSeries as $value){
            if(!isset($result[$value])) {
                $result[$value] = 1;
            } else {
                $result[$value]++;
            }
        }
        ksort($result);
        return $result;
    }

    private function buildDateSeries(iterable $articlesIterator, AnalyzerInterface $analyzer): array
    {
        $dateSeries = [];

        foreach ($articlesIterator as $idx => $item) {
            /** @var Article $article */
            $article = $item[0];
            $dayDiff = $analyzer->datesDiff($article);
            if ($dayDiff < 0) {
                continue;
            }

            $dateSeries[] = $dayDiff;

            if ($idx % 50 === 0) {
                $this->em->clear();
            }
        }
        sort($dateSeries);
        return $dateSeries;
    }
    private function quartiles(array $dateSeries): array
    {
        if(count($dateSeries) < 4) {
            return [];
        }

        $quartiles = [];

        for($quartile = 1; $quartile <= 4; $quartile++) {
            $endIdx = (int) floor((count($dateSeries) / 4) * $quartile - 1);
            $quartiles[] = [
                'quartile' => $quartile,
                'idx' => $endIdx,
                'value' => $dateSeries[$endIdx],
            ];
        }

        return $quartiles;
    }

}