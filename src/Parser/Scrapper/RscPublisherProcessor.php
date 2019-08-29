<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class RscPublisherProcessor implements PublisherProcessor
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

    public function name(): string
    {
        return 'rsc';
    }

    public function publisherNames(): array
    {
        return [
            'rsc'
        ];
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

            $this->em->persist($article);
            $this->em->flush();

            return $datesProcessed;
        } catch (RequestException $e) {
            $data = [
                'success' => false,
                'httpCode' => $e->getResponse() === null ? null : $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ];
            $article->setPublisherData($data);
            $this->em->persist($article);
            $this->em->flush();
            return 0;
        }

    }

    private function parseData(string $body): array
    {
        $crawler = new Crawler($body);

        try {
            $publicationDetailText = $crawler->filter('#divAbout div[class="autopad--h"] p')->text();
        } catch (\InvalidArgumentException $e) {
            return [];
        }

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

        $result = [];
        foreach ($parts as $part) {
            foreach ($prefixMap as $prefix => $key) {
                if (strpos($part, $prefix) === 0) {
                    $result[$key] = substr($part, strlen($prefix) + 1);
                }
            }
        }
        return $result;
    }
}