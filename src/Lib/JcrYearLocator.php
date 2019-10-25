<?php


namespace App\Lib;


use App\Entity\Jcr\JournalJcr2Impact;
use Doctrine\ORM\EntityManagerInterface;

class JcrYearLocator
{
    protected $em;
    protected $cache = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function latestYear(): int {
        if($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = (int)$this->em->createQueryBuilder()
            ->select('MAX(entity.year)')
            ->from(JournalJcr2Impact::class, 'entity')
            ->getQuery()
            ->getSingleScalarResult();
        return $this->cache;
    }
}