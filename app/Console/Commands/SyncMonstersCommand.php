<?php

namespace App\Console\Commands;

use App\Enums\Monsters\MonsterSize;
use App\Enums\Monsters\MonsterType;
use App\Models\Monster;
use App\Models\Spell;
use App\Services\Open5eClient;
use Illuminate\Console\Command;

/**
 * Import monsters from Open5e v2 API into the local database
 *
 * @description Syncs creature data and resolves spell URLs to local spell
 * records by extracting slugs. Prefers srd-2024 spells when duplicates exist.
 *
 * @example
 * php artisan open5e:sync-monsters --document=srd-2024
 * php artisan open5e:sync-monsters --all
 */
class SyncMonstersCommand extends Command
{
    protected $signature = 'open5e:sync-monsters
                            {--document=srd-2024 : Open5e document key to sync}
                            {--all : Sync all known document keys}';

    protected $description = 'Sync monsters from the Open5e v2 API';

    /** @var list<string> */
    private const KNOWN_DOCUMENTS = [
        'srd-2024',
        'srd-2014',
        'tob',
        'tob-2023',
        'tob2',
        'tob3',
        'ccdx',
        'a5e-mm',
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
        $this->info("Syncing monsters from [{$documentKey}]...");

        $count = 0;

        foreach ($client->getAllCreatures($documentKey) as $data) {
            $abilities = $data['ability_scores'] ?? [];
            $actions = $data['actions'] ?? [];

            $typeSlug = strtolower($data['type']['name'] ?? 'unknown');
            $sizeSlug = strtolower($data['size']['name'] ?? 'medium');
            $alignment = $this->normalizeAlignment($data['alignment'] ?? null);

            $fingerprint = $this->buildFingerprint([
                'type'         => $typeSlug,
                'size'         => $sizeSlug,
                'alignment'    => $alignment,
                'cr'           => $data['challenge_rating_decimal'] ?? '',
                'strength'     => $abilities['strength'] ?? '',
                'dexterity'    => $abilities['dexterity'] ?? '',
                'constitution' => $abilities['constitution'] ?? '',
            ]);

            $monster = Monster::updateOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'name' => ['en' => $data['name']],
                    'size' => MonsterSize::tryFrom($sizeSlug) ?? MonsterSize::Medium,
                    'type' => MonsterType::tryFrom($typeSlug) ?? MonsterType::Unknown,
                    'alignment' => $alignment,
                    'desc' => ! empty($data['desc']) ? ['en' => $data['desc']] : null,
                    'challenge_rating' => $data['challenge_rating_text'] ?? '0',
                    'cr' => $data['challenge_rating_decimal'] ?? null,
                    'armor_class' => $data['armor_class'] ?? 10,
                    'hit_points' => $data['hit_points'] ?? 1,
                    'hit_dice' => $data['hit_dice'] ?? null,
                    'speed' => $data['speed'] ?? null,
                    'strength' => $abilities['strength'] ?? null,
                    'dexterity' => $abilities['dexterity'] ?? null,
                    'constitution' => $abilities['constitution'] ?? null,
                    'intelligence' => $abilities['intelligence'] ?? null,
                    'wisdom' => $abilities['wisdom'] ?? null,
                    'charisma' => $abilities['charisma'] ?? null,
                    'saving_throws' => $data['saving_throws'] ?? null,
                    'traits' => ['en' => $data['traits'] ?? []],
                    'actions' => ['en' => $this->filterByType($actions, 'ACTION')],
                    'legendary_actions' => ['en' => $this->filterByType($actions, 'LEGENDARY_ACTION')],
                    'reactions' => ['en' => $this->filterByType($actions, 'REACTION')],
                    'bonus_actions' => ['en' => $this->filterByType($actions, 'BONUS_ACTION')],
                ],
            );

            $this->syncMonsterSpells($monster, $data['spells'] ?? []);

            $count++;

            if ($count % 50 === 0) {
                $this->output->write("  {$count} monsters imported...\r");
            }
        }

        $this->info("  Done: {$count} monsters synced from [{$documentKey}].");
    }

    /**
     * Filter Open5e v2 actions array by action_type
     */
    private function filterByType(array $actions, string $type): array
    {
        return array_values(array_filter(
            $actions,
            fn (array $action) => ($action['action_type'] ?? '') === $type,
        ));
    }

    /**
     * Sync monster-spell pivot from Open5e spell URLs
     *
     * @description Extracts slugs from API URLs, resolves them against the
     * local spells table (preferring srd-2024), and syncs the pivot.
     *
     * @param  list<string>  $spellUrls
     */
    private function syncMonsterSpells(Monster $monster, array $spellUrls): void
    {
        if (empty($spellUrls)) {
            $monster->spells()->detach();
            return;
        }

        $spellIds = [];

        foreach ($spellUrls as $url) {
            $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            $slug = basename($path);

            if ($slug === '') {
                continue;
            }

            $spell = Spell::where('slug', 'LIKE', "%{$slug}%")
                ->orderByRaw("CASE WHEN document_slug = 'srd-2024' THEN 0 ELSE 1 END")
                ->first();

            if ($spell) {
                $spellIds[] = $spell->id;
            }
        }

        $monster->spells()->sync($spellIds);
    }

    /**
     * Build the fingerprint used to deduplicate monsters across sources
     *
     * @description Mirrors ParsePdfMonstersXMLCommand::buildFingerprint so a
     * monster imported from the SRD PDF and from Open5e resolves to the same
     * identity when type/size/alignment/CR/STR/DEX/CON match.
     */
    private function buildFingerprint(array $monster): string
    {
        return md5(implode('|', [
            $monster['type'] ?? '',
            $monster['size'] ?? '',
            $monster['alignment'] ?? '',
            $monster['cr'] ?? '',
            $monster['strength'] ?? '',
            $monster['dexterity'] ?? '',
            $monster['constitution'] ?? '',
        ]));
    }

    /**
     * Normalize an Open5e alignment string to the PDF parser slug format
     *
     * @example "lawful good" → "lawful-good", "unaligned" → "unaligned"
     */
    private function normalizeAlignment(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return strtolower(str_replace(' ', '-', trim($value)));
    }
}
