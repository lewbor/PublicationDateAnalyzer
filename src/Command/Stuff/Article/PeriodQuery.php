<?php


namespace App\Command\Stuff\Article;


use Doctrine\ORM\QueryBuilder;

class PeriodQuery
{
    public static array $PERIODS;
}

PeriodQuery::$PERIODS = [
    'all' => fn(QueryBuilder $qb) => null,
    '2000-2009' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year >= 2000')
        ->andWhere('entity.year <= 2009'),
    '2010-2019' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year >= 2010')
        ->andWhere('entity.year <= 2019'),
    '2018' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year = 2018'),
    '2019' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year = 2019'),
];