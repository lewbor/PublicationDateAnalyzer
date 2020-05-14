<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table13Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table13');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dates = [
            'received-accepted' => fn(QueryBuilder $qb) => $qb
                ->andWhere('entity.publisherReceived IS NOT NULL')
                ->andWhere('entity.publisherAccepted IS NOT NULL'),
            'accepted-published' => fn(QueryBuilder $qb) => $qb
                ->andWhere('entity.$publisherAccepted IS NOT NULL')
                ->andWhere('entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL'),
            'received-published' => fn(QueryBuilder $qb) => $qb
                ->andWhere('entity.publisherReceived IS NOT NULL')
                ->andWhere('entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL'),
        ];

        $result = [];

        foreach ($dates as $columnName => $columnFunction) {
            $row = [
                'Date' => $columnName
            ];

            foreach (PeriodQuery::$PERIODS as $periodName => $periodFunction) {
                $qb = $this->em->createQueryBuilder()
                    ->select('entity')
                    ->from(ArticleDatesOaAggregate::class, 'entity');
                $columnFunction($qb);
                $periodFunction($qb);
                $articlesCount = $qb->getQuery()->getResult();
                $row[$periodName] = $articlesCount;
            }
        }
        Utils::saveData($result);
    }

}