<?php


namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article_url",  indexes={
 *   @ORM\Index(name="domain_year", columns={"domain_id", "year"})
 * })
 */
class ArticleUrl
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Article
     * @ORM\OneToOne(targetEntity="\App\Entity\Article", inversedBy="url")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $article;

    /**
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    protected int $year;

    /**
     * @var ?\DateTime
     * @ORM\Column(name="scrapped_at", type="datetime", nullable=false)
     */
    protected $scrappedAt;

    /**
     * @var int
     * @ORM\Column(name="response_code", type="integer", nullable=false)
     */
    protected $responseCode;

    /**
     * @var string
     * @ORM\Column(name="url", type="string", length=2000, nullable=true)
     */
    protected $url;

    /**
     * @var ?ArticleUrlDomain
     * @ORM\ManyToOne(targetEntity="ArticleUrlDomain")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", nullable=true)
     */
    protected $domain;

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }


    public function getId()
    {
        return $this->id;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(Article $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function getScrappedAt()
    {
        return $this->scrappedAt;
    }

    public function setScrappedAt(\DateTime $scrappedAt): self
    {
        $this->scrappedAt = $scrappedAt;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseCode(?int $responseCode): self
    {
        $this->responseCode = $responseCode;
        return $this;
    }


    public function getDomain(): ?ArticleUrlDomain
    {
        return $this->domain;
    }

    public function setDomain(?ArticleUrlDomain $domain): self
    {
        $this->domain = $domain;
        return $this;
    }




}