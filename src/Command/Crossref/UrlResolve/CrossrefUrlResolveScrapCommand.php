<?php


namespace App\Command\Crossref\UrlResolve;


use App\Parser\Crossref\CrossrefUrlScrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefUrlResolveScrapCommand extends Command
{
    const CMD_NAME = 'crossref.url.scrap';

    protected CrossrefUrlScrapper $scraper;

    public function __construct(CrossrefUrlScrapper $scraper)
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