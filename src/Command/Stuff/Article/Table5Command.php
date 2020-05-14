<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\ArticleUrlDomain;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table5Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table5');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ArticleUrlDomain[] $domains */
        $domains = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(ArticleUrlDomain::class, 'entity')
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($domains as $domain) {
            $row = [
                'Domain' => $domain->getDomain()
            ];

            $row['Journals'] = $this->em->createQueryBuilder()
                ->select('COUNT(DISTINCT journal.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->join('entity.journal', 'journal')
                ->andWhere('entity.domain = :domain')
                ->setParameter('domain', $domain)
                ->getQuery()
                ->getSingleScalarResult();

            $row['Crossref'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.domain = :domain')
                ->andWhere('entity.hasCrossrefRecord = true')
                ->setParameter('domain', $domain)
                ->getQuery()
                ->getSingleScalarResult();

            $row['Publisher'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.domain = :domain')
                ->andWhere('entity.hasPublisherRecord = true')
                ->setParameter('domain', $domain)
                ->getQuery()
                ->getSingleScalarResult();
            $result[] = $row;
        }

        Utils::saveData($result);
    }
}