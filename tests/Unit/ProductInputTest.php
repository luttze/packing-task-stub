<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\ProductInput;
use PHPUnit\Framework\TestCase;

class ProductInputTest extends TestCase
{
    public function testGetVolume(): void
    {
        $product = new ProductInput(1, 2.0, 3.0, 4.0, 5.0);

        self::assertSame(24.0, $product->getVolume());
    }

    public function testGetSortedDimensions(): void
    {
        $product = new ProductInput(1, 5.0, 2.0, 8.0, 1.0);

        self::assertSame([2.0, 5.0, 8.0], $product->getSortedDimensions());
    }

    public function testGetSortedDimensionsAlreadySorted(): void
    {
        $product = new ProductInput(1, 1.0, 2.0, 3.0, 1.0);

        self::assertSame([1.0, 2.0, 3.0], $product->getSortedDimensions());
    }
}
