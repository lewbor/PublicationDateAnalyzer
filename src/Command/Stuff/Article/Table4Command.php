<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\Journal\Journal;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table4Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table4');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Journal[] $journals */
        $journals = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($journals as $journal) {
            $row = [
                'Journal' => $journal->getName()
            ];
            $row['From'] = Utils::journalDomains($journal, $this->em);
            $minYear = (int)$this->em->createQueryBuilder()
                ->select('MIN(entity.year)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();
            $maxYear = (int)$this->em->createQueryBuilder()
                ->select('MAX(entity.year)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();
            $row['Period'] = sprintf('%d-%d', $minYear, $maxYear);

            $row['Crossref'] = (int)$this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->andWhere('entity.hasCrossrefRecord = true')
                ->getQuery()
                ->getSingleScalarResult();
            $row['Publisher'] = (int)$this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->andWhere('entity.hasPublisherRecord = true')
                ->getQuery()
                ->getSingleScalarResult();
            $row['OA'] = (int)$this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->andWhere('entity.openAccess = true')
                ->getQuery()
                ->getSingleScalarResult();

            $result[] = $row;
        }

        Utils::saveData($result);
    }
}