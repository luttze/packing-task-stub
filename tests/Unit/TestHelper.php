<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Packaging;
use ReflectionProperty;

class TestHelper
{
    public static function createPackaging(int $id, float $width, float $height, float $length, float $maxWeight): Packaging
    {
        $box = new Packaging($width, $height, $length, $maxWeight);

        $ref = new ReflectionProperty(Packaging::class, 'id');
        $ref->setValue($box, $id);

        return $box;
    }
}
