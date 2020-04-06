<?php


namespace App\Command\Stuff;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\ArticleUrlDomain;
use App\Lib\CsvWriter;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DomainDatesAnalyzer extends Command
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('domain.dates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ArticleUrlDomain[] $domains */
        $domains = DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(ArticleUrlDomain::class, 'entity')
        );

        $writer = new CsvWriter('php://stdout');
        $writer->open();

        foreach ($domains as $domain) {
            $journalData = $this->domainData($domain);
            $row = array_merge([
                'id' => $domain->getId(),
                'name' => $domain->getDomain(),
            ], $journalData);
            $writer->write($row);
        }

        $writer->close();
    }

    private function domainData(ArticleUrlDomain $domain): array
    {
        $result = [];
        $result['min_year'] = (int)$this->em->createQueryBuilder()
            ->select('MIN(entity.year)')
            ->from(ArticleDatesOaAggregate::class, 'entity')
            ->andWhere('entity.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleScalarResult();
        $result['max_year'] = (int)$this->em->createQueryBuilder()
            ->select('MAX(entity.year)')
            ->from(ArticleDatesOaAggregate::class, 'entity')
            ->andWhere('entity.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleScalarResult();
        $result['journals'] = (int)$this->em->createQueryBuilder()
            ->select('COUNT(distinct journal.id)')
            ->from(ArticleDatesOaAggregate::class, 'entity')
            ->join('entity.journal', 'journal')
            ->andWhere('entity.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleScalarResult();

        foreach (Queries::$queries as $queryName => $qbBuilder) {
            foreach (Queries::$yearModifiers as $modifierName => $yearModifier) {
                foreach (Queries::$openAccessModifiers as $oaName => $oaModifier) {
                    $currentQb = $qbBuilder($this->em);
                    $yearModifier($currentQb);
                    $oaModifier($currentQb);
                    $currentQb
                        ->andWhere('entity.domain = :domain')
                        ->setParameter('domain', $domain);

                    $recordsCount = (int)$currentQb
                        ->getQuery()
                        ->getSingleScalarResult();
                    $result[$queryName . '_' . $modifierName . $oaName] = $recordsCount;
                }
            }
        }


        return $result;
    }
}