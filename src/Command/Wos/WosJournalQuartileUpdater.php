<?php


namespace App\Command\Wos;


use App\Entity\Jcr\JournalJcrQuartile;
use App\Entity\Jcr\JournalJcrQuartileSource;
use App\Entity\Journal\Journal;
use App\Lib\Iterator\DoctrineIterator;
use App\Lib\Utils\IssnUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WosJournalQuartileUpdater extends Command
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('wos.journal.quartile_update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->clearQuartiles();

        foreach ($this->journalIterator() as $idx => $journal) {
            $this->processJournal($journal);
            $this->em->clear();

            if ($idx % 10 === 0) {
                $this->logger->info(sprintf("Processed %d journals", $idx));
            }
        }
    }

    private function clearQuartiles(): void
    {
        $deleteCount = $this->em->createQueryBuilder()
            ->delete(JournalJcrQuartile::class, 'entity')
            ->getQuery()
            ->getResult();
        $this->logger->info(sprintf("Deleted %d from %s", $deleteCount, JournalJcrQuartile::class));
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

        /** @var JournalJcrQuartileSource $item */
        foreach ($result as $item) {
            $journalItem = (new JournalJcrQuartile())
                ->setJournal($journal)
                ->setYear($item->getYear())
                ->setCategory($item->getCategory())
                ->setQuartile($item->getQuartile());
            $this->em->persist($journalItem);
        }
        $this->em->flush();
    }
}