<?php

declare(strict_types=1);

namespace App\Service\Packer;

use App\Dto\ProductInput;
use App\Entity\Packaging;

interface PackerInterface
{
    /**
     * @param ProductInput[] $products
     * @param Packaging[] $boxes
     * @return Packaging|null
     */
    public function findSmallestBox(array $products, array $boxes): ?Packaging;
}
