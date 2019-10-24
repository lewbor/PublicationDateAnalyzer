<?php


namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article_publisher_data")
 */
class ArticlePublisherData
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Article
     * @ORM\OneToOne(targetEntity="\App\Entity\Article", inversedBy="publisherData")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $article;

    /**
     * @var array
     * @ORM\Column(name="publisher_data", type="json", nullable=false)
     */
    protected $data;

    /**
     * @var DateTime
     * @ORM\Column(name="publisher_received", type="date", nullable=true)
     */
    protected $publisherReceived;

    /**
     * @var DateTime
     * @ORM\Column(name="publisher_accepted", type="date", nullable=true)
     */
    protected $publisherAccepted;

    /**
     * @var DateTime
     * @ORM\Column(name="publisher_available_print", type="date", nullable=true)
     */
    protected $publisherAvailablePrint;

    /**
     * @var DateTime
     * @ORM\Column(name="publisher_available_online", type="date", nullable=true)
     */
    protected $publisherAvailableOnline;

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

    public function getPublisherReceived(): ?DateTime
    {
        return $this->publisherReceived;
    }

    public function setPublisherReceived(?DateTime $publisherReceived): self
    {
        $this->publisherReceived = $publisherReceived;
        return $this;
    }


    public function getPublisherAccepted(): ?DateTime
    {
        return $this->publisherAccepted;
    }


    public function setPublisherAccepted(?DateTime $publisherAccepted): self
    {
        $this->publisherAccepted = $publisherAccepted;
        return $this;
    }

    public function getPublisherAvailableOnline(): ?DateTime
    {
        return $this->publisherAvailableOnline;
    }

    public function setPublisherAvailableOnline(?DateTime $publisherAvailableOnline): self
    {
        $this->publisherAvailableOnline = $publisherAvailableOnline;
        return $this;
    }

    public function getPublisherAvailablePrint(): ?DateTime
    {
        return $this->publisherAvailablePrint;
    }

    public function setPublisherAvailablePrint(?DateTime $publisherAvailablePrint): self
    {
        $this->publisherAvailablePrint = $publisherAvailablePrint;
        return $this;
    }

}