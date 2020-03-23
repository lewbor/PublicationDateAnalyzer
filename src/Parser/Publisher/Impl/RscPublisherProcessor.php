<?php


namespace App\Parser\Publisher\Impl;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Parser\Publisher\ProcessorTrait;
use App\Parser\Publisher\PublisherProcessor;
use Campo\UserAgent;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class RscPublisherProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.rsc';

    use ProcessorTrait;

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function name(): string
    {
        return 'rsc';
    }

    public function scrappingDomains(): array
    {
        return ['pubs.rsc.org', 'xlink.rsc.org'];
    }

    public function queueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function process(Article $article): void
    {
        $publisherData = $this->extractDataFromCrossref($article);
        if (!empty($publisherData)) {
            $datesProcessed = $this->updateArticleByPublisherData($article, $publisherData);
            $this->logger->info(sprintf('%s - updated from crossref, %d dates', $article->getDoi(), $datesProcessed));

            return;
        }

        $this->scrapPublisherDataFromWeb($article);
    }

    private function scrapPublisherDataFromWeb(Article $article): void
    {
        try {
            $this->logger->info(sprintf('Article %d - go to web', $article->getId()));

            $client = new Client([
                'cookies' => true,
                'allow_redirects' => true,
                'verify' => false,
                'headers' => [
                    'User-Agent' => UserAgent::random([
                        'os_type' => 'Windows',
                        'device_type' => 'Desktop'
                    ])
                ],
            ]);

            if ($article->getUrl() === null) {
                $this->logger->error(sprintf('Article %d - url not exist', $article->getId()));
                return;
            }

            $url = $article->getUrl()->getUrl();
            if (empty($url)) {
                $this->logger->error(sprintf('Article %d - url is empty', $article->getId()));
                return;
            }

            $startTime = microtime(true);
            $response = $client->request('GET', $url);
            $duration = microtime(true) - $startTime;

            $body = $response->getBody()->getContents();

            $publisherData = $this->parsePublisherWebData($article, $body);
            $datesProcessed = $this->updateArticleByPublisherData($article, $publisherData);
            $this->logger->info(sprintf('%s - updated %d dates, duration=%.3f', $article->getDoi(), $datesProcessed, $duration));


        } catch (RequestException $e) {
            $publisherData = [
                'success' => false,
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ];
            $this->updateArticleByPublisherData($article, $publisherData);
            $this->logger->info(sprintf('%s - response code is %d', $publisherData['httpCode']));

        }
    }

    private function parsePublisherWebData(Article $article, string $body): array
    {
        $crawler = new Crawler($body);

        try {
            $publicationDetailText = $crawler->filter('#divAbout div[class="autopad--h"] p')->text();

            $publicationDetailText = trim($publicationDetailText);
            $publicationDetailText = strtolower($publicationDetailText);
            $publicationDetailText = str_replace("\r\n", '', $publicationDetailText);
            $publicationDetailText = preg_replace('!\s+!', ' ', $publicationDetailText);

            $publicationDetailText = str_replace('and', ',', $publicationDetailText);
            $publicationDetailText = str_replace('the article was', '', $publicationDetailText);

            $parts = explode(',', $publicationDetailText);
            $parts = array_map('trim', $parts);

            $prefixMap = [
                'received on' => 'Received',
                'accepted on' => 'Accepted',
                'first published on' => 'Published online'
            ];

            $publisherDates = [];
            foreach ($parts as $part) {
                foreach ($prefixMap as $prefix => $key) {
                    if (strpos($part, $prefix) === 0) {
                        $publisherDates[$key] = substr($part, strlen($prefix) + 1);
                    }
                }
            }

            return $publisherDates;
        } catch (\InvalidArgumentException $e) {
            $this->logger->info(sprintf('Article(%d) - no dates', $article->getId()));
            return [];
        }

    }

    private function extractDataFromCrossref(Article $article): array
    {
        if ($article->getCrossrefData() === null) {
            return [];
        }

        $crossrefData = $article->getCrossrefData()->getData();
        if (!isset($crossrefData['assertion'])) {
            return [];
        }

        $historyItem = null;
        foreach ($crossrefData['assertion'] as $assertionItem) {
            if ($assertionItem['name'] === 'history') {
                $historyItem = $assertionItem;
                break;
            }
        }
        if ($historyItem === null) {
            return [];
        }

        $historyText = $historyItem['value'];
        if (empty($historyText)) {
            return [];
        }

        $historyText = trim($historyText);
        $historyText = strtolower($historyText);

        $parts = explode(';', $historyText);
        $parts = array_map('trim', $parts);


        $publisherDates = [];

        $publisherDates = array_merge($publisherDates, $this->processReceivedAcceptedDates($parts));
        $publisherDates = array_merge($publisherDates, $this->processPublishedDates($parts));

        return $publisherDates;
    }

    private function updateArticleByPublisherData(Article $article, array $publisherData): int
    {
        $publisherDataEntity = $this->createPublisherData($article, $publisherData, ArticlePublisherData::SCRAP_RESULT_SUCCESS);

        $datesProcessed = 0;
        if (isset($publisherData['Received'])) {
            $publisherDataEntity->setPublisherReceived(new DateTime($publisherData['Received']));
            $datesProcessed++;
        }
        if (isset($publisherData['Accepted'])) {
            $publisherDataEntity->setPublisherAccepted(new DateTime($publisherData['Accepted']));
            $datesProcessed++;
        }
        if (isset($publisherData['Published online'])) {
            $publisherDataEntity->setPublisherAvailableOnline(new DateTime($publisherData['Published online']));
            $datesProcessed++;
        }

        $this->em->persist($publisherDataEntity);
        $this->em->flush();
        return $datesProcessed;
    }

    private function processReceivedAcceptedDates(array $parts): array
    {
        $publisherDates = [];

        $prefixMap = [
            'received' => 'Received',
            'accepted' => 'Accepted',
        ];

        foreach ($parts as $partIdx => $part) {
            foreach ($prefixMap as $prefix => $key) {
                if (strpos($part, $prefix) === 0 && count(explode(' ', $part)) === 4) {
                    $publisherDates[$key] = $this->extractDateFromEndOfString($part);
                    unset($parts[$partIdx]);
                    break;
                }
            }
        }

        return $publisherDates;
    }

    private function processPublishedDates(array $parts): array
    {
        $publishedDates = [];

        foreach ($parts as $part) {
            if (strpos($part, 'published') !== false) {
                $publishedDates[] = $this->extractDateFromEndOfString($part);
            }
        }
        if (count($publishedDates) === 0) {
            return [];
        }

        return ['Published online' => $publishedDates[0]];
    }

    private function extractDateFromEndOfString(string $str): string
    {
        $parts = explode(' ', $str);
        $lastParts = array_slice($parts, -3, 3);
        return implode(' ', $lastParts);
    }
}