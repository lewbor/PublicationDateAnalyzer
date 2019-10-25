<?php


namespace App\Command\Wos\JournalCrawler;


use App\Entity\Journal\Journal;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WosJournalQueerCommand extends Command
{
    const QUEUE_NAME = 'wos_journals';

    protected $em;
    protected $logger;
    protected $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
    }

    protected function configure()
    {
        $this->setName('wos.journal.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->journalIterator() as $journal) {
            $this->queueManager->offer(self::QUEUE_NAME, ['id' => $journal->getId()]);
        }
    }

    private function journalIterator(): iterable
    {
        yield from DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
        );
    }
}