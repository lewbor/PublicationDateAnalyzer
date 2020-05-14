<?php


namespace App\Parser\Publisher;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use Campo\UserAgent;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;

class PublisherService
{
    use ProcessorTrait;

    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function createClient(): Client {
        return new Client([
            'cookies' => true,
            'allow_redirects' => true,
            'verify' => false,
            'connect_timeout' => 5,
            'headers' => [
                'User-Agent' => UserAgent::random([
                    'os_type' => 'Windows',
                    'device_type' => 'Desktop'
                ])
            ],
        ]);
    }

    public function articleUrl(Article $article): ?string {
        if ($article->getUrl() === null) {
            $this->logger->error(sprintf('Article %d - url not exist', $article->getId()));
            return null;
        }

        $url = $article->getUrl()->getUrl();
        if (empty($url)) {
            $this->logger->error(sprintf('Article %d - url is empty', $article->getId()));
            return null;
        }
        return $url;
    }

    public function getBody(Client $client, Article $article, string $url): ?array
    {
        try {
            /** @var Uri $effectiveUrl */
            $effectiveUrl = null;
            $startTime = microtime(true);
            $response = $client->request('GET', $url, [
                'on_stats' => function (TransferStats $stats) use (&$effectiveUrl) {
                    $effectiveUrl = $stats->getEffectiveUri();
                }
            ]);
            $duration = microtime(true) - $startTime;

            $body = $response->getBody()->getContents();

            return [$body, $duration, $effectiveUrl === null ? null : $effectiveUrl->__toString()];

        }catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            $data = [
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
            ];
            $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
            $this->em->persist($publisherDataEntity);
            $this->em->flush();
            $this->logger->error(sprintf("%s, http code=%d",$article->getDoi(), $data['httpCode']));

            return null;
        }
    }

    public function savePublisherData(ArticlePublisherData $publisherData, float $duration): void
    {
        $this->em->persist($publisherData);
        $this->em->flush();

        $datesProcessed = 0;
        if($publisherData->getPublisherAccepted() !== null) {
            $datesProcessed++;
        }
        if($publisherData->getPublisherReceived() !== null) {
            $datesProcessed++;
        }
        if($publisherData->getPublisherAvailablePrint() !== null) {
            $datesProcessed++;
        }
        if($publisherData->getPublisherAvailableOnline() !== null) {
            $datesProcessed++;
        }

        $this->logger->info(sprintf("%d, year=%d, dates=%d, dur=%.3f, %s",
            $publisherData->getArticle()->getId(),
            $publisherData->getArticle()->getYear(),
            $datesProcessed,
            $duration,
            $this->datesToStr($publisherData)));
    }

    private function datesToStr(ArticlePublisherData $publisherData): string
    {
        $parts = [];

        if($publisherData->getPublisherAccepted() !== null) {
            $parts[] = sprintf('acc=%s', $publisherData->getPublisherAccepted()->format('Y-m-d'));
        }
        if($publisherData->getPublisherReceived() !== null) {
            $parts[] = sprintf('rec=%s', $publisherData->getPublisherReceived()->format('Y-m-d'));
        }
        if($publisherData->getPublisherAvailablePrint() !== null) {
            $parts[] = sprintf('print=%s', $publisherData->getPublisherAvailablePrint()->format('Y-m-d'));
        }
        if($publisherData->getPublisherAvailableOnline() !== null) {
            $parts[] = sprintf('online=%s', $publisherData->getPublisherAvailableOnline()->format('Y-m-d'));
        }
        return implode(',', $parts);
    }
}