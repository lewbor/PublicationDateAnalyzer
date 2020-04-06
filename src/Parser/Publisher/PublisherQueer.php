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

    public function run(string $processorClass): void
    {
        $processor = $this->processorFinder->processorForClass($processorClass);
        if($processor === null) {
            $this->logger->error(sprintf('No processor for class %s', $processorClass));
            return;
        }

        $domainEntities = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(ArticleUrlDomain::class, 'entity')
            ->andWhere('entity.domain IN (:domain)')
            ->setParameter('domain', $processor->scrappingDomains())
            ->getQuery()
            ->getResult();

        if (count($domainEntities) !== count($processor->scrappingDomains())) {
            $entitiesStr = json_encode(array_map(
                fn(ArticleUrlDomain $entity) => [$entity->getDomain()],
                $domainEntities
            ));
            $this->logger->error(sprintf('domain entities count differ from processor domains: %s, %s',
                $entitiesStr, json_encode($processor->scrappingDomains())));
            return;
        }

        $conn = $this->em->getConnection();

        $domainIds = array_map(
            fn(ArticleUrlDomain $entity) => $entity->getId(),
            $domainEntities
        );
        $affectedRows = $conn->executeUpdate(sprintf("insert into queue_item(queue_name, status, data) 
select '%s', 0, JSON_OBJECT('id', a.id) from article a
         join article_url au on a.id = au.article_id 
    left join article_publisher_data apd on a.id = apd.article_id
where apd.article_id IS NULL AND  au.domain_id IN (%s) 
ORDER BY a.year DESC",
            $processor->queueName(),
            implode(',', $domainIds)));
        $this->logger->info(sprintf('Inserted %d rows', $affectedRows));


    }
}