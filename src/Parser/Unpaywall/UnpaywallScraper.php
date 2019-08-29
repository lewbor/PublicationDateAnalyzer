<?php


namespace App\Parser\Unpaywall;


use App\Entity\Article;
use App\Entity\QueueItem;
use App\Lib\QueueManager;
use App\Parser\Unpaywall\UnpaywallQueer;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class UnpaywallScraper
{
    const UNPAYWALL_EMAIL = 'barchan@ngs.ru';

    protected $em;
    protected $logger;
    protected $queueManager;
    protected $httpClient;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->httpClient = new Client();
    }

    public function run()
    {
        foreach ($this->queueManager->singleIterator(UnpaywallQueer::QUEUE_NAME) as $idx => $queueItem) {
            $this->processItem($queueItem);
            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();

            if($idx % 10 === 0) {
                $this->logger->info(sprintf("reminding %d records",
                    $this->queueManager->remindingTasks(UnpaywallQueer::QUEUE_NAME)));
            }
        }
    }

    private function processItem(QueueItem $queueItem)
    {
        $data = $queueItem->getData();
        $article = $this->em->getRepository(Article::class)->find($data['id']);
        if ($article === null) {
            $this->logger->info("Article is null");
            return;
        }

        $response = $this->unpaywallResponse($article->getDoi());
        $article->setUnpaywallData($response);
        $this->em->persist($article);
        $this->em->flush();
    }

    private function encodeDoi($doi)
    {
        return str_replace(['%', '"', '#', ' ', '?'], ['%25', '%22', '%23', '%20', '%3F'], $doi);
    }

    private function unpaywallResponse(string $doi): array
    {
        $url = sprintf('https://api.unpaywall.org/v2/%s?email=%s', $this->encodeDoi($doi), self::UNPAYWALL_EMAIL);

        $response = $this->httpClient->get($url, ['exceptions' => false]);
        $body = $response->getBody()->getContents();

        switch ($response->getStatusCode()) {
            case 200:
            case 404:
                $result = \GuzzleHttp\json_decode($body, true);
                return $result;
            default:
                throw new Exception($body);
        }
    }
}