<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class AscPublisherProcessor implements PublisherProcessor
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }


    public function publisherName(): string
    {
        return 'american chemical society';
    }

    public function process(Article $article): int
    {
        try {
            $client = new Client([
                'cookies' => true,
                'allow_redirects' => true,
                'verify' => false,
                'headers' => [
                    'User-Agent' => "Mozilla/5.0 (X11; FreeBSD i386) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.130 Safari/537.36",
                ],
            ]);

            $url = sprintf('https://doi.org/%s', $article->getDoi());

            $response = $client->request('GET', $url);
            $body = $response->getBody()->getContents();

            $data = $this->parseData($body);

            $article->setPublisherData($data);

            $datesProcessed = 0;
            if (isset($data['Received'])) {
                $article->setPublisherReceived(new DateTime($data['Received']));
                $datesProcessed++;
            }
            if (isset($data['Accepted'])) {
                $article->setPublisherAccepted(new DateTime($data['Accepted']));
                $datesProcessed++;
            }
            if (isset($data['Published online'])) {
                $article->setPublisherAvailableOnline(new DateTime($data['Published online']));
                $datesProcessed++;
            }
            if (isset($data['Published in issue'])) {
                $article->setPublisherAvailablePrint(new DateTime($data['Published in issue']));
                $datesProcessed++;
            }

            $this->em->persist($article);
            $this->em->flush();

            return $datesProcessed;

        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            return 0;
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