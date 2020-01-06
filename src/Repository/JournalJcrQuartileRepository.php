<?php


namespace App\Repository;


use App\Entity\Jcr\JournalJcrQuartile;
use App\Entity\Journal\Journal;
use Doctrine\ORM\EntityRepository;

class JournalJcrQuartileRepository extends EntityRepository
{

    /**
     * @param Journal $journal
     * @return JournalJcrQuartile[]
     */
    public function forJournal(Journal $journal, int $minYear = null): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('entity')
            ->from(JournalJcrQuartile::class, 'entity')
            ->andWhere('entity.journal = :journal')
            ->orderBy('entity.year', 'asc')
            ->setParameter('journal', $journal);
        if ($minYear !== null) {
            $qb->andWhere('entity.year >= :minYear')
                ->setParameter('minYear', $minYear);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }
}