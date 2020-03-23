<?php


namespace App\Command\Crossref\DomainMerge;


use App\Entity\ArticleUrlDomain;
use App\Lib\QueueManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DomainMergeQueueCommand extends Command
{
    const QUEUE_NAME = 'crossref.domains';

    protected EntityManagerInterface $em;
    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        QueueManager $queueManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->queueManager = $queueManager;
    }

    protected function configure()
    {
        $this->setName('crossref.domains.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queueManager->truncate(self::QUEUE_NAME);

        /** @var ArticleUrlDomain[] $domains */
        $domains = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(ArticleUrlDomain::class, 'entity')
            ->orderBy('entity.id')
            ->getQuery()
            ->getResult();

        foreach ($domains as $domain) {
            if(in_array($domain->getDomain(), ['test.doi.com', 'doi.org'])) {
                continue;
            }
            $this->queueManager->offer(self::QUEUE_NAME, [
                'id' => $domain->getId()
            ]);
        }
    }
}