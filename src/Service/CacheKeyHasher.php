<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProductInput;
use App\Entity\Packaging;

class CacheKeyHasher
{
    /**
     * @param ProductInput[] $products
     * @param Packaging[] $boxes
     */
    public function computeKey(array $products, array $boxes): string
    {
        $productData = array_map(
            static fn(ProductInput $p) => [$p->width, $p->height, $p->length, $p->weight],
            $products,
        );
        sort($productData);

        $boxData = array_map(
            static fn(Packaging $b) => [$b->getId(), $b->getWidth(), $b->getHeight(), $b->getLength(), $b->getMaxWeight()],
            $boxes,
        );
        sort($boxData);

        return hash('sha256', serialize(['p' => $productData, 'b' => $boxData]));
    }
}
