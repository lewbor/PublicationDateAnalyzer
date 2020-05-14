<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\Journal\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table8Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table8');
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
            $row['Total'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();

            $row['Print'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->andWhere('entity.crossrefPublishedPrint IS NOT NULL')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();

            $row['Online'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.journal = :journal')
                ->andWhere('entity.crossrefPublishedOnline IS NOT NULL')
                ->setParameter('journal', $journal)
                ->getQuery()
                ->getSingleScalarResult();

            $result[] = $row;
        }

        Utils::saveData($result);
    }

}