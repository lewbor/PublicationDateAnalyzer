<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
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
     * @ORM\Column(name="analytics", type="json", nullable=false)
     */
    protected $analytics;

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


}