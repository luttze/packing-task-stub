<?php

declare(strict_types=1);

namespace App\Dto\BinPacking;

use App\Dto\ProductInput;

/**
 * Represents an item in the binpacking API request.
 */
class ItemDto implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly int $width,
        public readonly int $length,
        public readonly int $depth,
        public readonly int $weight,
    ) {}

    public static function fromProduct(ProductInput $product, int $index): self
    {
        return new self(
            id: (string) $index,
            width: (int) $product->width,
            length: (int) $product->length,
            depth: (int) $product->height,
            weight: (int) $product->weight,
        );
    }

    /**
     * @return array<string, string|int>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'width' => $this->width,
            'length' => $this->length,
            'depth' => $this->depth,
            'weight' => $this->weight,
        ];
    }
}
