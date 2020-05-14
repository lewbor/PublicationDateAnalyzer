<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table7Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table7');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $columns = [
            'Print' =>fn(QueryBuilder $qb) => $qb->andWhere('entity.crossrefPublishedPrint IS NOT NULL'),
            'Online' =>fn(QueryBuilder $qb) => $qb->andWhere('entity.crossrefPublishedOnline IS NOT NULL'),
        ];

        $result = [];
        foreach ($columns as $columnName => $columnFunction) {
            $row = [
                'Date' => $columnName
            ];

            foreach (PeriodQuery::$PERIODS as $periodName => $periodFunction) {
                $qb = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from(ArticleDatesOaAggregate::class, 'entity');
                $columnFunction($qb);
                $periodFunction($qb);
                $articlesCount = (int) $qb->getQuery()->getSingleScalarResult();
                $row[$periodName] = $articlesCount;
            }
            $result[] = $row;
        }

        Utils::saveData($result);
    }

}