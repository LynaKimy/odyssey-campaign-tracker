<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use App\Models\Monster;

/**
 * Parse monsters from the French or English SRD PDF using pdftohtml XML output.
 *
 * @example
 * php artisan srd:sync-monsters-xml --pdf=/path/to/FR_SRD.pdf --lang=fr
 * php artisan srd:sync-monsters-xml --pdf=/path/to/EN_SRD.pdf --lang=en --from=272 --to=380
 */
class ParsePdfMonstersXMLCommand extends Command
{
    protected $signature = 'srd:sync-monsters-xml
                            {--pdf=     : Absolute path to the SRD PDF file}
                            {--lang=    : Language of the PDF (fr|en)}
                            {--from=272 : First page (inclusive) of the monster section}
                            {--to=380   : Last page (inclusive) of the monster section}';

    protected $description = 'Parse monsters from the SRD PDF (FR or EN) and dump structured data';

    // -------------------------------------------------------------------------
    // Runtime state
    // -------------------------------------------------------------------------

    protected $outputarray = [];
    protected $fontIndex = [];
    protected $lang = 'fr';

    // -------------------------------------------------------------------------
    // Font roles → ID, per language
    // -------------------------------------------------------------------------

    private const FONT_CONFIG = [
        'fr' => [
            'monster_name'     => 4,
            'monster_type'     => 5,
            'stats'            => 6,
            'ability_initial'  => 9,
            'ability_suffix'   => 10,
            'ability_values'   => 11,
            'ability_negative' => 12,
            'section_header'   => 13,
            'capability_name'  => 14,
            'capability_body'  => 15,
        ],
        'en' => [
            'monster_name'     => 4,
            'monster_type'     => 5,
            'stats'            => 6,
            'ability_initial'  => 9,
            'ability_suffix'   => 10,
            'ability_values'   => 29,
            'ability_negative' => 12,
            'section_header'   => 13,
            'capability_name'  => 14,
            'capability_body'  => 15,
        ],
    ];

    // -------------------------------------------------------------------------
    // Stats label → key, per language
    // -------------------------------------------------------------------------

    private const STATS_MAP = [
        'fr' => [
            'CA'          => 'armor_class',
            'Initiative'  => 'initiative',
            'Pv'          => 'hit_points',
            'Vitesse'     => 'speed',
            'Compétences' => 'skills',
            'Résistances' => 'resistances',
            'Immunités'   => 'immunities',
            'Sens'        => 'senses',
            'Langues'     => 'languages',
            'FP'          => 'challenge_rating',
        ],
        'en' => [
            'AC'          => 'armor_class',
            'Initiative'  => 'initiative',
            'HP'          => 'hit_points',
            'Speed'       => 'speed',
            'Skills'      => 'skills',
            'Resistances' => 'resistances',
            'Immunities'  => 'immunities',
            'Senses'      => 'senses',
            'Languages'   => 'languages',
            'CR'          => 'challenge_rating',
        ],
    ];

    // -------------------------------------------------------------------------
    // Section header label → key, per language
    // -------------------------------------------------------------------------

    private const SECTION_HEADERS = [
        'fr' => [
            'Traits'              => 'traits',
            'Actions'             => 'actions',
            'Actions Légendaires' => 'legendary_actions',
            'Actions Bonus'       => 'bonus_actions',
            'Réactions'           => 'reactions',
        ],
        'en' => [
            'Traits'            => 'traits',
            'Actions'           => 'actions',
            'Legendary Actions' => 'legendary_actions',
            'Bonus Actions'     => 'bonus_actions',
            'Reactions'         => 'reactions',
        ],
    ];

    // -------------------------------------------------------------------------
    // Ability abbreviations per language
    // -------------------------------------------------------------------------

