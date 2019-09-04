<?php


namespace App\Lib;


use Doctrine\ORM\QueryBuilder;

class IteratorUtils
{

    public static function idIterator(QueryBuilder $qb, string $entityAlias = 'entity', int $maxResults = 100): iterable {
        $id = 0;

        while(true) {
            $currentQb = clone $qb;
            echo "querying\n";
            $iterator = $currentQb->andWhere(sprintf('%s.id > %d' , $entityAlias, $id))
                ->setMaxResults($maxResults)
                ->orderBy(sprintf('%s.id', $entityAlias))
                ->getQuery()
                ->iterate();

            $hasItems = false;
            foreach($iterator as $item) {
                $entity = $item[0];
                $hasItems = true;
                yield $entity;
                $id = $entity->getId();
            }

            if(!$hasItems){
                break;
            }
        }
    }
}