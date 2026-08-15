<?php

namespace App\Controller;

use App\Entity\TaskImage;
use App\Repository\TaskImageRepository;
use App\Security\Voter\EntityOwnerVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task/image')]
class TaskImageController extends AbstractController
{
    #[Route('/{id}', name: 'app_task_image_delete', methods: ['POST'])]
    public function delete(Request $request, TaskImage $taskImage, TaskImageRepository $imageRepository): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::DELETE, $taskImage);

        if ($this->isCsrfTokenValid('delete'.$taskImage->getId(), $request->request->get('_token'))) {
            $imageRepository->remove($taskImage, true);
            $this->addFlash('success', 'Bild wurde gelöscht.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_offer_index'));
    }
}
