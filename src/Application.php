<?php

declare(strict_types=1);

namespace App;

use App\Controller\PackController;
use App\Exception\NoSuitableBoxException;
use App\Exception\PackingException;
use App\Exception\ValidationException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Application
{
    public function __construct(
        private readonly PackController $controller,
    ) {}

    public function run(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->route($request);
        } catch (ValidationException $e) {
            return $this->errorResponse(400, $e->getMessage());
        } catch (NoSuitableBoxException $e) {
            return $this->errorResponse(422, $e->getMessage());
        } catch (PackingException) {
            return $this->errorResponse(503, 'Packing service temporarily unavailable');
        } catch (\Throwable) {
            return $this->errorResponse(500, 'Internal server error');
        }
    }

    private function route(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST' && $request->getUri()->getPath() === '/pack') {
            return $this->controller->handlePackRequest($request);
        }

        throw new RuntimeException('Not found');
    }

    private function errorResponse(int $status, string $message): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => $message], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }
}
