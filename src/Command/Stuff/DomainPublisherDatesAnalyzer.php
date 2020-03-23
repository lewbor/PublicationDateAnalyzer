<?php


namespace App\Command\Stuff;


use App\Entity\ArticlePublisherData;
use App\Entity\ArticleUrl;
use App\Entity\ArticleUrlDomain;
use App\Lib\CsvWriter;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DomainPublisherDatesAnalyzer extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('domain.publisher_dates');
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
        $queries = [
            'total' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity'),
            'accepted' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherAccepted IS NOT NULL'),
            'received' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherReceived IS NOT NULL'),
            'print' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherAvailablePrint IS NOT NULL'),
            'online' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherAvailableOnline IS NOT NULL'),
            'received_accepted' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherReceived IS NOT NULL')
                ->andWhere('entity.publisherAccepted IS NOT NULL'),
            'received_published' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherReceived IS NOT NULL')
                ->andWhere('(entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL)'),
            'accepted_published' => $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticlePublisherData::class, 'entity')
                ->andWhere('entity.publisherAccepted IS NOT NULL')
                ->andWhere('(entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL)'),
        ];

        $modifiers = [
            'all' => function (QueryBuilder $qb): QueryBuilder {
                return clone $qb;
            },
            '2000-2009' => function (QueryBuilder $qb): QueryBuilder {
                $newQb = clone $qb;
                $newQb->andWhere('article.year >= 2000 AND article.year <= 2009');
                return $newQb;
            },
            '2010-2019' => function (QueryBuilder $qb): QueryBuilder {
                $newQb = clone $qb;
                $newQb->andWhere('article.year >= 2010 AND article.year <= 2019');
                return $newQb;
            },
            '2018' => function (QueryBuilder $qb): QueryBuilder {
                $newQb = clone $qb;
                $newQb->andWhere('article.year = 2018');
                return $newQb;
            },
            '2019' => function (QueryBuilder $qb): QueryBuilder {
                $newQb = clone $qb;
                $newQb->andWhere('article.year = 2019');
                return $newQb;
            },
        ];

        $result = [];

        foreach ($queries as $queryName => $qb) {
            foreach ($modifiers as $modifierName => $modifier) {
                /** @var QueryBuilder $modifiedQb */
                $modifiedQb = $modifier($qb);
                $modifiedQb->andWhere(sprintf('entity.article IN (%s)',
                    $this->em->createQueryBuilder()
                        ->select('article_url.article')
                        ->from(ArticleUrl::class, 'article_url')
                        ->andWhere('article_url.domain = :domain')
                        ->getDQL()))
                    ->setParameter('domain', $domain);
                $recordsCount = (int)$modifiedQb
                    ->getQuery()
                    ->getSingleScalarResult();
                $result[$queryName . '_' . $modifierName] = $recordsCount;
            }
        }


        return $result;
    }
}