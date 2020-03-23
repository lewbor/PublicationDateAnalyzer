<?php


namespace App\Command;


use App\Entity\QueueItem;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearQueueCommand extends Command
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

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
        $this->setName('queue.clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deletedRecords = $this->em->createQueryBuilder()
            ->delete(QueueItem::class, 'entity')
            ->andWhere('entity.status = :status')
            ->setParameter('status', QueueItem::FINISHED)
            ->getQuery()
            ->execute();
        $this->logger->info(sprintf('Deleted %s records from queue', number_format($deletedRecords)));

        return 0;
    }
}