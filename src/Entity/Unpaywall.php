<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="unpaywall")
 */
class Unpaywall
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="doi", type="string", length=700, unique=true, nullable=false)
     */
    protected $doi;

    /**
     * @var bool
     * @ORM\Column(name="open_access", type="boolean", nullable=true)
     */
    protected $openAccess;

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