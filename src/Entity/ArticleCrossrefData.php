<?php


namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article_crossref_data")
 */
class ArticleCrossrefData
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Article
     * @ORM\OneToOne(targetEntity="\App\Entity\Article", inversedBy="crossrefData")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $article;

    /**
     * @var array
     * @ORM\Column(name="crossref_data", type="json", nullable=false)
     */
    protected $crossrefData;

    /**
     * @var DateTime
     * @ORM\Column(name="published_print", type="date", nullable=true)
     */
    protected $publishedPrint;

    /**
     * @var DateTime
     * @ORM\Column(name="published_online", type="date", nullable=true)
     */
    protected $publishedOnline;

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

    public function getCrossrefData(): array
    {
        return $this->crossrefData;
    }

    public function setCrossrefData(array $crossrefData): self
    {
        $this->crossrefData = $crossrefData;
        return $this;
    }

    public function getPublishedPrint(): ?DateTime
    {
        return $this->publishedPrint;
    }

    public function setPublishedPrint(?DateTime $publishedPrint): self
    {
        $this->publishedPrint = $publishedPrint;
        return $this;
    }

    public function getPublishedOnline(): ?DateTime
    {
        return $this->publishedOnline;
    }

    public function setPublishedOnline(?DateTime $publishedOnline): self
    {
        $this->publishedOnline = $publishedOnline;
        return $this;
    }


}