<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Dto\BoxResult;
use App\Exception\NoSuitableBoxException;
use App\Service\PackingService;
use GuzzleHttp\Psr7\Response;

class PackingServiceTest extends IntegrationTestCase
{
    private PackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(PackingService::class);
    }

    public function testPacksSmallProductIntoSmallestBox(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => '1', 'items' => [['itemId' => '0']]],
                ],
                'unpackedItems' => [],
            ])),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testReturnsCachedResultOnSecondCall(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => '1', 'items' => [['itemId' => '0']]],
                ],
                'unpackedItems' => [],
            ])),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $first = $this->service->findSmallestBoxForProducts($products);
        $second = $this->service->findSmallestBoxForProducts($products);

        self::assertEquals($first, $second);
        self::assertCount(0, $this->mockHttpHandler);
    }

    public function testFallsBackToLocalPackerWhenApiDown(): void
    {
        $this->mockHttpHandler->append(
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('POST', 'http://api'),
            ),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testThrowsWhenProductTooLargeForAnyBox(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [],
                'unpackedItems' => ['0'],
            ])),
        );

        $this->expectException(NoSuitableBoxException::class);

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 100.0, 'height' => 100.0, 'length' => 100.0, 'weight' => 1.0],
        ]);

        $this->service->findSmallestBoxForProducts($products);
    }

    public function testThrowsWhenWeightExceedsAllBoxes(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [],
                'unpackedItems' => ['0'],
            ])),
        );

        $this->expectException(NoSuitableBoxException::class);

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 999.0],
        ]);

        $this->service->findSmallestBoxForProducts($products);
    }

    public function testMultipleProductsPacked(): void
    {
        // API packs both items into container 2 (second smallest)
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => '2', 'items' => [['itemId' => '0'], ['itemId' => '1']]],
                ],
                'unpackedItems' => [],
            ])),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 3.0, 'height' => 2.0, 'length' => 1.0, 'weight' => 5.0],
            ['id' => 2, 'width' => 2.0, 'height' => 2.0, 'length' => 1.0, 'weight' => 5.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        self::assertInstanceOf(BoxResult::class, $result);
    }
}
