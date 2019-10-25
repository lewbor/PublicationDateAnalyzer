<?php


namespace App\Entity\Jcr;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal_jcr_quartile", uniqueConstraints={
    @ORM\UniqueConstraint(name="journal_year_category", columns={"issn", "year", "category"})
 * })
 */
class JournalJcrQuartileSource
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
     * @ORM\Column(name="issn", type="string", length=9, nullable=false)
     */
    protected $issn;

    /**
     * @var string
     * @ORM\Column(name="category", type="string", nullable=false)
     */
    protected $category;

    /**
     * @var int
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    protected $rank;

    /**
     * @var int
     * @ORM\Column(name="quartile", type="integer", nullable=false)
     */
    protected $quartile;

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

    public function getIssn(): string
    {
        return $this->issn;
    }

    public function setIssn(string $issn): self
    {
        $this->issn = $issn;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;
        return $this;
    }

    public function getQuartile(): int
    {
        return $this->quartile;
    }

    public function setQuartile(int $quartile): self
    {
        $this->quartile = $quartile;
        return $this;
    }


}