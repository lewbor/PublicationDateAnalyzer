<?php


namespace App\Command\Unpaywall;


use App\Parser\Scrapper\PublisherQueer;
use App\Parser\Unpaywall\UnpaywallQueer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpaywallQueueCommand extends Command
{
    protected $queer;

    public function __construct(UnpaywallQueer $scraper)
    {
        parent::__construct();
        $this->queer = $scraper;
    }

    protected function configure()
    {
        $this->setName('unpaywall.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queer->run();
    }
}