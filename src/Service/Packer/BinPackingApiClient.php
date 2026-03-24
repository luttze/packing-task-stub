<?php

declare(strict_types=1);

namespace App\Service\Packer;

use App\Dto\BinPacking\PackRequest;
use App\Dto\BinPacking\PackResponse;
use App\Exception\PackingException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class BinPackingApiClient
{
    private const API_URL = 'https://binpacking.janedbal.cz/api/v1/pack';

    public function __construct(
        private readonly ClientInterface $httpClient,
    ) {}

    /**
     * @throws PackingException On network or API errors
     */
    public function sendPackRequest(PackRequest $request): PackResponse
    {
        try {
            $httpResponse = $this->httpClient->request('POST', self::API_URL, [
                'json' => $request->jsonSerialize(),
                'timeout' => 15,
                'connect_timeout' => 5,
            ]);

            return $this->parseResponse($httpResponse->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw new PackingException('Bin packing API unavailable: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws PackingException On invalid or error response
     */
    private function parseResponse(string $body): PackResponse
    {
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new PackingException('Invalid API response: not JSON');
        }

        if (isset($data['error'])) {
            $message = $data['message'] ?? $data['error'];
            throw new PackingException('API error: ' . $message);
        }

        if (!isset($data['packedContainers']) || !is_array($data['packedContainers'])) {
            throw new PackingException('Invalid API response: missing packedContainers');
        }

        $packed = [];

        foreach ($data['packedContainers'] as $container) {
            if (!isset($container['containerId'], $container['items']) || !is_array($container['items'])) {
                throw new PackingException('Invalid API response: malformed packedContainers entry');
            }

            $itemIds = array_map(
                static fn(array $item) => (string) ($item['itemId'] ?? ''),
                $container['items'],
            );

            $packed[(string) $container['containerId']] = $itemIds;
        }

        $unpacked = [];

        if (isset($data['unpackedItems']) && \is_array($data['unpackedItems'])) {
            $unpacked = array_map('strval', $data['unpackedItems']);
        }

        return new PackResponse($packed, $unpacked);
    }
}
