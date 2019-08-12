<?php


namespace App\Analytics;


class YearPeriod
{

    protected $start;
    protected $end;
    protected $openAccess;

    public function __construct(int $start, int $end, bool $openAccess = null)
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

}