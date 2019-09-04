<?php


namespace App\Lib;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractMultiProcessCommand extends Command
{

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected abstract function commandName(): string;
    protected abstract function processCount(): int;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processes = [];

        foreach (range(1, $this->processCount()) as $procNumber) {
            $process = new Process(['bin/console', $this->commandName()], null, $_ENV);
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