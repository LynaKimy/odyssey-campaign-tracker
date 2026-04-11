<?php

namespace App\Console\Commands;

use App\Models\Monster;
use App\Models\Spell;
use App\Services\DeepLClient;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Batch-translate reference data (monsters, spells) using DeepL API
 *
 * @example
 * php artisan translate:reference-data spells --locale=fr
 * php artisan translate:reference-data monsters --locale=fr --force
 */
class TranslateReferenceDataCommand extends Command
{
    protected $signature = 'translate:reference-data
                            {model : Model to translate (monsters|spells)}
                            {--locale=fr : Target locale}
                            {--force : Re-translate even if translation exists}';

    protected $description = 'Translate reference data (monsters/spells) via DeepL';

    public function handle(DeepLClient $deepl): int
    {
        $model = $this->argument('model');
        $locale = $this->option('locale');

        return match ($model) {
            'spells' => $this->translateSpells($deepl, $locale),
            'monsters' => $this->translateMonsters($deepl, $locale),
            default => $this->invalidModel(),
        };
    }

    private function translateSpells(DeepLClient $deepl, string $locale): int
    {
        $query = Spell::query()
            ->when(! $this->option('force'), function (Builder $q) use ($locale) {
                $q->whereRaw("JSON_EXTRACT(name, ?) IS NULL", ['$."' . $locale . '"']);
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('All spells already translated.');

            return self::SUCCESS;
        }

        $this->info("Translating {$total} spells to [{$locale}]...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(20, function ($spells) use ($deepl, $locale, $bar) {
            $names = $spells->map(fn ($s) => $s->getTranslation('name', 'en'))->toArray();
            $descs = $spells->map(fn ($s) => $s->getTranslation('desc', 'en'))->toArray();
            $higherLevels = $spells->map(fn ($s) => $s->getTranslation('higher_level', 'en'))->toArray();

            $translatedNames = $deepl->translateBatch($names, $locale);

            $nonNullDescs = array_filter($descs, fn ($v) => $v !== null && $v !== '');
            $translatedDescResults = ! empty($nonNullDescs)
                ? $deepl->translateBatch(array_values($nonNullDescs), $locale)
                : [];

            $nonNullHigher = array_filter($higherLevels, fn ($v) => $v !== null);
            $translatedHigher = ! empty($nonNullHigher)
                ? $deepl->translateBatch(array_values($nonNullHigher), $locale)
                : [];

            $descIndex = 0;
            $higherIndex = 0;

            foreach ($spells as $i => $spell) {
                $spell->setTranslation('name', $locale, $translatedNames[$i]);

                if ($descs[$i] !== null && $descs[$i] !== '') {
                    $spell->setTranslation('desc', $locale, $translatedDescResults[$descIndex++]);
                }

                if ($higherLevels[$i] !== null) {
                    $spell->setTranslation('higher_level', $locale, $translatedHigher[$higherIndex++]);
                }

                $spell->save();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$total} spells translated to [{$locale}].");

        return self::SUCCESS;
    }

    private function translateMonsters(DeepLClient $deepl, string $locale): int
    {
        $query = Monster::query()
            ->when(! $this->option('force'), function (Builder $q) use ($locale) {
                $q->whereRaw("JSON_EXTRACT(name, ?) IS NULL", ['$."' . $locale . '"']);
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('All monsters already translated.');

            return self::SUCCESS;
        }

        $this->info("Translating {$total} monsters to [{$locale}]...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(10, function ($monsters) use ($deepl, $locale, $bar) {
            foreach ($monsters as $monster) {
                $this->translateMonster($deepl, $monster, $locale);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$total} monsters translated to [{$locale}].");

        return self::SUCCESS;
    }

    /**
     * Translate a single monster's displayable fields using Spatie translatable
     *
     * @description Collects all translatable texts into a single batch call,
     * then rebuilds structured fields preserving non-translatable metadata.
     */
    private function translateMonster(DeepLClient $deepl, Monster $monster, string $locale): void
    {
        $textsToTranslate = [];
        $map = [];

        // Collect name
        $enName = $monster->getTranslation('name', 'en');

        if ($enName === null) {
            return;
        }

        $map[] = ['type' => 'name', 'index' => count($textsToTranslate)];
        $textsToTranslate[] = $enName;

        // Collect desc
        $enDesc = $monster->getTranslation('desc', 'en');

        if ($enDesc !== null && $enDesc !== '') {
            $map[] = ['type' => 'desc', 'index' => count($textsToTranslate)];
            $textsToTranslate[] = $enDesc;
        }

        // Collect all action-like groups (traits, actions, legendary_actions, etc.)
        $actionGroups = [
            'traits' => $monster->getTranslation('traits', 'en') ?? [],
            'actions' => $monster->getTranslation('actions', 'en') ?? [],
            'legendary_actions' => $monster->getTranslation('legendary_actions', 'en') ?? [],
            'reactions' => $monster->getTranslation('reactions', 'en') ?? [],
            'bonus_actions' => $monster->getTranslation('bonus_actions', 'en') ?? [],
            'special_abilities' => $monster->getTranslation('special_abilities', 'en') ?? [],
        ];

        foreach ($actionGroups as $groupKey => $items) {
            foreach ($items as $itemIndex => $item) {
                if (! empty($item['name'])) {
                    $map[] = ['type' => $groupKey, 'item' => $itemIndex, 'field' => 'name', 'index' => count($textsToTranslate)];
                    $textsToTranslate[] = $item['name'];
                }

                if (! empty($item['desc'])) {
                    $map[] = ['type' => $groupKey, 'item' => $itemIndex, 'field' => 'desc', 'index' => count($textsToTranslate)];
                    $textsToTranslate[] = $item['desc'];
                }
            }
        }

        if (empty($textsToTranslate)) {
            return;
        }

        // Single batch call for all texts of this monster
        $translated = $deepl->translateBatch($textsToTranslate, $locale);

        // Rebuild translated groups starting from EN copies to preserve metadata
        $translatedGroups = [];

        foreach ($actionGroups as $groupKey => $items) {
            if (! empty($items)) {
                $translatedGroups[$groupKey] = $items;
            }
        }

        foreach ($map as $entry) {
            $value = $translated[$entry['index']];

            if ($entry['type'] === 'name') {
                $monster->setTranslation('name', $locale, $value);
            } elseif ($entry['type'] === 'desc') {
                $monster->setTranslation('desc', $locale, $value);
            } else {
                $translatedGroups[$entry['type']][$entry['item']][$entry['field']] = $value;
            }
        }

        foreach ($translatedGroups as $groupKey => $items) {
            $monster->setTranslation($groupKey, $locale, array_values($items));
        }

        $monster->save();
    }

    private function invalidModel(): int
    {
        $this->error('Invalid model. Use "monsters" or "spells".');

        return self::FAILURE;
    }
}
