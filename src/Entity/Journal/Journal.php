<?php


namespace App\Entity\Journal;

use App\Entity\Jcr\JournalWosCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal")
 */
class Journal
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=true)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, unique=true, nullable=true)
     */
    protected $issn;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, unique=true, nullable=true)
     */
    protected $eissn;

    /**
     * @var JournalWosCategory[]|Collection
     * @ORM\OneToMany(targetEntity="\App\Entity\Jcr\JournalWosCategory", mappedBy="journal")
     */
    protected $wosCategories;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    protected $crossrefData;

    /**
     * @var JournalStat
     * @ORM\OneToOne(targetEntity="App\Entity\Journal\JournalStat", mappedBy="journal")
     */
    protected $stat;

    public function __construct()
    {
        $this->wosCategories = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
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

    public function getIssn(): ?string
    {
        return $this->issn;
    }

    public function setIssn(?string $issn): self
    {
        $this->issn = $issn;
        return $this;
    }

    public function getEissn(): ?string
    {
        return $this->eissn;
    }

    public function setEissn(?string $eissn): self
    {
        $this->eissn = $eissn;
        return $this;
    }


    public function getCrossrefData(): ?array
    {
        return $this->crossrefData;
    }

    public function setCrossrefData(?array $crossrefData): self
    {
        $this->crossrefData = $crossrefData;
        return $this;
    }

    public function getStat(): ?JournalStat
    {
        return $this->stat;
    }

    public function setStat(?JournalStat $stat): self
    {
        $this->stat = $stat;
        return $this;
    }

    public function getWosCategories()
    {
        return $this->wosCategories;
    }
}