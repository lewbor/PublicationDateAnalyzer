<?php


namespace App\Entity\Journal;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal_stat")
 */
class JournalStat
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Journal
     * @ORM\OneToOne(targetEntity="App\Entity\Journal\Journal", inversedBy="stat")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $journal;

    /**
     * @var string
     * @ORM\Column(name="publisher", type="string", nullable=false)
     */
    protected $publisher;

    /**
     * @var int
     * @ORM\Column(name="articles_count", type="integer", nullable=false)
     */
    protected $articlesCount;

    /**
     * @var int
     * @ORM\Column(name="science_articles_count", type="integer", nullable=false)
     */
    protected $scienceArticlesCount;

    /**
     * @var int
     * @ORM\Column(name="article_min_year", type="integer", nullable=true)
     */
    protected $articleMinYear;

    /**
     * @var int
     * @ORM\Column(name="article_max_year", type="integer", nullable=true)
     */
    protected $articleMaxYear;

    /**
     * @var int
     * @ORM\Column(name="wos_articles_count", type="integer", nullable=false)
     */
    protected $wosArticlesCount;

    /**
     * @var array
     * @ORM\Column(name="article_years", type="json", nullable=false)
     */
    protected $articleYears;

    /**
     * @var array
     * @ORM\Column(name="median_publication_time", type="json", nullable=false)
     */
    protected $medianPublicationTime;

    /**
     * @var array
     * @ORM\Column(name="wos_publication_types", type="json", nullable=false)
     */
    protected $wosPublicationTypes;

    public function __construct()
    {
        $this->articleYears = [];
        $this->wosPublicationTypes = [];
        $this->medianPublicationTime = [];
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

    public function getPublisher(): string
    {
        return $this->publisher;
    }

    public function setPublisher(string $publisher): self
    {
        $this->publisher = $publisher;
        return $this;
    }

    public function getArticlesCount(): int
    {
        return $this->articlesCount;
    }

    public function setArticlesCount(int $articlesCount): self
    {
        $this->articlesCount = $articlesCount;
        return $this;
    }

    public function getArticleMinYear(): ?int
    {
        return $this->articleMinYear;
    }

    public function setArticleMinYear(?int $articleMinYear): self
    {
        $this->articleMinYear = $articleMinYear;
        return $this;
    }

    public function getArticleMaxYear(): ?int
    {
        return $this->articleMaxYear;
    }

    public function setArticleMaxYear(?int $articleMaxYear): self
    {
        $this->articleMaxYear = $articleMaxYear;
        return $this;
    }

    public function getWosArticlesCount(): int
    {
        return $this->wosArticlesCount;
    }

    public function setWosArticlesCount(int $wosArticlesCount): self
    {
        $this->wosArticlesCount = $wosArticlesCount;
        return $this;
    }

    public function getArticleYears(): array
    {
        return $this->articleYears;
    }

    public function setArticleYears(array $articleYears): void
    {
        $this->articleYears = $articleYears;
    }

    public function getWosPublicationTypes(): array
    {
        return $this->wosPublicationTypes;
    }

    public function setWosPublicationTypes(array $wosPublicationTypes): self
    {
        $this->wosPublicationTypes = $wosPublicationTypes;
        return $this;
    }

    public function getScienceArticlesCount(): int
    {
        return $this->scienceArticlesCount;
    }

    public function setScienceArticlesCount(int $scienceArticlesCount): self
    {
        $this->scienceArticlesCount = $scienceArticlesCount;
        return $this;
    }

    public function getMedianPublicationTime(): array
    {
        return $this->medianPublicationTime;
    }

    public function setMedianPublicationTime(array $medianPublicationTime): self
    {
        $this->medianPublicationTime = $medianPublicationTime;
        return $this;
    }


}