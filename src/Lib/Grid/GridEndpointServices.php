<?php


namespace App\Lib\Grid;


use Doctrine\ORM\EntityManagerInterface;
use PaLabs\DatagridBundle\Grid\Export\GridExporterFacade;
use PaLabs\DatagridBundle\Grid\View\GridViewBuilder;
use Twig\Environment;
class GridEndpointServices
{
    protected $em;
    protected $templating;
    protected $viewBuilder;
    protected $exporterFacade;

    public function __construct(
        EntityManagerInterface $em,
        Environment $templating,
        GridViewBuilder $viewBuilder,
        GridExporterFacade $exporterFacade)
    {
        $this->em = $em;
        $this->templating = $templating;
        $this->viewBuilder = $viewBuilder;
        $this->exporterFacade = $exporterFacade;
    }

    public function getEm(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getTemplating(): Environment
    {
        return $this->templating;
    }

    public function getViewBuilder(): GridViewBuilder
    {
        return $this->viewBuilder;
    }

    public function getExporterFacade(): GridExporterFacade
    {
        return $this->exporterFacade;
    }
}