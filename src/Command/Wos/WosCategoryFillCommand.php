<?php


namespace App\Command\Wos;


use App\Entity\Jcr\JournalJcrQuartileSource;
use App\Entity\Jcr\WosCategory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WosCategoryFillCommand extends Command
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
        $this->setName('wos.category.fill');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $quarticles = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(JournalJcrQuartileSource::class, 'entity')
            ->getQuery()
            ->getResult();

        $categories = [];
        /** @var JournalJcrQuartileSource $quarticle */
        foreach ($quarticles as $quarticle) {
            $categories[$quarticle->getCategory()] = true;
        }
        $categories = array_filter(array_keys($categories));

        $this->em->clear();
        foreach ($categories as $category) {
            $categoryEntity = $this->em->getRepository(WosCategory::class)
                ->findOneBy(['name' => $category]);
            if ($categoryEntity !== null) {
                continue;
            }

            $categoryEntity = (new WosCategory())
                ->setName($category);
            $this->em->persist($categoryEntity);
            $this->em->flush();
        }
    }
}