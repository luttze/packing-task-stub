<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProductInput;
use App\Exception\ValidationException;
class InputValidator
{
    private const MAX_DIMENSION = 1000.0;
    private const MAX_WEIGHT = 10000.0;
    private const MAX_PRODUCTS = 1000;

    /**
     * @param array<mixed> $rawProducts
     * @return ProductInput[]
     * @throws ValidationException
     */
    public function validateAndMapProducts(array $rawProducts): array
    {
        if ($rawProducts === []) {
            throw new ValidationException('Product list cannot be empty');
        }

        if (count($rawProducts) > self::MAX_PRODUCTS) {
            throw new ValidationException(
                sprintf('Too many products: %d (max %d)', count($rawProducts), self::MAX_PRODUCTS)
            );
        }

        $products = [];

        foreach ($rawProducts as $index => $item) {
            $products[] = $this->validateProduct($item, $index);
        }

        return $products;
    }

    /**
     * @param mixed $item
     * @throws ValidationException
     */
    private function validateProduct(mixed $item, int $index): ProductInput
    {
        if (!is_array($item)) {
            throw new ValidationException(
                sprintf('Product at index %d must be an object', $index)
            );
        }

        $required = ['id', 'width', 'height', 'length', 'weight'];

        foreach ($required as $field) {
            if (!array_key_exists($field, $item)) {
                throw new ValidationException(
                    sprintf('Product at index %d is missing required field "%s"', $index, $field)
                );
            }
        }

        $id = filter_var($item['id'], FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            throw new ValidationException(
                sprintf('Product at index %d has invalid id', $index)
            );
        }

        $width = $this->validateDimension($item['width'], 'width', $index);
        $height = $this->validateDimension($item['height'], 'height', $index);
        $length = $this->validateDimension($item['length'], 'length', $index);
        $weight = $this->validateWeight($item['weight'], $index);

        return new ProductInput($id, $width, $height, $length, $weight);
    }

    private function validateDimension(mixed $value, string $field, int $index): float
    {
        if (!is_numeric($value)) {
            throw new ValidationException(
                sprintf('Product at index %d has non-numeric %s', $index, $field)
            );
        }

        $floatVal = (float) $value;

        if ($floatVal <= 0) {
            throw new ValidationException(
                sprintf('Product at index %d has %s that must be greater than 0', $index, $field)
            );
        }

        if ($floatVal > self::MAX_DIMENSION) {
            throw new ValidationException(
                sprintf('Product at index %d has %s exceeding maximum of %s', $index, $field, self::MAX_DIMENSION)
            );
        }

        return $floatVal;
    }

    private function validateWeight(mixed $value, int $index): float
    {
        if (!is_numeric($value)) {
            throw new ValidationException(
                sprintf('Product at index %d has non-numeric weight', $index)
            );
        }

        $floatVal = (float) $value;

        if ($floatVal <= 0) {
            throw new ValidationException(
                sprintf('Product at index %d has weight that must be greater than 0', $index)
            );
        }

        if ($floatVal > self::MAX_WEIGHT) {
            throw new ValidationException(
                sprintf('Product at index %d has weight exceeding maximum of %s', $index, self::MAX_WEIGHT)
            );
        }

        return $floatVal;
    }
}
