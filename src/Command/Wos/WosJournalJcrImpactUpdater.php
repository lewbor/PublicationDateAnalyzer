<?php


namespace App\Command\Wos;


use App\Entity\Journal\Journal;
use App\Entity\Jcr\JournalJcr2Impact;
use App\Entity\Jcr\JournalJcr5Impact;
use App\Entity\Jcr\JournalJcrImpactSource;
use App\Lib\Utils\IssnUtils;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WosJournalJcrImpactUpdater extends Command
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
        $this->setName('wos.journal.impacts_update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->clearImpacts();

        foreach ($this->journalIterator() as $idx => $journal) {
            $this->processJournal($journal);
            $this->em->clear();

            if ($idx % 10 === 0) {
                $this->logger->info(sprintf("Processed %d journals", $idx));
            }
        }
    }

    private function clearImpacts(): void
    {
        foreach ([JournalJcr2Impact::class, JournalJcr5Impact::class] as $impactClass) {
            $deleteCount = $this->em->createQueryBuilder()
                ->delete($impactClass, 'entity')
                ->getQuery()
                ->execute();
            $this->logger->info(sprintf("Deleted %d from %s impacts", $deleteCount, $impactClass));
        }
    }

    private function journalIterator(): iterable
    {
        yield from DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
        );
    }

    private function processJournal(Journal $journal): void
    {
        if (empty($journal->getIssn()) && empty($journal->getEissn())) {
            $this->logger->error(sprintf('Journal %d - no issn and eissn', $journal->getId()));
            return;
        }

        $qb = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(JournalJcrImpactSource::class, 'entity');
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

        /** @var JournalJcrImpactSource $item */
        foreach($result as $item) {
            if(!empty($item->getImpactFactor())) {
                $journalItem = (new JournalJcr2Impact())
                    ->setJournal($journal)
                    ->setYear($item->getYear())
                    ->setValue($item->getImpactFactor());
                $this->em->persist($journalItem);
            }
            if(!empty($item->getImpactFactor5())) {
                $journalItem = (new JournalJcr5Impact())
                    ->setJournal($journal)
                    ->setYear($item->getYear())
                    ->setValue($item->getImpactFactor5());
                $this->em->persist($journalItem);
            }
        }
        $this->em->flush();
    }

}