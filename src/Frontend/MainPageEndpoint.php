<?php


namespace App\Frontend;


use App\Entity\JournalAnalytics;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class MainPageEndpoint
{
    protected $em;
    protected $templating;

    public function __construct(
        EntityManagerInterface $em,
        Environment $templating)
    {
        $this->em = $em;
        $this->templating = $templating;
    }

    /**
      * @Route("/")
      */
    public function __invoke(Request $request): Response
    {
        $stat = $this->em->getRepository(JournalAnalytics::class)->all();

        return new Response($this->templating->render('main_page.html.twig', [
            'stat' => $stat
        ]));
    }
}