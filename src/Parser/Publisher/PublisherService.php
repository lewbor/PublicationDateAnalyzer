<?php


namespace App\Parser\Publisher;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use Campo\UserAgent;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
            $startTime = microtime(true);
            $response = $client->request('GET', $url);
            $duration = microtime(true) - $startTime;

            $body = $response->getBody()->getContents();

            return [$body, $duration];

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

        $this->logger->info(sprintf("%s, year=%d, update %d dates, duration=%.3f",
            $publisherData->getArticle()->getDoi(), $publisherData->getArticle()->getYear(), $datesProcessed, $duration));
    }
}