<?php


namespace App\Parser;


use App\Entity\Article;
use App\Parser\Publisher\Impl\AscPublisherProcessor;
use App\Parser\Publisher\Impl\TailorFrancisProcessor;
use App\Parser\Publisher\PublisherProcessor;
use App\Parser\Publisher\Impl\RscPublisherProcessor;
use App\Parser\Publisher\Impl\ScienceDirectPublisherProcessor;
use App\Parser\Publisher\Impl\SpringerPublisherProcessor;
use Psr\Log\LoggerInterface;

class PublisherProcessorFinder
{
    protected LoggerInterface $logger;

    /** @var PublisherProcessor[] */
    protected array $processors;

    public function __construct(
        LoggerInterface $logger,
        ScienceDirectPublisherProcessor $scienceDirectPublisherProcessor,
        SpringerPublisherProcessor $springerPublisherProcessor,
        AscPublisherProcessor $ascPublisherProcessor,
        RscPublisherProcessor $rscPublisherProcessor,
        TailorFrancisProcessor $tailorFrancisProcessor)
    {
        $this->logger = $logger;

        $this->processors = [
            $scienceDirectPublisherProcessor,
            $springerPublisherProcessor,
            $ascPublisherProcessor,
            $rscPublisherProcessor,
            $tailorFrancisProcessor
        ];
    }


    public function processorsForQueue(string $queueName): array
    {
        $processors = [];

        foreach ($this->processors as $processor) {
            if ($processor->queueName() === $queueName) {
                $processors[] = $processor;
            }
        }

        return $processors;
    }

    public function processorsForDomain(string $domain) : array {
        $processors = [];

        foreach ($this->processors as $processor) {
            if (in_array($domain, $processor->scrappingDomains())) {
                $processors[] = $processor;
            }
        }

        return $processors;
    }

}