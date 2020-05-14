<?php


namespace App\Command\Stuff\Article;


use App\Entity\Agregate\ArticleDatesOaAggregate;
use App\Entity\ArticleUrlDomain;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Table6Command extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('article.table6');
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

            $row['Total'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.domain = :domain')
                ->setParameter('domain', $domain)
                ->getQuery()
                ->getSingleScalarResult();

            $row['Print'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.domain = :domain')
                ->andWhere('entity.crossrefPublishedPrint IS NOT NULL')
                ->setParameter('domain', $domain)
                ->getQuery()
                ->getSingleScalarResult();
            $row['Online'] = $this->em->createQueryBuilder()
                ->select('COUNT(entity.id)')
                ->from(ArticleDatesOaAggregate::class, 'entity')
                ->andWhere('entity.domain = :domain')
                ->andWhere('entity.crossrefPublishedOnline IS NOT NULL')
                ->setParameter('domain', $domain)
                ->getQuery()
                ->getSingleScalarResult();

            $result[] = $row;
        }

        Utils::saveData($result);
    }
}