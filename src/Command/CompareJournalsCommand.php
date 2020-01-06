<?php


namespace App\Command;


use App\Entity\Journal\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareJournalsCommand extends Command
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
        $this->setName('compare_journals')
            ->addArgument('inputFile', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFilePath = $input->getArgument('inputFile');
        if (!file_exists($inputFilePath)) {
            throw new \Exception(sprintf('File %s is not exist', $inputFilePath));
        }

        $records = $this->rowsIterator($inputFilePath);
        foreach ($records as [$name, $issn, $eissn]) {
            $name = trim($name);
            $issn = $this->normalizeIssn($issn);
            $eissn = $this->normalizeIssn($eissn);

            if (empty($issn) && empty($eissn)) {
                $this->logger->error(sprintf('%s - no issns', $name));
                continue;
            }
            if (!empty($issn) && strlen($issn) !== 8) {
                $this->logger->error(sprintf('%s - invalid issn', $issn));
                continue;
            }
            if (!empty($eissn) && strlen($eissn) !== 8) {
                $this->logger->error(sprintf('%s - invalid issn', $eissn));
                continue;
            }

            $qb = $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
                ->orWhere('entity.name = :name')
                ->setParameter('name', $name);
            if (!empty($issn)) {
                $qb->orWhere('entity.issn = :issn')
                    ->orWhere('entity.eissn = :issn')
                    ->setParameter('issn', $issn);
            }
            if (!empty($eissn)) {
                $qb
                    ->orWhere('entity.issn = :eissn')
                    ->orWhere('entity.eissn = :eissn')
                    ->setParameter('eissn', $eissn);
            }

            $journals = $qb->getQuery()->getResult();
            if (count($journals) === 0) {
                echo $name, "\n";
            }
        }
    }

    private function rowsIterator(string $inputFilePath): iterable
    {
        $handle = fopen($inputFilePath, 'r');
        if (false === $handle) {
            throw new \Exception(sprintf("Cant open file %s", $inputFilePath));
        }

        while (($line = fgets($handle)) !== false) {
            $record = explode("\t", $line);
            $record = array_map('trim', $record);
            yield $record;
        }

        fclose($handle);
    }

    private function normalizeIssn(string $issn): string
    {
        $issn = str_replace('-', '', $issn);
        $issn = preg_replace("/[^A-Za-z0-9 ]/", '', $issn);
        if (strlen($issn) === 5) {
            $issn = '000' . $issn;
        } elseif (strlen($issn) === 6) {
            $issn = '00' . $issn;
        } elseif (strlen($issn) === 7) {
            $issn = '0' . $issn;

        }
        return $issn;
    }
}