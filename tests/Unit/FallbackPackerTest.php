<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Packaging;
use App\Exception\PackingException;
use App\Service\Packer\FallbackPacker;
use App\Service\Packer\PackerInterface;
use PHPUnit\Framework\TestCase;

class FallbackPackerTest extends TestCase
{
    public function testReturnsPrimaryResultOnSuccess(): void
    {
        $box = new Packaging(2.0, 2.0, 2.0, 20);

        $primary = $this->createMock(PackerInterface::class);
        $primary->method('findSmallestBox')->willReturn($box);

        $fallback = $this->createMock(PackerInterface::class);
        $fallback->expects(self::never())->method('findSmallestBox');

        $packer = new FallbackPacker($primary, $fallback);
        $result = $packer->findSmallestBox([], []);

        self::assertSame($box, $result);
    }

    public function testDelegatesToFallbackOnPackingException(): void
    {
        $box = new Packaging(3.0, 3.0, 3.0, 20);

        $primary = $this->createMock(PackerInterface::class);
        $primary->method('findSmallestBox')->willThrowException(
            new PackingException('API down'),
        );

        $fallback = $this->createMock(PackerInterface::class);
        $fallback->expects(self::once())->method('findSmallestBox')->willReturn($box);

        $packer = new FallbackPacker($primary, $fallback);
        $result = $packer->findSmallestBox([], []);

        self::assertSame($box, $result);
    }

    public function testPropagatesNonPackingExceptions(): void
    {
        $primary = $this->createMock(PackerInterface::class);
        $primary->method('findSmallestBox')->willThrowException(
            new \RuntimeException('unexpected'),
        );

        $fallback = $this->createMock(PackerInterface::class);

        $packer = new FallbackPacker($primary, $fallback);

        $this->expectException(\RuntimeException::class);
        $packer->findSmallestBox([], []);
    }

    public function testReturnsNullWhenBothReturnNull(): void
    {
        $primary = $this->createMock(PackerInterface::class);
        $primary->method('findSmallestBox')->willThrowException(
            new PackingException('API down'),
        );

        $fallback = $this->createMock(PackerInterface::class);
        $fallback->method('findSmallestBox')->willReturn(null);

        $packer = new FallbackPacker($primary, $fallback);
        $result = $packer->findSmallestBox([], []);

        self::assertNull($result);
    }
}
