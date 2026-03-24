<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Dto\ProductInput;
use App\Exception\ValidationException;
use App\Service\InputValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InputValidatorTest extends TestCase
{
    private InputValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new InputValidator();
    }

    public function testValidSingleProduct(): void
    {
        $result = $this->validator->validateAndMapProducts([
            ['id' => 1, 'width' => 2.0, 'height' => 3.0, 'length' => 4.0, 'weight' => 5.0],
        ]);

        self::assertCount(1, $result);
        self::assertInstanceOf(ProductInput::class, $result[0]);
        self::assertSame(1, $result[0]->id);
        self::assertSame(2.0, $result[0]->width);
        self::assertSame(3.0, $result[0]->height);
        self::assertSame(4.0, $result[0]->length);
        self::assertSame(5.0, $result[0]->weight);
    }

    public function testValidMultipleProducts(): void
    {
        $result = $this->validator->validateAndMapProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
            ['id' => 2, 'width' => 2.0, 'height' => 2.0, 'length' => 2.0, 'weight' => 2.0],
        ]);

        self::assertCount(2, $result);
    }

    public function testEmptyProducts(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot be empty');

        $this->validator->validateAndMapProducts([]);
    }

    public function testProductNotObject(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be an object');

        $this->validator->validateAndMapProducts(['not_an_object']);
    }

    #[DataProvider('missingFieldProvider')]
    public function testMissingRequiredField(string $missingField): void
    {
        $product = ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0];
        unset($product[$missingField]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($missingField);

        $this->validator->validateAndMapProducts([$product]);
    }

    public static function missingFieldProvider(): array
    {
        return [
            'missing id' => ['id'],
            'missing width' => ['width'],
            'missing height' => ['height'],
            'missing length' => ['length'],
            'missing weight' => ['weight'],
        ];
    }

    public function testInvalidIdZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('invalid id');

        $this->validator->validateAndMapProducts([
            ['id' => 0, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);
    }

    public function testInvalidIdNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('invalid id');

        $this->validator->validateAndMapProducts([
            ['id' => -5, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);
    }

    public function testInvalidIdString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('invalid id');

        $this->validator->validateAndMapProducts([
            ['id' => 'abc', 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);
    }

    #[DataProvider('negativeDimensionProvider')]
    public function testNegativeDimension(string $field): void
    {
        $product = ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0];
        $product[$field] = -1.0;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('greater than 0');

        $this->validator->validateAndMapProducts([$product]);
    }

    public static function negativeDimensionProvider(): array
    {
        return [
            'negative width' => ['width'],
            'negative height' => ['height'],
            'negative length' => ['length'],
            'negative weight' => ['weight'],
        ];
    }

    #[DataProvider('zeroDimensionProvider')]
    public function testZeroDimension(string $field): void
    {
        $product = ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0];
        $product[$field] = 0;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('greater than 0');

        $this->validator->validateAndMapProducts([$product]);
    }

    public static function zeroDimensionProvider(): array
    {
        return [
            'zero width' => ['width'],
            'zero height' => ['height'],
            'zero length' => ['length'],
            'zero weight' => ['weight'],
        ];
    }

    public function testNonNumericDimension(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('non-numeric');

        $this->validator->validateAndMapProducts([
            ['id' => 1, 'width' => 'big', 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);
    }

    public function testExceedingMaxDimension(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeding maximum');

        $this->validator->validateAndMapProducts([
            ['id' => 1, 'width' => 9999.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0],
        ]);
    }

    public function testExceedingMaxWeight(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeding maximum');

        $this->validator->validateAndMapProducts([
            ['id' => 1, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 99999.0],
        ]);
    }

    public function testTooManyProducts(): void
    {
        $products = [];
        for ($i = 1; $i <= 1001; $i++) {
            $products[] = ['id' => $i, 'width' => 1.0, 'height' => 1.0, 'length' => 1.0, 'weight' => 1.0];
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Too many products');

        $this->validator->validateAndMapProducts($products);
    }

    public function testIntegerDimensionsAccepted(): void
    {
        $result = $this->validator->validateAndMapProducts([
            ['id' => 1, 'width' => 2, 'height' => 3, 'length' => 4, 'weight' => 5],
        ]);

        self::assertSame(2.0, $result[0]->width);
    }
}
