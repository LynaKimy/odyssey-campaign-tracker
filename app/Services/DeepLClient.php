<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the DeepL translation API
 *
 * @description Supports single and batch translation. Batch mode sends
 * up to 50 texts per API call (DeepL limit). Configure via
 * DEEPL_API_KEY and DEEPL_BASE_URL in .env.
 *
 * @example
 * $client = app(DeepLClient::class);
 * $client->translate('Fireball', 'FR'); // "Boule de feu"
 * $client->translateBatch(['Fireball', 'Magic Missile'], 'FR');
 */
class DeepLClient
{
    private const MAX_BATCH_SIZE = 50;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api-free.deepl.com/v2',
    ) {}

    /**
     * Translate a single text
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'EN'): string
    {
        $results = $this->translateBatch([$text], $targetLang, $sourceLang);

        return $results[0];
    }

    /**
     * Translate multiple texts in one API call (max 50 per request)
     *
     * @param list<string> $texts
     * @return list<string>
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'EN'): array
    {
        if (empty($texts)) {
            return [];
        }

        $results = [];

        foreach (array_chunk($texts, self::MAX_BATCH_SIZE) as $chunk) {
            $response = Http::withHeaders([
                'Authorization' => "DeepL-Auth-Key {$this->apiKey}",
            ])->post("{$this->baseUrl}/translate", [
                'text' => $chunk,
                'target_lang' => strtoupper($targetLang),
                'source_lang' => strtoupper($sourceLang),
            ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    "DeepL API error: {$response->status()} - {$response->body()}"
                );
            }

            $translations = $response->json('translations', []);

            foreach ($translations as $translation) {
                $results[] = $translation['text'];
            }
        }

        return $results;
    }
}
