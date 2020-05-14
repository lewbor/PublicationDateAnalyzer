<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table3Command extends Command
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table3');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queries = [
            'crossref' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasCrossrefRecord = true'),
            'Crossref, OA' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasCrossrefRecord = true')
                ->andWhere('entity.openAccess = true'),
            'Publishers' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasPublisherRecord = true'),
            'Publishers, OA' => fn(QueryBuilder $qb) => $qb->andWhere('entity.hasPublisherRecord = true')
                ->andWhere('entity.openAccess = true'),
        ];

        $result = [];

        foreach ($queries as $queryName => $queryFunction) {
            $row = [
                'Source' => $queryName
            ];

            foreach (PeriodQuery::$PERIODS as $periodName => $periodFunction) {
                $qb = $this->em->createQueryBuilder()
                    ->select('COUNT(entity.id)')
                    ->from(ArticleDatesOaAggregate::class, 'entity');
                $queryFunction($qb);
                $periodFunction($qb);
                $articlesCount = $qb->getQuery()
                    ->getSingleScalarResult();
                $row[$periodName] = $articlesCount;
            }
            foreach ($row as $periodName => $articlesCount) {
                $percent = (int)round(($articlesCount / $row['all']) * 100.0);
                $row[$periodName] = sprintf('%d / %s', $articlesCount, $percent);
            }
            $result[] = $row;
        }

        Utils::saveData($result);
    }

}