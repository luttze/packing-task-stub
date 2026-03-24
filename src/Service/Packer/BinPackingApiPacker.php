<?php

declare(strict_types=1);

namespace App\Service\Packer;

use App\Dto\BinPacking\PackRequest;
use App\Entity\Packaging;

class BinPackingApiPacker implements PackerInterface
{
    public function __construct(
        private readonly BinPackingApiClient $apiClient,
    ) {}

    public function findSmallestBox(array $products, array $boxes): ?Packaging
    {
        $request = new PackRequest($boxes, $products);
        $response = $this->apiClient->sendPackRequest($request);
        $totalItems = count($products);

        return array_find(
            $boxes,
            static fn(Packaging $box) => $response->getItemCount((string) $box->getId()) === $totalItems,
        );
    }
}
