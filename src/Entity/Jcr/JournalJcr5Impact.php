<?php


namespace App\Entity\Jcr;

use App\Entity\Journal\JournalImpactBase;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="journal_impact_jcr5", uniqueConstraints={
        @ORM\UniqueConstraint(name="search_idx", columns={"journal_id", "year"})
 * })
 */
class JournalJcr5Impact extends JournalImpactBase
{

}