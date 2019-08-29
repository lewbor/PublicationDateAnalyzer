<?php


namespace App\Parser;


use App\Entity\Article;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;

class CrossrefDateUpdater
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    )
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function run()
    {
        $recordsProcessed = 0;
        foreach ($this->articleIterator() as $article) {
            $this->processArticle($article);
            $this->em->clear();

            $recordsProcessed++;
            if($recordsProcessed % 50 === 0) {
                $this->logger->info(sprintf('Processed %d records', $recordsProcessed));
            }
        }
    }

    private function articleIterator(): iterable
    {
        yield from $this->idIterator($this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.crossrefData IS NOT NULL')
        );
    }

    private function idIterator(QueryBuilder $qb): iterable {
        $lastId = 0;

        while(true) {
            $currentQb = clone $qb;
            $iterator = $currentQb
                ->andWhere('entity.id > :id')
                ->setMaxResults(10000)
                ->orderBy('entity.id', 'asc')
                ->setParameter('id', $lastId)
                ->getQuery()
                ->iterate();

            $hasItems = false;
            foreach ($iterator as $item) {
                $hasItems = true;
                $obj = $item[0];
                yield $obj;
                $lastId = $obj->getId();
                $this->em->detach($obj);
            }

            if(!$hasItems) {
                break;
            }
        }
    }

    private function processArticle(Article $article): void
    {
        $crossrefData = $article->getCrossrefData();

        $this->updatePublishedPrint($crossrefData, $article);
        $this->updatePublishedOnline($crossrefData, $article);
        $this->em->persist($article);
        $this->em->flush();
    }

    private function updatePublishedPrint(array $response, Article $publication)
    {
        if (isset($response['published-print'])) {
            $parts = $response['published-print']['date-parts'][0];
            if (count($parts) > 1) {
                $publishedDate = $this->formatDate($parts);
                $publication->setPublishedPrint($publishedDate);
            }
        }
    }

    private function updatePublishedOnline(array $response, Article $publication)
    {
        if (isset($response['published-online'])) {
            $parts = $response['published-online']['date-parts'][0];

            if (count($parts) == 3) {
                $publishedDate = $this->formatDate($parts);
                $publication->setPublishedOnline($publishedDate);
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