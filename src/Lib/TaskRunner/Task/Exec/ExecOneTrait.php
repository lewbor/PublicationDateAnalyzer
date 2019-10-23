<?php


namespace App\Lib\TaskRunner\Task\Exec;


use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\Exec\ExecTrait;
use App\Lib\TaskRunner\TaskException;
use App\Lib\TaskRunner\TaskResult;

trait ExecOneTrait
{
    use ExecTrait;

    /** @var  bool */
    private $throwOnFail = true;

    public function executeCommand($cmd, TaskLogger $logger)
    {
        $this->buildProcess($cmd);

        $logger->logMessage(sprintf("<info>$ %s</info>\n", $cmd));

        $outputCallback = function ($type, $output) use ($logger) {
            $logger->logOutput($type, $output);
        };

        $this->process->run($outputCallback);

        $exitCode = $this->process->getExitCode();
        if ($exitCode != 0 && $this->throwOnFail) {
            throw new TaskException(sprintf("%s, exitCode %d", $cmd, $exitCode));
        }

        $result = new TaskResult($exitCode, $this->process->getOutput());
        $this->process = null;
        return $result;
    }

    public function throwOnFail(bool $throwOnFail)
    {
        $this->throwOnFail = $throwOnFail;
        return $this;
    }


}