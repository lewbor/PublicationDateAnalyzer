<?php


namespace App\Parser\Crossref;


use App\Entity\Article;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CrossrefUrlResolveQueer
{
    const QUEUE_NAME = 'crossref.url';

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected QueueManager $queueManager;

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
        $truncatedRecords = $this->queueManager->truncate(self::QUEUE_NAME);
        $this->logger->info(sprintf('Removed %d records from queue %s', $truncatedRecords, self::QUEUE_NAME));

        $conn = $this->em->getConnection();
        $insertedRows = $conn->executeUpdate("insert into queue_item(queue_name, status, data)
SELECT 'crossref.url', 0, JSON_OBJECT('id',a.id, 'doi', a.doi)
from article a
left JOIN article_url url on a.id = url.article_id
where url.article_id IS NULL");
        $this->logger->info(sprintf("Inserted %d records", $insertedRows));


    }
}