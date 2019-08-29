<?php


namespace App\Command\Publisher;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PublisherMultiProcessScrapCommand extends Command
{

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('publisher.multi_process_scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (range(1, 10) as $procNumber) {
            $process = new Process(['bin/console', PublisherScrapCommand::CMD_NAME], null, $_ENV);
            $process->start(function ($type, $buffer) use ($output) {
                echo $buffer;
            });
            $processes[] = $process;
        }

        while (count($processes) > 0) {
            foreach ($processes as $i => $runningProcess) {
                if (!$runningProcess->isRunning()) {
                    unset($processes[$i]);
                }

                sleep(1);
            }
        }
    }
}