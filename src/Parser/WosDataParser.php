<?php


namespace App\Parser;


use App\Lib\CsvIterator;
use App\Lib\FileIterator;
use App\Lib\RecordIterator;
use Exception;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class WosDataParser
{

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
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
        }
    }

    private function processRecord(array $record)
    {
        if(empty(record['DI'])) {
            $this->logger->info(sprintf('%s - doi is empty', $record['UT']));
            return;
        }
        echo 1;
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