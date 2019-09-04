<?php


namespace App\Command\Analytics;


use App\Analytics\AnalyticsSaver;
use App\Analytics\Analyzer\AcceptedPublishedAnalyzer;
use App\Analytics\Analyzer\ReceivedAcceptedAnalyzer;
use App\Analytics\Analyzer\ReceivedPublishedAnalyzer;
use App\Analytics\JournalAnalyticsMaker;
use App\Analytics\YearPeriod;
use App\Entity\Journal;
use App\Entity\JournalAnalytics;
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

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        JournalAnalyticsMaker $analyticsMaker)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->analyticsMaker = $analyticsMaker;
    }

    protected function configure()
    {
        $this->setName('journal.stat');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yearPeriods = [
            new YearPeriod(2000, 2009),
            new YearPeriod(2000, 2009, true),
            new YearPeriod(2010, 2019),
            new YearPeriod(2010, 2019, true),
            new YearPeriod(2018, 2019),
            new YearPeriod(2018, 2019, true),
        ];
        $dateAnalyzers = [
            new ReceivedAcceptedAnalyzer(),
            new AcceptedPublishedAnalyzer(),
            new ReceivedPublishedAnalyzer(),
        ];

        $this->clearStat();

        /** @var Journal $journal */
        foreach ($this->journalIterator() as $journal) {
            $stat = $this->analyticsMaker->analyticsForJournal($journal, $yearPeriods, $dateAnalyzers);
            $journal = $this->em->getRepository(Journal::class)->find($journal->getId());
            $this->saveStat($journal, $stat);

            $this->em->clear();
            $this->logger->info(sprintf('Processed journal %s', $journal->getName()));
        }
    }

    private function journalIterator(): iterable
    {
        $journals = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->getQuery()
            ->getResult();
        foreach ($journals as $journal) {
            yield $journal;
        }

    }

    private function clearStat()
    {
        $this->em->createQueryBuilder()
            ->delete(JournalAnalytics::class, 'entity')
            ->getQuery()
            ->execute();
    }

    private function saveStat(?Journal $journal, array $stat)
    {
        $entity = (new JournalAnalytics())
            ->setJournal($journal)
            ->setAnalytics($stat);

        $this->em->persist($entity);
        $this->em->flush();
    }


}