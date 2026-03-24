<?php

declare(strict_types=1);

namespace App\Service\Packer;

use App\Entity\Packaging;
use App\Exception\PackingException;

class FallbackPacker implements PackerInterface
{
    public function __construct(
        private readonly PackerInterface $primary,
        private readonly PackerInterface $fallback,
    ) {}

    public function findSmallestBox(array $products, array $boxes): ?Packaging
    {
        try {
            return $this->primary->findSmallestBox($products, $boxes);
        } catch (PackingException) {
            return $this->fallback->findSmallestBox($products, $boxes);
        }
    }
}
