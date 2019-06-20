<?php


namespace App\Entity;

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
     * @var string
     * @ORM\Column(name="authors", type="text", nullable=true)
     */
    protected $authors;

    /**
     * @var Journal
     * @ORM\ManyToOne(targetEntity="Journal")
     * @ORM\JoinColumn(name="journal_id", referencedColumnName="id", nullable=false)
     **/
    protected $journal;

    /**
     * @var string
     * @ORM\Column(name="wos_id", type="string", nullable=false, unique=true)
     */
    protected $wosId;

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

    public function getAuthors(): string
    {
        return $this->authors;
    }

    public function setAuthors(string $authors): self
    {
        $this->authors = $authors;
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

    public function getWosId(): ?string
    {
        return $this->wosId;
    }

    public function setWosId(?string $wosId): self
    {
        $this->wosId = $wosId;
        return $this;
    }



}