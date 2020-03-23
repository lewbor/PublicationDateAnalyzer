<?php


namespace App\Parser\Crossref;


use App\Entity\QueueItem;
use App\Lib\AbstractMultiProcessCommand;
use App\Lib\QueueManager;
use Campo\UserAgent;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class CrossrefUrlScrapper
{

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
    }

    public function scrap()
    {
        $procNumber = (int)$_ENV[AbstractMultiProcessCommand::ENV_PROCESS_NUMBER] ?? 0;

        foreach ($this->queueManager->singleIterator(CrossrefUrlResolveQueer::QUEUE_NAME) as $idx => $queueItem) {
            $this->processItem($queueItem, $procNumber);
            $this->queueManager->acknowledge($queueItem, QueueManager::ASKNOWLEDGE_MODE_UPDATE);

            $this->em->clear();
//            if ($procNumber === 1 && ($idx % 100 === 0)) {
//                $this->logger->info(sprintf("reminding %d records",
//                    $this->queueManager->remindingTasks(CrossrefUrlResolveQueer::QUEUE_NAME)));
//            }
        }

    }

    private function processItem(QueueItem $queueItem, int $procNumber): void
    {
        $data = $queueItem->getData();
        $articleId = (int) $data['id'];
        $doi = (string) $data['doi'];

        $this->processArticle($articleId, $doi, $procNumber);
    }

    private function processArticle(int $articleId, string $doi, int $procNumber): void
    {
        for ($i = 1; $i <= 3; $i++) {
            try {
                $client = new Client([
                    'verify' => false,
                    'http_errors' => false,
                    'headers' => [
                        'User-Agent' => UserAgent::random([
                            'os_type' => 'Windows',
                            'device_type' => 'Desktop'
                        ])
                    ],
                ]);


                $url = sprintf('https://%s/api/handles/%s', 'doi.org', $doi);

                $startTime = microtime(true);
                $response = $client->request('GET', $url);
                $duration = microtime(true) - $startTime;

                if ($response->getStatusCode() !== 200) {
                    $this->logger->info(sprintf('%s - response code %d', $doi, $response->getStatusCode()));
                    $this->insertInvalidResponse($articleId, $response->getStatusCode());
                    return;
                }
                $content = $response->getBody()->getContents();
                $json = \GuzzleHttp\json_decode($content, true);
                if (empty($json)) {
                    $this->logger->error(sprintf('%s - empty json', $doi));
                    return;
                }
                if ($json['responseCode'] != 1) {
                    $this->logger->error(sprintf('%s - invalid response code: %s', $doi, $content));
                    return;
                }
                if (empty($json['values'])) {
                    $this->logger->error(sprintf('%s - empty values: %s', $doi, $content));
                    return;
                }
                $urlValue = $this->urlValue($json['values']);
                if ($urlValue === null) {
                    $this->logger->error(sprintf('%s - no URL value: %s', $doi, $content));
                    return;
                }
                $url = $urlValue['data']['value'];
                if (empty($url)) {
                    $this->logger->error(sprintf('%s - empty url: %s', $doi, $content));
                    return;
                }
                $this->insertArticleUrl($articleId, $url);
                echo sprintf("%d - %s - %.3f\n", $procNumber, $doi, $duration);
                return;
            } catch (Exception $e) {
                $this->logger->error(sprintf('%s - exception %s', $doi, $e->getMessage()));
            }
        }
    }

    private function urlValue(array $values): ?array
    {
        foreach ($values as $value) {
            if ($value['type'] == 'URL') {
                return $value;
            }
        }
        return null;
    }

    private function insertArticleUrl(int $articleId, string $url): void
    {
        $conn = $this->em->getConnection();
        $affectedRows = $conn->insert('article_url', [
            'article_id' => $articleId,
            'scrapped_at' => date("Y-m-d H:i:s"),
            'url' => $url,
            'response_code' => 200
        ]);
        if($affectedRows !== 1) {
            throw new Exception();
        }
    }

    private function insertInvalidResponse(int $articleId, int $responseCode): void
    {
        $conn = $this->em->getConnection();
        $affectedRows = $conn->insert('article_url', [
            'article_id' => $articleId,
            'scrapped_at' => date("Y-m-d H:i:s"),
            'url' => null,
            'response_code' => $responseCode
        ]);
        if($affectedRows !== 1) {
            throw new Exception();
        }
    }
}