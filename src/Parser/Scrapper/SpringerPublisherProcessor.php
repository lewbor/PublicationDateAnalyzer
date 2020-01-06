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

class SpringerPublisherProcessor implements PublisherProcessor
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
        return 'springer';
    }

    public function publisherNames(): array
    {
        return [
            'springer',
            'pleiades'
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
            return $datesProcessed;
        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            $data = [
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
            ];
            $publisherDataEntity = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
            $this->em->persist($publisherDataEntity);
            $this->em->flush();
            return 0;
        }
    }

    private function parseData(string $body): array
    {
        $data = [];

        $crawler = new Crawler($body);

        $issueDate = $crawler->filter('div.ArticleHeader .ArticleCitation_Year time')->text(null);
        if (!empty($issueDate)) {
            $data['First print'] = $issueDate;
        }

        $crawler->filter('div.bibliographic-information ul.bibliographic-information__list li.bibliographic-information__item')
            ->each(function (Crawler $node) use (&$data) {
                $attrName = $node->filter('.bibliographic-information__title')->text(null);
                $attrValue = $node->filter('.bibliographic-information__value')->text(null);
                if ($attrName !== null && $attrValue !== null) {
                    $data[$attrName] = $attrValue;
                }
            });

        return $data;
    }
}