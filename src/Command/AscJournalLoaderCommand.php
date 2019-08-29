<?php


namespace App\Command;


use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class AscJournalLoaderCommand extends Command
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
        $this->setName('journals.load.acs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        for ($page = 0; $page <= 3; $page++) {
            $this->processPage($page);
        }
    }

    private function normalizeIssn(string $issn): string
    {
        foreach (['ISSN:', 'EISSN:'] as $prefix) {
            if (strpos($issn, $prefix) === 0) {
                $issn = trim(substr($issn, strlen($prefix)));
                $issn = str_replace('-', '', $issn);
            }
        }
        return $issn;
    }

    private function processPage(int $page): void
    {
        $client = new Client();
        $jar = new CookieJar();

        $response = $client->request('GET', sprintf('https://pubs.acs.org/action/showPublications?pubType=journal&pageSize=20&startPage=%d', $page), [
            RequestOptions::ALLOW_REDIRECTS => true,
            RequestOptions::COOKIES => $jar,
            RequestOptions::HEADERS => [
                'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36'
            ]

        ]);
        $body = $response->getBody()->getContents();
        $crawler = new Crawler($body);

        $crawler->filter('ul.titles-results li')->each(function (Crawler $item) {
            $journalType = (string)$item->filter('div.item__body span.meta__type')->text();
            if ($journalType !== 'Journal') {
                return;
            }
            $journal = new Journal();

            $journalTitle = $item->filter('.meta__title')->text();
            $journal->setName(trim($journalTitle));

            $issnNode = $item->filter('.meta__info .meta__issns');
            if ($issnNode->count() > 0) {
                $issn = trim($issnNode->text());
                if (!empty($issn)) {
                    $issn = $this->normalizeIssn($issn);
                    $journal->setIssn($issn);
                }
            }

            $eissnNode = $item->filter('.meta__info .meta__eissn');
            if ($eissnNode->count() > 0) {
                $eissn = trim($eissnNode->text());
                if (!empty($eissn)) {
                    $eissn = $this->normalizeIssn($eissn);
                    $journal->setEissn($eissn);
                }
            }

            $this->processJournal($journal);
        });
    }


    private function processJournal(Journal $journal): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity')
            ->orWhere('entity.name = :name')
            ->setParameter('name', $journal->getName());

        if (!empty($journal->getIssn())) {
            $qb->orWhere('entity.issn = :issn')
                ->setParameter('issn', $journal->getIssn());
        }
        if (!empty($journal->getEissn())) {
            $qb->orWhere('entity.eissn = :eissn')
                ->setParameter('eissn', $journal->getEissn());
        }

        $journals = $qb->getQuery()->getResult();
        switch (count($journals)) {
            case 0:
                $this->em->persist($journal);
                $this->em->flush();
                $this->logger->info(sprintf('Inserted journal %s', $journal->getName()));
                break;
            case 1:
                $this->logger->info(sprintf('Journal %s exist', $journal->getName()));
                break;
            default:
                $this->logger->info(sprintf('To many journals exist: %s', $journal->getIssn()));
                break;

        }

    }

}