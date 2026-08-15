<?php

namespace App\Repository;

use App\Entity\TaskDraw;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskDraw>
 *
 * @method TaskDraw|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskDraw|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskDraw[]    findAll()
 * @method TaskDraw[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskDrawRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskDraw::class);
    }

    public function save(TaskDraw $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskDraw $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
