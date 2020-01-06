<?php


namespace App\Parser;


use App\Entity\Article;
use App\Lib\QueueManager;
use App\Parser\Scrapper\AscPublisherProcessor;
use App\Parser\Scrapper\PublisherProcessor;
use App\Parser\Scrapper\RscPublisherProcessor;
use App\Parser\Scrapper\ScienceDirectPublisherProcessor;
use App\Parser\Scrapper\SpringerPublisherProcessor;
use App\Parser\Scrapper\WileyPublisherProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherProcessorFinder
{
    protected $logger;
    /** @var PublisherProcessor[] */
    protected $processors;

    public function __construct(
        LoggerInterface $logger,
        ScienceDirectPublisherProcessor $scienceDirectPublisherProcessor,
        SpringerPublisherProcessor $springerPublisherProcessor,
        AscPublisherProcessor $ascPublisherProcessor,
        RscPublisherProcessor $rscPublisherProcessor,
        WileyPublisherProcessor $wileyPublisherProcessor)
    {
        $this->logger = $logger;

        $this->processors = [
            $scienceDirectPublisherProcessor,
            $springerPublisherProcessor,
            $ascPublisherProcessor,
            $rscPublisherProcessor,
            $wileyPublisherProcessor
        ];
    }


    public function publishersByQueueName(string $queueName): array {
        $publishers = [];

        foreach($this->processors as $processor) {
            if($processor->queueName() == $queueName) {
                $publishers = array_merge($publishers, $processor->publisherNames());
            }
        }

        $publishers = array_unique(array_filter($publishers));

        return $publishers;
    }

    public function findProcessor(Article $article): ?PublisherProcessor
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