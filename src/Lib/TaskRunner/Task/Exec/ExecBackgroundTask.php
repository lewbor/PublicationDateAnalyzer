<?php


namespace App\Lib\TaskRunner\Task\Exec;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\Exec\ExecTrait;
use App\Lib\TaskRunner\TaskResult;

class ExecBackgroundTask
{
    use ExecBackgroundTrait;

    private $cmd;

    public function __construct(string $cmd)
    {
        $this->cmd = $cmd;
    }

    public function run(TaskLogger $logger)
    {
        $this->executeCommandInBackground($this->cmd, $logger);
    }

}