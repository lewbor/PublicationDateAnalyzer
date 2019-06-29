<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Entity\QueueItem;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherScrapper
{
    protected $queueManager;
    protected $em;
    protected $logger;
    protected $processors;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager,
        ScienceDirectPublisherProcessor $scienceDirectPublisherProcessor,
        SpringerPublisherProcessor $springerPublisherProcessor,
        AscPublisherProcessor $ascPublisherProcessor)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;

        $this->processors = [
            $scienceDirectPublisherProcessor,
            $springerPublisherProcessor,
            $ascPublisherProcessor,
        ];
    }

    public function run()
    {
        foreach ($this->queueManager->singleIterator(PublisherQueer::QUEUE_NAME) as $queueItem) {
            $this->processItem($queueItem);
            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();
        }
    }

    private function processItem(QueueItem $queueItem): void
    {
        $data = $queueItem->getData();
        $article = $this->em->getRepository(Article::class)->find($data['id']);
        if ($article === null) {
            $this->logger->info("Article is null");
            return;
        }

        $processor = $this->findProcessor($article);
        if ($processor === null) {
            return;
        }
        $datesUpdate = $processor->process($article);
        $this->logger->info(sprintf("Processed article=%d, publisher=%s, year=%d, update %d dates, reminding %d tasks",
            $article->getId(), $processor->publisherName(),
            $article->getYear(), $datesUpdate,
            $this->queueManager->remindingTasks(PublisherQueer::QUEUE_NAME)));
    }


    private function findProcessor(Article $article): ?PublisherProcessor
    {
        $publisher = $article->getCrossrefData()['publisher'];
        $publisher = mb_strtolower(trim($publisher));

        /** @var PublisherProcessor $processor */
        foreach ($this->processors as $processor) {
            if (strpos($publisher, $processor->publisherName()) !== false) {
                return $processor;
            }
        }
        $this->logger->error(sprintf("No processor for publisher %s", $publisher));
    }
}