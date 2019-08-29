<?php


namespace App\Parser\Crossref;


use App\Entity\Journal;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CrossrefPublicationsQueer
{
    const QUEUE_NAME = 'crossref.publications';

    protected $em;
    protected $logger;
    protected $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
    }

    public function run()
    {

        foreach ($this->journalIterator() as $idx => $journal) {
            $this->queueAJournal($journal);
            $this->em->clear();
        }
        $this->logger->info(sprintf('Totally queued %d journals', $idx + 1));

    }

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

    private function queueAJournal(Journal $journal): void
    {
        $this->queueManager->offer(self::QUEUE_NAME, ['id' => $journal->getId()]);
    }
}