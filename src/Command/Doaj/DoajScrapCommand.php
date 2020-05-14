<?php


namespace App\Command\Doaj;


use App\Entity\Journal\Journal;
use App\Entity\Journal\JournalDoaj;
use App\Lib\QueueManager;
use App\Lib\Utils\IssnUtils;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoajScrapCommand extends Command
{
    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;
    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
    }

    protected function configure()
    {
        $this->setName('doaj.scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->queueManager->singleIterator(DoajQueueCommand::QUEUE_NAME) as $idx => $queueItem) {
            $journalId = $queueItem->getData()['id'];

            $this->processJournal($journalId);

            $this->queueManager->acknowledge($queueItem);
            $this->em->clear();
        }
        return 0;
    }

    private function processJournal(int $journalId): void
    {
        /** @var Journal $journal */
        $journal = $this->em->getRepository(Journal::class)
            ->find($journalId);
        if($journal === null) {
            $this->logger->error('Not found journal');
            return;
        }
        $issn = null;
        if(!empty($journal->getIssn())) {
            $issn = $journal->getIssn();
        } elseif(!empty($journal->getEissn())) {
            $issn = $journal->getEissn();
        }
        if($issn === null) {
            $this->logger->error('No issn found');
            return;
        }

        $url = sprintf('https://doaj.org/api/v1/search/journals/issn:%s',IssnUtils::formatIssnWithHyphen($issn));

        $client = new Client();
        $response = $client->get($url, [
            'exceptions' => false,
            'headers' => [
                "Accept" => "application/json"
            ]
        ]);
        if($response->getStatusCode() !== 200) {
            $this->logger->error(sprintf('Journal %d - response code is %d', $journal->getId(), $response->getStatusCode()));
            return;
        }

        $body = $response->getBody()->getContents();
        $result = \GuzzleHttp\json_decode($body, true);

        switch($result['total']) {
            case 0:
                $this->logger->info(sprintf('Journal %d - no data', $journal->getId()));
                return;
            case 1:
                $this->processData($result['results'][0], $journal);
                break;
            default:
                $this->logger->error(sprintf('Journal %d - %d results found', $journal->getId(), $result['total']));
                break;
        }

    }

    private function processData(array $data, Journal $journal): void
    {
        $existingDoaj = $this->em->getRepository(JournalDoaj::class)
            ->findOneBy(['doajId' => $data['id']]);
        if($existingDoaj !== null) {
            $this->logger->error(sprintf('Journal %d - doaj already exist', $journal->getId()));
            return;
        }

        $doaj = (new JournalDoaj())
            ->setJournal($journal)
            ->setDoajId($data['id'])
            ->setData($data['bibjson']);
        $this->em->persist($doaj);
        $this->em->flush();

        $this->logger->info(sprintf('Journal %d - saved data', $journal->getId()));
    }
}