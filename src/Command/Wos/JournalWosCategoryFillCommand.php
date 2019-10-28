<?php


namespace App\Command\Wos;


use App\Entity\Jcr\JournalJcrQuartile;
use App\Entity\Jcr\JournalJcrQuartileSource;
use App\Entity\Jcr\JournalWosCategory;
use App\Entity\Jcr\WosCategory;
use App\Entity\Journal\Journal;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\Utils\IssnUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalWosCategoryFillCommand extends Command
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
        $this->setName('wos.category.journal_fill');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->clearData();

        foreach ($this->journalIterator() as $idx => $journal) {
            $this->processJournal($journal);

            if ($idx % 10 === 0) {
                $this->logger->info(sprintf("Processed %d journals", $idx));
            }
        }
    }

    private function clearData()
    {
        $deleteCount = $this->em->createQueryBuilder()
            ->delete(JournalWosCategory::class, 'entity')
            ->getQuery()
            ->getResult();
        $this->logger->info(sprintf("Deleted %d from %s", $deleteCount, JournalWosCategory::class));
    }

    private function journalIterator(): iterable
    {
        return DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
        );
    }

    private function processJournal(Journal $journal): void
    {

        if (empty($journal->getIssn()) && empty($journal->getEissn())) {
            return;
        }

        $qb = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(JournalJcrQuartileSource::class, 'entity');
        if (!empty($journal->getIssn())) {
            $qb->orWhere('entity.issn = :issn')
                ->setParameter('issn', IssnUtils::formatIssnWithHyphen($journal->getIssn()));
        }
        if (!empty($journal->getEissn())) {
            $qb->orWhere('entity.issn = :eissn')
                ->setParameter('eissn', IssnUtils::formatIssnWithHyphen($journal->getEissn()));
        }

        $result = $qb->getQuery()->getResult();
        if (count($result) === 0) {
            return;
        }


        $journalCategories = [];
        /** @var JournalJcrQuartileSource $item */
        foreach ($result as $item) {
            $journalCategories[$item->getCategory()] = true;
        }
        $journalCategories = array_filter(array_keys($journalCategories));

        foreach($journalCategories as $category) {
            /** @var WosCategory $categoryEntity */
            $categoryEntity = $this->em->getRepository(WosCategory::class)
                ->findOneBy(['name' => $category]);
            if($categoryEntity === null) {
                $this->logger->error(sprintf('No category %s', $category));
                continue;
            }
            $journalCategoryEntity = (new JournalWosCategory())
                ->setJournal($journal)
                ->setCategory($categoryEntity);
            $this->em->persist($journalCategoryEntity);
            $this->em->flush();
        }
    }
}