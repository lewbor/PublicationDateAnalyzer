<?php


namespace App\Command\Publisher;


use App\Parser\Publisher\PublisherQueer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublisherQueueCommand extends Command
{
    protected PublisherQueer $queer;

    public function __construct(PublisherQueer $scraper)
    {
        parent::__construct();
        $this->queer = $scraper;
    }

    protected function configure()
    {
        $this
            ->setName('publisher.queue')
            ->addArgument('domain', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->queer->run($input->getArgument('domain'));
        return 0;
    }
}