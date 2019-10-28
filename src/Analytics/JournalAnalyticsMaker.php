<?php


namespace App\Analytics;


use App\Analytics\Analyzer\AnalyzerInterface;
use App\Entity\Article;
use App\Entity\Journal\Journal;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\Iterator\IteratorUtils;
use App\Lib\ScienceArticleFilter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class JournalAnalyticsMaker
{
    protected $em;
    protected $scienceArticleFilter;

    public function __construct(
        EntityManagerInterface $em,
        ScienceArticleFilter $scienceArticleFilter)
    {
        $this->em = $em;
        $this->scienceArticleFilter = $scienceArticleFilter;
    }

    public function analyticsForJournal(Journal $journal, YearPeriod $yearPeriod, array $dateAnalyzers): array
    {
        $stat = [];

        $iterator = DoctrineIterator::idIterator(
            $yearPeriod->limitQuery(
                $this->dataQuery(
                    $this->journalQuery($journal))));
        $iterator = $this->scienceArticleFilter->apply($iterator);
        $periodArticlesCount = IteratorUtils::itemCount($iterator);
        $stat['articles_count'] = $periodArticlesCount;

        $this->em->clear();

        /** @var AnalyzerInterface $analyzer */
        foreach ($dateAnalyzers as $analyzer) {
            $articlesIterator = DoctrineIterator::idIterator(
                $analyzer->limitArticles(
                    $yearPeriod->limitQuery(
                        $this->dataQuery(
                            $this->journalQuery($journal)))));
            $articlesIterator = $this->scienceArticleFilter->apply($articlesIterator);

            $dateSeries = $this->buildDateSeries($articlesIterator, $analyzer);
            $dateSeriesHistogram = $this->buildHistogram($dateSeries);

            if (count($dateSeries) > 0) {
                $medianIdx = $this->median($dateSeries);

                $analyzerResult = [
                    'articles_count' => count($dateSeries),
                    'percent' => (count($dateSeries) / $periodArticlesCount) * 100,
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

            $stat['analyzers'][$analyzer->getName()] = $analyzerResult;

        }


        return $stat;
    }

    private function avg(array $data): int
    {
        if (count($data) === 0) {
            return 0;
        }

        $sum = 0;
        foreach ($data as $value) {
            $sum += $value;
        }

        return (int)($sum / count($data));
    }

    private function median(array $data): int
    {
        if (count($data) === 0) {
            return 0;
        }
        return floor(count($data) / 2);
    }

    private function buildHistogram(array $dateSeries): array
    {
        $result = [];
        foreach ($dateSeries as $value) {
            if (!isset($result[$value])) {
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

        /** @var Article $article */
        foreach ($articlesIterator as $idx => $article) {
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
        if (count($dateSeries) < 4) {
            return [];
        }

        $quartiles = [];

        for ($quartile = 1; $quartile <= 4; $quartile++) {
            $endIdx = (int)floor((count($dateSeries) / 4) * $quartile - 1);
            $quartiles[] = [
                'quartile' => $quartile,
                'idx' => $endIdx,
                'value' => $dateSeries[$endIdx],
            ];
        }

        return $quartiles;
    }

    private function dataQuery(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->leftJoin('entity.crossrefData', 'crossrefData')
            ->leftJoin('entity.publisherData', 'publisherData')
            ->leftJoin('entity.webOfScienceData', 'webOfScienceData')
            ->leftJoin('entity.unpaywallData', 'unpaywallData')
            ->addSelect(
                'partial crossrefData.{id}',
                'partial webOfScienceData.{id}',
                'publisherData',
                'partial unpaywallData.{id, openAccess}'
            );
        return $qb;
    }

    private function journalQuery(Journal $journal): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal);
    }

}