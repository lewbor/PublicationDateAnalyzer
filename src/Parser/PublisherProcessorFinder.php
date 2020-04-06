<?php


namespace App\Parser;


use App\Parser\Publisher\Impl\AscPublisherProcessor;
use App\Parser\Publisher\Impl\RscPublisherProcessor;
use App\Parser\Publisher\Impl\ScienceDirectPublisherProcessor;
use App\Parser\Publisher\Impl\ScitationProcessor;
use App\Parser\Publisher\Impl\SpringerPublisherProcessor;
use App\Parser\Publisher\Impl\TailorFrancisProcessor;
use App\Parser\Publisher\PublisherProcessor;
use Psr\Log\LoggerInterface;

class PublisherProcessorFinder
{
    protected LoggerInterface $logger;
    protected array $processors = [];

    public function __construct(
        LoggerInterface $logger,
        ScienceDirectPublisherProcessor $scienceDirectPublisherProcessor,
        SpringerPublisherProcessor $springerPublisherProcessor,
        AscPublisherProcessor $ascPublisherProcessor,
        RscPublisherProcessor $rscPublisherProcessor,
        TailorFrancisProcessor $tailorFrancisProcessor,
        ScitationProcessor $scitationProcessor)
    {
        $this->logger = $logger;

        $processors = [
            $scienceDirectPublisherProcessor,
            $springerPublisherProcessor,
            $ascPublisherProcessor,
            $rscPublisherProcessor,
            $tailorFrancisProcessor,
            $scitationProcessor
        ];
        foreach($processors as $processor) {
            $this->processors[get_class($processor)] = $processor;
        }
    }

    public function processorForClass(string $processorClass): ?PublisherProcessor {
        return $this->processors[$processorClass] ?? null;
    }

}