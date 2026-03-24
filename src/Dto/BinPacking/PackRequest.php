<?php

declare(strict_types=1);

namespace App\Dto\BinPacking;

use App\Dto\ProductInput;
use App\Entity\Packaging;

/**
 * Request payload for the binpacking API.
 * Sends all containers and items in a single batch request.
 */
class PackRequest implements \JsonSerializable
{
    /** @var ContainerDto[] */
    private readonly array $containers;

    /** @var ItemDto[] */
    private readonly array $items;

    /**
     * @param Packaging[] $boxes
     * @param ProductInput[] $products
     */
    public function __construct(array $boxes, array $products)
    {
        $this->containers = array_map(ContainerDto::fromPackaging(...), $boxes);
        $this->items = array_map(
            static fn(int $i, ProductInput $p) => ItemDto::fromProduct($p, $i),
            array_keys($products),
            $products,
        );
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function jsonSerialize(): array
    {
        return [
            'containers' => array_map(
                static fn(ContainerDto $b) => $b->jsonSerialize(),
                $this->containers,
            ),
            'items' => array_map(
                static fn(ItemDto $i) => $i->jsonSerialize(),
                $this->items,
            ),
        ];
    }
}
