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
            $translatedDescs = $deepl->translateBatch($descs, $locale);

            $nonNullHigher = array_filter($higherLevels, fn ($v) => $v !== null);
            $translatedHigher = ! empty($nonNullHigher)
                ? $deepl->translateBatch(array_values($nonNullHigher), $locale)
                : [];

            $higherIndex = 0;

            foreach ($spells as $i => $spell) {
                $spell->setTranslation('name', $locale, $translatedNames[$i]);
                $spell->setTranslation('desc', $locale, $translatedDescs[$i]);

                if ($spell->getTranslation('higher_level', 'en') !== null) {
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
     */
    private function translateMonster(DeepLClient $deepl, Monster $monster, string $locale): void
    {
        $textsToTranslate = [$monster->getTranslation('name', 'en')];
        $map = [['type' => 'name', 'index' => 0]];

        // Collect trait names and descriptions
        $traits = $monster->getTranslation('traits', 'en') ?? [];
        foreach ($traits as $trait) {
            $map[] = ['type' => 'trait_name', 'index' => count($textsToTranslate)];
            $textsToTranslate[] = $trait['name'];
            $map[] = ['type' => 'trait_desc', 'index' => count($textsToTranslate)];
            $textsToTranslate[] = $trait['desc'];
        }

        // Collect action names and descriptions
        $actions = $monster->getTranslation('actions', 'en') ?? [];
        foreach ($actions as $action) {
            $map[] = ['type' => 'action_name', 'index' => count($textsToTranslate)];
            $textsToTranslate[] = $action['name'];
            $map[] = ['type' => 'action_desc', 'index' => count($textsToTranslate)];
            $textsToTranslate[] = $action['desc'];
        }

        // Single batch call for all texts of this monster
        $translated = $deepl->translateBatch($textsToTranslate, $locale);

        // Set translated name
        $monster->setTranslation('name', $locale, $translated[0]);

        // Rebuild translated traits
        $translatedTraits = [];
        $traitIndex = 0;
        $translatedActions = [];
        $actionIndex = 0;

        foreach ($map as $entry) {
            $value = $translated[$entry['index']];

            match ($entry['type']) {
                'trait_name' => $translatedTraits[$traitIndex]['name'] = $value,
                'trait_desc' => $translatedTraits[$traitIndex++]['desc'] = $value,
                'action_name' => $translatedActions[$actionIndex]['name'] = $value,
                'action_desc' => $translatedActions[$actionIndex++]['desc'] = $value,
                default => null,
            };
        }

        if (! empty($translatedTraits)) {
            $monster->setTranslation('traits', $locale, $translatedTraits);
        }

        if (! empty($translatedActions)) {
            $monster->setTranslation('actions', $locale, $translatedActions);
        }

        $monster->save();
    }

    private function invalidModel(): int
    {
        $this->error('Invalid model. Use "monsters" or "spells".');

        return self::FAILURE;
    }
}
