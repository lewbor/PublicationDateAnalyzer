<?php


namespace App\Command\Stuff;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class Queries
{
    public static array $queries;
    public static array $yearModifiers;
    public static array $openAccessModifiers;
}

Queries::$queries = [
    'crossref_records' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.hasCrossrefRecord = 1'),
    'publisher_records' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.hasPublisherRecord = 1'),
    'crossref_print' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.crossrefPublishedPrint IS NOT NULL'),
    'crossref_online' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.crossrefPublishedOnline IS NOT NULL'),
    'publisher_accepted' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherAccepted IS NOT NULL'),
    'publisher_received' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherReceived IS NOT NULL'),
    'publisher_print' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherAvailablePrint IS NOT NULL'),
    'publisher_online' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherAvailableOnline IS NOT NULL'),
    'received_accepted' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherReceived IS NOT NULL')
        ->andWhere('entity.publisherAccepted IS NOT NULL'),
    'received_published' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherReceived IS NOT NULL')
        ->andWhere('(entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL)'),
    'accepted_published' => fn(EntityManagerInterface $em) => $em->createQueryBuilder()
        ->select('COUNT(entity.id)')
        ->from(ArticleDatesOaAggregate::class, 'entity')
        ->andWhere('entity.publisherAccepted IS NOT NULL')
        ->andWhere('(entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL)'),
];

Queries::$yearModifiers = [
    'all' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year <= 2019'),
    '2000-2009' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year >= 2000 AND entity.year <= 2009'),
    '2010-2019' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year >= 2010 AND entity.year <= 2019'),
    '2018' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year = 2018'),
    '2019' => fn(QueryBuilder $qb) => $qb->andWhere('entity.year = 2019'),
];

Queries::$openAccessModifiers = [
    '' => function (QueryBuilder $qb) {

    },
    '_oa' => function (QueryBuilder $qb) {
        $qb->andWhere('entity.openAccess = 1');
    },
];