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

class DegruterProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.degruyter';

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
        return 'degruyter';
    }

    public function scrappingDomains(): array
    {
        return ['www.degruyter.com'];
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

        $client = $this->publisherService->createClient();
        $bodyArr = $this->publisherService->getBody($client, $article, $url);
        if ($bodyArr === null) {
            return;
        }

        [$body, $duration] = $bodyArr;

        $publisherData = $this->parseBody($article, $body);
        if ($publisherData !== null) {
            $this->publisherService->savePublisherData($publisherData, $duration);
        }
    }

    private function parseBody(Article $article, string $body): ?ArticlePublisherData
    {
        $crawler = new Crawler($body);
        $accessor = new PropertyAccessor();

        $dateParts = $this->parseDateParts($crawler);
        if (count($dateParts) === 0) {
            $this->logger->error(sprintf('id=%d - no dates found', $article->getId()));
            return null;
        }

        $publisherData = $this->createPublisherData($article, [], ArticlePublisherData::SCRAP_RESULT_SUCCESS);

        $prefixes = [
            'revised:' => null,
            'first published:' => 'publisherAvailablePrint',
            'received:' => 'publisherReceived',
            'accepted:' => 'publisherAccepted',
            'published online:' => 'publisherAvailableOnline',
            'published in print:' => 'publisherAvailablePrint',
        ];

        foreach ($dateParts as $datePart) {
            $nodeText = strtolower(trim($datePart));
            $found = false;
            foreach ($prefixes as $prefix => $propertyPath) {
                if (StringUtils::startsWith($nodeText, $prefix)) {
                    if ($propertyPath !== null) {
                        $dateStr = trim(substr($nodeText, strlen($prefix)));
                        $date = new DateTime($dateStr);
                        $accessor->setValue($publisherData, $propertyPath, $date);
                    }
                    $found = true;
                    break;

                }
            }
            if (!$found) {
                throw new Exception(sprintf("id=%d - Unknown date string '%s'",
                    $article->getId(), $nodeText));
            }

        }


        return $publisherData;

    }

    private function parseDateParts(Crawler $crawler): array
    {
        $elems = $crawler->filter('div.pub-history dl.journalDate');
        if ($elems->count() > 0) {

            $dateParts = [];
            foreach ($elems as $elem) {
                $dateParts[] = $elem->textContent;
            }
            return $dateParts;
        } else {
            $dateParts = [];

            $elems = $crawler->filter('div.component div.content-box-body dl.c-List__items dt.text-metadata-label');
            foreach($elems as $elem) {
                if(StringUtils::startsWith(strtolower(trim($elem->textContent)), 'published online:')) {
                    $dateValue = (new Crawler($elem))->nextAll()->filter('dd.text-metadata-value')->text(null, false);
                    $dateParts[] = $elem->textContent . $dateValue;
                    break;
                }
            }

            $publishDateElems = $crawler->filter('dl.date-publish');
            if($publishDateElems->count() > 0) {
                $dateLabel = $publishDateElems->filter('dt.text-metadata-label')->text(null, false);
                $dateValue = $publishDateElems->filter('dd.text-metadata-value')->text(null, false);
                $dateParts[] = $dateLabel . $dateValue;
            }

            return $dateParts;
        }

    }


}