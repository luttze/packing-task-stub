<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\BinPacking\PackResponse;
use PHPUnit\Framework\TestCase;

class PackResponseTest extends TestCase
{
    public function testGetItemCountReturnsCorrectCount(): void
    {
        $response = new PackResponse(
            packedContainers: [
                'small' => ['0'],
                'big' => ['0', '1'],
            ],
            unpackedItems: [],
        );

        self::assertSame(1, $response->getItemCount('small'));
        self::assertSame(2, $response->getItemCount('big'));
    }

    public function testGetItemCountReturnsZeroForUnknownContainer(): void
    {
        $response = new PackResponse(
            packedContainers: [],
            unpackedItems: ['0'],
        );

        self::assertSame(0, $response->getItemCount('nonexistent'));
    }

    public function testUnpackedItems(): void
    {
        $response = new PackResponse(
            packedContainers: ['box1' => ['0']],
            unpackedItems: ['1', '2'],
        );

        self::assertSame(['1', '2'], $response->unpackedItems);
    }
}
