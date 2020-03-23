<?php


namespace App\Command\Crossref\Publications;


use App\Parser\Crossref\CrossrefPublicationsScrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefPublicationsScrapCommand extends Command
{
    const CMD_NAME = 'crossref.publications.scrap';

    protected CrossrefPublicationsScrapper $scraper;

    public function __construct(CrossrefPublicationsScrapper $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    protected function configure()
    {
        $this->setName(self::CMD_NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scraper->scrap();
        return 0;
    }
}