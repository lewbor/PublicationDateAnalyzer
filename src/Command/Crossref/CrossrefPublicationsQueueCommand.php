<?php


namespace App\Command\Crossref;


use App\Parser\Crossref\CrossrefPublicationsQueer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefPublicationsQueueCommand extends Command
{
    protected $queer;

    public function __construct(CrossrefPublicationsQueer $queer)
    {
        parent::__construct();
        $this->queer = $queer;
    }

    protected function configure()
    {
        $this->setName('crossref.publications.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queer->run();
    }
}