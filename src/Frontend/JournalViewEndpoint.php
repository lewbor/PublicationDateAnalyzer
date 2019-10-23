<?php


namespace App\Frontend;


use App\Entity\Journal;
use App\Entity\JournalAnalytics;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/journal/{id}", name="journal_view")
 */
class JournalViewEndpoint
{
    protected $em;
    protected $templating;

    public function __construct(
        EntityManagerInterface $em,
        \Twig\Environment $templating)
    {
        $this->em = $em;
        $this->templating = $templating;
    }

    public function __invoke(Request $request): Response
    {
        $entity = $this->em->getRepository(Journal::class)->find($request->attributes->get('id'));
        if ($entity === null) {
            throw new NotFoundHttpException();
        }

        $analytics = $this->em->getRepository(JournalAnalytics::class)->findOneBy(['journal' => $entity]);
        if ($analytics === null) {
            throw new \LogicException();
        }

        return new Response($this->templating->render('journal.html.twig', [
            'journal' => $entity,
            'analytics' => $analytics->getAnalytics()
        ]));

    }
}