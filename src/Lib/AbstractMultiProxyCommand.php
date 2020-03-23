<?php


namespace App\Lib;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractMultiProxyCommand extends Command
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected abstract function commandName(): string;

    protected function configure()
    {
        $this
            ->addOption('procCount', null, InputOption::VALUE_REQUIRED, '', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $procCount = (int)$input->getOption('procCount');
        if ($procCount < 0) {
            throw new \Exception(sprintf('Invalid procCount value: %s', $input->getOption('procCount')));
        }


        /** @var Process[] $processes */
        $processes = [];

        $this->runProcesses($procCount, $input, $output, $processes);
        $this->logger->info(sprintf('Will run %d processes', $procCount));


        while (count($processes) > 0) {
            foreach ($processes as $i => $runningProcess) {
                if (!$runningProcess->isRunning()) {
                    unset($processes[$i]);
                }

                sleep(1);
            }
        }

        return 0;
    }

    private function runProcesses(int $procCount, InputInterface $input, OutputInterface $output, array &$processes): void
    {
        foreach (range(1, $procCount) as $procNumber) {
            $env = array_merge($_ENV, [AbstractMultiProcessCommand::ENV_PROCESS_NUMBER => count($processes) + 1,]);
            $process = $this->createProcess($input, $env);
            $process->start(function ($type, $buffer) use ($output) {
                echo $buffer;
            });
            $processes[] = $process;
        }
    }

    protected function createProcess(InputInterface $input, array $env): Process
    {
        return new Process(['bin/console', $this->commandName()], null, $env);
    }
}