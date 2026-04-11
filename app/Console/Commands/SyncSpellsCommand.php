<?php

namespace App\Console\Commands;

use App\Models\Spell;
use App\Services\Open5eClient;
use Illuminate\Console\Command;

/**
 * Import spells from Open5e v2 API into the local database
 *
 * @example
 * php artisan open5e:sync-spells --document=srd-2024
 * php artisan open5e:sync-spells --all
 */
class SyncSpellsCommand extends Command
{
    protected $signature = 'open5e:sync-spells
                            {--document=srd-2024 : Open5e document key to sync}
                            {--all : Sync all known document keys}';

    protected $description = 'Sync spells from the Open5e v2 API';

    /** @var list<string> */
    private const KNOWN_DOCUMENTS = [
        'srd-2024',
        'srd-2014',
        'deepm',
        'deepmx',
        'a5e-ag',
    ];

    public function handle(Open5eClient $client): int
    {
        $documents = $this->option('all')
            ? self::KNOWN_DOCUMENTS
            : [$this->option('document')];

        foreach ($documents as $documentKey) {
            $this->syncDocument($client, $documentKey);
        }

        return self::SUCCESS;
    }

    private function syncDocument(Open5eClient $client, string $documentKey): void
    {
        $this->info("Syncing spells from [{$documentKey}]...");

        $count = 0;

        foreach ($client->getAllSpells($documentKey) as $data) {
            Spell::updateOrCreate(
                ['slug' => $data['key']],
                [
                    'name' => ['en' => $data['name']],
                    'level_int' => $data['level'] ?? 0,
                    'school' => $data['school']['name'] ?? 'Unknown',
                    'casting_time' => $data['casting_time'] ?? 'Unknown',
                    'range' => $data['range_text'] ?? 'Unknown',
                    'duration' => $data['duration'] ?? 'Unknown',
                    'requires_concentration' => $data['concentration'] ?? false,
                    'can_be_cast_as_ritual' => $data['ritual'] ?? false,
                    'components' => $this->buildComponentsString($data),
                    'desc' => ['en' => $data['desc'] ?? ''],
                    'higher_level' => $data['higher_level'] !== null ? ['en' => $data['higher_level']] : null,
                    'dnd_class' => $this->buildClassString($data),
                    'document_slug' => $data['document']['key'] ?? $documentKey,
                    'document_title' => $data['document']['display_name'] ?? null,
                    'last_synced_at' => now(),
                ],
            );

            $count++;

            if ($count % 50 === 0) {
                $this->output->write("  {$count} spells imported...\r");
            }
        }

        $this->info("  Done: {$count} spells synced from [{$documentKey}].");
    }

    /**
     * Build "V, S, M (component details)" string from v2 boolean fields
     */
    private function buildComponentsString(array $data): string
    {
        $parts = [];

        if ($data['verbal'] ?? false) {
            $parts[] = 'V';
        }

        if ($data['somatic'] ?? false) {
            $parts[] = 'S';
        }

        if ($data['material'] ?? false) {
            $material = 'M';

            if (! empty($data['material_specified'])) {
                $material .= " ({$data['material_specified']})";
            }

            $parts[] = $material;
        }

        return implode(', ', $parts);
    }

    /**
     * Build comma-separated class string from v2 classes array
     */
    private function buildClassString(array $data): ?string
    {
        $classes = $data['classes'] ?? [];

        if (empty($classes)) {
            return null;
        }

        return implode(', ', array_map(fn (array $class) => $class['name'], $classes));
    }
}
