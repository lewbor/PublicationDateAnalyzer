<?php


namespace App\Parser;


use App\Entity\Article;
use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class CrossrefScrapper
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

    public function scrap()
    {

        foreach ($this->journalIterator() as $journal) {
            $this->processJournal($journal);
            $this->em->clear();
        }
    }

    private function journalIterator()
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->andWhere('entity.crossrefData IS NOT NULL')
            ->andWhere(sprintf('(%s) <= 500',
                $this->em->createQueryBuilder()
                    ->select('COUNT(article.id)')
                    ->from(Article::class, 'article')
                    ->andWhere('article.journal = entity')
                    ->getQuery()
                    ->getDQL()
            ))
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }

    private function processJournal(Journal $journal)
    {
        $issn = null;
        if (!empty($journal->getIssn())) {
            $issn = $journal->getIssn();
        } elseif (!empty($journal->getEissn())) {
            $issn = $journal->getEissn();
        } else {
            return;
        }

        $this->logger->info(sprintf('Start process journal %d', $journal->getId()));

        $scrappedArticles = 0;
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
                $this->processItem($item, $journal);
            }

            $nextCursor = $result['message']['next-cursor'];
            if (empty($nextCursor)) {
                break;
            }

            $scrappedArticles += count($result['message']['items']);
            $this->logger->info(sprintf('Journal %d - processed %d items', $journal->getId(), $scrappedArticles));
        }

        $this->logger->info(sprintf('End process journal %d', $journal->getId()));
    }

    private function processItem(array $item, Journal $journal)
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
            return;
        }

        $article = (new Article())
            ->setDoi($doi)
            ->setName($item['title'][0] ?? '')
            ->setYear($item['issued']['date-parts'][0][0] ?? 0)
            ->setJournal($journal)
            ->setCrossrefData($item);
        $this->em->persist($article);
        $this->em->flush();
    }

    private function fetchCrossrefData(string $issn, string $nextCursor): array
    {
        $lastException = null;

        for ($i = 0; $i < 10; $i++) {
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
            } catch (\InvalidArgumentException $e) {
                $this->logger->error($e->getMessage());
                $this->logger->error($body);
                $lastException = $e;
                sleep(5);
            }
        }
        throw $lastException;
    }


}