    private const ABILITY_KEYS = [
        'fr' => [
            'For' => 'strength',
            'Dex' => 'dexterity',
            'Con' => 'constitution',
            'Int' => 'intelligence',
            'Sag' => 'wisdom',
            'Cha' => 'charisma',
        ],
        'en' => [
            'Str' => 'strength',
            'Dex' => 'dexterity',
            'Con' => 'constitution',
            'Int' => 'intelligence',
            'Wis' => 'wisdom',
            'Cha' => 'charisma',
        ],
    ];

    // -------------------------------------------------------------------------
    // Type normalisation per language → slug
    // -------------------------------------------------------------------------

    private const TYPE_MAP = [
        'fr' => [
            'Aberration'            => 'aberration',
            'Bête'                  => 'beast',
            'Céleste'               => 'celestial',
            'Artificiel'            => 'construct',
            'Dragon'                => 'dragon',
            'Élémentaire'           => 'elemental',
            'Fée'                   => 'fey',
            'Fiélon'                => 'fiend',
            'Géant'                 => 'giant',
            'Humanoïde'             => 'humanoid',
            'Monstruosité'          => 'monstrosity',
            'Vase'                  => 'ooze',
            'Plante'                => 'plant',
            'Mort-vivant'           => 'undead',
        ],
        'en' => [
            'Aberration'  => 'aberration',
            'Beast'       => 'beast',
            'Celestial'   => 'celestial',
            'Construct'   => 'construct',
            'Dragon'      => 'dragon',
            'Elemental'   => 'elemental',
            'Fey'         => 'fey',
            'Fiend'       => 'fiend',
            'Giant'       => 'giant',
            'Humanoid'    => 'humanoid',
            'Monstrosity' => 'monstrosity',
            'Ooze'        => 'ooze',
            'Plant'       => 'plant',
            'Undead'      => 'undead',
        ],
    ];

    // -------------------------------------------------------------------------
    // Size normalisation per language → slug
    // -------------------------------------------------------------------------

    private const SIZE_MAP = [
        'fr' => [
            'de taille TP'  => 'tiny',
            'de taille P'   => 'small',
            'de taille M'   => 'medium',
            'de taille G'   => 'large',
            'de taille TG'  => 'huge',
            'de taille Gig' => 'gargantuan',
        ],
        'en' => [
            'Tiny'       => 'tiny',
            'Small'      => 'small',
            'Medium'     => 'medium',
            'Large'      => 'large',
            'Huge'       => 'huge',
            'Gargantuan' => 'gargantuan',
        ],
    ];

    // -------------------------------------------------------------------------
    // Alignment normalisation per language → slug
    // -------------------------------------------------------------------------

    private const ALIGNMENT_MAP = [
        'fr' => [
            'Loyal Bon'          => 'lawful-good',
            'Loyale Bonne'       => 'lawful-good',
            'Neutre Bon'         => 'neutral-good',
            'Neutre Bonne'       => 'neutral-good',
            'Chaotique Bon'      => 'chaotic-good',
            'Chaotique Bonne'    => 'chaotic-good',
            'Loyal Neutre'       => 'lawful-neutral',
            'Loyale Neutre'      => 'lawful-neutral',
            'Neutre'             => 'neutral',
            'Chaotique Neutre'   => 'chaotic-neutral',
            'Loyal Mauvais'      => 'lawful-evil',
            'Loyale Mauvaise'    => 'lawful-evil',
            'Neutre Mauvais'     => 'neutral-evil',
            'Neutre Mauvaise'    => 'neutral-evil',
            'Chaotique Mauvais'  => 'chaotic-evil',
            'Chaotique Mauvaise' => 'chaotic-evil',
            'Sans alignement'    => 'unaligned',
            'non alignée'        => 'unaligned',
            'Non alignée'        => 'unaligned',
            'non aligné'         => 'unaligned',
            'Non aligné'         => 'unaligned',
            'Tout alignement'    => 'any',
        ],
        'en' => [
            'Lawful Good'     => 'lawful-good',
            'Neutral Good'    => 'neutral-good',
            'Chaotic Good'    => 'chaotic-good',
            'Lawful Neutral'  => 'lawful-neutral',
            'Neutral'         => 'neutral',
            'Chaotic Neutral' => 'chaotic-neutral',
            'Lawful Evil'     => 'lawful-evil',
            'Neutral Evil'    => 'neutral-evil',
            'Chaotic Evil'    => 'chaotic-evil',
            'Unaligned'       => 'unaligned',
            'Any Alignment'   => 'any',
        ],
    ];

