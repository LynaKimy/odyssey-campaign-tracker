<?php

namespace App\Services;

use Generator;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for the Open5e v2 API (api.open5e.com/v2)
 *
 * @description Wraps Laravel's HTTP client for paginated access to
 * creatures, spells, and other reference data. Uses generators for
 * memory-efficient batch processing.
 *
 * @example
 * $client = new Open5eClient();
 * foreach ($client->getAllCreatures('srd-2024') as $creature) {
 *     // process one creature at a time
 * }
 */
class Open5eClient
{
    private const BASE_URL = 'https://api.open5e.com/v2';

    private const PAGE_SIZE = 50;

    /**
     * Fetch a single page of creatures
     *
     * @return array{count: int, next: ?string, results: list<array>}
     */
    public function getCreatures(string $documentKey, int $page): array
    {
        $response = Http::baseUrl(self::BASE_URL)
            ->get('/creatures/', [
                'document__key' => $documentKey,
                'page' => $page,
                'format' => 'json',
            ]);


        $response->throw();

        return $response->json();
    }

    /**
     * Fetch a single page of spells
     *
     * @return array{count: int, next: ?string, results: list<array>}
     */
    public function getSpells(string $documentKey, int $page): array
    {
        $response = Http::baseUrl(self::BASE_URL)
            ->get('/spells/', [
                'document__key' => $documentKey,
                'page' => $page,
                'format' => 'json',
            ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Yield all creatures for a document key, page by page
     *
     * @return Generator<int, array>
     */
    public function getAllCreatures(string $documentKey): Generator
    {
        $page = 1;

        do {
            $data = $this->getCreatures($documentKey, $page);

            foreach ($data['results'] as $creature) {
                yield $creature;
            }

            $page++;
        } while ($data['next'] !== null);
    }

    /**
     * Yield all spells for a document key, page by page
     *
     * @return Generator<int, array>
     */
    public function getAllSpells(string $documentKey): Generator
    {
        $page = 1;

        do {
            $data = $this->getSpells($documentKey, $page);

            foreach ($data['results'] as $spell) {
                yield $spell;
            }

            $page++;
        } while ($data['next'] !== null);
    }
}
