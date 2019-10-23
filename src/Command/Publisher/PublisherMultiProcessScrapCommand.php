<?php


namespace App\Command\Publisher;


use App\Lib\AbstractMultiProcessCommand;

class PublisherMultiProcessScrapCommand extends AbstractMultiProcessCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('publisher.multi_process_scrap');
    }

    protected function defaultProcessCount(): int
    {
        return 10;
    }

    protected function commandName(): string
    {
        return  PublisherScrapCommand::CMD_NAME;
    }
}