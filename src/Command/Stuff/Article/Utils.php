<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\Journal\Journal;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;

class Utils
{

    public static function journalDomains(Journal $journal, EntityManagerInterface $em): string {
        $articleDomainCount = $em->createQueryBuilder()
            ->select('distinct domain.id', 'domain.name', 'COUNT(entity.id) AS entityCount')
            ->from(ArticleDatesOaAggregate::class, 'entity')
            ->join('entity.domain', 'domain')
            ->groupBy('domain.id')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getResult();
        $domainStr = implode(',', array_map(
            fn(array $articleDomain) => sprintf('%s (%d)', $articleDomain['name'], $articleDomain['entityCount']),
            $articleDomainCount
        ));
        return $domainStr;
    }

    public static function saveData(array $data): void {
        $writer = new CsvWriter('php://stdout');
        $writer->open();

        foreach($data as $row) {
            $writer->write($row);
        }
        $writer->close();
    }
}