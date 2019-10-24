<?php


namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article_web_of_science_data")
 */
class ArticleWebOfScienceData
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Article
     * @ORM\OneToOne(targetEntity="\App\Entity\Article", inversedBy="webOfScienceData")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $article;

    /**
     * @var array
     * @ORM\Column(name="web_of_science_data", type="json", nullable=false)
     */
    protected $data;

    /**
     * @var string
     * @ORM\Column(name="wos_id", type="string", unique=true)
     */
    protected $wosId;

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

    public function getWosId(): string
    {
        return $this->wosId;
    }

    public function setWosId(string $wosId): self
    {
        $this->wosId = $wosId;
        return $this;
    }



}