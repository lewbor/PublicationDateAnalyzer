<?php


namespace App\Command\Unpaywall;


use App\Entity\Article;
use App\Entity\ArticleUnpaywallData;
use App\Entity\QueueItem;
use App\Entity\Unpaywall;
use App\Lib\AbstractMultiProcessCommand;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallSyncDatabaseScrapCommand extends Command
{
    const UNPAYWALL_EMAIL = 'barchan@ngs.ru';
    const CMD_NAME = 'unpaywall.sync_database.scrap';

    protected $em;
    protected $logger;
    protected $queueManager;

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
        $procNumber = (int)$_ENV[AbstractMultiProcessCommand::ENV_PROCESS_NUMBER] ?? 0;
        foreach ($this->queueManager->singleIterator(UnpaywallSyncDatabaseQueueCommand::QUEUE_NAME) as $idx => $queueItem) {
            $article = $this->em->getRepository(Article::class)->find($queueItem->getData()['id']);
            $this->processArticle($article);

            $queueItem = $this->em->getRepository(QueueItem::class)->find($queueItem->getId());
            $this->queueManager->acknowledge($queueItem);

            $this->em->clear();
            if ($idx % 10 === 0) {
                $this->logger->info(sprintf("%d - Processed %d records", $procNumber, $idx + 1));
            }
            if ($idx % 10 === 0 && $procNumber === 1) {
                $this->logger->info(sprintf('Reminded %d tasks',
                    $this->queueManager->remindingTasks(UnpaywallSyncDatabaseQueueCommand::QUEUE_NAME)));
            }
        }
    }

    private function processArticle(Article $article): void
    {
        if ($article->getUnpaywallData() !== null) {
            $this->logger->info(sprintf('Article already have a unpaywall, id=%d', $article->getId()));
        }

        try {
            $response = $this->unpaywallResponse($article->getDoi());
            $entity = (new ArticleUnpaywallData())
                ->setArticle($article)
                ->setData($response)
                ->setOpenAccess(isset($response['is_oa']) ? $response['is_oa'] : null);
            $this->em->persist($entity);
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function encodeDoi($doi)
    {
        return str_replace(['%', '"', '#', ' ', '?'], ['%25', '%22', '%23', '%20', '%3F'], $doi);
    }

    private function unpaywallResponse(string $doi): array
    {

        $url = sprintf('https://api.unpaywall.org/v2/%s?email=%s', $this->encodeDoi($doi), self::UNPAYWALL_EMAIL);

        $response = $this->getUnpaywallResponse($url);
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

    private function getUnpaywallResponse(string $url)
    {
        $client = new Client();
        $lastException = null;

        for ($i = 0; $i < 5; $i++) {
            try {
                return $client->get($url, ['exceptions' => false]);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $lastException = $e;
                sleep(5);
            }
        }
        throw $lastException;
    }
}