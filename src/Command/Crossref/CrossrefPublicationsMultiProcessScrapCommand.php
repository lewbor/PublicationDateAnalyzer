<?php


namespace App\Command\Crossref;


use App\Lib\AbstractMultiProcessCommand;
use App\Lib\AbstractMultiProxyCommand;

class CrossrefPublicationsMultiProcessScrapCommand extends AbstractMultiProxyCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('crossref.publications.multi_scrap');
    }

    protected function defaultProcessCount(): int
    {
        return 10;
    }

    protected function commandName(): string
    {
        return CrossrefPublicationsScrapCommand::CMD_NAME;
    }
}