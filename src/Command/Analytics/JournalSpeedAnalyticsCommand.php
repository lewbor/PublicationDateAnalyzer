<?php


namespace App\Command\Analytics;


use App\Entity\Article;
use App\Entity\JournalAnalytics;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalSpeedAnalyticsCommand extends Command
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
        $this->setName('journal.speed_analyze');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $comparePeriods = [
            [
                'path' => 'Received_Accepted.csv',
                'columns' => [
                    [
                        'column' => '2000-2009',
                        'analyticsKey' => '2000_2009',
                        'analyzerKey' => 'Received_Accepted'
                    ],
                    [
                        'column' => '2010-2019',
                        'analyticsKey' => '2010_2019',
                        'analyzerKey' => 'Received_Accepted'
                    ]
                ]
            ],
            [
                'path' => 'Received_Published.csv',
                'columns' => [
                    [
                        'column' => '2000-2009',
                        'analyticsKey' => '2000_2009',
                        'analyzerKey' => 'Received_Published'
                    ],
                    [
                        'column' => '2010-2019',
                        'analyticsKey' => '2010_2019',
                        'analyzerKey' => 'Received_Published'
                    ]
                ]
            ],
            [
                'path' => 'Received_Accepted_OA.csv',
                'columns' => [
                    [
                        'column' => '2000-2009',
                        'analyticsKey' => '2000_2009_OA',
                        'analyzerKey' => 'Received_Accepted'
                    ],
                    [
                        'column' => '2010-2019',
                        'analyticsKey' => '2010_2019_OA',
                        'analyzerKey' => 'Received_Accepted'
                    ]
                ]
            ],
            [
                'path' => 'Received_Published_OA.csv',
                'columns' => [
                    [
                        'column' => '2000-2009',
                        'analyticsKey' => '2000_2009_OA',
                        'analyzerKey' => 'Received_Published'
                    ],
                    [
                        'column' => '2010-2019',
                        'analyticsKey' => '2010_2019_OA',
                        'analyzerKey' => 'Received_Published'
                    ]
                ]
            ],
        ];

        $basePath = __DIR__ . '/../../data/stat/speed';
        foreach ($comparePeriods as $comparePeriod) {
            $this->saveData($this->analyzePeriod($comparePeriod), $basePath . '/' . $comparePeriod['path']);
        }
    }

    private function analyzePeriod(array $period): array
    {
        $journalAnalytics = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(JournalAnalytics::class, 'entity')
            ->getQuery()
            ->getResult();

        $rows = [];

        /** @var JournalAnalytics $journalAnalytic */
        foreach ($journalAnalytics as $journalAnalytic) {
            $journal = $journalAnalytic->getJournal();
            $analyticsData = $journalAnalytic->getAnalytics();

            $minYear = (int)$this->em->createQueryBuilder()
                ->select('MIN(entity.year)')
                ->from(Article::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();
            $maxYear = (int)$this->em->createQueryBuilder()
                ->select('MAX(entity.year)')
                ->from(Article::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();

            $row = [
                'Journal' => trim($journal->getName()),
                'Publisher' => trim($journal->getCrossrefData()['publisher']) ?? '',
                'Period' => sprintf('%d-%d', $minYear, $maxYear),
            ];

            foreach ($period['columns'] as $column) {
                $columnName = $column['column'] . '-Total';
                $row[$columnName] = $analyticsData[$column['analyticsKey']]['analyzers'][$column['analyzerKey']]['count'];
            }
            foreach ($period['columns'] as $column) {
                $columnName = $column['column'] . '-Median';
                $row[$columnName] = $analyticsData[$column['analyticsKey']]['analyzers'][$column['analyzerKey']]['median'];
            }

            $rows[] = $row;
        }

        return $rows;
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

}