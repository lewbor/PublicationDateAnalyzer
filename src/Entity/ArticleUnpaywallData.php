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
}