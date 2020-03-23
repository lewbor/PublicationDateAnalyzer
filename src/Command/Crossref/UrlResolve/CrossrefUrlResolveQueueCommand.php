<?php


namespace App\Command\Crossref\UrlResolve;


use App\Parser\Crossref\CrossrefUrlResolveQueer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossrefUrlResolveQueueCommand extends Command
{
    protected CrossrefUrlResolveQueer $queer;

    public function __construct(CrossrefUrlResolveQueer $queer)
    {
        parent::__construct();
        $this->queer = $queer;
    }

    protected function configure()
    {
        $this->setName('crossref.url.queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queer->run();
        return 0;
    }
}