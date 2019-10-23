<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Lib\IteratorUtils;
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
        yield from IteratorUtils::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Article::class, 'entity')
                ->andWhere(sprintf('entity.id NOT IN (%s)',
                    $this->em->createQueryBuilder()
                        ->select('article_publisher_data.id')
                        ->from(ArticlePublisherData::class, 'article_publisher_data')
                        ->getDQL()))
        );
    }

    private function queueArticle(Article $article): void
    {
        $this->queueManager->offer(self::QUEUE_NAME, ['id' => $article->getId()]);
    }
}