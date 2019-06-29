<?php


namespace App\Entity;

use DateTime;
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
     * @var array
     * @ORM\Column(name="crossref_data", type="json", nullable=true)
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

    /**
     * @var array
     * @ORM\Column(name="publisher_data", type="json", nullable=true)
     */
    protected $publisherData;

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

    public function getCrossrefData(): ?array
    {
        return $this->crossrefData;
    }

    public function setCrossrefData(?array $crossrefData): self
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

    public function getPublisherData(): ?array
    {
        return $this->publisherData;
    }


    public function setPublisherData(?array $publisherData): self
    {
        $this->publisherData = $publisherData;
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