<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

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
                            {--pdf=    : Absolute path to the SRD PDF file}
                            {--lang=   : Language of the PDF (fr|en)}
                            {--from=272 : First page (inclusive) of the monster section}
                            {--to=380   : Last page (inclusive) of the monster section}';

    protected $description = 'Parse monsters from the SRD PDF (FR or EN) and dump structured data';

    // -------------------------------------------------------------------------
    // Runtime state
    // -------------------------------------------------------------------------

    protected $output = [];
    private $fontIndex = [];
    private $lang = 'fr';

    // -------------------------------------------------------------------------
    // Font config per language
    // role => font id
    // -------------------------------------------------------------------------

    private const FONT_CONFIG = [
        'fr' => [
            'monster_name' => 4,
            'monster_type' => 5,
            'stats' => 6,
            'ability_initial' => 9,
            'ability_suffix' => 10,
            'ability_values' => 11,
            'ability_negative' => 12,
            'section_header' => 13,
            'capability_name' => 14,
            'capability_body' => 15,
        ],
        'en' => [
            'monster_name' => 3,
            'monster_type' => 5,
            'stats' => 6,
            'ability_initial' => 9,
            'ability_suffix' => 10,
            'ability_values' => 11,
            'ability_negative' => 12,
            'section_header' => 13,
            'capability_name' => 14,
            'capability_body' => 15,
        ],
    ];

    // -------------------------------------------------------------------------
    // Stats label → key, per language
    // -------------------------------------------------------------------------

    private const STATS_MAP = [
        'fr' => [
            'CA' => 'armor_class',
            'Initiative' => 'initiative',
            'Pv' => 'hit_points',
            'Vitesse' => 'speed',
            'Compétences' => 'skills',
            'Résistances' => 'resistances',
            'Immunités' => 'immunities',
            'Sens' => 'senses',
            'Langues' => 'languages',
            'FP' => 'challenge_rating',
        ],
        'en' => [
            'AC' => 'armor_class',
            'Initiative' => 'initiative',
            'HP' => 'hit_points',
            'Speed' => 'speed',
            'Skills' => 'skills',
            'Resistances' => 'resistances',
            'Immunities' => 'immunities',
            'Senses' => 'senses',
            'Languages' => 'languages',
            'CR' => 'challenge_rating',
        ],
    ];

    // -------------------------------------------------------------------------
    // Section header label → key, per language
    // -------------------------------------------------------------------------

    private const SECTION_HEADERS = [
        'fr' => [
            'Traits' => 'traits',
            'Actions' => 'actions',
            'Actions Légendaires' => 'legendary_actions',
            'Actions Bonus' => 'bonus_actions',
            'Réactions' => 'reactions',
        ],
        'en' => [
            'Traits' => 'traits',
            'Actions' => 'actions',
            'Legendary Actions' => 'legendary_actions',
            'Bonus Actions' => 'bonus_actions',
            'Reactions' => 'reactions',
        ],
    ];

    // -------------------------------------------------------------------------
    // Ability abbreviations per language
    // -------------------------------------------------------------------------

    private const ABILITY_KEYS = [
        'fr' => ['For', 'Dex', 'Con', 'Int', 'Sag', 'Cha'],
        'en' => ['Str', 'Dex', 'Con', 'Int', 'Wis', 'Cha'],
    ];

    // Lines to discard (page footers etc.)
    private const NOISE_LINES = [
        'Document de Référence du Système 5.2.1',
        'System Reference Document 5.2.1'
    ];

    // =========================================================================
    // Entry point
    // =========================================================================

    public function handle(): int
    {
        $pdfPath = (string)$this->option('pdf');
        $lang = (string)$this->option('lang');
        $fromPage = (int)$this->option('from');
        $toPage = (int)$this->option('to');

        // --- Validate inputs -------------------------------------------------

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

        $this->lang = $lang;
        $this->output = $this->extractXml($pdfPath, $fromPage, $toPage);
        $this->fontIndex = $this->indexFonts();

        // --- Parse -----------------------------------------------------------

        $monsters = $this->parseAllMonsters();
        dd($monsters[320]);


        return self::SUCCESS;
    }

    // =========================================================================
    // XML extraction
    // =========================================================================

    private function extractXml(string $pdfPath, int $from, int $to): array
    {
        $process = new Process([
            'pdftohtml', '-xml', '-nodrm',
            '-f', (string)$from,
            '-l', (string)$to,
            '-stdout',
            $pdfPath,
        ]);

        $process->mustRun();

        return array_values(
            array_filter(
                explode("\n", $process->getOutput()),
                fn(string $line) => trim($line) !== '' && !$this->isNoiseLine($line)
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

        foreach ($this->output as $i => $line) {
            if (str_contains($line, '<text') && preg_match('/font="(\d+)"/', $line, $m)) {
                $index[(int)$m[1]][] = $i;
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
        $total = count($this->output);
        $blocks = [];

        foreach ($markerIndexes as $pos => $lineIndex) {
            $end = $markerIndexes[$pos + 1] ?? $total;
            $blocks[] = [$lineIndex, $end];
        }

        return $blocks;
    }

    private function resolveBlock(int $start, int $end): array
    {
        return array_slice($this->output, $start, $end - $start);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Get font id from a <text> line, or null. */
    private function getFontId(string $line): ?int
    {
        if (preg_match('/font="(\d+)"/', $line, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /** Extract plain text content from a <text ...>...</text> line. */
    private function extractText(string $line): string
    {
        preg_match('/<text[^>]+>(.*?)<\/text>/s', $line, $m);
        return trim(strip_tags($m[1] ?? ''));
    }

    /** Resolve a font role name to its id for the current language. */
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
            $block = $this->resolveBlock($start, $end);
            $monster = $this->parseMonster($block);

            if ($monster !== null) {
                $monster['fingerprint'] = $this->fingerprint($monster);
                $monsters[] = $monster;
            }
        }

        return $monsters;
    }

    private function parseMonster(array $block): ?array
    {
        $monster = [
            'name' => null,
            'type' => null,
            'stats' => [],
            'abilities' => [],
            'sections' => [],
        ];

        // First line of the block is the monster_name font
        foreach ($block as $line) {
            if ($this->getFontId($line) === $this->font('monster_name')) {
                $monster['name'] = $this->extractText($line);
                break;
            }
        }

        if (!$monster['name']) {
            return null;
        }

        // First monster_type font = type/alignment
        foreach ($block as $line) {
            if ($this->getFontId($line) === $this->font('monster_type')) {
                $monster['type'] = $this->extractText($line);
                break;
            }
        }

        $monster['stats'] = $this->parseStats($block);
        $monster['abilities'] = $this->parseAbilities($block);
        $monster['sections'] = $this->parseSections($block);

        return $monster;
    }

    // =========================================================================
    // Stats parser  (font: stats)
    // =========================================================================

    private function parseStats(array $block): array
    {
        $stats = [];
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
    // Ability scores parser  (fonts: ability_initial / ability_suffix / ability_values / ability_negative)
    // =========================================================================

    private function parseAbilities(array $block): array
    {
        $abilities = [];
        $validKeys = self::ABILITY_KEYS[$this->lang];
        $currentAbility = null;
        $currentValues = [];
        $rows = [];

        foreach ($block as $line) {
            $fontId = $this->getFontId($line);
            $text = trim($this->extractText($line));

            if (empty($text)) {
                continue;
            }

            if ($fontId === $this->font('ability_initial')) {
                // Save previous ability before starting a new one
                if ($currentAbility !== null) {
                    $rows[] = [$currentAbility, $currentValues];
                }
                $currentAbility = $text;
                $currentValues = [];
                continue;
            }

            if ($fontId === $this->font('ability_suffix') && $currentAbility !== null) {
                // Append suffix (lowercase to normalise "IS" → "is" for EN "Wis")
                $currentAbility .= strtolower($text);
                continue;
            }

            if (in_array($fontId, [$this->font('ability_values'), $this->font('ability_negative')], true)
                && $currentAbility !== null
            ) {
                // Extract all numeric tokens (handles "21 +5 +5", "−1", "+3" etc.)
                preg_match_all('/[+−\-]?\d+/', $text, $m);
                foreach ($m[0] as $val) {
                    $currentValues[] = $val;
                }
            }
        }

        // Don't forget the last ability
        if ($currentAbility !== null) {
            $rows[] = [$currentAbility, $currentValues];
        }

        foreach ($rows as [$name, $values]) {
            // Normalise key: capitalise first letter only ("FOR" → "For", "str" → "Str")
            $name = ucfirst(strtolower($name));

            if (!in_array($name, $validKeys, true)) {
                continue;
            }

            $abilities[strtolower($name)] = [
                'score' => isset($values[0]) ? (int)$values[0] : null,
                'mod' => $values[1] ?? null,
                'save' => $values[2] ?? null,
            ];
        }

        return $abilities;
    }

    // =========================================================================
    // Sections parser  (font: section_header)
    // =========================================================================

    private function parseSections(array $block): array
    {
        $sections = [];
        $currentSection = null;
        $currentLines = [];
        $sectionHeaders = self::SECTION_HEADERS[$this->lang];

        foreach ($block as $line) {
            $fontId = $this->getFontId($line);
            $text = $this->extractText($line);

            if ($fontId === $this->font('section_header')) {
                // Close previous section
                if ($currentSection !== null) {
                    $sections[$currentSection] = $this->parseCapabilities($currentLines);
                }

                $currentSection = $sectionHeaders[$text] ?? strtolower($text);
                $currentLines = [];
                continue;
            }

            if ($currentSection !== null) {
                $currentLines[] = $line;
            }
        }

        // Close last section
        if ($currentSection !== null) {
            $sections[$currentSection] = $this->parseCapabilities($currentLines);
        }

        return $sections;
    }

    // =========================================================================
    // Capabilities parser  (fonts: capability_name / capability_body)
    // =========================================================================

    private function parseCapabilities(array $lines): array
    {
        $capabilities = [];
        $current = null;

        foreach ($lines as $line) {
            $fontId = $this->getFontId($line);
            $text = $this->extractText($line);

            if (empty($text)) {
                continue;
            }

            if ($fontId === $this->font('capability_name')) {
                // Save previous capability
                if ($current !== null) {
                    $current['description'] = trim($current['description']);
                    $capabilities[] = $current;
                }

                [$capName, $capDesc] = $this->splitCapabilityName($text);

                $current = [
                    'name' => $capName,
                    'description' => $capDesc,
                ];
                continue;
            }

            // capability_body (and font 16 overflow lines) = continuation
            if ($current !== null) {
                $current['description'] .= ' ' . $text;
            }
        }

        // Close last capability
        if ($current !== null) {
            $current['description'] = trim($current['description']);
            $capabilities[] = $current;
        }

        return $capabilities;
    }

    /**
     * Split "Amphibie. L'aboleth peut..." into ['Amphibie', "L'aboleth peut..."]
     * Handles multi-word names ending with a period.
     */
    private function splitCapabilityName(string $text): array
    {
        if (preg_match('/^(.+?\.)\s*(.*)$/s', $text, $m)) {
            return [trim($m[1], '. '), trim($m[2])];
        }

        return [$text, ''];
    }

    /* Create an unique fingerprint for a monster.
    This fingerprint is used to map monsters to their SRD entry.
    This will be useful to map English monsters to their French equivalent. */
    private function fingerprint(array $monster): string
    {
        return md5(implode('|', [
            $monster['abilities']['str']['score'] ?? $monster['abilities']['for']['score'] ?? '',
            $monster['abilities']['dex']['score'] ?? '',
            $monster['abilities']['con']['score'] ?? '',
            $monster['abilities']['int']['score'] ?? '',
            $monster['abilities']['wis']['score'] ?? $monster['abilities']['sag']['score'] ?? '',
            $monster['abilities']['cha']['score'] ?? '',
            count($monster['sections'] ?? 0)
        ]));
    }
}
