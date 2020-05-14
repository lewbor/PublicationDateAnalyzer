<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table9Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table9');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $columns = [
            'Crossref' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasCrossrefRecord = true'),
            'Publisher' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasPublisherRecord = true'),
            'Publisher, OA' => fn(QueryBuilder $qb) =>
            $qb->andWhere('entity.hasPublisherRecord = true')
            ->andWhere('entity.openAccess = true'),
        ];

        $result = [];

        foreach ($columns as $columnName => $columnFunction) {
            $row = [
                'Resource' => $columnName
            ];

            foreach(PeriodQuery::$PERIODS as $periodName => $periodFunction) {
                $qb = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from(ArticleDatesOaAggregate::class, 'entity');
                $columnFunction($qb);
                $periodFunction($qb);

                $articlesCount = $qb->getQuery()
                    ->getSingleScalarResult();
                $row[$periodName] = $articlesCount;
            }
            $result[] = $row;
        }

        Utils::saveData($result);
    }

}