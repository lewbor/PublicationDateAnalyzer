<?php


namespace App\Lib\TaskRunner;


use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\Exec\ExecTask;
use App\Lib\TaskRunner\Task\FileSystemTasks;
use App\Lib\TaskRunner\Task\Exec\ParallelExecTask;
use App\Lib\TaskRunner\Task\Remote\SshMasterTask;
use App\Lib\TaskRunner\Task\Remote\SshTask;

trait BaseTasksTrait
{

    private function taskExec($cmd)
    {
        return new ExecTask($cmd);
    }

    private function taskFileSystem(TaskLogger $logger) {
        return new FileSystemTasks($logger);
    }

    private function taskSsh($hostname, $user) {
        return new SshTask($hostname, $user);
    }

    private function taskMasterSsh($hostname, $user){
        return new SshMasterTask($hostname, $user);
    }
}