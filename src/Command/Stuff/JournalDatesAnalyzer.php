<?php


namespace App\Command\Stuff;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\Journal\Journal;
use App\Lib\CsvWriter;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalDatesAnalyzer extends Command
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('journal.dates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Journal[] $journals */
        $journals = DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
        );

        $writer = new CsvWriter('php://stdout');
        $writer->open();

        foreach ($journals as $journal) {
            $journalData = $this->journalData($journal);
            $row = array_merge([
                'id' => $journal->getId(),
                'name' => $journal->getName(),
            ], $journalData);
            $writer->write($row);
            $this->em->clear();
        }

        $writer->close();
    }

    private function journalData(Journal $journal): array
    {
        $result = [];
        $result['min_year'] = (int)$this->em->createQueryBuilder()
            ->select('MIN(entity.year)')
            ->from(ArticleDatesOaAggregate::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();
        $result['max_year'] = (int)$this->em->createQueryBuilder()
            ->select('MAX(entity.year)')
            ->from(ArticleDatesOaAggregate::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getSingleScalarResult();

        foreach (Queries::$queries as $queryName => $qbBuilder) {
            foreach (Queries::$yearModifiers as $modifierName => $modifier) {
                foreach (Queries::$openAccessModifiers as $oaName => $openAccessModifier) {
                    /** @var QueryBuilder $currentQb */
                    $currentQb = $qbBuilder($this->em);
                    $modifier($currentQb);
                    $openAccessModifier($currentQb);
                    $recordsCount = (int)$currentQb
                        ->andWhere('entity.journal = :journal')
                        ->setParameter('journal', $journal)
                        ->getQuery()
                        ->getSingleScalarResult();

                    $result[$queryName . '_' . $modifierName . $oaName] = $recordsCount;
                }
            }
        }


        return $result;
    }
}