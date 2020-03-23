<?php


namespace App\Parser\Publisher;


use App\Entity\ArticleUrlDomain;
use App\Lib\QueueManager;
use App\Parser\PublisherProcessorFinder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherQueer
{
    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected QueueManager $queueManager;
    protected PublisherProcessorFinder $processorFinder;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager,
        PublisherProcessorFinder $processorFinder)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->processorFinder = $processorFinder;
    }

    public function run(string $domainName): void
    {
        $domainEntity = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(ArticleUrlDomain::class, 'entity')
            ->andWhere('entity.domain = :domain')
            ->setParameter('domain', $domainName)
            ->getQuery()
            ->getOneOrNullResult();

        if ($domainEntity === null) {
            $this->logger->error(sprintf('No domain entity found for domain %s', $domainName));
            return;
        }

        $processors = $this->processorFinder->processorsForDomain($domainName);
        if (count($processors) !== 1) {
            $this->logger->error(sprintf('Invalid processors count for domain %s', $domainName));
            return;
        }

        /** @var PublisherProcessor $processor */
        $processor = $processors[0];

        $conn = $this->em->getConnection();

        $affectedRows = $conn->executeUpdate(sprintf("insert into queue_item(queue_name, status, data) 
select '%s', 0, JSON_OBJECT('id', a.id) from article a
         join article_url au on a.id = au.article_id 
    left join article_publisher_data apd on a.id = apd.article_id
where apd.article_id IS NULL AND  au.domain_id = %d 
ORDER BY a.year DESC",
            $processor->queueName(),
            $domainEntity->getId()));
        $this->logger->info(sprintf('Inserted %d rows', $affectedRows));


    }
}