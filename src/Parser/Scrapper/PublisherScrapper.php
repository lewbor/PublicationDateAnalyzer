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
        AscPublisherProcessor $ascPublisherProcessor,
        RscPublisherProcessor $rscPublisherProcessor,
        WileyPublisherProcessor $wileyPublisherProcessor)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;

        $this->processors = [
            $scienceDirectPublisherProcessor,
            $springerPublisherProcessor,
            $ascPublisherProcessor,
            $rscPublisherProcessor,
            $wileyPublisherProcessor
        ];
    }

    public function run()
    {
        foreach ($this->queueManager->singleIterator(PublisherQueer::QUEUE_NAME) as $idx => $queueItem) {
            $this->processItem($queueItem);
            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();

            if($idx % 15 === 0) {
                $this->logger->info(sprintf('Reminded %d tasks', $this->queueManager->remindingTasks(PublisherQueer::QUEUE_NAME)));
            }
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
        $this->logger->info(sprintf("Processed article=%d, publisher=%s, year=%d, update %d dates",
            $article->getId(), $processor->name(),
            $article->getYear(), $datesUpdate));
    }


    private function findProcessor(Article $article): ?PublisherProcessor
    {
        if($article->getCrossrefData() === null) {
            $this->logger->info(sprintf('Article %d - no crossref data', $article->getId()));
            return null;
        }

        if(empty($article->getCrossrefData()->getData()['publisher'])) {
            $this->logger->info(sprintf('Article %d - empty publisher', $article->getId()));
            return null;
        }

        $publisher = $article->getCrossrefData()->getData()['publisher'];
        $publisher = mb_strtolower(trim($publisher));

        /** @var PublisherProcessor $processor */
        foreach ($this->processors as $processor) {
            foreach ($processor->publisherNames() as $publisherName) {
                if (strpos($publisher, $publisherName) !== false) {
                    return $processor;
                }
            }
        }
        $this->logger->error(sprintf("No processor for publisher %s", $publisher));
        return null;
    }
}