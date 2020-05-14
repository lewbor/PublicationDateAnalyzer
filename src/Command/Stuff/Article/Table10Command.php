<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table10Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table10');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $columns = [
            'Print' => fn(QueryBuilder $qb) => $qb->andWhere('entity.crossrefPublishedPrint IS NOT NULL'),
            'Online' => fn(QueryBuilder $qb) => $qb->andWhere('entity.crossrefPublishedOnline IS NOT NULL'),
            'Total' => fn(QueryBuilder $qb) => null,
        ];

        $dataSources = [
            'Crossref' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasCrossrefRecord = true'),
            'Publisher' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasPublisherRecord = true'),
            'Crossref (OA)' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasCrossrefRecord = true')
                ->andWhere('entity.openAccess = true'),
            'Publisher (OA)' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasPublisherRecord = true')
                ->andWhere('entity.openAccess = true'),
        ];

        $result = [];

        foreach ($columns as $columnName => $columnFunction) {
            $row = [
                'Date' => $columnName,
            ];

            foreach ($dataSources as $name => $dataSourceFunction) {
                $qb = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from(ArticleDatesOaAggregate::class, 'entity');
                $columnFunction($qb);
                $dataSourceFunction($qb);
                $articlesCount = $qb->getQuery()->getSingleScalarResult();
                $row[$name] = $articlesCount;
            }

            $result[] = $row;
        }

        Utils::saveData($result);
    }
}