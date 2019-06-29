<?php


namespace App\Command\Publisher;


use App\Parser\Scrapper\PublisherScrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublisherScrapCommand  extends Command
{
    protected $scraper;

    public function __construct(PublisherScrapper $queer)
    {
        parent::__construct();
        $this->scraper = $queer;
    }

    protected function configure()
    {
        $this->setName('publisher.scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scraper->run();
    }
}