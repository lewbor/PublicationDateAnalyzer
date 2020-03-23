<?php


namespace App\Command\Unpaywall;


use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallScrapCommand extends Command
{
    const UNPAYWALL_EMAIL = 'barchan@ngs.ru';
    const CMD_NAME = 'unpaywall.scrap';

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
    }

    protected function configure()
    {
        $this->setName(self::CMD_NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->queueManager->singleIterator(UnpaywallQueueCommand::QUEUE_NAME) as $idx => $queueItem) {
            $articleId = $queueItem->getData()['id'];
            $doi = $queueItem->getData()['doi'];

            $this->processArticle($articleId, $doi);

            $this->queueManager->acknowledge($queueItem, QueueManager::ASKNOWLEDGE_MODE_UPDATE);
            $this->em->clear();
        }
        return 0;
    }

    private function processArticle(int $articleId, string $doi): void
    {
        try {
            $url = sprintf('https://api.unpaywall.org/v2/%s?email=%s', $this->encodeDoi($doi), self::UNPAYWALL_EMAIL);

            /** @var ResponseInterface $response */
            [$response, $duration] = $this->getUnpaywallResponse($url);
            echo sprintf("%s - %.f, response %d\n", $doi, $duration, $response->getStatusCode());

            if(!in_array($response->getStatusCode(), [200, 404])) {
                return;
            }
            $body = $response->getBody()->getContents();
            $result = \GuzzleHttp\json_decode($body, true);
            $this->insertUnpaywallData($articleId, $result, $response->getStatusCode());


        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function encodeDoi($doi)
    {
        return str_replace(['%', '"', '#', ' ', '?'], ['%25', '%22', '%23', '%20', '%3F'], $doi);
    }

    private function getUnpaywallResponse(string $url): array
    {
        $client = new Client();
        $lastException = null;

        for ($i = 0; $i < 5; $i++) {
            try {
                $startTime = microtime(true);
                $response = $client->get($url, ['exceptions' => false]);
                $duration = microtime(true) - $startTime;

                return [$response, $duration];
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $lastException = $e;
                sleep(5);
            }
        }
        throw $lastException;
    }

    private function insertUnpaywallData(int $articleId, array $response, int $responseCode): void
    {
        $conn = $this->em->getConnection();
        $affectedRows = $conn->insert('article_unpaywall_data', [
            'article_id' => $articleId,
            'scrapped_at' => date("Y-m-d H:i:s"),
            'response_code' => $responseCode,
            'publisher_data' => json_encode($response),
            'open_access' => isset($response['is_oa']) ? (int) $response['is_oa'] : null
        ]);
        if ($affectedRows !== 1) {
            throw new Exception();
        }
    }
}