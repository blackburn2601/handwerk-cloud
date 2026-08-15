<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Task;
use App\Entity\User;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use App\Repository\TaskRepository;
use App\Security\Voter\EntityOwnerVoter;
use App\Service\TaskImageUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/offer')]
class OfferController extends AbstractController
{
    #[Route('/', name: 'app_offer_index', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('offer/index.html.twig', [
            'offers' => $this->isGranted('ROLE_ADMIN')
                ? $offerRepository->findAll()
                : $offerRepository->findByOwner($user),
        ]);
    }

    #[Route('/new', name: 'app_offer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, OfferRepository $offerRepository, TaskImageUploader $uploader): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setCreated(new \DateTime());
            $offer->setCreatedBy($this->getUser());

            $offerRepository->save($offer, true);
            $uploader->upload($form->get('taskImages')->getData() ?? [], $offer);

            $this->addFlash('success', 'Angebot wurde angelegt.');

            return $this->redirectToRoute('app_offer_edit', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('offer/new.html.twig', [
            'offer' => $offer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_offer_show', methods: ['GET'])]
    public function show(Offer $offer): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::VIEW, $offer);

        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_offer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offer $offer, OfferRepository $offerRepository, TaskImageUploader $uploader): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::EDIT, $offer);

        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offerRepository->save($offer, true);
            $uploader->upload($form->get('taskImages')->getData() ?? [], $offer);

            $this->addFlash('success', 'Angebot wurde gespeichert.');

            return $this->redirectToRoute('app_offer_edit', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('offer/edit.html.twig', [
            'offer' => $offer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_offer_delete', methods: ['POST'])]
    public function delete(Request $request, Offer $offer, OfferRepository $offerRepository): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::DELETE, $offer);

        if ($this->isCsrfTokenValid('delete'.$offer->getId(), $request->request->get('_token'))) {
            $offerRepository->remove($offer, true);
            $this->addFlash('success', 'Angebot wurde gelöscht.');
        }

        return $this->redirectToRoute('app_offer_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Turns an accepted offer into a task, carrying over the customer, dates
     * and any drawings and photos already attached to the offer.
     */
    #[Route('/{id}/generateTask', name: 'app_offer_generate_task', methods: ['GET', 'POST'])]
    public function generateTask(Offer $offer, TaskRepository $taskRepository): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::EDIT, $offer);

        if (null !== $existing = $offer->getTask()) {
            $this->addFlash('info', 'Für dieses Angebot existiert bereits ein Auftrag.');

            return $this->redirectToRoute('app_task_edit', ['id' => $existing->getId()], Response::HTTP_SEE_OTHER);
        }

        $task = new Task();
        $task->setOffer($offer);
        $task->setCustomer($offer->getCustomer());
        $task->setTaskDate($offer->getTermDate() ?? new \DateTime());
        $task->setTermDate($offer->getTermDate());
        $task->setComment($offer->getComment());
        $task->setTextarea($offer->getTextarea());
        $task->setCreatedBy($this->getUser());

        foreach ($offer->getTaskDraws() as $taskDraw) {
            $task->addTaskDraw($taskDraw);
        }

        foreach ($offer->getTaskImages() as $taskImage) {
            $task->addTaskImage($taskImage);
        }

        $taskRepository->save($task, true);

        $this->addFlash('success', 'Auftrag wurde aus dem Angebot erstellt.');

        return $this->redirectToRoute('app_task_edit', ['id' => $task->getId()], Response::HTTP_SEE_OTHER);
    }
}
