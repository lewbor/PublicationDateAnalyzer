<?php


namespace App\Command\Unpaywall;


use App\Parser\Scrapper\PublisherScrapper;
use App\Parser\UnpaywallScraper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallScrapCommand  extends Command
{
    protected $scraper;

    public function __construct(UnpaywallScraper $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    protected function configure()
    {
        $this->setName('unpaywall.scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scraper->run();
    }
}