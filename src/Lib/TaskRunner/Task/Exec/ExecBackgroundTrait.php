<?php


namespace App\Lib\TaskRunner\Task\Exec;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\TaskResult;

trait ExecBackgroundTrait
{
    use ExecTrait;

    public function executeCommandInBackground($cmd, TaskLogger $logger)
    {
        // Using exec to avoid creating a subprocess with sh -c
        $cmd = 'exec ' . $cmd;

        $logger->logMessage(sprintf("<info>(background)$ %s\n</info>", $cmd));

        $this->buildProcess($cmd);
        $this->process->setIdleTimeout(null);
        $this->process->start();
    }

    public function wait()
    {
        if ($this->process === null) {
            return new TaskResult(null);
        }

        $this->process->wait();

        $exitCode = $this->process->getExitCode();
        return new TaskResult($exitCode, $this->process->getOutput());
    }

    public function stop(TaskLogger $logger)
    {
        if ($this->process === null) {
            $logger->logMessage("<error>Trying to stop not-running task\n</error>");
            return null;
        }

        $pid = $this->process->getPid();
        $logger->logMessage(sprintf("<info>(stopping %d) %s\n</info>", $pid, $this->process->getCommandLine()));
        $this->process->getStatus();
        $this->process->stop();

        $exitCode = $this->process->getExitCode();
        return new TaskResult($exitCode, $this->process->getOutput());
    }

}