<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\BoxResult;
use App\Dto\ProductInput;
use App\Exception\NoSuitableBoxException;
use App\Repository\PackagingRepositoryInterface;
use App\Repository\PackingCacheRepositoryInterface;
use App\Service\Packer\PackerInterface;

class PackingService
{
    public function __construct(
        private readonly PackerInterface $packer,
        private readonly PackagingRepositoryInterface $packagingRepository,
        private readonly PackingCacheRepositoryInterface $cacheRepository,
        private readonly CacheKeyHasher $hasher,
    ) {}

    /**
     * @param ProductInput[] $products
     * @throws NoSuitableBoxException If no single box can hold all products
     */
    public function findSmallestBoxForProducts(array $products): BoxResult
    {
        $boxes = $this->packagingRepository->findAllSortedByVolume();

        if ($boxes === []) {
            throw new NoSuitableBoxException('No packaging boxes are configured in the system');
        }

        $cacheKey = $this->hasher->computeKey($products, $boxes);

        $cached = $this->cacheRepository->findByHash($cacheKey);

        if ($cached === null) {
            $computed = $this->packer->findSmallestBox($products, $boxes);
            $cached = $this->cacheRepository->saveResult($cacheKey, $computed);
        }

        $result = $cached->getPackaging();

        if ($result === null) {
            throw new NoSuitableBoxException('No suitable box found for the given products');
        }

        return BoxResult::fromEntity($result);
    }
}
