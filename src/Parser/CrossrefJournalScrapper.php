<?php


namespace App\Parser;


use App\Entity\Journal\Journal;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class CrossrefJournalScrapper
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function run()
    {
        /** @var Journal $journal */
        foreach ($this->journalIterator() as $journal) {
            $this->processJournal($journal);
            $this->em->clear();
            $this->logger->info(sprintf('Processed journal id=%d', $journal->getId()));
        }
    }

    private function journalIterator()
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }

    private function processJournal(Journal $journal)
    {
        $issn = null;
        if (!empty($journal->getIssn())) {
            $issn = $journal->getIssn();
        } elseif (!empty($journal->getEissn())) {
            $issn = $journal->getEissn();
        } else {
            return;
        }

        $url = sprintf('https://api.crossref.org/journals/%s', $issn);

        $client = new Client();
        $response = $client->get($url, ['exceptions' => false]);
        $body = $response->getBody()->getContents();
        $result = \GuzzleHttp\json_decode($body, true);

        $journal->setCrossrefData($result['message']);
        $this->em->persist($journal);
        $this->em->flush();
    }
}