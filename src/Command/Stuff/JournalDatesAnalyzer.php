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
        }

        $writer->close();
    }

    private function journalData(Journal $journal): array
    {
        $queries = [
            'crossref_records' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.hasCrossrefRecord = 1'),
            'publisher_records' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.hasPublisherRecord = 1'),
            'crossref_print' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.crossrefPublishedPrint IS NOT NULL'),
            'crossref_online' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.crossrefPublishedOnline IS NOT NULL'),
            'publisher_accepted' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherAccepted IS NOT NULL'),
            'publisher_received' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherReceived IS NOT NULL'),
            'publisher_print' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherAvailablePrint IS NOT NULL'),
            'publisher_online' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherAvailableOnline IS NOT NULL'),
            'received_accepted' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherReceived IS NOT NULL')
                ->andWhere('entity.publisherAccepted IS NOT NULL'),
            'received_published' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherReceived IS NOT NULL')
                ->andWhere('(entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL)'),
            'accepted_published' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.publisherAccepted IS NOT NULL')
                ->andWhere('(entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL)'),
        ];

        $yearModifiers = [
            'all' => function (QueryBuilder $qb) {
                $qb->andWhere('entity.year <= 2009');
            },
            '2000-2009' => function (QueryBuilder $qb) {
                $qb->andWhere('entity.year >= 2000 AND entity.year <= 2009');
            },
            '2010-2019' => function (QueryBuilder $qb) {
                $qb->andWhere('entity.year >= 2010 AND entity.year <= 2019');
            },
            '2018' => function (QueryBuilder $qb) {
                $qb->andWhere('entity.year = 2018');
            },
            '2019' => function (QueryBuilder $qb) {
                $qb->andWhere('entity.year = 2019');
            },
        ];

        $openAccessModifiers = [
            '' => function(QueryBuilder $qb){

            },
            '_oa' => function(QueryBuilder $qb){
                $qb->andWhere('entity.openAccess = 1');
            },
        ];

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

        foreach ($queries as $queryName => $qb) {
            foreach ($yearModifiers as $modifierName => $modifier) {
                foreach ($openAccessModifiers as $oaName => $openAccessModifier) {
                    /** @var QueryBuilder $currentQb */
                    $currentQb = clone $qb;
                    $modifier($currentQb);
                    $openAccessModifier($currentQb);
                    $recordsCount =  (int) $currentQb
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