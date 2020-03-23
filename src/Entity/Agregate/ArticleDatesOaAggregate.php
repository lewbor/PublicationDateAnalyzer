<?php


namespace App\Entity\Agregate;

use App\Entity\Article;
use App\Entity\ArticleUrlDomain;
use App\Entity\Journal\Journal;
use DateTime;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity()
 * @ORM\Table(name="article_dates_oa_agregate")
 */
class ArticleDatesOaAggregate
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity=Article::class)
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected ?Article $article = null;

    /**
     * @ORM\ManyToOne(targetEntity=Journal::class)
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected ?Journal $journal = null;

    /**
     * @ORM\ManyToOne(targetEntity=ArticleUrlDomain::class)
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", nullable=true)
     */
    protected ?ArticleUrlDomain $domain = null;

    /**
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    protected int $year;

    /**
     * @ORM\Column(name="has_crossref_record", type="boolean", nullable=false)
     */
    protected bool $hasCrossrefRecord;

    /**
     * @ORM\Column(name="has_unpaywall_record", type="boolean", nullable=false)
     */
    protected bool $hasUnpaywallRecord;

    /**
     * @ORM\Column(name="has_publisher_record", type="boolean", nullable=false)
     */
    protected bool $hasPublisherRecord;

    /**
     * @ORM\Column(name="open_access", type="boolean", nullable=true)
     */
    protected ?bool $openAccess = null;

    /**
     * @ORM\Column(name="crossref_published_print", type="date", nullable=true)
     */
    protected ?DateTime $crossrefPublishedPrint= null;

    /**
     * @ORM\Column(name="crossref_published_online", type="date", nullable=true)
     */
    protected ?DateTime $crossrefPublishedOnline= null;

    /**
     * @ORM\Column(name="publisher_received", type="date", nullable=true)
     */
    protected ?DateTime $publisherReceived= null;

    /**
     * @ORM\Column(name="publisher_accepted", type="date", nullable=true)
     */
    protected ?DateTime $publisherAccepted= null;

    /**
     * @ORM\Column(name="publisher_available_print", type="date", nullable=true)
     */
    protected ?DateTime $publisherAvailablePrint= null;

    /**
     * @ORM\Column(name="publisher_available_online", type="date", nullable=true)
     */
    protected ?DateTime $publisherAvailableOnline= null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Article|null
     */
    public function getArticle(): ?Article
    {
        return $this->article;
    }

    /**
     * @param Article|null $article
     */
    public function setArticle(?Article $article): void
    {
        $this->article = $article;
    }

    /**
     * @return Journal|null
     */
    public function getJournal(): ?Journal
    {
        return $this->journal;
    }

    /**
     * @param Journal|null $journal
     */
    public function setJournal(?Journal $journal): void
    {
        $this->journal = $journal;
    }

    /**
     * @return ArticleUrlDomain|null
     */
    public function getDomain(): ?ArticleUrlDomain
    {
        return $this->domain;
    }

    /**
     * @param ArticleUrlDomain|null $domain
     */
    public function setDomain(?ArticleUrlDomain $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return bool|null
     */
    public function getOpenAccess(): ?bool
    {
        return $this->openAccess;
    }

    /**
     * @param bool|null $openAccess
     */
    public function setOpenAccess(?bool $openAccess): void
    {
        $this->openAccess = $openAccess;
    }

    /**
     * @return DateTime|null
     */
    public function getCrossrefPublishedPrint(): ?DateTime
    {
        return $this->crossrefPublishedPrint;
    }

    /**
     * @param DateTime|null $crossrefPublishedPrint
     */
    public function setCrossrefPublishedPrint(?DateTime $crossrefPublishedPrint): void
    {
        $this->crossrefPublishedPrint = $crossrefPublishedPrint;
    }

    /**
     * @return DateTime|null
     */
    public function getCrossrefPublishedOnline(): ?DateTime
    {
        return $this->crossrefPublishedOnline;
    }

    /**
     * @param DateTime|null $crossrefPublishedOnline
     */
    public function setCrossrefPublishedOnline(?DateTime $crossrefPublishedOnline): void
    {
        $this->crossrefPublishedOnline = $crossrefPublishedOnline;
    }

    /**
     * @return DateTime|null
     */
    public function getPublisherReceived(): ?DateTime
    {
        return $this->publisherReceived;
    }

    /**
     * @param DateTime|null $publisherReceived
     */
    public function setPublisherReceived(?DateTime $publisherReceived): void
    {
        $this->publisherReceived = $publisherReceived;
    }

    /**
     * @return DateTime|null
     */
    public function getPublisherAccepted(): ?DateTime
    {
        return $this->publisherAccepted;
    }

    /**
     * @param DateTime|null $publisherAccepted
     */
    public function setPublisherAccepted(?DateTime $publisherAccepted): void
    {
        $this->publisherAccepted = $publisherAccepted;
    }

    /**
     * @return DateTime|null
     */
    public function getPublisherAvailablePrint(): ?DateTime
    {
        return $this->publisherAvailablePrint;
    }

    /**
     * @param DateTime|null $publisherAvailablePrint
     */
    public function setPublisherAvailablePrint(?DateTime $publisherAvailablePrint): void
    {
        $this->publisherAvailablePrint = $publisherAvailablePrint;
    }

    /**
     * @return DateTime|null
     */
    public function getPublisherAvailableOnline(): ?DateTime
    {
        return $this->publisherAvailableOnline;
    }

    /**
     * @param DateTime|null $publisherAvailableOnline
     */
    public function setPublisherAvailableOnline(?DateTime $publisherAvailableOnline): void
    {
        $this->publisherAvailableOnline = $publisherAvailableOnline;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    /**
     * @return bool
     */
    public function isHasCrossrefRecord(): bool
    {
        return $this->hasCrossrefRecord;
    }

    /**
     * @param bool $hasCrossrefRecord
     */
    public function setHasCrossrefRecord(bool $hasCrossrefRecord): void
    {
        $this->hasCrossrefRecord = $hasCrossrefRecord;
    }

    /**
     * @return bool
     */
    public function isHasUnpaywallRecord(): bool
    {
        return $this->hasUnpaywallRecord;
    }

    /**
     * @param bool $hasUnpaywallRecord
     */
    public function setHasUnpaywallRecord(bool $hasUnpaywallRecord): void
    {
        $this->hasUnpaywallRecord = $hasUnpaywallRecord;
    }

    /**
     * @return bool
     */
    public function isHasPublisherRecord(): bool
    {
        return $this->hasPublisherRecord;
    }

    /**
     * @param bool $hasPublisherRecord
     */
    public function setHasPublisherRecord(bool $hasPublisherRecord): void
    {
        $this->hasPublisherRecord = $hasPublisherRecord;
    }


}