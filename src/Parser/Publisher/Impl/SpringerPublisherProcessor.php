<?php


namespace App\Parser\Publisher\Impl;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Parser\Publisher\ProcessorTrait;
use App\Parser\Publisher\PublisherProcessor;
use App\Parser\Publisher\PublisherService;
use Campo\UserAgent;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class SpringerPublisherProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.springer';

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
        $this->publisherService  = $publisherService;
    }

    public function name(): string
    {
        return 'springer';
    }

    public function scrappingDomains(): array
    {
        return ['link.springer.com'];
    }

    public function queueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function process(Article $article): void
    {
        try {
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

            $data = $this->parseData($body);
            $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_SUCCESS);

            $datesProcessed = 0;
            if (isset($data['Received'])) {
                $publisherDataEntity->setPublisherReceived(new DateTime($data['Received']));
                $datesProcessed++;
            }
            if (isset($data['Accepted'])) {
                $publisherDataEntity->setPublisherAccepted(new DateTime($data['Accepted']));
                $datesProcessed++;
            }
            if (isset($data['First print'])) {
                $publisherDataEntity->setPublisherAvailablePrint(new DateTime($data['First print']));
                $datesProcessed++;
            }
            if (isset($data['First Online'])) {
                $publisherDataEntity->setPublisherAvailableOnline(new DateTime($data['First Online']));
                $datesProcessed++;
            }

            $this->em->persist($publisherDataEntity);
            $this->em->flush();

            $this->logger->info(sprintf("%s, year=%d, update %d dates, duration=%.3f",
                $article->getDoi(), $article->getYear(), $datesProcessed, $duration));

        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            $data = [
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
            ];
            $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
            $this->em->persist($publisherDataEntity);
            $this->em->flush();
            $this->logger->error(sprintf("%s, http code=%d",
                $article->getDoi(), $data['httpCode']));
        }
    }

    private function parseData(string $body): array
    {
        $data = [];

        $crawler = new Crawler($body);

        try {
            $issueDate = $crawler->filter('div.ArticleHeader .ArticleCitation_Year time')->text(null);
            if (!empty($issueDate)) {
                $data['First print'] = $issueDate;
            }
        } catch (\InvalidArgumentException $e) {

        }

        $crawler->filter('div.bibliographic-information ul.bibliographic-information__list li.bibliographic-information__item')
            ->each(function (Crawler $node) use (&$data) {
                try {
                    $attrName = $node->filter('.bibliographic-information__title')->text(null);
                    $attrValue = $node->filter('.bibliographic-information__value')->text(null);
                    if ($attrName !== null && $attrValue !== null) {
                        $data[$attrName] = $attrValue;
                    }
                } catch (\InvalidArgumentException $e) {

                }
            });

        return $data;
    }
}