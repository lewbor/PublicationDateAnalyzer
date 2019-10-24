<?php


namespace App\Command\Wos;


use App\Entity\Article;
use App\Entity\ArticleWebOfScienceData;
use App\Lib\Iterator\CsvIterator;
use App\Lib\Iterator\FileIterator;
use App\Lib\Iterator\RecordIterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class WosFillDataCommand extends Command
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('wos.fill_data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataPath = __DIR__ . '/../../../data/wos';
        $finder = (new Finder())
            ->in($dataPath)
            ->files()
            ->name('wos_abstract_*.txt');
        foreach($finder as $file) {
            $this->processFile($file);
        }
    }

    private function processFile(SplFileInfo $file): void
    {
        $recordProcessed = 0;
        foreach($this->itemIterator($file) as [$record, $error]) {
            if($error !== null) {
                $this->logger->error($error->getMessage());
                continue;
            }
            $this->processRecord($record);
            $recordProcessed++;
            $this->em->clear();

            if($recordProcessed % 50 === 0) {
                $this->logger->info(sprintf('Processed %d records', $recordProcessed));
            }
        }
    }

    private function itemIterator(SplFileInfo $file): iterable
    {
        $iterator = FileIterator::line($file->getRealPath());
        $iterator = $this->abstractsCsvIterator($iterator);
        $iterator = CsvIterator::trimmedCsv($iterator);
        $iterator = CsvIterator::clearedCsv($iterator);
        $iterator = RecordIterator::record($iterator);
        return $iterator;
    }

    private function abstractsCsvIterator($iterator): iterable
    {
        foreach ($iterator as $item) {
            $line = $item[FileIterator::LINE];
            $parts = explode("\t", $line);

            yield array_replace($item, [FileIterator::LINE => $parts]);
        }
    }

    private function processRecord(array $record): void
    {
        if(!isset($record['UT'])) {
            $this->logger->error(sprintf('UT is not set'));
            return;
        }

        if(empty($record['DI'])) {
            $this->logger->error(sprintf('Doi is empty, UT=%s', $record['UT']));
            return;
        }

        $article = $this->em->getRepository(Article::class)->findOneBy(['doi' => $record['DI']]);
        if($article === null) {
            $this->logger->info(sprintf('Article with doi not found, doi=%s', $record['DI']));
            return;
        }

        if($article->getWebOfScienceData() === null) {
            $dataEntity = (new ArticleWebOfScienceData())
                ->setArticle($article);
        }  else {
            $dataEntity = $article->getWebOfScienceData();
        }

        $dataEntity->setData($record)
            ->setWosId($record['UT']);
        $this->em->persist($dataEntity);
        $this->em->flush();
    }
}