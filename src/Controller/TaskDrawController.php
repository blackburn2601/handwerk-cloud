<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\TaskDraw;
use App\Form\TaskDrawType;
use App\Repository\OfferRepository;
use App\Repository\TaskDrawRepository;
use App\Repository\TaskRepository;
use App\Security\Voter\EntityOwnerVoter;
use App\Service\TaskDrawRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task/draw')]
class TaskDrawController extends AbstractController
{
    /**
     * Sketch pad for an offer. The drawing is captured on a canvas in the
     * browser and submitted as a base64 data URL.
     */
    #[Route('/new/{id}', name: 'app_task_draw_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Offer $offer,
        TaskDrawRepository $taskDrawRepository,
        OfferRepository $offerRepository,
        TaskRepository $taskRepository,
        TaskDrawRenderer $renderer,
    ): Response {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::EDIT, $offer);

        $taskDraw = new TaskDraw();
        $taskDraw->setOffer($offer);

        $form = $this->createForm(TaskDrawType::class, $taskDraw);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $taskDraw->setPath($renderer->render($taskDraw));
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->renderForm('task_draw/new.html.twig', [
                    'offer' => $offer,
                    'task_draw' => $taskDraw,
                    'form' => $form,
                ]);
            }

            $offer->addTaskDraw($taskDraw);
            $taskDrawRepository->save($taskDraw);
            $offerRepository->save($offer, true);

            // The offer and its task share drawings.
            if (null !== $task = $offer->getTask()) {
                $taskDraw->setTask($task);
                $task->addTaskDraw($taskDraw);
                $taskRepository->save($task, true);

                $this->addFlash('success', 'Zeichnung wurde gespeichert.');

                return $this->redirectToRoute('app_task_edit', ['id' => $task->getId()], Response::HTTP_SEE_OTHER);
            }

            $this->addFlash('success', 'Zeichnung wurde gespeichert.');

            return $this->redirectToRoute('app_offer_edit', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('task_draw/new.html.twig', [
            'offer' => $offer,
            'task_draw' => $taskDraw,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_task_draw_delete', methods: ['POST'])]
    public function delete(Request $request, TaskDraw $taskDraw, TaskDrawRepository $taskDrawRepository): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::DELETE, $taskDraw);

        if ($this->isCsrfTokenValid('delete'.$taskDraw->getId(), $request->request->get('_token'))) {
            $taskDrawRepository->remove($taskDraw, true);
            $this->addFlash('success', 'Zeichnung wurde gelöscht.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_offer_index'));
    }
}
