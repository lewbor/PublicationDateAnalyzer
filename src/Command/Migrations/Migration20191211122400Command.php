<?php


namespace App\Command\Migrations;


use App\Entity\ArticlePublisherData;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Migration20191211122400Command extends Command
{
    const BATCH_SIZE = 500;

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
        $this
            ->setName('migration.20191211122400')
            ->setDescription('fill success field in article publisher data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $itemCount = (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(ArticlePublisherData::class, 'entity')
            ->getQuery()
            ->getSingleScalarResult();

        $iterator = DoctrineIterator::batchIdIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(ArticlePublisherData::class, 'entity'),
            'entity', self::BATCH_SIZE
        );

        /** @var ArticlePublisherData[] $items */
        foreach($iterator as $iteratorIdx => $items) {
            foreach($items as $item) {
                $data = $item->getData();
                if(isset($data['success']) && $data['success'] === false) {
                    $item->setScrapResult(ArticlePublisherData::SCRAP_RESULT_ERROR);
                    $this->em->persist($item);
                }
            }
            $this->em->flush();
            $this->logger->info(sprintf('Processed %s of %s',
               number_format(($iteratorIdx + 1) * self::BATCH_SIZE),
                number_format($itemCount)
            ));
        }
    }
}