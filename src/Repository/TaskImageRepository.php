<?php

namespace App\Repository;

use App\Entity\TaskImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskImage>
 *
 * @method TaskImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskImage[]    findAll()
 * @method TaskImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskImage::class);
    }

    public function save(TaskImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
