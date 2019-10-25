<?php


namespace App\Entity\Jcr;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal_jcr_impact", uniqueConstraints={
        @ORM\UniqueConstraint(name="journal_year", columns={"issn", "year"})
 * })
 */
class JournalJcrImpactSource
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    protected $year;

    /**
     * @var string
     * @ORM\Column(name="full_title", type="string", nullable=false)
     */
    protected $fullTitle;

    /**
     * @var string
     * @ORM\Column(name="abbreviated_title", type="string", nullable=false)
     */
    protected $abbreviatedTitle;

    /**
     * @var string
     * @ORM\Column(name="issn", type="string", length=9, nullable=false)
     */
    protected $issn;

    /**
     * @var float
     * @ORM\Column(name="impact_factor", type="float", nullable=false)
     */
    protected $impactFactor;

    /**
     * @var float
     * @ORM\Column(name="impact_factor_5", type="float", nullable=false)
     */
    protected $impactFactor5;

    public function getId()
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }


    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getFullTitle(): string
    {
        return $this->fullTitle;
    }

    public function setFullTitle(string $fullTitle): self
    {
        $this->fullTitle = $fullTitle;
        return $this;
    }

    public function getAbbreviatedTitle(): string
    {
        return $this->abbreviatedTitle;
    }

    public function setAbbreviatedTitle(string $abbreviatedTitle): self
    {
        $this->abbreviatedTitle = $abbreviatedTitle;
        return $this;
    }

    public function getIssn(): string
    {
        return $this->issn;
    }

    public function setIssn(string $issn): self
    {
        $this->issn = $issn;
        return $this;
    }

    public function getImpactFactor(): float
    {
        return $this->impactFactor;
    }

    public function setImpactFactor(float $impactFactor): self
    {
        $this->impactFactor = $impactFactor;
        return $this;
    }

    public function getImpactFactor5(): float
    {
        return $this->impactFactor5;
    }

    public function setImpactFactor5(float $impactFactor5): self
    {
        $this->impactFactor5 = $impactFactor5;
        return $this;
    }


}