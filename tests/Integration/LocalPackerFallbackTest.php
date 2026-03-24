<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Dto\BoxResult;
use App\Exception\NoSuitableBoxException;
use App\Service\PackingService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the fallback behavior when the binpacking API is unavailable.
 * Verifies that LocalPacker produces reasonable results.
 */
class LocalPackerFallbackTest extends IntegrationTestCase
{
    private PackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(PackingService::class);
    }

    public function testFallsBackOnConnectionError(): void
    {
        $this->mockHttpHandler->append(
            new ConnectException('Connection refused', new Request('POST', 'http://api')),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testFallsBackOnTimeout(): void
    {
        $this->mockHttpHandler->append(
            new ConnectException('Connection timed out', new Request('POST', 'http://api')),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testFallsBackOnApiError(): void
    {
        $this->mockHttpHandler->append(
            new Response(500, [], 'Internal Server Error'),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        self::assertInstanceOf(BoxResult::class, $result);
    }

    public function testLocalPackerRejectsOversizedProduct(): void
    {
        $this->mockHttpHandler->append(
            new ConnectException('Connection refused', new Request('POST', 'http://api')),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 50.0, 'height' => 50.0, 'length' => 50.0, 'weight' => 1.0],
        ]);

        $this->expectException(NoSuitableBoxException::class);
        $this->service->findSmallestBoxForProducts($products);
    }

    public function testLocalPackerRejectsOverweightProduct(): void
    {
        $this->mockHttpHandler->append(
            new ConnectException('Connection refused', new Request('POST', 'http://api')),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 999.0],
        ]);

        $this->expectException(NoSuitableBoxException::class);
        $this->service->findSmallestBoxForProducts($products);
    }

    public function testLocalPackerSelectsSmallestFittingBox(): void
    {
        $this->mockHttpHandler->append(
            new ConnectException('Connection refused', new Request('POST', 'http://api')),
        );

        $products = $this->validateProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $result = $this->service->findSmallestBoxForProducts($products);

        // Smallest box by volume is 2.5 * 3.0 * 1.0 = 7.5
        $volume = $result->width * $result->height * $result->length;
        self::assertSame(7.5, $volume);
    }
}
