<?php


namespace App\Command;


use App\Parser\CrossrefJournalScrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefJournalScrapCommand extends Command
{
    protected CrossrefJournalScrapper $scrapper;

    public function __construct(CrossrefJournalScrapper $scrapper)
    {
        parent::__construct();
        $this->scrapper = $scrapper;
    }

    protected function configure()
    {
        $this->setName('crossref.journal_scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scrapper->run();
        return 0;
    }
}