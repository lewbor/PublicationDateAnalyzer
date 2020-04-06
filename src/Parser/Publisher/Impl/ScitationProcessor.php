<?php


namespace App\Parser\Publisher\Impl;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Lib\Utils\StringUtils;
use App\Parser\Publisher\ProcessorTrait;
use App\Parser\Publisher\PublisherProcessor;
use App\Parser\Publisher\PublisherService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ScitationProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.scitation';

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
        return 'scitation';
    }

    public function scrappingDomains(): array
    {
        return ['aip.scitation.org'];
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
        $this->publisherService->savePublisherData($publisherData, $duration);
    }

    private function parseBody(Article $article, string $body): ?ArticlePublisherData
    {
        $crawler = new Crawler($body);
        $elems = $crawler->filter('div.publication-dates-con div.dates');
        if ($elems->count() === 0) {
            throw new \Exception(sprintf('id=%s, No dates divs', $article->getId()));
        }

        $accessor = new PropertyAccessor();
        $publisherData = $this->createPublisherData($article, [], ArticlePublisherData::SCRAP_RESULT_SUCCESS);

        $prefixes = [
            'accepted:' => 'publisherReceived',
            'published online:' => 'publisherAvailableOnline',
        ];

        for ($i = 0; $i < $elems->count(); $i++) {
            $nodeText = strtolower(trim($elems->getNode($i)->textContent));
            $found = false;
            foreach ($prefixes as $prefix => $propertyPath) {
                if (StringUtils::startsWith($nodeText, $prefix)) {
                    if($propertyPath !== null) {
                        $dateStr = trim(substr($nodeText, strlen($prefix)));
                        $date = new \DateTime($dateStr);
                        $accessor->setValue($publisherData, $propertyPath, $date);
                    }
                    $found = true;
                    break;

                }
            }
            if(!$found) {
                throw new \Exception(sprintf('id=%d - Unknown date string %s',
                    $article->getId(), $nodeText));
            }

        }


        return $publisherData;

    }


}