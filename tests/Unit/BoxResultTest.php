<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\BoxResult;
use PHPUnit\Framework\TestCase;

class BoxResultTest extends TestCase
{
    public function testFromEntity(): void
    {
        $packaging = TestHelper::createPackaging(1, 5.5, 6.0, 7.5, 30);

        $result = BoxResult::fromEntity($packaging);

        self::assertSame(5.5, $result->width);
        self::assertSame(6.0, $result->height);
        self::assertSame(7.5, $result->length);
        self::assertSame(30.0, $result->maxWeight);
    }

    public function testJsonSerialize(): void
    {
        $result = new BoxResult(1, 5.5, 6.0, 7.5, 30.0);

        $json = json_encode($result);
        $data = json_decode($json, true);

        self::assertSame(1, $data['id']);
        self::assertSame(5.5, $data['width']);
        self::assertEquals(6.0, $data['height']);
        self::assertSame(7.5, $data['length']);
        self::assertEquals(30.0, $data['maxWeight']);
    }
}
