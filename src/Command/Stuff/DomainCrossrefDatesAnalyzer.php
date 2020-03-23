<?php


namespace App\Command\Stuff;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\ArticleUrlDomain;
use App\Lib\CsvWriter;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DomainCrossrefDatesAnalyzer extends Command
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('domain.crossref_dates');
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


        $queries = [
            'crossref_records' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.hasCrossrefRecord = 1'),
            'publisherRecords' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.hasPublisherRecord = 1'),
            'has_print' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.crossrefPublishedPrint IS NOT NULL'),
            'has_online' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.crossrefPublishedOnline IS NOT NULL')
        ];

        $articleModifiers = [
            'all' => function (QueryBuilder $qb) {
                $qb->andWhere("entity.year <= 2019");
            },
            '2000-2009' => function (QueryBuilder $qb) {
                $qb->andWhere("entity.year >= 2000 AND entity.year <= 2009");
            },
            '2010-2019' => function (QueryBuilder $qb) {
                $qb->andWhere("entity.year >= 2010 AND entity.year <= 2019");
            },
            '2018' => function (QueryBuilder $qb) {
                $qb->andWhere("entity.year = 2018");
            },
            '2019' => function (QueryBuilder $qb) {
                $qb->andWhere("entity.year = 2019");
            },
        ];

        $openAccessModifiers = [
            '' => function(QueryBuilder $qb){

            },
            '_oa' => function(QueryBuilder $qb){
                $qb->andWhere('entity.openAccess = 1');
            },
        ];

        foreach ($queries as $queryName => $qb) {
            foreach ($articleModifiers as $modifierName => $articleModifier) {
                foreach ($openAccessModifiers as $oaName => $oaModifier) {
                    $currentQb = clone $qb;
                    $articleModifier($currentQb);
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