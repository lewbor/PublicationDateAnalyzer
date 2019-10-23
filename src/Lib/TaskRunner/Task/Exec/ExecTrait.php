<?php


namespace App\Lib\TaskRunner\Task\Exec;


use Symfony\Component\Process\Process;

trait ExecTrait
{
    private $timeout = null;
    private $pty = null;
    private $env = null;

    /** @var Process */
    private $process = null;

    public function buildProcess($cmd)
    {
        if ($this->process !== null) {
            throw new \Exception(sprintf("Task is already running"));
        }

        $this->process = Process::fromShellCommandline($cmd);
        $this->process->setTimeout($this->timeout);
        if ($this->pty !== null) {
            $this->process->setPty($this->pty);
        }
        if ($this->env !== null) {
            $this->process->setEnv($this->env);
        }
        return $this->process;
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setPty(bool $pty)
    {
        $this->pty = $pty;
        return $this;
    }

    public function setEnv(array $env)
    {
        $this->env = $env;
        return $this;
    }

    public function addEnv($key, $value) {
        if($this->env === null) {
            $this->env = [];
        }
        $this->env[$key] = $value;
        return $this;
    }
}