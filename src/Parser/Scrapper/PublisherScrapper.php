<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Entity\QueueItem;
use App\Lib\AbstractMultiProcessCommand;
use App\Lib\QueueManager;
use App\Parser\PublisherProcessorFinder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PublisherScrapper
{
    protected $queueManager;
    protected $em;
    protected $logger;
    protected $processorFinder;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager,
        PublisherProcessorFinder $processorFinder
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->processorFinder = $processorFinder;
    }

    public function run(string $queueName)
    {
        $procNumber = (int)$_ENV[AbstractMultiProcessCommand::ENV_PROCESS_NUMBER] ?? 0;

        foreach ($this->queueManager->singleIterator($queueName) as $idx => $queueItem) {
            $this->processItem($queueItem);
            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();

            if ($idx % 10 === 0 && $procNumber === 1) {
                $this->logger->info(sprintf('Reminded %d tasks',
                    $this->queueManager->remindingTasks($queueName)));
            }
        }
    }

    private function processItem(QueueItem $queueItem): void
    {
        $data = $queueItem->getData();
        $article = $this->em->getRepository(Article::class)->find($data['id']);
        if ($article === null) {
            $this->logger->info("Article is null");
            return;
        }

        $processor = $this->processorFinder->findProcessor($article);
        if ($processor === null) {
            return;
        }
        $datesUpdate = $processor->process($article);
        $this->logger->info(sprintf("Processed article=%s, publisher=%s, year=%d, update %d dates",
            $article->getDoi(), $processor->name(),
            $article->getYear(), $datesUpdate));
    }


}