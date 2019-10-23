<?php


namespace App\Lib\TaskRunner\Task\Remote;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\CommandArguments;
use App\Lib\TaskRunner\Task\Exec\ExecBackgroundTask;
use App\Lib\TaskRunner\Task\Exec\ExecTask;
use App\Lib\TaskRunner\TaskException;

class SshMasterTask
{
    use CommandArguments;

    protected $hostname;
    protected $user;
    protected $masterSocket;

    private $backgroundTask = null;

    public function __construct(string $hostname, string $user)
    {
        $this->hostname = $hostname;
        $this->user = $user;
    }


    public function run(TaskLogger $logger)
    {
        if (empty($this->masterSocket)) {
            throw new \LogicException("Master socket is not set");
        }

        $this->createMasterConnection($logger);
        if (!$this->checkMasterConnection($logger)) {
            throw new TaskException("Error establish master ssh connection");
        }
    }

    public function stop(TaskLogger $logger)
    {
        $hostSpec = $this->hostSpec();
        $cmd = "ssh -O \"exit\" -S{$this->masterSocket} {$hostSpec}";

        (new ExecTask($cmd))
            ->throwOnFail(false)
            ->run($logger);
    }

    private function createMasterConnection(TaskLogger $logger)
    {
        $this->option('-f')
            ->option('-N')
            ->option('-T')
            ->option('-M')
            ->option('-S', $this->masterSocket);
        $sshOptions = $this->arguments;
        $hostSpec = $this->hostSpec();
        $cmd = "ssh {$sshOptions} {$hostSpec}";
        $this->backgroundTask = new ExecBackgroundTask($cmd);
        $this->backgroundTask->run($logger);
    }

    private function checkMasterConnection(TaskLogger $logger)
    {
        $hostSpec = $this->hostSpec();
        $cmd = "ssh -O \"check\" -S{$this->masterSocket} {$hostSpec}";

        for ($i = 0; $i < 10; $i++) {
            $result = (new ExecTask($cmd))
                ->throwOnFail(false)
                ->run($logger);
            if ($result->exitCode === 0) {
                return true;
            }
            sleep(1);
        }

        return false;
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
        $this->masterSocket = $masterSocket;
        return $this;
    }

    public function ignoreKnownHosts() {
        $this->option('-o', 'UserKnownHostsFile=/dev/null');
        return $this;
    }

    public function noStrictChecking() {
        $this->option('-o', 'StrictHostKeyChecking=no');
        return $this;
    }
}