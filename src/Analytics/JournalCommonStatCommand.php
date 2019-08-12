<?php


namespace App\Analytics;


use App\Analytics\Analyzer\AcceptedPublishedAnalyzer;
use App\Analytics\Analyzer\ReceivedAcceptedAnalyzer;
use App\Analytics\Analyzer\ReceivedPublishedAnalyzer;
use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalCommonStatCommand extends Command
{
    protected $em;
    protected $logger;
    protected $analyticsMaker;
    protected $analyticsSaver;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        JournalAnalyticsMaker $analyticsMaker,
        AnalyticsSaver $analyticsSaver)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->analyticsMaker = $analyticsMaker;
        $this->analyticsSaver = $analyticsSaver;
    }

    protected function configure()
    {
        $this->setName('common_stat');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yearPeriods = [
            new YearPeriod(1800, 2019),
            new YearPeriod(1800, 2019),
            new YearPeriod(2011, 2019, true),
            new YearPeriod(2011, 2011),
            new YearPeriod(2019, 2019),
        ];
        $dateAnalyzers = [
            new ReceivedAcceptedAnalyzer(),
            new AcceptedPublishedAnalyzer(),
            new ReceivedPublishedAnalyzer(),
        ];

        $journalStat = [];

        /** @var Journal $journal */
        foreach ($this->journalIterator() as $journal) {
            $journalStat[$journal->getId()] = $this->analyticsMaker->analyticsForJournal($journal, $yearPeriods, $dateAnalyzers);
            $this->em->clear();
            $this->logger->info(sprintf('Processed journal %s', $journal->getName()));
        }

        $this->analyticsSaver->save($journalStat);
    }

    private function journalIterator(): iterable
    {
        $journals = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->getQuery()
            ->getResult();
        usort($journals, function (Journal $a, Journal $b) {
            if(!isset($a->getCrossrefData()['publisher'])) {
                return 0;
            }
            if(!isset($b->getCrossrefData()['publisher'])) {
                return 0;
            }
            $aPublisher = trim($a->getCrossrefData()['publisher']);
            $bPublisher = trim($b->getCrossrefData()['publisher']);
            return strcmp($aPublisher, $bPublisher);
        });
        foreach ($journals as $journal) {
            yield $journal;
        }

    }


}