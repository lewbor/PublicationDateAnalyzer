<?php


namespace App\Command\Migrations;


use App\Entity\ArticleUrlDomain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Migration20102003102000Command extends Command
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('migration.20102003102000');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ArticleUrlDomain[] $entities */
        $entities = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(ArticleUrlDomain::class, 'entity')
            ->getQuery()
            ->getResult();

        foreach($entities as $entity) {
            $entity->setDomain($entity->getDomain());
            $this->em->persist($entity);
            $this->em->flush();
        }
    }
}