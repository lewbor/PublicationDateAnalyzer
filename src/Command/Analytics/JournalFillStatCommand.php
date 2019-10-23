<?php


namespace App\Command\Analytics;


use App\Entity\Article;
use App\Entity\Journal;
use App\Entity\JournalStat;
use App\Lib\IteratorUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalFillStatCommand extends Command
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('journal.stat');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->journalIterator() as $journal) {
            $this->processJournal($journal);
            $this->em->clear();
            $this->logger->info(sprintf("Processed journal %d", $journal->getId()));
        }
    }

    private function journalIterator(): iterable
    {
        return IteratorUtils::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
        );
    }

    private function processJournal(Journal $journal): void
    {
        if ($journal->getStat() !== null) {
            $this->em->remove($journal->getStat());
            $journal->setStat(null);
            $this->em->flush();
        }

        if (empty($journal->getCrossrefData())) {
            $this->logger->info(sprintf('Journal %d - no crossref data', $journal->getId()));
            return;
        }
        if (empty($journal->getCrossrefData()['publisher'])) {
            $this->logger->info(sprintf('Journal %d - empty publisher', $journal->getId()));
            return;
        }

        $stat = (new JournalStat())
            ->setJournal($journal)
            ->setPublisher($journal->getCrossrefData()['publisher']);

        $articlesCount = (int)$this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $stat->setArticlesCount($articlesCount);

        if ($articlesCount > 0) {
            $minYear = (int)$this->em->createQueryBuilder()
                ->select('MIN(entity.year)')
                ->from(Article::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();
            $stat->setArticleMinYear($minYear);

            $maxYear = (int)$this->em->createQueryBuilder()
                ->select('MAX(entity.year)')
                ->from(Article::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();
            $stat->setArticleMaxYear($minYear);

        } else {
            $stat->setArticleMinYear(0)
                ->setArticleMaxYear(0);
        }

        $this->em->persist($stat);
        $this->em->flush();
    }
}