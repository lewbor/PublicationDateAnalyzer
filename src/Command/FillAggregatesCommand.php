<?php


namespace App\Command;


use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FillAggregatesCommand extends Command
{
    protected EntityManagerInterface $em;
    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        parent::__construct(null);
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('update_agregates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statements = [
            'truncate table article_dates_oa_agregate',
            'insert into article_dates_oa_agregate(article_id, journal_id, year, has_crossref_record, has_publisher_record, has_unpaywall_record) 
select a.id,a.journal_id,a.year,0,0,0 from article a',

            'update article_dates_oa_agregate agregate left join article_url au on agregate.article_id = au.article_id set agregate.domain_id = au.domain_id',

            'update article_dates_oa_agregate agregate left join article_unpaywall_data unpaywall on agregate.article_id = unpaywall.article_id set agregate.open_access = unpaywall.open_access',

            'update article_dates_oa_agregate agregate left join article_crossref_data acd on agregate.article_id = acd.article_id 
set agregate.crossref_published_print = acd.published_print, agregate.crossref_published_online=acd.published_online',

            'update article_dates_oa_agregate agregate left join article_publisher_data publisher 
on agregate.article_id = publisher.article_id 
set agregate.publisher_accepted = publisher.publisher_accepted,
    agregate.publisher_received = publisher.publisher_received,
    agregate.publisher_available_print = publisher.publisher_available_print,
    agregate.publisher_available_online = publisher.publisher_available_online',

            'update article_dates_oa_agregate agregate set has_crossref_record = exists(
        select crossref.id from article_crossref_data crossref where crossref.article_id = agregate.article_id)',

            'update article_dates_oa_agregate agregate set has_publisher_record = exists(
        select publisher.id from article_publisher_data publisher where publisher.article_id = agregate.article_id)',

            'update article_dates_oa_agregate agregate set has_unpaywall_record = exists(
        select unpaywall.id from article_unpaywall_data unpaywall where unpaywall.article_id = agregate.article_id)'
        ];

        $conn = $this->em->getConnection();
        foreach ($statements as $statement) {
            $statement = str_replace("\n", '', $statement);
            $this->logger->info(sprintf("Start execute %s", $statement));
            $startTime = microtime(true);
            $insertedRows = $conn->executeUpdate($statement);
            $duration = microtime(true) - $startTime;
            $this->logger->info(sprintf('End execute, %d records, duration %s', $insertedRows, number_format($duration)));
        }
    }

}