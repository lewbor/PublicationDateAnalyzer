<?php


namespace App\Entity\Journal;

use App\Entity\Journal\Journal;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\App\Repository\JournalAnalyticsRepository")
 * @ORM\Table(name="journal_analytics")
 */
class JournalAnalytics
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Journal
     * @ORM\ManyToOne(targetEntity="Journal")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $journal;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="start_year", type="integer", nullable=false)
     */
    protected $startYear;

    /**
     * @var int
     * @ORM\Column(name="end_year", type="integer", nullable=false)
     */
    protected $endYear;

    /**
     * @var ?bool
     * @ORM\Column(name="open_access", type="boolean", nullable=true)
     */
    protected $openAccess;

    /**
     * @var array
     * @ORM\Column(name="analytics", type="json", nullable=false)
     */
    protected $analytics;

    public function __construct()
    {
        $this->analytics = [];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getJournal(): Journal
    {
        return $this->journal;
    }

    public function setJournal(Journal $journal): self
    {
        $this->journal = $journal;
        return $this;
    }

    public function getAnalytics(): array
    {
        return $this->analytics;
    }

    public function setAnalytics(array $analytics): self
    {
        $this->analytics = $analytics;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStartYear(): int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): self
    {
        $this->startYear = $startYear;
        return $this;
    }

    public function getEndYear(): int
    {
        return $this->endYear;
    }

    public function setEndYear(int $endYear): self
    {
        $this->endYear = $endYear;
        return $this;
    }

    public function getOpenAccess(): ?bool
    {
        return $this->openAccess;
    }

    public function setOpenAccess(?bool $openAccess): self
    {
        $this->openAccess = $openAccess;
        return $this;
    }



}