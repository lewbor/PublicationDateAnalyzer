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

    public function __construct(CrossrefScrapper $queer)
    {
        parent::__construct();
        $this->scraper = $queer;
    }

    protected function configure()
    {
        $this->setName('crossref.scrap')
            ->addArgument('journalIds', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $journalIds = $input->getArgument('journalIds');
        if(empty($journalIds)) {
            throw new \Exception('Journals ids is empty');
        }
        $ids = explode(',', $journalIds);
        $this->scraper->scrap($ids);
    }
}