<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\BoxResult;
use App\Dto\ProductInput;
use App\Entity\PackingCache;
use App\Exception\NoSuitableBoxException;
use App\Repository\PackagingRepositoryInterface;
use App\Repository\PackingCacheRepositoryInterface;
use App\Service\CacheKeyHasher;
use App\Service\Packer\PackerInterface;
use App\Service\PackingService;
use PHPUnit\Framework\TestCase;

class PackingServiceTest extends TestCase
{
    private PackerInterface $packer;
    private PackagingRepositoryInterface $packagingRepo;
    private PackingCacheRepositoryInterface $cacheRepo;
    private PackingService $service;

    protected function setUp(): void
    {
        $this->packer = $this->createMock(PackerInterface::class);
        $this->packagingRepo = $this->createMock(PackagingRepositoryInterface::class);
        $this->cacheRepo = $this->createMock(PackingCacheRepositoryInterface::class);

        $this->service = new PackingService(
            $this->packer,
            $this->packagingRepo,
            $this->cacheRepo,
            new CacheKeyHasher(),
        );
    }

    private function sampleProducts(): array
    {
        return [new ProductInput(1, 1.0, 1.0, 1.0, 1.0)];
    }

    public function testPackDelegatesToPackerAndReturnsResult(): void
    {
        $box = TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20);

        $this->packagingRepo->method('findAllSortedByVolume')->willReturn([$box]);
        $this->cacheRepo->method('findByHash')->willReturn(null);
        $this->cacheRepo->method('saveResult')->willReturn(new PackingCache('hash', $box));
        $this->packer->method('findSmallestBox')->willReturn($box);

        $result = $this->service->findSmallestBoxForProducts($this->sampleProducts());

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testReturnsCachedResult(): void
    {
        $box = TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20);
        $cached = new PackingCache('hash', $box);

        $this->packagingRepo->method('findAllSortedByVolume')->willReturn([$box]);
        $this->cacheRepo->method('findByHash')->willReturn($cached);
        $this->packer->expects(self::never())->method('findSmallestBox');

        $result = $this->service->findSmallestBoxForProducts($this->sampleProducts());

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testCachedNullResultThrows(): void
    {
        $box = TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20);
        $cached = new PackingCache('hash', null);

        $this->packagingRepo->method('findAllSortedByVolume')->willReturn([$box]);
        $this->cacheRepo->method('findByHash')->willReturn($cached);

        $this->expectException(NoSuitableBoxException::class);

        $this->service->findSmallestBoxForProducts($this->sampleProducts());
    }

    public function testThrowsWhenNoBoxesConfigured(): void
    {
        $this->packagingRepo->method('findAllSortedByVolume')->willReturn([]);

        $this->expectException(NoSuitableBoxException::class);
        $this->expectExceptionMessage('No packaging boxes are configured');

        $this->service->findSmallestBoxForProducts($this->sampleProducts());
    }

    public function testThrowsWhenPackerReturnsNull(): void
    {
        $box = TestHelper::createPackaging(1, 5.0, 5.0, 5.0, 20);

        $this->packagingRepo->method('findAllSortedByVolume')->willReturn([$box]);
        $this->cacheRepo->method('findByHash')->willReturn(null);
        $this->cacheRepo->method('saveResult')->willReturn(new PackingCache('hash', null));
        $this->packer->method('findSmallestBox')->willReturn(null);

        $this->expectException(NoSuitableBoxException::class);

        $this->service->findSmallestBoxForProducts($this->sampleProducts());
    }
}
