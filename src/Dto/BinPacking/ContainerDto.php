<?php

declare(strict_types=1);

namespace App\Dto\BinPacking;

use App\Entity\Packaging;

/**
 * Represents a container in the binpacking API request.
 */
class ContainerDto implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly int $width,
        public readonly int $length,
        public readonly int $depth,
        public readonly int $maxWeight,
    ) {}

    public static function fromPackaging(Packaging $packaging): self
    {
        return new self(
            id: (string) $packaging->getId(),
            width: (int) $packaging->getWidth(),
            length: (int) $packaging->getLength(),
            depth: (int) $packaging->getHeight(),
            maxWeight: (int) $packaging->getMaxWeight(),
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
            'maxWeight' => $this->maxWeight,
        ];
    }
}
