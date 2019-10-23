<?php


namespace App\Lib\Grid;


use PaLabs\DatagridBundle\DataSource\ConfigurableDataSource;
use PaLabs\DatagridBundle\DataTable\ConfigurableDataTable;
use PaLabs\DatagridBundle\Grid\GridParameters;

class GridEndpointConfigurationBuilder
{
    protected $parameters;

    public function setDataSource(ConfigurableDataSource $dataSource): self {
        $this->parameters['dataSource'] = $dataSource;
        return $this;
    }

    public function setDataTable(ConfigurableDataTable $dataTable): self {
        $this->parameters['dataTable'] = $dataTable;
        return $this;
    }

    public function setGridParameters(GridParameters $gridParameters): self {
        $this->parameters['gridParameters'] = $gridParameters;
        return $this;
    }

    public function setTemplate(string $template): self {
        $this->parameters['template'] = $template;
        return $this;
    }

    public function setExportFormats(array $exportFormats):self {
        $this->parameters['exportFormats'] = $exportFormats;
        return $this;
    }

    public function setExportFileName(string $exportFileName): self {
        $this->parameters['exportFileName'] = $exportFileName;
        return $this;
    }

    public function build(): GridEndpointConfiguration {
        return GridEndpointConfiguration::fromArray($this->parameters);
    }
}