<?php


namespace App\Lib\TaskRunner\Task\Exec;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ParallelExecTask
{
    /** @var ExecTask[]  */
    private $tasks = [];


    public function addTask(ExecTask $task)
    {
        $this->tasks[] = $task;
    }

    public function run(TaskLogger $logger)
    {
        /** @var Process[] $processes */
        $processes = [];

        foreach ($this->tasks as $task) {
            $processes[] = $task->buildProcess($task->getCmd());
        }

        foreach ($processes as $process) {
            $logger->logMessage(sprintf("<info>(parallel)$ %s\n</info>", $process->getCommandLine()));
            $callback = function ($type, $output) use ($logger) {
                $logger->logOutput($type, $output);
            };
            $process->start($callback);
        }

        while (true) {
            foreach ($processes as $k => $process) {
                try {
                    $process->checkTimeout();
                } catch (ProcessTimedOutException $e) {
                    // todo
                }
                if (!$process->isRunning()) {
                    unset($processes[$k]);
                }
            }
            if (empty($processes)) {
                break;
            }
            usleep(1000);
        }
    }

}