<?php

declare(strict_types=1);

namespace App\Service\Packer;

use App\Dto\ProductInput;
use App\Entity\Packaging;

/**
 * Simple local fallback packer using volume and dimension heuristics.
 *
 * Used when the 3DBinPacking API is unavailable. This is a conservative approximation:
 * - Total product volume must fit within box volume
 * - Total weight must not exceed box max weight
 * - Each individual product must physically fit inside the box (dimension check with rotation)
 *
 * This does not solve optimal 3D bin packing but provides a reasonable estimate
 * for shipping cost calculations when the external API is down.
 */
class LocalPacker implements PackerInterface
{
    public function findSmallestBox(array $products, array $boxes): ?Packaging
    {
        $totalWeight = 0.0;
        $totalVolume = 0.0;

        foreach ($products as $product) {
            $totalWeight += $product->weight;
            $totalVolume += $product->getVolume();
        }

        foreach ($boxes as $box) {
            if ($totalWeight > $box->getMaxWeight()) {
                continue;
            }

            if ($totalVolume > $box->getVolume()) {
                continue;
            }

            if (!$this->allProductsFitDimensionally($products, $box)) {
                continue;
            }

            return $box;
        }

        return null;
    }

    /**
     * Checks that each product can individually fit inside the box when rotated optimally.
     *
     * Sorts both product and box dimensions ascending and compares pairwise.
     * This ensures no single product is physically too large for the box in any orientation.
     *
     * @param ProductInput[] $products
     */
    private function allProductsFitDimensionally(array $products, Packaging $box): bool
    {
        $boxDims = $box->getSortedDimensions();

        foreach ($products as $product) {
            $prodDims = $product->getSortedDimensions();

            if (
                $prodDims[0] > $boxDims[0]
                || $prodDims[1] > $boxDims[1]
                || $prodDims[2] > $boxDims[2]
            ) {
                return false;
            }
        }

        return true;
    }
}
