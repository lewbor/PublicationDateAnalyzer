<?php


namespace App\Lib\TaskRunner\Task\Remote;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\CommandArguments;
use App\Lib\TaskRunner\Task\Exec\ExecOneTrait;

class SshTask
{
    use ExecOneTrait;
    use CommandArguments;

    protected $hostname;
    protected $user;
    protected $remoteDir;
    protected $stopOnFail = true;
    protected $exec = [];


    public function __construct(string $hostname, string $user)
    {
        $this->hostname = $hostname;
        $this->user = $user;
    }

    public function run(TaskLogger $logger)
    {
        $command = $this->getCommand();
        return $this->executeCommand($command, $logger);
    }

    public function getCommand()
    {
        $commands = $this->exec;

        if (!empty($this->remoteDir)) {
            array_unshift($commands, sprintf('cd "%s"', $this->remoteDir));
        }
        $command = implode($this->stopOnFail ? ' && ' : ' ; ', $commands);

        return $this->sshCommand($command);
    }

    protected function sshCommand($command)
    {
        $sshOptions = $this->arguments;
        $hostSpec = $this->hostSpec();

        return sprintf("ssh{$sshOptions} {$hostSpec} '{$command}'");
    }

    protected function hostSpec() {
        $hostSpec = $this->hostname;
        if ($this->user) {
            $hostSpec = $this->user . '@' . $hostSpec;
        }
        return $hostSpec;
    }

    public function hostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    public function user($user)
    {
        $this->user = $user;
        return $this;
    }

    public function stopOnFail($stopOnFail = true)
    {
        $this->stopOnFail = $stopOnFail;
        return $this;
    }

    public function remoteDir($remoteDir)
    {
        $this->remoteDir = $remoteDir;
        return $this;
    }

    public function exec($command)
    {
        $this->exec[] = $command;
        return $this;
    }

    public function disableTty()
    {
        $this->option('-T');
        return $this;
    }

    public function masterSocket(string $masterSocket) {
        $this->option('-S', $masterSocket);
        return $this;
    }

    public function background() {
        $this->option('-f');
        return $this;
    }

    public function doNotExecute() {
        $this->option('-N');
        return $this;
    }

    public function proxy(int $port) {
        $this->option('-D', $port);
        return $this;
    }
}