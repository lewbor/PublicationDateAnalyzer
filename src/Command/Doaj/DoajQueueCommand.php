<?php


namespace App\Command\Doaj;


use App\Entity\Journal\Journal;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoajQueueCommand extends Command
{
    const QUEUE_NAME = 'doaj.scrap';

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected QueueManager $queueManager;

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
        $this->setName('doaj.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $truncatedRecords = $this->queueManager->truncate(self::QUEUE_NAME);
        $this->logger->info(sprintf('Removed %d records from queue %s', $truncatedRecords, self::QUEUE_NAME));

        $iterator = DoctrineIterator::batchIdIterator(
            $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->leftJoin('entity.doaj', 'doaj')
            ->andWhere('doaj IS NULL')
        );

        /** @var Journal[] $batch */
        foreach ($iterator as $batch) {
            foreach($batch as $journal) {
                $this->queueManager->offer(self::QUEUE_NAME, ['id' => $journal->getId()]);
            }
        }

        return 0;
    }

}