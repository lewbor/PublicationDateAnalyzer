<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article")
 */
class Article
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="doi", type="string", unique=true, nullable=true)
     */
    protected $doi;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $name;

    /**
     * @var integer
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    protected $year;

    /**
     * @var Journal
     * @ORM\ManyToOne(targetEntity="Journal")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $journal;

    /**
     * @var ArticleCrossrefData
     * @ORM\OneToOne(targetEntity="\App\Entity\ArticleCrossrefData", mappedBy="article")
     */
    protected $crossrefData;

    /**
     * @var ArticlePublisherData
     * @ORM\OneToOne(targetEntity="\App\Entity\ArticlePublisherData", mappedBy="article")
     */
    protected $publisherData;

    /**
     * @var ArticleWebOfScienceData
     * @ORM\OneToOne(targetEntity="\App\Entity\ArticleWebOfScienceData", mappedBy="article")
     */
    protected $webOfScienceData;

    /**
     * @var bool
     * @ORM\Column(name="open_access", type="boolean", nullable=true)
     */
    protected $openAccess;


    public function getId()
    {
        return $this->id;
    }

    public function getDoi(): string
    {
        return $this->doi;
    }

    public function setDoi(string $doi): self
    {
        $this->doi = $doi;
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

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
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

    public function getCrossrefData(): ?ArticleCrossrefData
    {
        return $this->crossrefData;
    }

    public function setCrossrefData(?ArticleCrossrefData $crossrefData): self
    {
        $this->crossrefData = $crossrefData;
        return $this;
    }

    public function getPublisherData(): ?ArticlePublisherData
    {
        return $this->publisherData;
    }


    public function setPublisherData(?ArticlePublisherData $publisherData): self
    {
        $this->publisherData = $publisherData;
        return $this;
    }

    public function getWebOfScienceData(): ?ArticleWebOfScienceData
    {
        return $this->webOfScienceData;
    }

    public function setWebOfScienceData(?ArticleWebOfScienceData $webOfScienceData): self
    {
        $this->webOfScienceData = $webOfScienceData;
        return $this;
    }



    public function isOpenAccess(): ?bool
    {
        return $this->openAccess;
    }

    public function setOpenAccess(?bool $openAccess): self
    {
        $this->openAccess = $openAccess;
        return $this;
    }



}