    // Lines to discard (page footers etc.)
    private const NOISE_LINES = [
        'Document de Référence du Système 5.2.1',
    ];

    // =========================================================================
    // Entry point
    // =========================================================================

    public function handle(): int
    {
        $pdfPath  = (string) $this->option('pdf');
        $lang     = (string) $this->option('lang');
        $fromPage = (int)    $this->option('from');
        $toPage   = (int)    $this->option('to');

        // --- Validate --------------------------------------------------------

        if ($pdfPath === '' || !is_file($pdfPath)) {
            $this->error("PDF file not found: [{$pdfPath}]");
            return self::FAILURE;
        }

        if (!in_array($lang, ['fr', 'en'], true)) {
            $this->error("Invalid lang: [{$lang}]. Must be 'fr' or 'en'");
            return self::FAILURE;
        }

        if ($fromPage < 1 || $toPage < $fromPage) {
            $this->error("Invalid page range: --from={$fromPage} --to={$toPage}");
            return self::FAILURE;
        }

        // --- Bootstrap -------------------------------------------------------

        $this->lang      = $lang;
        $this->outputarray    = $this->extractXml($pdfPath, $fromPage, $toPage);
        $this->fontIndex = $this->indexFonts();

        // --- Parse -----------------------------------------------------------

        $monsters = $this->parseAllMonsters();

        $this->syncToDatabase($monsters);
        return self::SUCCESS;
    }

    // =========================================================================
    // XML extraction
    // =========================================================================

    private function extractXml(string $pdfPath, int $from, int $to): array
    {
        $process = new Process([
            'pdftohtml', '-xml', '-nodrm',
            '-f', (string) $from,
            '-l', (string) $to,
            '-stdout',
            $pdfPath,
        ]);

        $process->mustRun();

        return array_values(
            array_filter(
                explode("\n", $process->getOutput()),
                fn (string $line) => trim($line) !== '' && !$this->isNoiseLine($line)
            )
        );
    }

