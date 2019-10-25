<?php


namespace App\Entity\Journal;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Journal\Journal;
use Symfony\Component\Validator\Constraints\NotBlank;

abstract class JournalImpactBase
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \App\Entity\Journal\Journal
     * @ORM\ManyToOne(targetEntity="App\Entity\Journal\Journal")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @NotBlank()
     **/
    protected $journal;

    /**
     * @var integer
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    protected $year;

    /**
     * @var float
     * @ORM\Column(name="value", type="float", nullable=false)
     */
    protected $value;


    public function getId()
    {
        return $this->id;
    }


    public function getJournal(): ?\App\Entity\Journal\Journal
    {
        return $this->journal;
    }


    public function setJournal(\App\Entity\Journal\Journal $journal)
    {
        $this->journal = $journal;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year)
    {
        $this->year = $year;
        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value)
    {
        $this->value = $value;
        return $this;
    }


}