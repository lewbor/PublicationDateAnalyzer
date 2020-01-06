<?php


namespace App\Frontend\Journal;


use App\Entity\Journal\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @Route("/journal/{id}", name="journal_view")
 */
class JournalViewEndpoint
{
    protected $em;
    protected $templating;
    protected $viewBuilder;

    public function __construct(
        EntityManagerInterface $em,
        Environment $templating,
        JournalViewBuilder $viewBuilder)
    {
        $this->em = $em;
        $this->templating = $templating;
        $this->viewBuilder = $viewBuilder;
    }

    public function __invoke(Request $request): Response
    {
        $entity = $this->em->getRepository(Journal::class)->find($request->attributes->get('id'));
        if ($entity === null) {
            throw new NotFoundHttpException();
        }

        return new Response($this->templating->render('journal.html.twig', $this->viewBuilder->buildData($entity)));

    }


}