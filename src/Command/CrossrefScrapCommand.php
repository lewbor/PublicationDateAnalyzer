<?php


namespace App\Command;


use App\Parser\CrossrefScrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefScrapCommand extends Command
{

    protected $scraper;

    public function __construct(CrossrefScrapper $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    protected function configure()
    {
        $this->setName('crossref.scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scraper->scrap();
    }
}