    private function isNoiseLine(string $line): bool
    {
        $text = $this->extractText($line);
        foreach (self::NOISE_LINES as $noise) {
            if (str_contains($text, $noise)) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // Indexing
    // =========================================================================

    private function indexFonts(): array
    {
        $index = [];

        foreach ($this->outputarray as $i => $line) {
            if (str_contains($line, '<text') && preg_match('/font="(\d+)"/', $line, $m)) {
                $index[(int) $m[1]][] = $i;
            }
        }

        return $index;
    }

    // =========================================================================
    // Block slicing
    // =========================================================================

    /**
     * Return [ [start, end], ... ] index pairs for each block delimited by $fontId.
     * The block INCLUDES the marker line itself.
     */
    private function splitByFont(int $fontId): array
    {
        if (!isset($this->fontIndex[$fontId])) {
            return [];
        }

        $markerIndexes = array_values($this->fontIndex[$fontId]);
        $total         = count($this->outputarray);
        $blocks        = [];

        foreach ($markerIndexes as $pos => $lineIndex) {
            $end      = $markerIndexes[$pos + 1] ?? $total;
            $blocks[] = [$lineIndex, $end];
        }

        return $blocks;
    }

    private function resolveBlock(int $start, int $end): array
    {
        return array_slice($this->outputarray, $start, $end - $start);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function getFontId(string $line): ?int
    {
        if (preg_match('/font="(\d+)"/', $line, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function extractText(string $line): string
    {
        preg_match('/<text[^>]+>(.*?)<\/text>/s', $line, $m);
        return trim(strip_tags($m[1] ?? ''));
    }

    private function font(string $role): int
    {
        return self::FONT_CONFIG[$this->lang][$role];
    }

    // =========================================================================
    // Main parser
    // =========================================================================

    private function parseAllMonsters(): array
    {
        $monsters = [];

        foreach ($this->splitByFont($this->font('monster_name')) as [$start, $end]) {
            $block   = $this->resolveBlock($start, $end);
            $monster = $this->parseMonster($block);

            if ($monster !== null) {
                $monsters[] = $monster;
            }
        }

        return $monsters;
    }

    private function parseMonster(array $block): ?array
    {
        // Name — first line of the block is the monster_name font
        $name = null;
        foreach ($block as $line) {
            if ($this->getFontId($line) === $this->font('monster_name')) {
                $name = $this->extractText($line);
                break;
            }
        }

        if (!$name) {
            return null;
        }

        // Raw type line (font monster_type) — "Aberration de taille G, Loyale Mauvaise"
        $rawType = null;
        foreach ($block as $key => $line) {
            if ($this->getFontId($line) === $this->font('monster_type')) {
                $rawType = $this->extractText($line);
                if ($this->getFontId($block[$key + 1]) === $this->font('monster_type')) {
                    $rawType .= ' ' . $this->extractText($block[$key + 1]);
                }
                break;
            }
        }

        $typeBlock = $rawType ? $this->parseTypeBlock($rawType) : [];
        $stats     = $this->parseStats($block);
        $abilities = $this->parseAbilities($block);
        $sections  = $this->parseSections($block);

        $monster = [
            'name' => $name,
            'type' => $typeBlock['type']      ?? null,
            'size' => $typeBlock['size']      ?? null,
            'alignment' => $typeBlock['alignment'] ?? null,
            'challenge_rating' => $stats['challenge_rating'] ?? null,
            'cr' => $this->parseCr($stats['challenge_rating'] ?? null),
            'armor_class' => isset($stats['armor_class'])
                ? (int) $stats['armor_class']
                : null,
            'hit_points' => $this->parseHitPoints($stats['hit_points'] ?? null),
            'hit_dice' => $this->parseHitDice($stats['hit_points'] ?? null),
            'speed' => $this->parseSpeed($stats['speed'] ?? null),
            'strength' => $abilities['strength']['score']     ?? null,
            'dexterity' => $abilities['dexterity']['score']    ?? null,
            'constitution' => $abilities['constitution']['score'] ?? null,
            'intelligence' => $abilities['intelligence']['score'] ?? null,
            'wisdom' => $abilities['wisdom']['score']       ?? null,
            'charisma' => $abilities['charisma']['score']     ?? null,
            'saving_throws' => $this->extractSavingThrows($abilities),
            'traits' => $sections['traits']            ?? [],
            'actions' => $sections['actions']           ?? [],
            'legendary_actions' => $sections['legendary_actions'] ?? [],
            'reactions' => $sections['reactions']         ?? [],
            'bonus_actions' => $sections['bonus_actions']     ?? [],
        ];
        $monster['fingerprint'] = $this->buildFingerprint($monster);
        return $monster;
    }

    // =========================================================================
    // Type / size / alignment parser
    // =========================================================================

    private function parseTypeBlock(string $raw): array
    {
        $result = ['type' => null, 'size' => null, 'alignment' => null];

        if ($this->lang === 'fr') {
            // "Aberration de taille G, Loyale Mauvaise"
            $parts = array_map('trim', explode(',', $raw, 2));

            $result['alignment'] = $this->normalizeAlignment($parts[1] ?? '');

            if (preg_match('/(de taille \w+)/i', $parts[0], $m)) {
                $result['size'] = $this->normalizeSize($m[1]);
            }

            $typePart        = trim(preg_replace('/\s+de taille \w+/i', '', $parts[0]));
            $result['type']  = $this->normalizeType($typePart);

        } else {
            // "Large Monstrosity, Chaotic Evil"
            $parts = array_map('trim', explode(',', $raw, 2));
            $result['alignment'] = $this->normalizeAlignment($parts[1] ?? '');
            $words           = explode(' ', $parts[0], 2);
            $result['size']  = $this->normalizeSize($words[0]);
            $result['type']  = $this->normalizeType($words[1] ?? '');
        }

        if(empty($result['alignment']) || empty($result['size'])) {
            dd($raw);
        }

        return $result;
    }

    private function normalizeType(string $value): ?string
    {
        foreach (self::TYPE_MAP[$this->lang] as $key => $type) {
            if (str_contains($value, $key)) {
                return $type;
            }
        }

        return 'unknown';
    }

    private function normalizeSize(string $value): ?string
    {
        $size = self::SIZE_MAP[$this->lang][trim($value)] ?? null;
        if(is_null($size)) {
            dd('size:' .$value);
        }
        return $size;
    }

    private function normalizeAlignment(string $value): ?string
    {
        return self::ALIGNMENT_MAP[$this->lang][trim($value)] ?? null;
    }

    // =========================================================================
    // Stats parser
    // =========================================================================

    private function parseStats(array $block): array
    {
        $stats    = [];
        $statsMap = self::STATS_MAP[$this->lang];

        foreach ($block as $line) {
            if ($this->getFontId($line) !== $this->font('stats')) {
                continue;
            }

            $text = $this->extractText($line);

            foreach ($statsMap as $label => $key) {
                if (str_starts_with($text, $label)) {
                    $stats[$key] = trim(substr($text, strlen($label)));
                    break;
                }
            }
        }

        return $stats;
    }

    // =========================================================================
    // Stats value parsers
    // =========================================================================

    /**
     * "150 (20d10 + 40)" → 150
     */
    private function parseHitPoints(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        if (preg_match('/^(\d+)/', trim($raw), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * "150 (20d10 + 40)" → "20d10 + 40"
     */
    private function parseHitDice(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (preg_match('/\(([^)]+)\)/', $raw, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * "10 (5 900 PX, ou 7 200 dans son antre ; BM +4)" → 10.0
     * "6 (XP 2,300; PB +3)" → 6.0
     * "1/2" → 0.5
     */
    private function parseCr(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        // Extract just the first token before any parenthesis or space
        if (preg_match('/^(\d+\/\d+|\d+(?:\.\d+)?)/', trim($raw), $m)) {
            if (str_contains($m[1], '/')) {
                [$num, $den] = explode('/', $m[1]);
                return round((float) $num / (float) $den, 2);
            }
            return (float) $m[1];
        }

        return null;
    }

    /**
     * "3 m, nage 12 m" or "30 ft., Fly 60 ft." → array
     */
    private function parseSpeed(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $speed = [];
        $parts = array_map('trim', explode(',', $raw));

        foreach ($parts as $part) {
            $part = trim($part);

            if ($this->lang === 'fr') {
                if (preg_match('/^(\d+)\s*m$/', $part, $m)) {
                    $speed['walk'] = (int) $m[1];
                } elseif (preg_match('/nage\s+(\d+)\s*m/i', $part, $m)) {
                    $speed['swim'] = (int) $m[1];
                } elseif (preg_match('/vol\s+(\d+)\s*m/i', $part, $m)) {
                    $speed['fly'] = (int) $m[1];
                } elseif (preg_match('/fouissement\s+(\d+)\s*m/i', $part, $m)) {
                    $speed['burrow'] = (int) $m[1];
                } elseif (preg_match('/escalade\s+(\d+)\s*m/i', $part, $m)) {
                    $speed['climb'] = (int) $m[1];
                }
            } else {
                if (preg_match('/^(\d+)\s*ft/i', $part, $m)) {
                    $speed['walk'] = (int) $m[1];
                } elseif (preg_match('/swim\s+(\d+)\s*ft/i', $part, $m)) {
                    $speed['swim'] = (int) $m[1];
                } elseif (preg_match('/fly\s+(\d+)\s*ft/i', $part, $m)) {
                    $speed['fly'] = (int) $m[1];
                } elseif (preg_match('/burrow\s+(\d+)\s*ft/i', $part, $m)) {
                    $speed['burrow'] = (int) $m[1];
                } elseif (preg_match('/climb\s+(\d+)\s*ft/i', $part, $m)) {
                    $speed['climb'] = (int) $m[1];
                }
            }
        }

        return $speed;
    }

    // =========================================================================
    // Ability scores parser
    // =========================================================================

    private function parseAbilities(array $block): array
    {
        $abilities     = [];
        $abilityKeys   = self::ABILITY_KEYS[$this->lang];
        $currentAbbr   = null;
        $currentValues = [];

        $structuralFonts = [
            $this->font('monster_name'),
            $this->font('monster_type'),
            $this->font('stats'),
            $this->font('section_header'),
            $this->font('capability_name'),
            $this->font('capability_body'),
        ];

        // Store the current ability and reset state
        $flush = function () use (&$abilities, &$currentAbbr, &$currentValues, $abilityKeys) {
            if ($currentAbbr === null) {
                return;
            }
            $abbr = ucfirst(strtolower($currentAbbr));
            if (isset($abilityKeys[$abbr])) {
                $abilities[$abilityKeys[$abbr]] = [
                    'score' => isset($currentValues[0]) ? (int) $currentValues[0] : null,
                    'mod'   => $currentValues[1] ?? null,
                    'save'  => $currentValues[2] ?? null,
                ];
            }
            $currentAbbr   = null;
            $currentValues = [];
        };

        foreach ($block as $line) {
            $fontId = $this->getFontId($line);
            $text   = trim($this->extractText($line));

            if (empty($text)) {
                continue;
            }

            // Split abbreviation: initial letter (font 9)
            if ($fontId === $this->font('ability_initial')) {
                $flush();
                $currentAbbr = $text;
                continue;
            }

            // Split abbreviation: suffix (font 10), e.g. "IS" → "is"
            if ($fontId === $this->font('ability_suffix') && $currentAbbr !== null) {
                $currentAbbr .= strtolower($text);
                continue;
            }

            // Complete abbreviation in one element (e.g. font="25" for "Con")
            $normalized = ucfirst(strtolower($text));
            if (isset($abilityKeys[$normalized])) {
                $flush();
                $currentAbbr = $normalized;
                continue;
            }

            // Structural font → we have left the ability-score area
            if (in_array($fontId, $structuralFonts, true)) {
                $flush();
                continue;
            }

            // Any other font inside an ability → capture numeric values
            if ($currentAbbr !== null) {
                preg_match_all('/[+−\-]?\d+/', $text, $m);
                foreach ($m[0] as $val) {
                    $currentValues[] = $val;
                }
            }
        }

        $flush();

        return $abilities;
    }

    /**
     * Extract saving throws from parsed abilities (3rd value = save modifier).
     * Only include saves that differ from the ability modifier.
     */
    private function extractSavingThrows(array $abilities): array
    {
        $saves = [];

        foreach ($abilities as $key => $data) {
            if (isset($data['save']) && $data['save'] !== $data['mod']) {
                $saves[$key] = $data['save'];
            }
        }

        return $saves;
    }

    // =========================================================================
    // Sections parser
    // =========================================================================

    private function parseSections(array $block): array
    {
        $sections       = [];
        $currentSection = null;
        $currentLines   = [];
        $sectionHeaders = self::SECTION_HEADERS[$this->lang];

        foreach ($block as $line) {
            $fontId = $this->getFontId($line);
            $text   = $this->extractText($line);

            if ($fontId === $this->font('section_header')) {
                if ($currentSection !== null) {
                    $sections[$currentSection] = $this->parseCapabilities($currentLines);
                }

                $currentSection = $sectionHeaders[$text] ?? strtolower($text);
                $currentLines   = [];
                continue;
            }

            if ($currentSection !== null) {
                $currentLines[] = $line;
            }
        }

        if ($currentSection !== null) {
            $sections[$currentSection] = $this->parseCapabilities($currentLines);
        }

        return $sections;
    }

    // =========================================================================
    // Capabilities parser
    // =========================================================================

    private function parseCapabilities(array $lines): array
    {
        $capabilities = [];
        $current      = null;

        foreach ($lines as $line) {
            $fontId = $this->getFontId($line);
            $text   = $this->extractText($line);

            if (empty($text)) {
                continue;
            }

            if ($fontId === $this->font('capability_name')) {
                if ($current !== null) {
                    $current['description'] = trim($current['description']);
                    $capabilities[]         = $current;
                }

                [$capName, $capDesc] = $this->splitCapabilityName($text);

                $current = [
                    'name'        => $capName,
                    'description' => $capDesc,
                ];
                continue;
            }

            // Body font or overflow lines — append to current description
            if ($current !== null) {
                $current['description'] .= ' ' . $text;
            }
        }

        if ($current !== null) {
            $current['description'] = trim($current['description']);
            $capabilities[]         = $current;
        }

        return $capabilities;
    }

    /**
     * Split "Amphibie. L'aboleth peut..." → ['Amphibie', "L'aboleth peut..."]
     * Handles names ending mid-sentence with a period.
     */
    private function splitCapabilityName(string $text): array
    {
        if (preg_match('/^(.+?\.)\s*(.*)$/s', $text, $m)) {
            return [trim($m[1], '. '), trim($m[2])];
        }

        return [$text, ''];
    }

    // =========================================================================
    // Fingerprint
    // =========================================================================

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

    private function syncToDatabase(array $monsters): void
    {
        $bar = $this->output->createProgressBar(count($monsters));
        $bar->start();

        $upserted  = 0;
        $skipped   = 0;

        foreach ($monsters as $key => $data) {
            if (!$data['fingerprint']) {
                $skipped++;
                $bar->advance();
                continue;
            }
            $monster = Monster::firstOrNew(['fingerprint' => $data['fingerprint']]);

            // Translatable fields — setTranslation pour ne pas écraser les autres locales
            $monster->setTranslation('name', $this->lang, $data['name']);
            $monster->setTranslation('traits', $this->lang, $data['traits']);
            $monster->setTranslation('actions', $this->lang, $data['actions']);
            $monster->setTranslation('legendary_actions', $this->lang, $data['legendary_actions']);
            $monster->setTranslation('reactions', $this->lang, $data['reactions']);
            $monster->setTranslation('bonus_actions', $this->lang, $data['bonus_actions']);

            // Non-translatable fields — seulement sur le premier run (pas d'écrasement)
            if (!$monster->exists) {
                $monster->type = $data['type'];
                $monster->size = $data['size'];
                $monster->alignment = $data['alignment'];
                $monster->challenge_rating = $data['challenge_rating'];
                $monster->cr = $data['cr'];
                $monster->armor_class = $data['armor_class'];
                $monster->hit_points = $data['hit_points'];
                $monster->hit_dice = $data['hit_dice'];
                $monster->speed = $data['speed'];
                $monster->strength = $data['strength'];
                $monster->dexterity = $data['dexterity'];
                $monster->constitution = $data['constitution'];
                $monster->intelligence = $data['intelligence'];
                $monster->wisdom = $data['wisdom'];
                $monster->charisma = $data['charisma'];
                $monster->saving_throws = $data['saving_throws'];
            }
            try {
                $monster->save();
                $upserted++;
            } catch (\Exception $e) {
                $this->error("Error saving monster {$data['name']} ({$key})");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced : {$upserted} monsters.");

        if ($skipped > 0) {
            $this->warn("Skipped (no fingerprint) : {$skipped} monsters.");
        }
    }
}
