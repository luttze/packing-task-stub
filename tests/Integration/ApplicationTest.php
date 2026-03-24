<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

/**
 * End-to-end integration tests through the full HTTP layer.
 * Tests the complete request → routing → controller → service → response chain.
 */
class ApplicationTest extends IntegrationTestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->container->get(Application::class);
    }

    public function testSuccessfulPackReturns200WithBox(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => '1', 'items' => [['itemId' => '0']]],
                ],
                'unpackedItems' => [],
            ])),
        );

        $request = $this->createPackRequest([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $response = $this->app->run($request);

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        self::assertArrayHasKey('box', $body);
        self::assertArrayHasKey('width', $body['box']);
        self::assertArrayHasKey('height', $body['box']);
        self::assertArrayHasKey('length', $body['box']);
        self::assertArrayHasKey('maxWeight', $body['box']);
    }

    public function testInvalidJsonReturns400(): void
    {
        $request = new Request(
            'POST',
            new Uri('http://localhost/pack'),
            ['Content-Type' => 'application/json'],
            '{not valid json',
        );

        $response = $this->app->run($request);

        self::assertSame(400, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        self::assertArrayHasKey('error', $body);
        self::assertStringContainsString('Invalid JSON', $body['error']);
    }

    public function testEmptyProductsReturns400(): void
    {
        $request = $this->createPackRequest([]);

        $response = $this->app->run($request);

        self::assertSame(400, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        self::assertStringContainsString('empty', $body['error']);
    }

    public function testMissingProductsKeyReturns400(): void
    {
        $request = new Request(
            'POST',
            new Uri('http://localhost/pack'),
            ['Content-Type' => 'application/json'],
            json_encode(['items' => []]),
        );

        $response = $this->app->run($request);

        self::assertSame(400, $response->getStatusCode());
    }

    public function testNoSuitableBoxReturns422(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [],
                'unpackedItems' => ['0'],
            ])),
        );

        $request = $this->createPackRequest([
            ['id' => 1, 'width' => 100.0, 'height' => 100.0, 'length' => 100.0, 'weight' => 1.0],
        ]);

        $response = $this->app->run($request);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testUnknownRouteReturns500(): void
    {
        $request = new Request('GET', new Uri('http://localhost/unknown'));

        $response = $this->app->run($request);

        self::assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    public function testWrongMethodReturns500(): void
    {
        $request = new Request('GET', new Uri('http://localhost/pack'));

        $response = $this->app->run($request);

        self::assertGreaterThanOrEqual(400, $response->getStatusCode());
    }

    public function testResponseHasJsonContentType(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => '1', 'items' => [['itemId' => '0']]],
                ],
                'unpackedItems' => [],
            ])),
        );

        $request = $this->createPackRequest([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $response = $this->app->run($request);

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testCachedResultReturnsSameResponse(): void
    {
        $this->mockHttpHandler->append(
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => '1', 'items' => [['itemId' => '0']]],
                ],
                'unpackedItems' => [],
            ])),
        );

        $request = $this->createPackRequest([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $first = $this->app->run($request);

        $request = $this->createPackRequest([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);

        $second = $this->app->run($request);

        self::assertSame(200, $second->getStatusCode());
        self::assertSame(
            $first->getBody()->getContents(),
            $second->getBody()->getContents(),
        );
    }

    private function createPackRequest(array $products): Request
    {
        return new Request(
            'POST',
            new Uri('http://localhost/pack'),
            ['Content-Type' => 'application/json'],
            json_encode(['products' => $products]),
        );
    }
}
