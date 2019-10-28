<?php


namespace App\Entity\Jcr;


use App\Entity\Journal\Journal;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="journal_wos_category")
 */
class JournalWosCategory
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Journal
     * @ORM\ManyToOne(targetEntity="App\Entity\Journal\Journal", inversedBy="wosCategories")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $journal;

    /**
     * @var WosCategory
     * @ORM\ManyToOne(targetEntity="WosCategory")
     * @ORM\JoinColumn(name="wos_category_id", referencedColumnName="id", nullable=false)
     */
    protected $category;

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

    public function getCategory(): WosCategory
    {
        return $this->category;
    }

    public function setCategory(WosCategory $category): self
    {
        $this->category = $category;
        return $this;
    }



}