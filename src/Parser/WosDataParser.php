<?php


namespace App\Parser;


use App\Entity\Article;
use App\Entity\Journal\Journal;
use App\Lib\Iterator\CsvIterator;
use App\Lib\Iterator\FileIterator;
use App\Lib\Iterator\RecordIterator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class WosDataParser
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function parse(string $path)
    {
        $finder = (new Finder())
            ->files()
            ->in($path);

        foreach ($finder as $file) {
            $this->processFile($file);
        }
    }

    private function processFile(SplFileInfo $file)
    {
        $iterator = FileIterator::line($file->getRealPath());
        $iterator = $this->abstractsCsvIterator($iterator);
        $iterator = CsvIterator::clearedCsv($iterator);
        $iterator = RecordIterator::record($iterator);

        foreach ($iterator as [$record, $error]) {
            /** @var Exception $error */
            if ($error !== null) {
                $this->logger->error($error->getMessage());
                continue;
            }

            $this->processRecord($record);
            $this->em->clear();
        }
    }

    private function processRecord(array $record)
    {
        if (empty($record['DI'])) {
            $this->logger->info(sprintf('%s - doi is empty', $record['UT']));
            return;
        }

        $existingArticle = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.doi = :doi')
            ->setParameter('doi', $record['DI'])
            ->getQuery()
            ->getOneOrNullResult();
        if ($existingArticle !== null) {
            $this->logger->info(sprintf('Article with doi exist, doi=%s', $record['DI']));
            return;
        }

        $journalQb = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity');
        if (!empty($record['SN'])) {
            $journalQb->orWhere('entity.issn = :issn')
                ->setParameter('issn', $record['SN']);
        }
        if (!empty($record['EI'])) {
            $journalQb->andWhere('entity.eissn = :eissn')
                ->setParameter('eissn', $record['EI']);
        }
        $journal = $journalQb
            ->getQuery()
            ->getOneOrNullResult();
        if ($journal === null) {
            $journal = (new Journal())
                ->setName($record['SO']);
            if (!empty($record['SN'])) {
                $journal->setIssn($record['SN']);
            }
            if (!empty($record['EI'])) {
                $journal->setEissn($record['EI']);
            }
            $this->em->persist($journal);
            $this->em->flush();
        }

        $article = (new Article())
            ->setName($record['TI'])
            ->setAuthors($record['AF'])
            ->setDoi($record['DI'])
            ->setJournal($journal)
            ->setYear((int)$record['PY'])
            ->setWosId($record['UT']);
        $this->em->persist($article);
        $this->em->flush();

        $this->logger->info(sprintf('Inserted article id=%d', $article->getId()));
    }

    private function abstractsCsvIterator($iterator)
    {
        foreach ($iterator as $item) {
            $line = $item[FileIterator::LINE];
            $parts = explode("\t", $line);

            yield array_replace($item, [FileIterator::LINE => $parts]);
        }
    }
}