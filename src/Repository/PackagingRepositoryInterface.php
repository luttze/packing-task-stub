<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Packaging;

interface PackagingRepositoryInterface
{
    /**
     * @return Packaging[]
     */
    public function findAllSortedByVolume(): array;
}
