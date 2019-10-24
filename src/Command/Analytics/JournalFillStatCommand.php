<?php


namespace App\Command\Analytics;


use App\Entity\Article;
use App\Entity\ArticleWebOfScienceData;
use App\Entity\Journal;
use App\Entity\JournalStat;
use App\Lib\Iterator\DoctrineIterator;
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
        return DoctrineIterator::idIterator(
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

        $this->updateArticlesCount($stat, $journal);
        $this->updateMinMaxYears($stat, $journal);
        $this->updateWosArticlesCount($stat, $journal);
        $this->updateArticleYears($stat, $journal);

        $this->em->persist($stat);
        $this->em->flush();
    }

    private function updateArticlesCount(JournalStat $stat, Journal $journal): void
    {
        $articlesCount = (int)$this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $stat->setArticlesCount($articlesCount);
    }

    private function updateMinMaxYears(JournalStat $stat, Journal $journal): void
    {
        if ($stat->getArticlesCount() > 0) {
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
            $stat->setArticleMaxYear($maxYear);

        } else {
            $stat
                ->setArticleMinYear(0)
                ->setArticleMaxYear(0);
        }
    }

    private function updateWosArticlesCount(JournalStat $stat, Journal $journal): void
    {
        $wosArticlesCount = (int)$this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(ArticleWebOfScienceData::class, 'entity')
            ->andWhere(sprintf('entity.article IN (%s)',
                $this->em->createQueryBuilder()
                    ->select('article.id')
                    ->from(Article::class, 'article')
                    ->andWhere('article.journal = :journal')
                    ->getDQL()))
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $stat->setWosArticlesCount($wosArticlesCount);
    }

    private function updateArticleYears(JournalStat $stat, Journal $journal): void
    {
        $data = $this->em->createQueryBuilder()
            ->select('entity.year', 'COUNT(entity.id) as articles')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->groupBy('entity.year')
            ->orderBy('entity.year', 'asc')
            ->getQuery()
            ->getResult();

        $years = [];
        foreach($data as $row) {
            $years[(int) $row['year']] = (int) $row['articles'];
        }

        $stat->setArticleYears($years);

    }
}