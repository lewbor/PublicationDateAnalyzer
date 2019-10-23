<?php


namespace App\Lib\Grid;


use PaLabs\DatagridBundle\Grid\GridOptions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AbstractGridEndpoint
{
    protected $em;
    protected $templating;
    protected $viewBuilder;
    protected $exporterFacade;

    public function __construct(GridEndpointServices $services)
    {
        $this->em = $services->getEm();
        $this->templating = $services->getTemplating();
        $this->viewBuilder = $services->getViewBuilder();
        $this->exporterFacade = $services->getExporterFacade();
    }

    protected abstract function isGranted(array $parameters): bool;

    public abstract function configure(Request $request, array $parameters): GridEndpointConfiguration;

    public function __invoke(Request $request): Response
    {
        $parameters = $this->buildParameters($request);
        if (!$this->isGranted($parameters)) {
            throw new AccessDeniedHttpException("You not allowed to access this resource");
        }

        $config = $this->configure($request, $parameters);
        return $this->displayGrid($request, $config, $parameters);

    }

    protected function displayGrid(Request $request, GridEndpointConfiguration $configuration, array $parameters): Response
    {
        $options = new GridOptions(GridOptions::PAGING_TYPE_SPLIT_BY_PAGES, GridOptions::RENDER_FORMAT_HTML);
        $grid = $this->viewBuilder->buildView(
            $request,
            $configuration->getDataTable(),
            $configuration->getDataSource(),
            $configuration->getGridParameters(),
            $options
        );

        $templateVars = array_merge([
            'grid' => $grid,
            'configuration' => $configuration
        ], $this->templateVars($request, $configuration, $parameters));
        return new Response($this->templating->render($configuration->getTemplate(), $templateVars));
    }

    protected function buildParameters(Request $request): array
    {
        return [];
    }

    protected function templateVars(Request $request, GridEndpointConfiguration $configuration, array $parameters): array
    {
        return [];
    }
}