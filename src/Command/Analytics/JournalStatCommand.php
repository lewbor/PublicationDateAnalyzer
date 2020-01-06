<?php


namespace App\Command\Analytics;


use App\Analytics\Analyzer\ReceivedPublishedAnalyzer;
use App\Analytics\JournalAnalyticsMaker;
use App\Analytics\YearPeriod;
use App\Entity\Article;
use App\Entity\ArticleWebOfScienceData;
use App\Entity\Journal\Journal;
use App\Entity\Journal\JournalStat;
use App\Lib\ArticleQueries;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\Iterator\IteratorUtils;
use App\Lib\ScienceArticleFilter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalStatCommand extends Command
{
    protected $em;
    protected $logger;
    protected $scienceArticleFilter;
    protected $analyticsMaker;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        ScienceArticleFilter $scienceArticleFilter,
        JournalAnalyticsMaker $analyticsMaker)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->scienceArticleFilter = $scienceArticleFilter;
        $this->analyticsMaker = $analyticsMaker;
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
                ->select('entity', 'stat')
                ->from(Journal::class, 'entity')
                ->leftJoin('entity.stat', 'stat'));
    }

    private function processJournal(Journal $journal): void
    {
        if ($journal->getStat() !== null) {
            $stat = $this->em->getRepository(JournalStat::class)->find($journal->getStat()->getId());
            $this->em->remove($stat);
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
            ->setPublisher($journal->getCrossrefData()['publisher']);

        $this->calculateArticlesCount($stat, $journal);
        $this->calculateScienceArticlesCount($stat, $journal);
        $this->calculateMinMaxYears($stat, $journal);
        $this->calculateWosArticlesCount($stat, $journal);
        $this->calculateArticleYears($stat, $journal);
        $this->calculateArticleWosTypes($stat, $journal);
        $this->calculateMedianPublicationTime($stat, $journal);

        $stat->setJournal($this->em->getRepository(Journal::class)->find($journal->getId()));
        $this->em->persist($stat);
        $this->em->flush();
    }

    private function calculateArticlesCount(JournalStat $stat, Journal $journal): void
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

    private function calculateMinMaxYears(JournalStat $stat, Journal $journal): void
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

    private function calculateWosArticlesCount(JournalStat $stat, Journal $journal): void
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

    private function calculateArticleYears(JournalStat $stat, Journal $journal): void
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
        foreach ($data as $row) {
            $years[(int)$row['year']] = (int)$row['articles'];
        }

        $stat->setArticleYears($years);

    }

    private function calculateArticleWosTypes(JournalStat $stat, Journal $journal): void
    {
        $this->em->clear();

        $iterator = DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity', 'webOfScienceData')
                ->from(Article::class, 'entity')
                ->join('entity.webOfScienceData', 'webOfScienceData')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
        );

        $wosTypes = [];
        /** @var Article $article */
        foreach ($iterator as $article) {
            $this->em->clear();
            if ($article->getWebOfScienceData() === null) {
                continue;
            }
            if (empty($article->getWebOfScienceData()->getData()['DT'])) {
                continue;
            }
            $documentTypes = explode(';', $article->getWebOfScienceData()->getData()['DT']);
            $documentTypes = array_map('trim', $documentTypes);
            $documentTypes = array_filter($documentTypes);

            foreach ($documentTypes as $documentType) {
                if (isset($wosTypes[$documentType])) {
                    $wosTypes[$documentType]++;
                } else {
                    $wosTypes[$documentType] = 1;
                }
            }
        }

        $stat->setWosPublicationTypes($wosTypes);
    }

    private function calculateScienceArticlesCount(JournalStat $stat, Journal $journal): void
    {
        $iterator = DoctrineIterator::idIterator(
            ArticleQueries::partialDataJoinQuery(
                $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from(Article::class, 'entity')
                    ->andWhere('entity.journal = :journal')
                    ->setParameter('journal', $journal)));
        $iterator = $this->scienceArticleFilter->apply($iterator);

        $articlesCount = IteratorUtils::itemCount($iterator);
        $stat->setScienceArticlesCount($articlesCount);
    }

    private function calculateMedianPublicationTime(JournalStat $stat, Journal $journal): void
    {
        $articleYears = $this->em->createQueryBuilder()
            ->select('distinct entity.year')
            ->from(Article::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->orderBy('entity.year', 'asc')
            ->getQuery()
            ->getResult();
        $years = array_map(function (array $row) {
            return $row['year'];
        }, $articleYears);

        $medianPublicationTime = [];

        foreach ($years as $year) {
            $yearPeriod = new YearPeriod($year, $year);
            $analyzerResult = $this->analyticsMaker->analyticsForJournal($journal, $yearPeriod, [new ReceivedPublishedAnalyzer()]);
            $medianPublicationTime[$year] = [
                'articles_count' => $analyzerResult['analyzers']['Received_Published']['articles_count'],
                'median' => $analyzerResult['analyzers']['Received_Published']['median'],
                'median_count' => $analyzerResult['analyzers']['Received_Published']['median_count'],
            ];
        }

        $stat->setMedianPublicationTime($medianPublicationTime);
    }
}