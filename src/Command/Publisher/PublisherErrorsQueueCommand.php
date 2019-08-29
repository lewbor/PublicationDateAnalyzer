<?php


namespace App\Command\Publisher;


use App\Entity\Article;
use App\Lib\QueueManager;
use App\Parser\Scrapper\PublisherQueer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublisherErrorsQueueCommand extends Command
{
    protected $em;
    protected  $logger;
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
        $this->setName('publisher.errors.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $offeredArticles = 0;

        foreach($this->articleIterator() as $article) {
            /** @var Article $article */
            $publisherData = $article->getPublisherData();
            if(isset($publisherData['success']) && $publisherData['success'] === false) {
                $this->queueManager->offer(PublisherQueer::QUEUE_NAME, ['id' => $article->getId()]);
                $offeredArticles++;
                if($offeredArticles % 10 === 0) {
                    $this->logger->info(sprintf("Queed %d articles", $offeredArticles));
                }
            }
            $this->em->clear();
        }
    }

    private function articleIterator(): iterable
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherData IS NULL')
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }
}