<?php


namespace App\Entity\Journal;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal_doaj")
 */
class JournalDoaj
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity=Journal::class, inversedBy="doaj")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected ?Journal $journal = null;

    /**
     * @ORM\Column(name="scraped_at", type="datetime", nullable=false)
     */
    protected \DateTime $scrapedAd;

    /**
     * @ORM\Column(name="doaj_id", type="string", nullable=false, unique=true)
     */
    protected string $doajId = '';

    /**
     * @ORM\Column(name="data", type="json", nullable=false)
     */
    protected array $data = [];

    public function __construct()
    {
        $this->scrapedAd = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getJournal(): ?Journal
    {
        return $this->journal;
    }

    public function setJournal(?Journal $journal): self
    {
        $this->journal = $journal;
        return $this;
    }

    public function getDoajId(): string
    {
        return $this->doajId;
    }

    public function setDoajId(string $doajId): self
    {
        $this->doajId = $doajId;
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

    public function getScrapedAd(): \DateTime
    {
        return $this->scrapedAd;
    }

    public function setScrapedAd(\DateTime $scrapedAd): self
    {
        $this->scrapedAd = $scrapedAd;
        return $this;
    }


}