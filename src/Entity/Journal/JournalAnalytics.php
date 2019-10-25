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
     * @var array
     * @ORM\Column(name="options", type="json", nullable=false)
     */
    protected $options;

    /**
     * @var array
     * @ORM\Column(name="analytics", type="json", nullable=false)
     */
    protected $analytics;

    public function __construct()
    {
        $this->options = [];
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

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }


}