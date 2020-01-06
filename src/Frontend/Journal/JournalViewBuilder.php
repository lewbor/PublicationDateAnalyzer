<?php


namespace App\Frontend\Journal;


use App\Entity\Jcr\JournalJcr2Impact;
use App\Entity\Jcr\JournalJcrQuartile;
use App\Entity\Journal\Journal;
use App\Entity\Journal\JournalAnalytics;
use Doctrine\ORM\EntityManagerInterface;

class JournalViewBuilder
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildData(Journal $journal): array {
        $jcr2Impacts = $this->buildJcrImpacts($journal);
        $jcrQuartiles = $this->buildJcrQuartiles($journal);
        $wosTypes = $this->buildWosTypes($journal->getStat()->getWosPublicationTypes());

        $analyticsItems = $this->em->getRepository(JournalAnalytics::class)->findBy(['journal' => $journal]);
        usort($analyticsItems, function (JournalAnalytics $a, JournalAnalytics $b) {
            return $b->getOptions()['start'] - $a->getOptions()['start'];
        });

        $publicationSpeed = $this->publicationSpeed($journal->getStat()->getMedianPublicationTime());

        return  [
            'journal' => $journal,
            'stat' => $journal->getStat(),
            'jcr2Impacts' => $jcr2Impacts,
            'jcrQuartiles' => $jcrQuartiles,
            'wosTypes' => $wosTypes,
            'publicationSpeed' => $publicationSpeed,
            'analytics' => $analyticsItems
        ];

    }

    private function buildJcrImpacts(Journal $entity): array
    {
        /** @var  JournalJcr2Impact[] $rows */
        $rows = $this->em->getRepository(JournalJcr2Impact::class)->forJournal($entity);

        $impacts = [];
        foreach ($rows as $row) {
            $impacts[$row->getYear()] = $row->getValue();
        }
        return $impacts;
    }

    private function buildJcrQuartiles(Journal $entity): array
    {

        $rows = $this->em->getRepository(JournalJcrQuartile::class)->forJournal($entity, 2010);

        $quartilesPartialMap = [];
        foreach ($rows as $row) {
            $quartilesPartialMap[$row->getCategory()][$row->getYear()] = $row->getQuartile();
        }
        $categories = array_unique(array_map(function (JournalJcrQuartile $quartile) {
            return $quartile->getCategory();
        }, $rows));
        $years = array_unique(array_map(function (JournalJcrQuartile $quartile) {
            return $quartile->getYear();
        }, $rows));

        return [
            'categories' => $categories,
            'years' => $years,
            'quartiles' => $quartilesPartialMap
        ];
    }

    private function publicationSpeed(array $publicationTime): array
    {
        $speed = [];
        foreach($publicationTime as $year => $data) {
            $speed[$year] = $data['median'];
        }

        return $speed;
    }

    private function buildWosTypes(array $wosTypes): array
    {
        arsort($wosTypes);
        return $wosTypes;
    }
}