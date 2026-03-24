<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Packaging;
use Doctrine\ORM\EntityManager;

class DoctrinePackagingRepository implements PackagingRepositoryInterface
{
    public function __construct(
        private readonly EntityManager $entityManager,
    ) {}

    public function findAllSortedByVolume(): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->addSelect('(p.width * p.height * p.length) AS HIDDEN volume')
            ->from(Packaging::class, 'p')
            ->orderBy('volume', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
