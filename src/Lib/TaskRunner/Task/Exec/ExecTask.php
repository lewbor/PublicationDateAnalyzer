<?php


namespace App\Lib\TaskRunner\Task\Exec;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\Exec\ExecOneTrait;

class ExecTask
{
    use ExecOneTrait;

    /** @var  string */
    private $cmd;

    public function __construct($cmd)
    {
        $this->cmd = $cmd;
    }

    public function run(TaskLogger $logger)
    {
        return $this->executeCommand($this->cmd, $logger);
    }

    public function getCmd(): string
    {
        return $this->cmd;
    }



}