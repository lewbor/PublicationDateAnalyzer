<?php


namespace App\Command\Analytics;


use App\Entity\Article;
use App\Entity\Journal;
use App\Entity\JournalAnalytics;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalAnalyzerCommand extends Command
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('journal.analyze.dates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $basePath = __DIR__ . '/../../data/dates';

        $this->saveData($this->analyzeArticlesCount(), $basePath . '/articles_count.csv');

        $hasDatePeriods = [
            [
                'start' => null,
                'end' => null,
                'openAccess' => null,
                'path' => 'articles_has_dates_all.csv'
            ],
            [
                'start' => 2000,
                'end' => 2009,
                'openAccess' => null,
                'path' => 'articles_has_dates_2000_2009.csv'
            ],
            [
                'start' => 2010,
                'end' => 2019,
                'openAccess' => null,
                'path' => 'articles_has_dates_2010_2019.csv'
            ],
            [
                'start' => 2010,
                'end' => 2019,
                'openAccess' => true,
                'path' => 'articles_has_dates_2010_2019_OA.csv'
            ],
        ];
        foreach ($hasDatePeriods as $period) {
            $this->saveData($this->analyzeArticleHasDates($period), $basePath . '/' . $period['path']);
        }
    }

    private function analyzeArticleHasDates(array $period): array
    {
        $rows = [];

        $filters = [
            [
                'name' => 'Crossref print',
                'qbFilter' => function (QueryBuilder $qb) {
                    $qb->andWhere('entity.publishedPrint IS NOT NULL');
                },
            ],
            [
                'name' => 'Crossref online',
                'qbFilter' => function (QueryBuilder $qb) {
                    $qb->andWhere('entity.publishedOnline IS NOT NULL');
                },
            ],
            [
                'name' => 'Received-Accepted',
                'qbFilter' => function (QueryBuilder $qb) {
                    $qb->andWhere('entity.publisherReceived IS NOT NULL')
                        ->andWhere('entity.publisherAccepted IS NOT NULL');
                },
            ],
            [
                'name' => 'Accepted-Published',
                'qbFilter' => function (QueryBuilder $qb) {
                    $qb->andWhere('entity.publisherAccepted IS NOT NULL')
                        ->andWhere('entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL');
                },
            ],
            [
                'name' => 'Received-Published',
                'qbFilter' => function (QueryBuilder $qb) {
                    $qb->andWhere('entity.publisherReceived IS NOT NULL')
                        ->andWhere('entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL');
                },
            ]
        ];
        foreach ($this->journalIterator() as $journal) {
            $totalArticlesCount = (int)$this->applyPeriodFilter(
                $this->em->createQueryBuilder()
                    ->select('COUNT(entity.id)')
                    ->from(Article::class, 'entity')
                    ->andWhere('entity.journal = :journal')
                    ->setParameter('journal', $journal),
                $period)
                ->getQuery()
                ->getSingleScalarResult();

            $row = [
                'Journal' => trim($journal->getName()),
                'Publisher' => trim($journal->getCrossrefData()['publisher']) ?? '',
                'Total articles' => $totalArticlesCount
            ];

            foreach ($filters as $filter) {
                $filteredArticlesQb = $this->applyPeriodFilter(
                    $this->em->createQueryBuilder()
                        ->select('COUNT(entity.id)')
                        ->from(Article::class, 'entity')
                        ->andWhere('entity.journal = :journal')
                        ->andWhere('entity.publishedPrint IS NOT NULL')
                        ->setParameter('journal', $journal),
                    $period);
                $filterApplier = $filter['qbFilter'];
                $filterApplier($filteredArticlesQb);

                $filteredArticles = (int)$filteredArticlesQb
                    ->getQuery()
                    ->getSingleScalarResult();
                $row[$filter['name']] = $this->percentage($totalArticlesCount, $filteredArticles);
            }

            $rows[] = $row;
            $this->logger->info(sprintf('%s - Processed %s', $period['path'], $journal->getName()));
        }

        return $rows;
    }

    private function analyzeArticlesCount(): array
    {
        $rows = [];

        $periods = [
            [
                'name' => '2000-2009',
                'start' => 2000,
                'end' => 2009,
                'openAccess' => null
            ],
            [
                'name' => '2010-2019',
                'start' => 2010,
                'end' => 2019,
                'openAccess' => null
            ],
            [
                'name' => '2000-2009-OA',
                'start' => 2000,
                'end' => 2009,
                'openAccess' => true
            ],
            [
                'name' => '2010-2019-OA',
                'start' => 2010,
                'end' => 2019,
                'openAccess' => true
            ],
        ];

        $journalStatList = $this->em->getRepository(JournalAnalytics::class)->findAll();

        foreach ($journalStatList as $journalStat) {
            $journal = $journalStat->getJournal();
            $stat = $journalStat->getAnalytics();

            $row = [
                'Journal' => $journal->getName(),
                'Publisher' => trim($journal->getCrossrefData()['publisher']) ?? '',
                'Period' => sprintf('%d-%d', $stat['common']['min'], $stat['common']['max']),
                'Total articles' => $stat['common']['count']
            ];

            foreach ($periods as $period) {
                $articlesPeriodCount = (int)$this->applyPeriodFilter(
                    $this->em->createQueryBuilder()
                        ->select('COUNT(entity.id)')
                        ->from(Article::class, 'entity')
                        ->andWhere('entity.journal = :journal')
                        ->setParameter('journal', $journal),
                    $period)
                    ->getQuery()
                    ->getSingleScalarResult();
                $row[$period['name']] = $this->percentage($stat['common']['count'], $articlesPeriodCount);
            }

            $rows[] = $row;
            $this->logger->info(sprintf('Processed %s', $journal->getName()));
        }
        return $rows;
    }

    private function applyPeriodFilter(QueryBuilder $qb, array $period): QueryBuilder
    {
        if ($period['start'] !== null) {
            $qb->andWhere('entity.year >= :start')
                ->setParameter('start', $period['start']);
        }
        if ($period['end'] !== null) {
            $qb->andWhere('entity.year <= :end')
                ->setParameter('end', $period['end']);
        }
        if ($period['openAccess'] !== null) {
            $qb->andWhere('entity.openAccess = :openAccess')
                ->setParameter('openAccess', $period['openAccess']);
        }
        return $qb;
    }

    private function saveData(array $data, string $savePath): void
    {
        if (file_exists($savePath)) {
            unlink($savePath);
        }
        $writer = new CsvWriter($savePath, "\t");
        $writer->open();

        foreach ($data as $row) {
            $writer->write($row);
        }

        $writer->close();
    }

    /**
     * @return iterable|Journal[]
     */
    private function journalIterator(): iterable
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }

    private function percentage(int $totalArticlesCount, int $filteredArticles): string
    {
        if($totalArticlesCount === 0) {
            return $filteredArticles;
        }

        return sprintf('%d - %s%%', $filteredArticles,
            number_format($filteredArticles / $totalArticlesCount * 100, 2)
        );
    }
}