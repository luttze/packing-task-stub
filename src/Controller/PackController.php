<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ValidationException;
use App\Service\InputValidator;
use App\Service\PackingService;
use GuzzleHttp\Psr7\Response;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PackController
{
    public function __construct(
        private readonly PackingService $packingService,
        private readonly InputValidator $validator,
    ) {}

    public function handlePackRequest(RequestInterface $request): ResponseInterface
    {
        $rawProducts = $this->parseRequestBody($request);
        $products = $this->validator->validateAndMapProducts($rawProducts);
        $result = $this->packingService->findSmallestBoxForProducts($products);

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['box' => $result], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return array<mixed>
     * @throws ValidationException
     */
    private function parseRequestBody(RequestInterface $request): array
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ValidationException('Invalid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!isset($data['products']) || !\is_array($data['products'])) {
            throw new ValidationException('Request must contain a "products" array');
        }

        return $data['products'];
    }
}
