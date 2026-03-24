<?php

declare(strict_types=1);

namespace App\Dto;

class ProductInput
{
    public function __construct(
        public readonly int $id,
        public readonly float $width,
        public readonly float $height,
        public readonly float $length,
        public readonly float $weight,
    ) {}

    public function getVolume(): float
    {
        return $this->width * $this->height * $this->length;
    }

    /**
     * @return float[] Dimensions sorted ascending
     */
    public function getSortedDimensions(): array
    {
        $dims = [$this->width, $this->height, $this->length];
        sort($dims);

        return $dims;
    }
}
