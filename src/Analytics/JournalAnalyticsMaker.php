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

    public function analyticsForJournal(Journal $journal, array $yearPeriods, array $dateAnalyzers)
    {
        $stat = [
            'Name' => trim($journal->getName()),
            'Issn' => trim($journal->getIssn()),
            'Publisher' => isset($journal->getCrossrefData()['publisher']) ?
                trim($journal->getCrossrefData()['publisher']) : '',
        ];

        /** @var YearPeriod $yearPeriod */
        foreach ($yearPeriods as $yearPeriod) {
            $yearPeriodKey = sprintf('%d_%d%s',
                $yearPeriod->getStart(), $yearPeriod->getEnd(), $yearPeriod->isOpenAccess() === true ? '_OA' : '');

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
            $stat['byPeriods'][$yearPeriodKey]['Articles'] = $totalArticles;

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

                $dateDiffs = [];
                $articlesCount = 0;
                foreach ($articlesIterator as $idx => $item) {
                    /** @var Article $article */
                    $article = $item[0];
                    $dayDiff = $analyzer->datesDiff($article);
                    if ($dayDiff < 0) {
                        continue;
                    }

                    if (isset($dateDiffs[$dayDiff])) {
                        $dateDiffs[$dayDiff]++;
                    } else {
                        $dateDiffs[$dayDiff] = 1;
                    }

                    $articlesCount++;
                    if ($idx % 50 === 0) {
                        $this->em->clear();
                    }
                }
                ksort($dateDiffs);
                $dateDiffsKeys = array_keys($dateDiffs);

                $analyzerResult = [];
                if ($articlesCount > 0) {
                    $analyzerResult['count'] = $articlesCount;
                    $analyzerResult['min'] = sprintf('%d (%d)', $dateDiffsKeys[0], $dateDiffs[$dateDiffsKeys[0]]);
                    $analyzerResult['max'] = sprintf('%d (%d)', $dateDiffsKeys[count($dateDiffsKeys) - 1], $dateDiffs[$dateDiffsKeys[count($dateDiffsKeys) - 1]]);
                    $analyzerResult['avg'] = $this->avg($dateDiffs);
                    [$medianPartSum, $medianDay] = $this->median($dateDiffs);
                    $analyzerResult['median'] = sprintf('%d (%d)', $medianDay, $medianPartSum);
                } else {
                    $analyzerResult = [
                        'count' => 0,
                        'min' => null,
                        'max' => null,
                        'avg' => null,
                        'median' => null
                    ];
                }

                $stat['byPeriods'][$yearPeriodKey]['analyzers'][$analyzer->getName()] = $analyzerResult;

            }

        }
        return $stat;
    }

    private function avg(array $data): int
    {
        $sum = 0;
        foreach ($data as $value => $weight) {
            $sum += $value * $weight;
        }

        $sum = $sum / array_sum(array_values($data));
        return (int)$sum;
    }

    private function median(array $data): array
    {
        $total = array_sum($data) / 2;

        $partSum = 0;
        foreach ($data as $day => $articlesCount) {
            $partSum += $articlesCount;
            if ($partSum >= $total) {
                return [$partSum, $day];
            }
        }
        return null;
    }
}