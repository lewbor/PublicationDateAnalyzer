<?php


namespace App\Lib;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractMultiProcessCommand extends Command
{
    public const ENV_PROCESS_NUMBER = 'PROCESS_NUMBER';

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected abstract function commandName(): string;
    protected abstract function defaultProcessCount(): int;

    protected function configure()
    {
        $this->addOption('numproc', 'p', InputOption::VALUE_OPTIONAL,
            'Number of parallel processes to run', $this->defaultProcessCount());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processes = [];
        $procCount = (int) $input->getOption('numproc');
        if($procCount <= 0) {
            throw new \Exception(sprintf('Invalid numproc option: %s', $input->getOption('numproc')));
        }

        $this->logger->info(sprintf('Will run %d processes', $procCount));
        foreach (range(1, $procCount) as $procNumber) {
            $env = array_merge($_ENV, [
                self::ENV_PROCESS_NUMBER => $procNumber
            ]);
            $process = new Process(['bin/console', $this->commandName()], null, $env);
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