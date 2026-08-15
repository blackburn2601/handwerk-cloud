<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Security\Voter\EntityOwnerVoter;
use App\Service\TaskArchiver;
use App\Service\TaskImageUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'app_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('task/index.html.twig', [
            'tasks' => $this->isGranted('ROLE_ADMIN')
                ? $taskRepository->findAll()
                : $taskRepository->findByOwner($user),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, TaskRepository $taskRepository, TaskImageUploader $uploader): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::EDIT, $task);

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskRepository->save($task, true);
            $uploader->upload($form->get('taskImages')->getData() ?? [], $task);

            $this->addFlash('success', 'Auftrag wurde gespeichert.');

            return $this->redirectToRoute('app_task_edit', ['id' => $task->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    /**
     * Downloads every photo and drawing of the task as a single zip — what the
     * fitters take to site.
     */
    #[Route('/{id}/archive', name: 'app_task_archive', methods: ['GET'])]
    public function archive(Task $task, TaskArchiver $archiver): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::VIEW, $task);

        return $archiver->archive($task);
    }
}
