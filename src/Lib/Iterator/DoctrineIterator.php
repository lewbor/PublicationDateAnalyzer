<?php


namespace App\Lib\Iterator;


use Doctrine\ORM\QueryBuilder;

class DoctrineIterator
{

    public static function idIterator(QueryBuilder $qb, string $entityAlias = 'entity', int $maxResults = 100): iterable {
        $id = 0;

        while(true) {
            $currentQb = clone $qb;

            $iterator = $currentQb->andWhere(sprintf('%s.id > %d' , $entityAlias, $id))
                ->setMaxResults($maxResults)
                ->orderBy(sprintf('%s.id', $entityAlias))
                ->getQuery()
                ->getResult();

            $hasItems = false;
            foreach($iterator as $entity) {
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