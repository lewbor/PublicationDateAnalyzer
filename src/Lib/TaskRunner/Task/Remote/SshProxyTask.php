<?php


namespace App\Lib\TaskRunner\Task\Remote;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\CommandArguments;
use App\Lib\TaskRunner\Task\Exec\ExecBackgroundTrait;
use App\Lib\TaskRunner\TaskException;
use App\Lib\TaskRunner\Utils;

class SshProxyTask
{
    use ExecBackgroundTrait;
    use CommandArguments;

    protected $hostname;
    protected $user;
    protected $port;

    public function __construct(string $hostname, string $user)
    {
        $this->hostname = $hostname;
        $this->user = $user;
    }

    public function run(TaskLogger $logger)
    {
        $this->startConnection($logger);
        if (!Utils::testConnection('localhost', $this->port, 100)) {
            throw new TaskException(sprintf('Error establish connection to ssh proxy on localhost:%d', $this->port));
        }
    }

    private function startConnection(TaskLogger $logger)
    {
        $sshOptions = "-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -N -T -D{$this->port} " . $this->arguments;
        $hostSpec = $this->hostSpec();

        $cmd = "ssh ${sshOptions} ${hostSpec}";
        $this->executeCommandInBackground($cmd, $logger);
    }

    protected function hostSpec()
    {
        $hostSpec = $this->hostname;
        if ($this->user) {
            $hostSpec = $this->user . '@' . $hostSpec;
        }
        return $hostSpec;
    }

    public function masterSocket(string $masterSocket)
    {
        $this->option('-S', $masterSocket);
        return $this;
    }

    public function listenPort(int $port) {
        $this->port = $port;
        return $this;
    }


}