<?php


namespace App\Parser\Publisher\Impl;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Entity\ArticleUrlDomain;
use App\Parser\Publisher\ProcessorTrait;
use App\Parser\Publisher\PublisherProcessor;
use Campo\UserAgent;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class ScienceDirectPublisherProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.science_direct';

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
        return 'science direct';
    }

    public function scrappingDomains(): array
    {
        return ['linkinghub.elsevier.com'];
    }

    public function queueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function process(Article $article): void
    {
        try {
            if ($article->getUrl() === null) {
                $this->logger->error(sprintf('Article %d - url not exist', $article->getId()));
                return;
            }

            $url = $article->getUrl()->getUrl();
            if (empty($url)) {
                $this->logger->error(sprintf('Article %d - url is empty', $article->getId()));
                return;
            }

            $client = new Client([
                'cookies' => true,
                'allow_redirects' => [
                    'max' => 10,
                    'strict' => false,
                    'referer' => false,
                    'protocols' => ['http', 'https'],
                    'track_redirects' => false
                ],
                'verify' => false,
                'headers' => [
                    'User-Agent' => UserAgent::random([
                        'os_type' => 'Windows',
                        'device_type' => 'Desktop'
                    ])
                ],
            ]);

            $startTime = microtime(true);

            $response = $client->request('GET', $url);

            $contentType = $response->getHeaderLine('Content-Type');
            if ($contentType === 'application/pdf') {
                $publisherDataEntity = $this->createPublisherData($article, [], ArticlePublisherData::SCRAP_RESULT_PDF);
                $this->em->persist($publisherDataEntity);
                $this->em->flush();
                $this->logger->info(sprintf('%s - response is pdf', $article->getDoi()));
                return;
            }

            $body = $response->getBody()->getContents();
            $crawler = new Crawler($body);
            if ($crawler->filter('#trackErrorPage')->count() > 0) {
                $data = [
                    'success' => false,
                    'httpCode' => $response->getStatusCode(),
                    'message' => 'Requested article is not found in IHub',
                ];

                $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_NO_DATA);
                $this->em->persist($publisherDataEntity);
                $this->em->flush();
                $this->logger->info(sprintf('%s - article not found', $article->getDoi()));
                return;
            }

            if ($crawler->filter('input[name=redirectURL]')->count() === 0) {
                $data = [
                    'message' => 'No input[name=redirectURL] nodes',
                ];

                $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
                $this->em->persist($publisherDataEntity);
                $this->em->flush();
                return;
            }
            $redirectUrl = $crawler->filter('input[name=redirectURL]')->attr('value');
            $redirectUrl = trim(urldecode($redirectUrl));

            $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);

            if ($redirectHost !== 'www.sciencedirect.com') {
                $this->logger->error(sprintf('%s - invalid domain %s, new url %s',
                    $article->getDoi(), $redirectHost, $redirectUrl));
                $articleUrl = $article->getUrl();
                $articleUrl->setUrl($redirectUrl);
                $domain = $this->em->getRepository(ArticleUrlDomain::class)->findOneBy(['domain' => $redirectHost]);
                if ($domain === null) {
                    $domain = (new ArticleUrlDomain())
                        ->setDomain($redirectHost);
                    $this->em->persist($domain);
                }
                $articleUrl->setDomain($domain);
                $this->em->persist($articleUrl);
                $this->em->flush();
                return;
            }

            $response = $client->request('GET', $redirectUrl);
            $body = $response->getBody()->getContents();
            $duration = microtime(true) - $startTime;


            $crawler = new Crawler($body);
            $jsonDataStr = $crawler->filter('script[data-iso-key="_0"]')->text(null, false);
            $jsonData = \GuzzleHttp\json_decode($jsonDataStr, true);

            $publisherDataEntity = $this->createPublisherData($article, $jsonData, ArticlePublisherData::SCRAP_RESULT_SUCCESS);

            $datesProcessed = 0;
            $dates = $jsonData['article']['dates'];
            if (isset($dates['Received'])) {
                $publisherDataEntity->setPublisherReceived(new DateTime($dates['Received']));
                $datesProcessed++;
            }
            if (isset($dates['Accepted'])) {
                $publisherDataEntity->setPublisherAccepted(new DateTime($dates['Accepted']));
                $datesProcessed++;
            }
            if (isset($dates['Publication date'])) {
                $publisherDataEntity->setPublisherAvailablePrint(new DateTime($dates['Publication date']));
                $datesProcessed++;
            }
            if (isset($dates['Available online'])) {
                $publisherDataEntity->setPublisherAvailableOnline(new DateTime($dates['Available online']));
                $datesProcessed++;
            }

            $this->em->persist($publisherDataEntity);
            $this->em->flush();
            $this->logger->info(sprintf("%s, year=%d, update %d dates, duration=%.3f",
                $article->getDoi(), $article->getYear(), $datesProcessed, $duration));
        } catch (RequestException $e) {
            $data = [
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ];

            $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
            $publisherDataEntity->setData($data);
            $this->em->persist($publisherDataEntity);
            $this->em->flush();
            $this->logger->error(sprintf("%s, http code=%d, %s",
                $article->getDoi(), $data['httpCode'], $data['message']));
        }
    }
}