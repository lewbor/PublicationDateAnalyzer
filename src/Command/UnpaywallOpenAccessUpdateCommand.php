<?php


namespace App\Command;


use App\Parser\CrossrefDateUpdater;
use App\Parser\UnpaywallOpenAccessUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallOpenAccessUpdateCommand extends Command
{
    protected $updater;

    public function __construct(UnpaywallOpenAccessUpdater $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }

    protected function configure()
    {
        $this->setName('unpaywall.open_access_update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updater->run();
    }
}