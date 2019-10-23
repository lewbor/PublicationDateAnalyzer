<?php


namespace App\Frontend\MainPage;


use App\Lib\Grid\AbstractGridEndpoint;
use App\Lib\Grid\GridEndpointConfiguration;
use App\Lib\Grid\GridEndpointConfigurationBuilder;
use App\Lib\Grid\GridEndpointServices;
use PaLabs\DatagridBundle\Grid\GridParameters;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/", name="main_page")
 */
class MainPageEndpoint extends AbstractGridEndpoint
{
    protected $dataSource;
    protected $dataTable;

    public function __construct(
        GridEndpointServices $services,
        DataSource $dataSource,
        DataTable $dataTable)
    {
        parent::__construct($services);
        $this->dataSource = $dataSource;
        $this->dataTable = $dataTable;
    }

    public function configure(Request $request, array $parameters): GridEndpointConfiguration
    {
        return (new GridEndpointConfigurationBuilder())
            ->setDataSource($this->dataSource)
            ->setDataTable($this->dataTable)
            ->setGridParameters(new GridParameters([
                Request::class => $request,
            ]))
            ->setTemplate('main_page.html.twig')
            ->build();
    }

    protected function isGranted(array $parameters): bool
    {
        return true;
    }
}