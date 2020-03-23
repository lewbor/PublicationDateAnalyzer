<?php


namespace App\Parser\Publisher;


use App\Entity\Article;
use App\Entity\QueueItem;
use App\Lib\AbstractMultiProcessCommand;
use App\Lib\QueueManager;
use App\Parser\PublisherProcessorFinder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherScrapper
{
    protected QueueManager $queueManager;
    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected PublisherProcessorFinder $processorFinder;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager,
        PublisherProcessorFinder $processorFinder
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->processorFinder = $processorFinder;
    }

    public function run(string $queueName)
    {
        foreach ($this->queueManager->singleIterator($queueName) as $idx => $queueItem) {
            $this->processItem($queueItem, $queueName);
            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();
        }
    }

    private function processItem(QueueItem $queueItem, string $queueName): void
    {
        $data = $queueItem->getData();
        /** @var Article $article */
        $article = $this->em->getRepository(Article::class)->find($data['id']);
        if ($article === null) {
            $this->logger->info("Article is null");
            return;
        }

        $processors = $this->processorFinder->processorsForQueue($queueName);
        if (count($processors) !== 1) {
            $this->logger->error(sprintf('%d processors for queue %s', count($processors), $queueName));
            return;
        }

        /** @var PublisherProcessor $processor */
        $processor = $processors[0];

        $processor->process($article);
    }


}