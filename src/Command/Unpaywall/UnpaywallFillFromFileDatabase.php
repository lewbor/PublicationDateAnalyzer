<?php


namespace App\Command\Unpaywall;


use App\Entity\Article;
use App\Entity\ArticleUnpaywallData;
use App\Entity\Unpaywall;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallFillFromFileDatabase extends Command
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
        $this->setName('unpaywall.fill_database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $fileName = '/project/unpaywall_snapshot_2019-04-19T193256.jsonl.gz';

        $zh = gzopen($fileName, 'r');
        if ($zh === false) {
            die("can't open: $php_errormsg");
        }

        $recordsToSkip =  56808000;
        $processedRecords = 0;
        while ($line = gzgets($zh)) {
            try {
                if($processedRecords < $recordsToSkip) {
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

                $article = $this->em->getRepository(Article::class)
                    ->findOneBy(['doi' => $data['doi']]);
                if ($article === null) {
                    continue;
                }
                if ($article->getUnpaywallData() !== null) {
                    continue;
                }

                $dataEntity = (new ArticleUnpaywallData())
                    ->setArticle($article)
                    ->setData($data)
                    ->setOpenAccess($data['is_oa']);
                $this->em->persist($dataEntity);
                $this->em->flush();
            } finally {
                $processedRecords++;

                if ($processedRecords % 100 === 0) {
                    $this->em->clear();
                }

                if ($processedRecords % 1000 === 0) {
                    $this->logger->info(sprintf("%s records, memory %s kb", number_format($processedRecords),
                        memory_get_usage() / 1024));
                }
            }
        }

        gzclose($zh);

    }

}