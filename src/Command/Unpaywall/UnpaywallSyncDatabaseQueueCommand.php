<?php


namespace App\Command\Unpaywall;


use App\Entity\Article;
use App\Lib\IteratorUtils;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallSyncDatabaseQueueCommand extends Command
{
    const QUEUE_NAME = 'unpaywall_sync_scrap';

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
        $this->setName('unpaywall.sync_database.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iterator = IteratorUtils::idIterator($this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.year >= 2018')
            ->andWhere('entity.openAccess IS NULL')
        );
        foreach ($iterator as $idx => $article) {
            /** @var Article $article */
            $this->queueManager->offer(self::QUEUE_NAME, ['id' => $article->getId()]);
            $this->em->clear();

            if ($idx % 10 === 0) {
                $this->logger->info(sprintf("Queued %d articles", $idx));
            }
        }
    }

}