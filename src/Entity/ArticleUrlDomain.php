<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="article_url_domain")
 */
class ArticleUrlDomain
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="domain", type="string", nullable=false, unique=true)
     */
    protected string $domain = '';

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        $this->reverseDomain = $this->reverseDomain($domain);
        return $this;
    }

    private function reverseDomain(string $domain): string
    {
        return implode('.', array_reverse(explode('.', $domain)));
    }


}