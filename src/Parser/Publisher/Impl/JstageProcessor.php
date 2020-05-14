<?php


namespace App\Parser\Publisher\Impl;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Lib\Utils\StringUtils;
use App\Parser\Publisher\ProcessorTrait;
use App\Parser\Publisher\PublisherProcessor;
use App\Parser\Publisher\PublisherService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class JstageProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.jstage';

    use ProcessorTrait;

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected PublisherService $publisherService;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        PublisherService $publisherService)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->publisherService = $publisherService;
    }

    public function name(): string
    {
        return 'jstage';
    }

    public function scrappingDomains(): array
    {
        return ['www.jstage.jst.go.jp', 'joi.jlc.jst.go.jp'];
    }

    public function queueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function process(Article $article): void
    {
        $url = $this->publisherService->articleUrl($article);
        if ($url === null) {
            return;
        }
        if(StringUtils::contains($url, '/-char/ja/')) {
            $url = str_replace('/-char/ja/', '/-char/en/', $url);
        }

        $client = $this->publisherService->createClient();
        $bodyArr = $this->publisherService->getBody($client, $article, $url);
        if ($bodyArr === null) {
            return;
        }

        $effectiveUrl = $bodyArr[2];
        if($bodyArr[2] !== null && StringUtils::contains($effectiveUrl, '/-char/ja/')) {
            $effectiveUrl = str_replace('/-char/ja/', '/-char/en/', $effectiveUrl);
            $bodyArr = $this->publisherService->getBody($client, $article, $effectiveUrl);
            if ($bodyArr === null) {
                return;
            }
        }
        [$body, $duration, $effectiveUrl] = $bodyArr;


        $publisherData = $this->parseBody($article, $body);
        if ($publisherData !== null) {
            $this->publisherService->savePublisherData($publisherData, $duration);
        }
    }

    private function parseBody(Article $article, string $body): ?ArticlePublisherData
    {
        $crawler = new Crawler($body);

        $details = $crawler->filterXPath("//div[contains(@class, 'accordion_head') and contains(text(), 'Details')]");
        if (count($details) === 0) {
            $this->logger->error(sprintf('id=%d - no Details div found', $article->getId()));
            return null;
        }
        $dateNodes = (new Crawler($details->getNode(0)->parentNode))->filter('span.accodion_lic');
        $dateParts = [];
        foreach ($dateNodes as $node) {
            $dateParts[] = $node->textContent;
        }
        if (count($dateParts) === 0) {
            $this->logger->error(sprintf('id=%d - no dates found', $article->getId()));
            return null;
        }

        $publisherData = $this->createPublisherData($article, [], ArticlePublisherData::SCRAP_RESULT_SUCCESS);

        $prefixes = [
            'Revised:' => null,
            '[Advance Publication] Released' => null,
            'corrected' => null,
            'released on j-stage:' => null,
            'released j-stage:' => null,
            'released' => null,
            'published on j-stage:' => null,
            'Received:' => 'publisherReceived',
            'Accepted:' => 'publisherAccepted',
            'Published:' => 'publisherAvailablePrint',
        ];

        foreach ($dateParts as $datePart) {
            $this->processDatePart($datePart, $prefixes, $publisherData);
        }


        return $publisherData;

    }

    private function processDatePart(string $datePart, array $prefixes, ArticlePublisherData $publisherData): void
    {
        $accessor = new PropertyAccessor();

        $nodeText = strtolower(trim($datePart));

        foreach ($prefixes as $prefix => $propertyPath) {
            $prefix = strtolower($prefix);
            if (StringUtils::startsWith($nodeText, $prefix)) {
                if ($propertyPath === null) {
                    return;
                }

                $dateStr = trim(substr($nodeText, strlen($prefix)));
                if ($dateStr === '-') {
                    return;
                }
                if(is_numeric($dateStr)) {
                    $dateStr = sprintf('%s-01-01', $dateStr);
                }
                $date = new DateTime($dateStr);
                $accessor->setValue($publisherData, $propertyPath, $date);
                return;

            }
        }

        $this->logger->error(sprintf("id=%d - Unknown date string '%s'",
            $publisherData->getArticle()->getId(), $nodeText));
    }


}