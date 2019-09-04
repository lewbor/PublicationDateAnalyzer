<?php


namespace App\Command\Crossref;


use App\Lib\AbstractMultiProcessCommand;

class CrossrefPublicationsMultiProcessScrapCommand extends AbstractMultiProcessCommand
{

    protected function configure()
    {
        $this->setName('crossref.publications.multi_scrap');
    }

    protected function processCount(): int
    {
        return 10;
    }

    protected function commandName(): string
    {
        return CrossrefPublicationsScrapCommand::CMD_NAME;
    }
}