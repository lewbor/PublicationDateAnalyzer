<?php


namespace App\Entity\Jcr;

use App\Entity\Journal\Journal;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotNull;


/**
 * @ORM\Entity(repositoryClass="\App\Repository\JournalJcrQuartileRepository")
 * @ORM\Table(name="journal_wos_quartile", uniqueConstraints={
@ORM\UniqueConstraint(name="search_idx", columns={"journal_id", "year", "category"})
 * })
 */

class JournalJcrQuartile
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="year", type="integer")
     */
    protected $year;

    /**
     * @var Journal
     * @ORM\ManyToOne(targetEntity="\App\Entity\Journal\Journal")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @NotNull(message="not_blank")
     **/
    protected $journal;

    /**
     * @var string
     * @ORM\Column(name="category", type="string", nullable=true)
     */
    protected $category;

    /**
     * @var int
     * @ORM\Column(name="quartile", type="integer", nullable=false)
     */
    protected $quartile;

    public function getId()
    {
        return $this->id;
    }


    public function getJournal(): ?Journal
    {
        return $this->journal;
    }

    public function setJournal(Journal $journal): self
    {
        $this->journal = $journal;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getQuartile(): ?int
    {
        return $this->quartile;
    }

    public function setQuartile(int $quartile): self
    {
        $this->quartile = $quartile;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }
}