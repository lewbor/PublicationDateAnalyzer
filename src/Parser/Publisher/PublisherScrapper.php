<?php


namespace App\Parser\Publisher;


use App\Entity\Article;
use App\Entity\QueueItem;
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

    public function run(string $processorClass): void
    {
        $processor = $this->processorFinder->processorForClass($processorClass);
        if ($processor === null) {
            $this->logger->error(sprintf('Not found processor for class %s', $processorClass));
            return;
        }
        $queueName = $processor->queueName();
        foreach ($this->queueManager->singleIterator($queueName) as $idx => $queueItem) {
            $this->processItem($queueItem, $processor);
            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();
        }
    }

    private function processItem(QueueItem $queueItem, PublisherProcessor $processor): void
    {
        $data = $queueItem->getData();
        /** @var Article $article */
        $article = $this->em->getRepository(Article::class)->find($data['id']);
        if ($article === null) {
            $this->logger->info("Article is null");
            return;
        }

        $processor->process($article);
    }


}