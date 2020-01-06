<?php


namespace App\Repository;


use App\Entity\Jcr\JournalJcr2Impact;
use App\Entity\Journal\Journal;
use Doctrine\ORM\EntityRepository;

class JournalJcr2ImpactRepository extends EntityRepository
{

    /**
     * @param Journal $journal
     * @return JournalJcr2Impact[]
     */
    public function forJournal(Journal $journal): array {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('entity')
            ->from(JournalJcr2Impact::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->orderBy('entity.year', 'asc')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getResult();
    }
}