<?php


namespace App\Command\Publisher;


use App\Parser\Scrapper\PublisherQueer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublisherQueueCommand extends Command
{
    protected $queer;

    public function __construct(PublisherQueer $queer)
    {
        parent::__construct();
        $this->queer = $queer;
    }

    protected function configure()
    {
        $this->setName('publisher.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queer->run();
    }
}