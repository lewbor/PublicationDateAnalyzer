<?php


namespace App\Command;


use App\Parser\WosDataParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WosDataParserCommand extends Command
{
    protected $dataParser;

    public function __construct(WosDataParser $dataParser)
    {
        parent::__construct();
        $this->dataParser = $dataParser;
    }

    protected function configure()
    {
        $this->setName('parse.wos');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dataParser->parse(__DIR__ . '/../../data/wos');
    }

}