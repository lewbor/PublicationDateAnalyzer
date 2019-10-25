<?php


namespace App\Command\Analytics;


use App\Analytics\Analyzer\AcceptedPublishedAnalyzer;
use App\Analytics\Analyzer\ReceivedAcceptedAnalyzer;
use App\Analytics\Analyzer\ReceivedPublishedAnalyzer;
use App\Analytics\JournalAnalyticsMaker;
use App\Analytics\YearPeriod;
use App\Entity\Journal\Journal;
use App\Entity\Journal\JournalAnalytics;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalAnalyticsCommand extends Command
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
        $this->setName('journal.analytics');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var YearPeriod[] $yearPeriods */
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

        /** @var Journal $journal */
        foreach ($this->journalIterator() as $journal) {
            $this->clearStat($journal);

            foreach ($yearPeriods as $yearPeriod) {
                $stat = $this->analyticsMaker->analyticsForJournal($journal, $yearPeriod, $dateAnalyzers);
                $journal = $this->em->getRepository(Journal::class)->find($journal->getId());
                $this->saveStat($journal, $yearPeriod, $stat);

                $this->em->clear();
                $this->logger->info(sprintf('Processed journal %s with %s', $journal->getName(), json_encode($yearPeriod->toArray())));
            }
        }
    }

    private function journalIterator(): iterable
    {
        return DoctrineIterator::idIterator($this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
        );
    }

    private function clearStat(Journal $journal): void
    {
        $this->em->createQueryBuilder()
            ->delete(JournalAnalytics::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->execute();
    }

    private function saveStat(Journal $journal, YearPeriod $yearPeriod, array $stat): void
    {
        $entity = (new JournalAnalytics())
            ->setJournal($journal)
            ->setOptions($yearPeriod->toArray())
            ->setAnalytics($stat);

        $this->em->persist($entity);
        $this->em->flush();
    }


}