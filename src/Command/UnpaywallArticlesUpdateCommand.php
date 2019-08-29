<?php


namespace App\Command;


use App\Entity\Article;
use App\Entity\Unpaywall;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallArticlesUpdateCommand extends Command
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('unpaywall.update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rowsUpdated = $this->em->createQueryBuilder()
            ->update(Article::class, 'entity')
            ->set('entity.openAccess', sprintf('(%s)', $this->em->createQueryBuilder()
                ->select('unpaywall.openAccess')
                ->from(Unpaywall::class, 'unpaywall')
                ->andWhere('unpaywall.doi = entity.doi')))
            ->getQuery()
            ->execute();
        $this->logger->info(sprintf('Updated %d rows', $rowsUpdated));
    }
}