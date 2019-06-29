<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherQueer
{
    const QUEUE_NAME = 'publisher_scrap';

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

    public function run()
    {

        $queuedArticles = 0;
        foreach ($this->articleIterator() as $article) {
            $this->queueArticle($article);
            $queuedArticles++;
            if ($queuedArticles % 100 === 0) {
                $this->logger->info(sprintf("Queued %d articles", $queuedArticles));
            }
            $this->em->clear();
        }
        $this->logger->info(sprintf('Totally queued %d articles', $queuedArticles));

    }

    private function articleIterator(): iterable
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.crossrefData IS NOT NULL')
            ->andWhere('entity.publisherData IS NULL')
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }

    private function queueArticle(Article $article): void
    {
        $this->queueManager->offer(self::QUEUE_NAME, ['id' => $article->getId()]);
    }
}