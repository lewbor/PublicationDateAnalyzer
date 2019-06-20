<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal")
 */
class Journal
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=true)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, unique=true, nullable=true)
     */
    protected $issn;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, unique=true, nullable=true)
     */
    protected $eissn;

    public function getId()
    {
        return $this->id;
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

    public function getIssn(): string
    {
        return $this->issn;
    }

    public function setIssn(?string $issn): self
    {
        $this->issn = $issn;
        return $this;
    }

    public function getEissn(): string
    {
        return $this->eissn;
    }

    public function setEissn(?string $eissn): self
    {
        $this->eissn = $eissn;
        return $this;
    }



}