<?php


namespace App\Lib\TaskRunner\Task\Remote;


use Sciact\System\Component\FileSystem\Path;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\CommandArguments;
use App\Lib\TaskRunner\Task\Exec\ExecTask;
use App\Lib\TaskRunner\TaskException;

class QueueTransferTask
{
    const REMOTE_TO_LOCAL = 1;
    const LOCAL_TO_REMOTE = 2;

    use CommandArguments;

    private $hostname;
    private $user;
    private $serverDir;
    private $queueName;
    private $purgeQueue = true;
    private $direction;

    public function __construct(string $hostname, string $user, int $direction)
    {
        $this->hostname = $hostname;
        $this->user = $user;
        $this->direction = $direction;
    }

    public function run(TaskLogger $logger)
    {
        $remoteExecutable = Path::join($this->serverDir, 'bin/console');
        $arguments = $this->purgeQueue ? ' -p' : '';
        $sshOptions = $this->arguments;
        $hostSpec = $this->hostSpec();

        switch ($this->direction) {
            case self::REMOTE_TO_LOCAL:
                $cmd = "ssh {$sshOptions} {$hostSpec} '{$remoteExecutable} queue:transfer {$this->queueName}{$arguments}' | bin/console queue:load {$this->queueName}";
                break;
            case self::LOCAL_TO_REMOTE:
                $cmd = "bin/console queue:transfer {$this->queueName}{$arguments} | ssh {$sshOptions} {$hostSpec} '{$remoteExecutable} queue:load {$this->queueName}'";
                break;
            default:
                throw new TaskException("Invalid transfer direction");
        }


        (new ExecTask($cmd))
            ->run($logger);
    }

    protected function hostSpec()
    {
        $hostSpec = $this->hostname;
        if ($this->user) {
            $hostSpec = $this->user . '@' . $hostSpec;
        }
        return $hostSpec;
    }

    public function serverDir(string $serverDir)
    {
        $this->serverDir = $serverDir;
        return $this;
    }

    public function queueName(string $queueName)
    {
        $this->queueName = $queueName;
        return $this;
    }

    public function masterSocket(string $masterSocket)
    {
        $this->option('-S', $masterSocket);
        return $this;
    }

    public function purge(bool $purge)
    {
        $this->purgeQueue = $purge;
        return $this;
    }
}