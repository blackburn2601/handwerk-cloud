<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Security\Voter\EntityOwnerVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/customer')]
class CustomerController extends AbstractController
{
    #[Route('/', name: 'app_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('customer/index.html.twig', [
            'customers' => $this->isGranted('ROLE_ADMIN')
                ? $customerRepository->findAll()
                : $customerRepository->findByOwner($user),
        ]);
    }

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CustomerRepository $customerRepository): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setCreatedBy($this->getUser());
            $customerRepository->save($customer, true);

            $this->addFlash('success', 'Kunde wurde angelegt.');

            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, CustomerRepository $customerRepository): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::EDIT, $customer);

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerRepository->save($customer, true);

            $this->addFlash('success', 'Kunde wurde gespeichert.');

            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer, CustomerRepository $customerRepository): Response
    {
        $this->denyAccessUnlessGranted(EntityOwnerVoter::DELETE, $customer);

        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->request->get('_token'))) {
            if (!$customer->getOffers()->isEmpty() || !$customer->getTasks()->isEmpty()) {
                $this->addFlash('danger', 'Kunde hat noch Angebote oder Aufträge und kann nicht gelöscht werden.');

                return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
            }

            $customerRepository->remove($customer, true);
            $this->addFlash('success', 'Kunde wurde gelöscht.');
        }

        return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
    }
}
