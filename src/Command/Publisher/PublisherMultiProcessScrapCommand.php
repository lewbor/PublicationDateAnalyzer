<?php


namespace App\Command\Publisher;


use App\Lib\AbstractMultiProcessCommand;

class PublisherMultiProcessScrapCommand extends AbstractMultiProcessCommand
{

    protected function configure()
    {
        $this->setName('publisher.multi_process_scrap');
    }

    protected function processCount(): int
    {
        return 4;
    }

    protected function commandName(): string
    {
        return  PublisherScrapCommand::CMD_NAME;
    }
}