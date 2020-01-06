<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
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

    protected $em;
    protected $logger;

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

    public function publisherNames(): array
    {
        return [
            'elsevier'
        ];
    }

    public function queueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function process(Article $article): int
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

            $url = sprintf('https://doi.org/%s', $article->getDoi());

            $response = $client->request('GET', $url);
            $body = $response->getBody()->getContents();

            $crawler = new Crawler($body);
            if($crawler->filter('#trackErrorPage')->count() > 0) {
                $data = [
                    'success' => false,
                    'httpCode' => $response->getStatusCode(),
                    'message' => 'Requested article is not found in IHub',
                ];

                $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_NO_DATA);
                $this->em->persist($publisherDataEntity);
                $this->em->flush();
                return 0;
            }

            $redirectUrl = $crawler->filter('input[name=redirectURL]')->attr('value');
            $redirectUrl = urldecode($redirectUrl);

            $response = $client->request('GET', $redirectUrl);
            $body = $response->getBody()->getContents();

            $crawler = new Crawler($body);
            $jsonDataStr = $crawler->filter('script[data-iso-key="_0"]')->text();
            $jsonData = json_decode($jsonDataStr, true);

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

            return $datesProcessed;
        } catch (RequestException $e) {
            $data = [
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ];

            $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
            $publisherDataEntity->setData($data);
            $this->em->persist($publisherDataEntity);
            $this->em->flush();
            return 0;
        }
    }
}