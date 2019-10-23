<?php


namespace App\Lib\Grid;


use PaLabs\DatagridBundle\DataSource\ConfigurableDataSource;
use PaLabs\DatagridBundle\DataTable\ConfigurableDataTable;
use PaLabs\DatagridBundle\Grid\Export\XlsxExporter;
use PaLabs\DatagridBundle\Grid\GridParameters;

class GridEndpointConfiguration
{
    /** @var  ConfigurableDataSource */
    protected $dataSource;

    /** @var  ConfigurableDataTable */
    protected $dataTable;

    /** @var  GridParameters */
    protected $gridParameters;

    /** @var  string */
    protected $template;

    /** @var  array */
    protected $exportFormats;

    /** @var  string */
    protected $exportFileName;

    public static function fromArray(array $parameters)
    {
        $config = new static();
        $config->dataTable = $parameters['dataTable'];
        $config->dataSource = $parameters['dataSource'];
        $config->gridParameters = $parameters['gridParameters'];
        $config->template = $parameters['template'];

        $config->exportFormats = $parameters['exportFormats'] ?? ['Excel'  => XlsxExporter::FORMAT];
        $config->exportFileName = $parameters['exportFileName'] ?? 'grid_export';

        return $config;
    }

    public function getDataSource(): ConfigurableDataSource
    {
        return $this->dataSource;
    }

    public function getDataTable(): ConfigurableDataTable
    {
        return $this->dataTable;
    }

    public function getGridParameters(): GridParameters
    {
        return $this->gridParameters;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getExportFormats(): array
    {
        return $this->exportFormats;
    }

    public function getExportFileName(): string
    {
        return $this->exportFileName;
    }

}