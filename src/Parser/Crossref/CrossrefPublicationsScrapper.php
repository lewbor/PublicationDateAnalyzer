<?php


namespace App\Parser\Crossref;


use App\Entity\Article;
use App\Entity\ArticleCrossrefData;
use App\Entity\Journal;
use App\Entity\QueueItem;
use App\Lib\QueueManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class CrossrefPublicationsScrapper
{
    private const TRY_COUNT = 10;
    private const INVALID_RESPONSE_DELAY = 5;

    protected $em;
    protected $logger;
    protected $queueManager;

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

        foreach ($this->queueManager->singleIterator(CrossrefPublicationsQueer::QUEUE_NAME) as $idx => $queueItem) {
            $this->processItem($queueItem);

            $queueItem = $this->em->getRepository(QueueItem::class)->find($queueItem->getId());
            $this->queueManager->acknowledge($queueItem);

            $this->em->clear();
            $this->logger->info(sprintf("reminding %d records",
                $this->queueManager->remindingTasks(CrossrefPublicationsQueer::QUEUE_NAME)));
        }

    }

    private function processItem(QueueItem $queueItem)
    {
        $data = $queueItem->getData();
        $journal = $this->em->getRepository(Journal::class)->find($data['id']);
        if ($journal === null) {
            $this->logger->info("Journal is not exist");
            return;
        }

        $this->processJournal($journal);
    }

    private function processJournal(Journal $journal)
    {
        $issn = null;
        if (!empty($journal->getIssn())) {
            $issn = $journal->getIssn();
        } elseif (!empty($journal->getEissn())) {
            $issn = $journal->getEissn();
        } else {
            $this->logger->error(sprintf('Journal %s - no valid issn', $journal->getName()));
            return;
        }

        $this->logger->info(sprintf('Start process journal %d', $journal->getId()));

        $scrappedArticles = 0;
        $insertedArticles = 0;
        $nextCursor = '*';
        while (true) {
            $this->em->clear();
            $journal = $this->em->getRepository(Journal::class)->find($journal->getId());

            $result = $this->fetchCrossrefData($issn, $nextCursor);

            if ($result['status'] != 'ok') {
                $this->logger->error(sprintf('Error response: %s', json_encode($result)));
                break;
            }

            if (empty($result['message']['items'])) {
                $this->logger->info(sprintf('Journal %d - no more items', $journal->getId()));
                break;
            }

            foreach ($result['message']['items'] as $item) {
                $wasInsertion = $this->processPublication($item, $journal);
                if($wasInsertion) {
                    $insertedArticles++;
                }
                $this->em->clear();
            }

            $nextCursor = $result['message']['next-cursor'];
            if (empty($nextCursor)) {
                break;
            }

            $scrappedArticles += count($result['message']['items']);
            $this->logger->info(sprintf('Journal %d - processed %d items, inserted %d',
                $journal->getId(), $scrappedArticles, $insertedArticles));
        }

        $this->logger->info(sprintf('End process journal %d, scrapped %d articles', $journal->getId(), $scrappedArticles));
    }

    private function processPublication(array $item, Journal $journal): bool
    {
        $doi = $item['DOI'];

        $existingArticle = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.doi = :doi')
            ->setParameter('doi', $doi)
            ->getQuery()
            ->getOneOrNullResult();
        if ($existingArticle !== null) {
            return false;
        }

        $journal = $this->em->getRepository(Journal::class)->find($journal->getId());

        $article = (new Article())
            ->setDoi($doi)
            ->setName($item['title'][0] ?? '')
            ->setYear($item['issued']['date-parts'][0][0] ?? 0)
            ->setJournal($journal);
        $crossrefEntity = (new ArticleCrossrefData())
            ->setArticle($article)
            ->setData($item);

        $this->updateCrossrefDates($crossrefEntity, $item);
        $this->em->persist($article);
        $this->em->persist($crossrefEntity);
        $this->em->flush();

        return true;
    }

    private function fetchCrossrefData(string $issn, string $nextCursor): array
    {
        $lastException = null;

        for ($i = 0; $i < self::TRY_COUNT; $i++) {
            try {
                $url = sprintf('https://api.crossref.org/journals/%s/works', $issn);
                $urlParams = [
                    'filter' => 'type:journal-article',
                    'mailto' => 'lewbor@mail.ru',
                    'rows' => 500,
                    'cursor' => $nextCursor
                ];

                $client = new Client();
                $response = $client->request('GET', $url, [
                    'query' => $urlParams,
                    'exceptions' => false,
                    RequestOptions::CONNECT_TIMEOUT => 10,
                    RequestOptions::READ_TIMEOUT => 10,
                ]);
                $body = $response->getBody()->getContents();
                $result = \GuzzleHttp\json_decode($body, true);
                return $result;
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->logger->info(sprintf('Will sleep %d seconds', self::INVALID_RESPONSE_DELAY));
                $lastException = $e;
                sleep(self::INVALID_RESPONSE_DELAY);
            }
        }
        throw $lastException;
    }

    private function updateCrossrefDates(ArticleCrossrefData $article, array $item): void
    {
        if (isset($item['published-print'])) {
            $parts = $item['published-print']['date-parts'][0];
            if (count($parts) > 1) {
                $publishedDate = $this->formatDate($parts);
                $article->setPublishedPrint($publishedDate);
            }
        }
        if (isset($response['published-online'])) {
            $parts = $item['published-online']['date-parts'][0];

            if (count($parts) == 3) {
                $publishedDate = $this->formatDate($parts);
                $article->setPublishedOnline($publishedDate);
            }
        }
    }

    private function formatDate(array $parts): DateTime
    {
        $dateStr = sprintf('%04d-%02d-%02d',
            (int)$parts[0],
            (int)$parts[1],
            isset($parts[2]) ? (int)$parts[2] : 1
        );
        return new DateTime($dateStr);
    }


}