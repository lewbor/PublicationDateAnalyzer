<?php


namespace App\Command\Publisher;


use App\Parser\Publisher\PublisherScrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublisherScrapCommand  extends Command
{
    const CMD_NAME = 'publisher.scrap';

    protected LoggerInterface $logger;
    protected PublisherScrapper $scraper;

    public function __construct(
        LoggerInterface $logger,
        PublisherScrapper $scraper)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->scraper = $scraper;
    }

    protected function configure()
    {
        $this
            ->setName(self::CMD_NAME)
            ->addArgument('scraper', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->scraper->run($input->getArgument('scraper'));
        return 0;
    }
}