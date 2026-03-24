<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\ProductInput;
use App\Service\CacheKeyHasher;
use PHPUnit\Framework\TestCase;

class CacheKeyHasherTest extends TestCase
{
    private CacheKeyHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new CacheKeyHasher();
    }

    public function testSameInputProducesSameHash(): void
    {
        $products = [new ProductInput(1, 2.0, 3.0, 4.0, 5.0)];
        $boxes = [TestHelper::createPackaging(1, 10.0, 10.0, 10.0, 50)];

        $hash1 = $this->hasher->computeKey($products, $boxes);
        $hash2 = $this->hasher->computeKey($products, $boxes);

        self::assertSame($hash1, $hash2);
    }

    public function testDifferentProductsProduceDifferentHash(): void
    {
        $boxes = [TestHelper::createPackaging(1, 10.0, 10.0, 10.0, 50)];

        $hash1 = $this->hasher->computeKey([new ProductInput(1, 1.0, 1.0, 1.0, 1.0)], $boxes);
        $hash2 = $this->hasher->computeKey([new ProductInput(1, 2.0, 1.0, 1.0, 1.0)], $boxes);

        self::assertNotSame($hash1, $hash2);
    }

    public function testDifferentBoxesProduceDifferentHash(): void
    {
        $products = [new ProductInput(1, 1.0, 1.0, 1.0, 1.0)];

        $hash1 = $this->hasher->computeKey($products, [TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20)]);
        $hash2 = $this->hasher->computeKey($products, [TestHelper::createPackaging(1, 6.0, 6.0, 6.0, 20)]);

        self::assertNotSame($hash1, $hash2);
    }

    public function testProductOrderDoesNotAffectHash(): void
    {
        $boxes = [TestHelper::createPackaging(1, 10.0, 10.0, 10.0, 50)];

        $p1 = new ProductInput(1, 1.0, 2.0, 3.0, 4.0);
        $p2 = new ProductInput(2, 5.0, 6.0, 7.0, 8.0);

        $hash1 = $this->hasher->computeKey([$p1, $p2], $boxes);
        $hash2 = $this->hasher->computeKey([$p2, $p1], $boxes);

        self::assertSame($hash1, $hash2);
    }

    public function testBoxOrderDoesNotAffectHash(): void
    {
        $products = [new ProductInput(1, 1.0, 1.0, 1.0, 1.0)];

        $b1 = TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20);
        $b2 = TestHelper::createPackaging(2, 10.0, 10.0, 10.0, 30);

        $hash1 = $this->hasher->computeKey($products, [$b1, $b2]);
        $hash2 = $this->hasher->computeKey($products, [$b2, $b1]);

        self::assertSame($hash1, $hash2);
    }

    public function testHashIsSha256(): void
    {
        $hash = $this->hasher->computeKey(
            [new ProductInput(1, 1.0, 1.0, 1.0, 1.0)],
            [TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20)],
        );

        self::assertSame(64, \strlen($hash));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }
}
