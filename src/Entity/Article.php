<?php


namespace App\Entity;

use App\Entity\Journal\Journal;
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
    protected ?int $id = null;

    /**
     * @ORM\Column(name="doi", type="string", unique=true, nullable=true)
     */
    protected ?string $doi = null;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    protected ?int $year = null;

    /**
     * @ORM\ManyToOne(targetEntity=Journal::class)
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected ?Journal $journal = null;

    /**
     * @ORM\OneToOne(targetEntity=ArticleCrossrefData::class, mappedBy="article")
     */
    protected ?ArticleCrossrefData $crossrefData = null;

    /**
     * @ORM\OneToOne(targetEntity=ArticlePublisherData::class, mappedBy="article")
     */
    protected ?ArticlePublisherData $publisherData = null;

    /**
     * @ORM\OneToOne(targetEntity=ArticleWebOfScienceData::class, mappedBy="article")
     */
    protected ?ArticleWebOfScienceData $webOfScienceData = null;

    /**
     * @ORM\OneToOne(targetEntity=ArticleUnpaywallData::class, mappedBy="article")
     */
    protected ?ArticleUnpaywallData $unpaywallData = null;

    /**
     * @ORM\OneToOne(targetEntity=ArticleUrl::class, mappedBy="article")
     */
    protected ?ArticleUrl $url = null;


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

    public function getUnpaywallData(): ?ArticleUnpaywallData
    {
        return $this->unpaywallData;
    }

    public function setUnpaywallData(?ArticleUnpaywallData $unpaywallData): self
    {
        $this->unpaywallData = $unpaywallData;
        return $this;
    }

    public function getUrl(): ?ArticleUrl
    {
        return $this->url;
    }

    public function setUrl(?ArticleUrl $url): self
    {
        $this->url = $url;
        return $this;
    }



}