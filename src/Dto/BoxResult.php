<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Packaging;

/**
 * Response DTO representing the selected box.
 * Decouples the API response from the Doctrine entity.
 */
class BoxResult implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly float $width,
        public readonly float $height,
        public readonly float $length,
        public readonly float $maxWeight,
    ) {}

    public static function fromEntity(Packaging $packaging): self
    {
        return new self(
            id: $packaging->getId(),
            width: $packaging->getWidth(),
            height: $packaging->getHeight(),
            length: $packaging->getLength(),
            maxWeight: $packaging->getMaxWeight(),
        );
    }

    /**
     * @return array<string, int|float>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'maxWeight' => $this->maxWeight,
        ];
    }
}
