<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\OfferRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        CustomerRepository $customerRepository,
        OfferRepository $offerRepository,
        TaskRepository $taskRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Non-admins see counts for their own records only, matching what the
        // list pages will actually show them.
        return $this->render('home/index.html.twig', [
            'customers' => $isAdmin ? $customerRepository->count([]) : \count($customerRepository->findByOwner($user)),
            'offers' => $isAdmin ? $offerRepository->count([]) : \count($offerRepository->findByOwner($user)),
            'tasks' => $isAdmin ? $taskRepository->count([]) : \count($taskRepository->findByOwner($user)),
        ]);
    }
}
