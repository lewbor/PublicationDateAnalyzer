<?php


namespace App\Repository;


use App\Entity\JournalAnalytics;
use Doctrine\ORM\EntityRepository;

class JournalAnalyticsRepository extends EntityRepository
{

    public function all(): array {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('entity', 'journal')
            ->from(JournalAnalytics::class, 'entity')
            ->join('entity.journal', 'journal')
            ->getQuery()
            ->getResult();
    }
}