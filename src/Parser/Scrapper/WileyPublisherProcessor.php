<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class WileyPublisherProcessor implements PublisherProcessor
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
        return 'wiley';
    }

    public function publisherNames(): array
    {
        return [
            'wiley'
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

            $url = sprintf('https://onlinelibrary.wiley.com/action/ajaxShowPubInfo?widgetId=5cf4c79f-0ae9-4dc5-96ce-77f62de7ada9&ajax=true&doi=%s', $article->getDoi());

            $response = $client->request('GET', $url);
            $body = $response->getBody()->getContents();

            $data = $this->parseData($body);
            $article->setPublisherData($data);

            $datesProcessed = 0;
            if (isset($data['Received'])) {
                $article->setPublisherReceived($data['Received']);
                $datesProcessed++;
            }
            if (isset($data['Accepted'])) {
                $article->setPublisherAccepted($data['Accepted']);
                $datesProcessed++;
            }
            if (isset($data['Online'])) {
                $article->setPublisherAvailableOnline($data['Online']);
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
        $data = [];

        $crawler = new Crawler($body);
        $crawler->filter('section.publication-history ul.rlist li')
            ->each(function (Crawler $elem) use (&$data) {
                $itemText = trim($elem->text());
                $parts = explode(':', $itemText);
                if (count($parts) !== 2) {
                    $this->logger->notice(sprintf('Date string format error: %s', $itemText));
                    return;
                }

                $parts = array_map('trim', $parts);
                $data[strtolower($parts[0])] = new DateTime($parts[1]);
            });

        $publisherDates = [];
        if(isset($data['manuscript received'])) {
            $publisherDates['Received'] = $data['manuscript received'];
        }
        if(isset($data['manuscript accepted'])) {
            $publisherDates['Accepted'] = $data['manuscript accepted'];
        }elseif(isset($data['manuscript revised'])) {
            $publisherDates['Accepted'] = $data['manuscript revised'];
        }

        $onlineDates = array_filter($data, function(string $label){
            return strpos($label, 'online') !== false;
        }, ARRAY_FILTER_USE_KEY);
        if(count($onlineDates) > 0) {
            $publisherDates['Online'] = min($onlineDates);
        }

        return $publisherDates;

    }
}