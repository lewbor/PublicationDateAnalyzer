<?php


namespace App\Command\Publisher;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PublisherMultiProcessScrapCommand extends Command
{

    protected function configure()
    {
        $this->setName('publisher.multi_process_scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processes = [];

        foreach (range(1, 5) as $procNumber) {
            $process = new Process(['bin/console', 'publisher.scrap', '-vv']);
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