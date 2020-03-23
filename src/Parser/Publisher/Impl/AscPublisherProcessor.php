<?php


namespace App\Parser\Publisher\Impl;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Parser\Publisher\ProcessorTrait;
use App\Parser\Publisher\PublisherProcessor;
use App\Parser\Publisher\ScrapperUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class AscPublisherProcessor implements PublisherProcessor
{
    const QUEUE_NAME = 'publisher.asc';

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
        return 'asc';
    }

    public function scrappingDomains(): array
    {
        return ['pubs.acs.org'];
    }

    public function queueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function process(Article $article): void
    {
        try {
            $client = ScrapperUtils::createClient();

            if (!ScrapperUtils::validateUrl($article, $this->logger)) {
                return;
            }

            $startTime = microtime(true);
            $response = $client->request('GET', $article->getUrl()->getUrl());
            $duration = microtime(true) - $startTime;

            $body = $response->getBody()->getContents();

            $data = $this->parseData($body);

            $publisherData = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_SUCCESS);

            $datesProcessed = 0;
            if (isset($data['Received'])) {
                $publisherData->setPublisherReceived(new DateTime($data['Received']));
                $datesProcessed++;
            }
            if (isset($data['Accepted'])) {
                $publisherData->setPublisherAccepted(new DateTime($data['Accepted']));
                $datesProcessed++;
            }
            if (isset($data['Published online'])) {
                $publisherData->setPublisherAvailableOnline(new DateTime($data['Published online']));
                $datesProcessed++;
            }
            if (isset($data['Published in issue'])) {
                $publisherData->setPublisherAvailablePrint(new DateTime($data['Published in issue']));
                $datesProcessed++;
            }

            $this->em->persist($publisherData);
            $this->em->flush();
            $this->logger->info(sprintf('%s - process %d dates, duration=%.3f', $article->getDoi(), $datesProcessed, $duration));

        } catch (RequestException $e) {
            $data = [
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ];

            $publisherData = $this->createPublisherData($article, $data, ArticlePublisherData::SCRAP_RESULT_ERROR);
            $this->em->persist($publisherData);
            $this->em->flush();
            $this->logger->error(sprintf('%s - response code is %d', $article->getDoi(), $data['httpCode']));

        }
    }

    private function parseData(string $body): array
    {
        $data = [];

        $crawler = new Crawler($body);
        $crawler->filter('div.article_header-history ul.article-chapter-history-list li')
            ->each(function (Crawler $elem) use (&$data) {
                $attrName = $elem->filter('span.item_label')->text(null);
                $attrValue = $elem->text();
                if (!empty($attrName) && !empty($attrValue)) {
                    $attrName = trim($attrName);
                    $attrValue = trim(substr($attrValue, strlen($attrName)));
                    if (strpos($attrValue, 'issue') === 0) {
                        $attrName .= ' issue';
                        $attrValue = trim(substr($attrValue, strlen('issue')));
                    }
                    $data[$attrName] = $attrValue;
                }
            });

        return $data;

    }


}