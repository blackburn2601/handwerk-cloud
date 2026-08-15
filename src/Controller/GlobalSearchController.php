<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\OfferRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Searches customers, offers and tasks by customer name, postcode or city.
 */
class GlobalSearchController extends AbstractController
{
    #[Route('/global/search', name: 'app_global_search', methods: ['GET'])]
    public function search(
        Request $request,
        CustomerRepository $customerRepository,
        OfferRepository $offerRepository,
        TaskRepository $taskRepository,
    ): Response {
        $term = trim((string) $request->query->get('search', ''));

        if ('' === $term) {
            return $this->render('global_search/index.html.twig', [
                'term' => '',
                'customers' => [],
                'offers' => [],
                'tasks' => [],
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $restrictTo = $this->isGranted('ROLE_ADMIN') ? null : $user;

        return $this->render('global_search/index.html.twig', [
            'term' => $term,
            'customers' => $this->run($customerRepository->createQueryBuilder('c'), 'c', 'c', $term, $restrictTo),
            'offers' => $this->run($offerRepository->createQueryBuilder('o')->innerJoin('o.customer', 'c'), 'o', 'c', $term, $restrictTo),
            'tasks' => $this->run($taskRepository->createQueryBuilder('t')->innerJoin('t.customer', 'c'), 't', 'c', $term, $restrictTo),
        ]);
    }

    /**
     * The customer-matching conditions are grouped into a single OR expression
     * so that the ownership restriction below applies to the whole match rather
     * than only to the last branch.
     *
     * @param string $rootAlias     alias of the entity being returned
     * @param string $customerAlias alias holding the customer fields
     */
    private function run(QueryBuilder $qb, string $rootAlias, string $customerAlias, string $term, ?User $restrictTo): array
    {
        $matches = $qb->expr()->orX(
            $qb->expr()->like($customerAlias.'.firstname', ':term'),
            $qb->expr()->like($customerAlias.'.surname', ':term'),
            $qb->expr()->like($customerAlias.'.plz', ':term'),
            $qb->expr()->like($customerAlias.'.city', ':term'),
        );

        $qb->andWhere($matches)->setParameter('term', '%'.$term.'%');

        if (null !== $restrictTo) {
            $qb->andWhere($rootAlias.'.createdBy = :owner')->setParameter('owner', $restrictTo);
        }

        return $qb->setMaxResults(50)->getQuery()->getResult();
    }
}
