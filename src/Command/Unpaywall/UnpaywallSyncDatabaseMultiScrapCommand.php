<?php


namespace App\Command\Unpaywall;


use App\Lib\AbstractMultiProcessCommand;

class UnpaywallSyncDatabaseMultiScrapCommand extends AbstractMultiProcessCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('unpaywall.sync_database.multi_scrap');
    }

    protected function defaultProcessCount(): int
    {
        return 10;
    }

    protected function commandName(): string
    {
       return UnpaywallSyncDatabaseScrapCommand::CMD_NAME;
    }
}