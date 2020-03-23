<?php


namespace App\Lib;


use Doctrine\ORM\QueryBuilder;

class ArticleQueries
{

    public static function partialDataJoinQuery(QueryBuilder $qb): QueryBuilder {
        $qb
            ->leftJoin('entity.crossrefData', 'crossrefData')
            ->leftJoin('entity.publisherData', 'publisherData')
            ->leftJoin('entity.webOfScienceData', 'webOfScienceData')
            ->leftJoin('entity.unpaywallData', 'unpaywallData')
            ->leftJoin('entity.url', 'url')
            ->addSelect('partial crossrefData.{id}', 'partial publisherData.{id}',
                'partial webOfScienceData.{id}', 'partial unpaywallData.{id}', 'partial url.{id}');
        return $qb;
    }
}