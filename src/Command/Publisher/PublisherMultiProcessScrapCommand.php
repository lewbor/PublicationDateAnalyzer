<?php


namespace App\Command\Publisher;


use App\Lib\AbstractMultiProcessCommand;
use App\Lib\AbstractMultiProxyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;

class PublisherMultiProcessScrapCommand extends AbstractMultiProxyCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('publisher.multi_process_scrap')
            ->addArgument('queueName', InputArgument::REQUIRED);
    }

    protected function commandName(): string
    {
        return  PublisherScrapCommand::CMD_NAME;
    }

    protected function createProcess(InputInterface $input, array $env): Process
    {
        return new Process(['bin/console', $this->commandName(), $input->getArgument('queueName')], null, $env);
    }
}