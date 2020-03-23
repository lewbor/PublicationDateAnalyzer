<?php


namespace App\Command\Unpaywall;


use App\Entity\Article;
use App\Lib\ArticleQueries;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallQueueCommand extends Command
{
    const QUEUE_NAME = 'unpaywall_sync_scrap';

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
        $this->setName('unpaywall.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $truncatedRecords = $this->queueManager->truncate(self::QUEUE_NAME);
        $this->logger->info(sprintf('Removed %d records from queue %s', $truncatedRecords, self::QUEUE_NAME));

        $conn = $this->em->getConnection();
        $insertedRows = $conn->executeUpdate("insert into queue_item(queue_name, status, data)
SELECT '".self::QUEUE_NAME."', 0, JSON_OBJECT('id',a.id, 'doi', a.doi)
from article a
left JOIN article_unpaywall_data aud on a.id = aud.article_id
where aud.article_id IS NULL");
        $this->logger->info(sprintf("Inserted %d records", $insertedRows));

        return 0;
    }

}