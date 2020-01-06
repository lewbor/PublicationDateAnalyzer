<?php


namespace App\Entity\Jcr;


use App\Entity\Journal\JournalImpactBase;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\App\Repository\JournalJcr2ImpactRepository")
 * @ORM\Table(name="journal_impact_jcr2", uniqueConstraints={
        @ORM\UniqueConstraint(name="search_idx", columns={"journal_id", "year"})
 * })
 */
class JournalJcr2Impact extends JournalImpactBase
{

}