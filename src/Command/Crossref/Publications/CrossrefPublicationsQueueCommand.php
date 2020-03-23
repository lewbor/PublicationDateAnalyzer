<?php


namespace App\Command\Crossref\Publications;


use App\Parser\Crossref\CrossrefPublicationsQueer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefPublicationsQueueCommand extends Command
{
    protected CrossrefPublicationsQueer $queer;

    public function __construct(CrossrefPublicationsQueer $queer)
    {
        parent::__construct();
        $this->queer = $queer;
    }

    protected function configure()
    {
        $this
            ->setName('crossref.publications.queue')
            ->setDescription('Queued journals to scrap articles from crossref');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queer->run();
        return 0;
    }
}