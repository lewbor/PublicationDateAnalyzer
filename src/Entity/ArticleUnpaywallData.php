<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article_unpaywall_data")
 */
class ArticleUnpaywallData
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Article
     * @ORM\OneToOne(targetEntity="\App\Entity\Article", inversedBy="unpaywallData")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $article;

    /**
     * @var ?\DateTime
     * @ORM\Column(name="scrapped_at", type="datetime", nullable=true)
     */
    protected $scrappedAt;

    /**
     * @var int
     * @ORM\Column(name="response_code", type="integer", nullable=false)
     */
    protected $responseCode;

    /**
     * @var array
     * @ORM\Column(name="publisher_data", type="json", nullable=true)
     */
    protected $data;

    /**
     * @var bool
     * @ORM\Column(name="open_access", type="boolean", nullable=true)
     */
    protected $openAccess;

    public function getId()
    {
        return $this->id;
    }

    public function getArticle(): Article
    {
        return $this->article;
    }

    public function setArticle(Article $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
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

    public function getScrappedAt(): ?\DateTime
    {
        return $this->scrappedAt;
    }

    public function setScrappedAt(?\DateTime $scrappedAt): self
    {
        $this->scrappedAt = $scrappedAt;
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


}