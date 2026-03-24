<?php

declare(strict_types=1);

namespace App\Dto\BinPacking;

class PackResponse
{
    /**
     * @param array<string, string[]> $packedContainers Container ID => list of packed item IDs
     * @param string[] $unpackedItems Item IDs that didn't fit anywhere
     */
    public function __construct(
        public readonly array $packedContainers,
        public readonly array $unpackedItems,
    ) {}

    public function getItemCount(string $containerId): int
    {
        return count($this->packedContainers[$containerId] ?? []);
    }
}
