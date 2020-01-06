<?php


namespace App\Lib\Iterator;


use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\Filter\Form\Entity\EntityInterface;

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
            /** @var EntityInterface $entity */
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

    public static function batchIdIterator(QueryBuilder $qb, string $entityAlias = 'entity', int $maxResults = 100): iterable {
        $id = 0;

        while(true) {
            $currentQb = clone $qb;

            $items = $currentQb->andWhere(sprintf('%s.id > %d' , $entityAlias, $id))
                ->setMaxResults($maxResults)
                ->orderBy(sprintf('%s.id', $entityAlias))
                ->getQuery()
                ->getResult();

            if(count($items) === 0) {
                break;
            }
            if(count($items) < $maxResults) {
                yield $items;
                break;
            }

            yield $items;

            /** @var EntityInterface $lastItem */
            $lastItem = $items[count($items) -1];
            $id = $lastItem->getId();
            $qb->getEntityManager()->clear();
        }
    }

}