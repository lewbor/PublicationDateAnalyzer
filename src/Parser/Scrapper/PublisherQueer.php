<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\QueueManager;
use App\Parser\PublisherProcessorFinder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherQueer
{
    protected $em;
    protected $logger;
    protected $queueManager;
    protected $processorFinder;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager,
        PublisherProcessorFinder $processorFinder)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->processorFinder = $processorFinder;
    }

    public function run(string $queueName): void
    {
        $publishers = $this->processorFinder->publishersByQueueName($queueName);
        if (count($publishers) === 0) {
            $this->logger->error(sprintf('No publishers form queue name %s', $queueName));
            return;
        }
        $this->logger->info(sprintf('Will queue articles for publishers: %s', implode(', ', $publishers)));

        $queuedArticles = 0;
        foreach ($this->articleIterator($publishers) as $article) {
            $this->queueArticle($article, $queueName);
            $queuedArticles++;
            if ($queuedArticles % 100 === 0) {
                $this->logger->info(sprintf("Queued %d articles", $queuedArticles));
            }
            $this->em->clear();
        }
        $this->logger->info(sprintf('Totally queued %d articles', $queuedArticles));

    }

    private function articleIterator(array $publishers): iterable
    {
        yield from DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Article::class, 'entity')
                ->join('entity.journal', 'journal')
                ->andWhere('journal.publisher IN (:publishers)')
                ->andWhere(sprintf('entity.id NOT IN (%s)',
                    $this->em->createQueryBuilder()
                        ->select('article_publisher_data_article')
                        ->from(ArticlePublisherData::class, 'article_publisher_data')
                        ->join('article_publisher_data.article', 'article_publisher_data_article')
                        ->getDQL()))
                ->setParameter('publishers', $publishers)
        );
    }

    private function queueArticle(Article $article, string $queueName): void
    {
        $this->queueManager->offer($queueName, ['id' => $article->getId()]);
    }
}