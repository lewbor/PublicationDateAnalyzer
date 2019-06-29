<?php


namespace App\Command;


use App\Parser\CrossrefDateUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefDateUpdateCommand extends Command
{

    protected $updater;

    public function __construct(CrossrefDateUpdater $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }

    protected function configure()
    {
        $this->setName('crossref.date_update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updater->run();
    }
}