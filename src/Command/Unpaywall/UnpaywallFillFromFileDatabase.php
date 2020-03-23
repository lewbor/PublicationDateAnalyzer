<?php


namespace App\Command\Unpaywall;


use App\Entity\Article;
use App\Entity\ArticleUnpaywallData;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallFillFromFileDatabase extends Command
{
    private const BATCH_SIZE = 10000;

    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;

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
        $this->setName('unpaywall.fill_database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $fileName = '/project/unpaywall_snapshot_2019-04-19T193256.jsonl.gz';

        foreach ($this->batchIterator($fileName, 0, self::BATCH_SIZE) as $idx => $records) {
            /** @var Article[] $articles */
            $articles = $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Article::class, 'entity')
                ->leftJoin('entity.unpaywallData', 'unpaywallData')
                ->andWhere('entity.doi IN (:doi)')
                ->andWhere('unpaywallData IS NULL')
                ->setParameter('doi', array_keys($records))
                ->getQuery()
                ->getResult();

            foreach ($articles as $article) {
                $unpaywallData = $records[$article->getDoi()];
                $dataEntity = (new ArticleUnpaywallData())
                    ->setArticle($article)
                    ->setData($unpaywallData)
                    ->setResponseCode(200)
                    ->setScrappedAt(new \DateTime())
                    ->setOpenAccess($unpaywallData['is_oa']);
                $this->em->persist($dataEntity);
            }
            $this->em->flush();
            $this->em->clear();
            $this->logger->info(sprintf('Processed %s records, updated %d articles',
                number_format(($idx + 1) * self::BATCH_SIZE),
                count($articles)));
        }

    }

    protected function batchIterator(string $fileName, int $recordsToSkip = 0, int $batchSize = 500): iterable
    {
        $zh = gzopen($fileName, 'r');
        if ($zh === false) {
            die("can't open: $php_errormsg");
        }

        $processedRecords = 0;
        $batch = [];
        while ($line = gzgets($zh)) {
            $processedRecords++;
            if ($processedRecords < $recordsToSkip) {
                if ($processedRecords % 5000 === 0) {
                    $this->logger->info(sprintf('Skipping %s records', number_format($processedRecords)));
                }
                continue;
            }
            $data = json_decode($line, true);

            if (!isset($data['doi'])) {
                $this->logger->error(sprintf('Doi is not set, %s', json_encode($data)));
                continue;
            }

            if (!isset($data['is_oa'])) {
                $this->logger->error(sprintf('is_oa is not set, %s', json_encode($data)));
            }

            if (strlen($data['doi']) > 700) {
                $this->logger->error(sprintf('Too long doi: %s', $data['doi']));
                continue;
            }

            $batch[$data['doi']] = $data;
            if (count($batch) >= $batchSize) {
                yield $batch;
                $batch = [];
            }
        }

        if (count($batch) !== 0) {
            yield $batch;
        }

        gzclose($zh);

    }
}