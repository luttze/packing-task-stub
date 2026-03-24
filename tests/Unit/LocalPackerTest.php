<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\ProductInput;
use App\Entity\Packaging;
use App\Service\Packer\LocalPacker;
use PHPUnit\Framework\TestCase;

class LocalPackerTest extends TestCase
{
    private LocalPacker $packer;

    protected function setUp(): void
    {
        $this->packer = new LocalPacker();
    }

    public function testFindsSmallestBoxByVolume(): void
    {
        $products = [new ProductInput(1, 1.0, 1.0, 1.0, 1.0)];

        $boxes = [
            new Packaging(5.0, 5.0, 5.0, 20),  // volume 125
            new Packaging(2.0, 2.0, 2.0, 20),  // volume 8
            new Packaging(3.0, 3.0, 3.0, 20),  // volume 27
        ];

        // Sort by volume as the service does
        usort($boxes, fn(Packaging $a, Packaging $b) => $a->getVolume() <=> $b->getVolume());

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNotNull($result);
        self::assertSame(8.0, $result->getVolume());
    }

    public function testRejectsWhenTotalVolumeExceedsBox(): void
    {
        // Two products with total volume 16, only box has volume 8
        $products = [
            new ProductInput(1, 2.0, 2.0, 2.0, 1.0),
            new ProductInput(2, 2.0, 2.0, 2.0, 1.0),
        ];

        $boxes = [new Packaging(2.0, 2.0, 2.0, 20)];

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNull($result);
    }

    public function testRejectsWhenWeightExceeds(): void
    {
        $products = [new ProductInput(1, 1.0, 1.0, 1.0, 25.0)];
        $boxes = [new Packaging(5.0, 5.0, 5.0, 20)];

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNull($result);
    }

    public function testRejectsWhenProductDimensionExceedsBox(): void
    {
        // Product is 10 units long but box is only 3 in every dimension
        $products = [new ProductInput(1, 1.0, 1.0, 10.0, 1.0)];
        $boxes = [new Packaging(3.0, 3.0, 3.0, 20)];

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNull($result);
    }

    public function testProductRotationConsidered(): void
    {
        // Product is 1x1x5, box is 2x6x2
        // Sorted dimensions: product [1,1,5], box [2,2,6]
        // 1<=2, 1<=2, 5<=6 — fits
        $products = [new ProductInput(1, 1.0, 1.0, 5.0, 1.0)];
        $boxes = [new Packaging(2.0, 6.0, 2.0, 20)];

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNotNull($result);
    }

    public function testSkipsSmallBoxSelectsLarger(): void
    {
        // Product volume 8, weight 15
        // Small box: volume 8, maxWeight 10 — weight exceeds
        // Large box: volume 27, maxWeight 20 — fits
        $products = [new ProductInput(1, 2.0, 2.0, 2.0, 15.0)];

        $boxes = [
            new Packaging(2.0, 2.0, 2.0, 10),
            new Packaging(3.0, 3.0, 3.0, 20),
        ];

        usort($boxes, fn(Packaging $a, Packaging $b) => $a->getVolume() <=> $b->getVolume());

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNotNull($result);
        self::assertSame(27.0, $result->getVolume());
    }

    public function testEmptyProductsFitsAnyBox(): void
    {
        $boxes = [new Packaging(2.0, 2.0, 2.0, 20)];

        $result = $this->packer->findSmallestBox([], $boxes);

        self::assertNotNull($result);
    }

    public function testNoBoxesReturnsNull(): void
    {
        $products = [new ProductInput(1, 1.0, 1.0, 1.0, 1.0)];

        $result = $this->packer->findSmallestBox($products, []);

        self::assertNull($result);
    }

    public function testMultipleProductsCumulativeWeight(): void
    {
        // Each product weighs 8, total 16. Box maxWeight is 15.
        $products = [
            new ProductInput(1, 1.0, 1.0, 1.0, 8.0),
            new ProductInput(2, 1.0, 1.0, 1.0, 8.0),
        ];

        $boxes = [new Packaging(5.0, 5.0, 5.0, 15)];

        $result = $this->packer->findSmallestBox($products, $boxes);

        self::assertNull($result);
    }
}
