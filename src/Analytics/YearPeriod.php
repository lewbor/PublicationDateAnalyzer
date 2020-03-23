<?php


namespace App\Analytics;


use Doctrine\ORM\QueryBuilder;

class YearPeriod
{

    protected int $start;
    protected int $end;
    protected ?bool $openAccess;

    public function __construct(int $start, int $end, ?bool $openAccess)
    {
        $this->start = $start;
        $this->end = $end;
        $this->openAccess = $openAccess;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function isOpenAccess(): ?bool
    {
        return $this->openAccess;
    }

    public function limitQuery(QueryBuilder $qb): QueryBuilder
    {
        $qb->andWhere('entity.year >= :startYear')
            ->setParameter('startYear', $this->start)
            ->andWhere('entity.year <= :endYear')
            ->setParameter('endYear', $this->end);

        if ($this->openAccess !== null) {
            $qb->andWhere('unpaywallData.openAccess = true');
        }

        return $qb;
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
            'openAccess' => $this->openAccess
        ];
    }

}