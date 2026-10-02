<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiArticleGeneratorService
{
    /**
     * Generate a hobby guide or blog article grounded in trusted sources.
     *
     * @param  array{
     *     topic: string,
     *     category?: string|null,
     *     audience?: string|null,
     *     include_sources?: bool|null
     * }  $params
     * @return array{
     *     title: string,
     *     category: string,
     *     author: string,
     *     summary: string,
     *     content: string,
     *     sources: array<int, array{name: string, url: string, note?: string}>
     * }
     */
    public function generate(array $params): array
    {
        $topic = trim($params['topic'] ?? 'One Piece Card Game OP-08 Meta Analysis');
        $category = $params['category'] ?? null;
        $audience = $params['audience'] ?? 'Intermediate';
        $includeSources = $params['include_sources'] ?? true;

        $apiKey = config('services.gemini.api_key');
        if (! empty($apiKey)) {
            try {
                return $this->generateWithGemini($topic, $category, $audience, $includeSources);
            } catch (Throwable $e) {
                Log::warning('AI Article Generator Gemini error: '.$e->getMessage().'. Using trusted local fallback engine.');
            }
        }

        return $this->generateCuratedFallback($topic, $category, $audience, $includeSources);
    }

    /**
     * Generate article using Google Gemini API.
     */
    protected function generateWithGemini(string $topic, ?string $category, string $audience, bool $includeSources): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        $trustedSourcesInfo = <<<'SOURCES'
Trusted sources guidelines by category:
- One Piece Card Game: Official Bandai One Piece Portal (en.onepiece-cardgame.com), One Piece Top Decks (onepiecetopdecks.com), Limitless TCG (limitlesstcg.com), Bandai TCG Tournament Regulations.
- Pokémon TCG: Official Pokémon Portal (pokemon.com), JustinBasil Meta Guides (justinbasil.com), Limitless TCG, Pokellector database.
- Gunpla & Model Kits: Bandai Hobby Site (bandai-hobby.net), Gunpla 101 (gunpla101.com), Dalong Gunpla Archive (dalong.net), HobbyLink Japan (hlj.com).
- Anime Figures: Good Smile Company (goodsmile.info), AmiAmi (amiami.com), MyFigureCollection (myfigurecollection.net).
- Card Care & Archival: Ultra PRO Archival Guidelines, PSA Grading Preservation Standards, Dragon Shield Care Guide, tropical climate humidity mitigation (silica gel, dry-boxes).
SOURCES;

        $prompt = <<<EOT
You are an expert hobby journalist, tournament analyst, and modeling artisan writing for "RLG Hobby Shop", the premier Philippine destination for authentic Japanese hobby imports, Trading Card Games (TCG), Bandai Gunpla kits, and anime figures.

Generate a comprehensive, high-quality, authentic hobby guide or strategic article based on the following input:
- Topic: "{$topic}"
- Requested Category: {$category}
- Target Reader Audience: {$audience}

{$trustedSourcesInfo}

Rules:
1. Ground the article in verified, trusted facts, real card effects/set names/Gunpla grades/techniques from the authoritative sources listed above.
2. Structure the content logically with introduction, in-depth strategic analysis or step-by-step guide, pro tips, and conclusion.
3. If includeSources is true, include a dedicated "## 📚 Trusted References & Official Sources" section at the end citing the specific official sources, databases, or tournament archives used.
4. Return ONLY a valid JSON object matching the requested schema with no extra text or markdown code fence ticks outside the JSON.

JSON Schema to return:
{
  "title": "SEO-friendly Catchy Headline Title",
  "category": "TCG Strategy | Gunpla & Modeling | Care & Preservation | Market Trends",
  "author": "Persona Name (e.g. Chief Deck Architect, Master Builder Ken, or Archival Specialist Leo)",
  "summary": "1-2 punchy sentences summarizing the core value of the article",
  "content": "Full rich markdown article text including headings (##, ###), bullet points, strategy tips, and the sources section",
  "sources": [
    {
      "name": "Source Name (e.g. Bandai Official One Piece Card Game)",
      "url": "https://en.onepiece-cardgame.com",
      "note": "Official card rules, set errata and regional tournament decklists"
    }
  ]
}
EOT;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.4,
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini API call failed: '.$response->body());
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text = trim($text);

        // Strip any markdown codeblock wraps if present
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
        }
        if (str_starts_with($text, '```')) {
            $text = substr($text, 3);
        }
        if (str_ends_with($text, '```')) {
            $text = substr($text, 0, -3);
        }

        $decoded = json_decode(trim($text), true);
        if (! is_array($decoded) || empty($decoded['title']) || empty($decoded['content'])) {
            throw new \RuntimeException('Invalid JSON returned by Gemini: '.$text);
        }

        return [
            'title' => (string) $decoded['title'],
            'category' => (string) ($decoded['category'] ?? ($category ?: 'TCG Strategy')),
            'author' => (string) ($decoded['author'] ?? 'RLG Editorial Staff'),
            'summary' => (string) ($decoded['summary'] ?? ''),
            'content' => (string) $decoded['content'],
            'sources' => (array) ($decoded['sources'] ?? []),
        ];
    }

    /**
     * Fallback curated generator with authentic hobby knowledge and genuine sources.
     */
    protected function generateCuratedFallback(string $topic, ?string $category, string $audience, bool $includeSources): array
    {
        $lowerTopic = strtolower($topic);

        if (str_contains($lowerTopic, 'gunpla') || str_contains($lowerTopic, 'gundam') || str_contains($lowerTopic, 'model') || $category === 'Gunpla & Modeling') {
            return [
                'title' => 'Master Guide: Essential Nipping, Panel Lining, and Topcoat Techniques for Gunpla',
                'category' => 'Gunpla & Modeling',
                'author' => 'Master Builder Ken',
                'summary' => 'Transform your High Grade and Real Grade kits with professional nub cleanup, clean panel lines, and matte topcoat protection.',
                'content' => "## Introduction to Clean Gunpla Building\n\nBuilding Bandai Gundam model kits (Gunpla) is an art that bridges precision mechanics and visual presentation. Whether tackling a High Grade (HG), Real Grade (RG), or Master Grade (MG), achieving a competition-worthy finish starts with mastering fundamental assembly practices.\n\n### 1. Two-Cut Nub Removal Method\n- **Cut 1**: Clip the runner sprue 2-3mm away from the actual plastic part using dual-blade nippers to prevent stress marks.\n- **Cut 2**: Use a single-blade nipper (such as GodHand SPN-120 or Bandai Spirits Entry Nipper) parallel to the part face for a flush cut.\n- **Sanding**: Progress gently through 600, 800, and 1000 grit sponges to erase any remaining nub silhouette.\n\n### 2. Crisp Panel Lining Techniques\n- Pour-type Gundam Markers (GM301/GM302/GM303) utilize capillary action to flow into recessed armor grooves automatically.\n- Wipe away excess pigment after 15 seconds using an enamel-safe cotton swab dipped lightly in 99% Isopropyl Alcohol.\n\n### 3. Surface Finish: The Power of Matte Topcoat\n- A final layer of Mr. Hobby Super Clear Flat or Topcoat (Water-Based) eliminates the \"toy-like\" plastic gloss and creates a photorealistic anime aesthetic.\n- Spray in thin, sweeping coats from a distance of 20cm in low-humidity conditions.\n\n---\n\n## 📚 Trusted References & Official Sources\n- **Bandai Hobby Site Official Manuals**: [bandai-hobby.net](https://bandai-hobby.net) — Official kit assembly guidelines and safety data.\n- **Gunpla 101 Builder Archive**: [gunpla101.com](https://www.gunpla101.com) — Standard tools, nipper comparisons, and beginner-to-advanced finishing methods.\n- **Dalong.net Gunpla Archive**: [dalong.net](http://www.dalong.net) — Exhaustive runner breakdowns and unpainted kit reference photos.",
                'sources' => [
                    ['name' => 'Bandai Hobby Site Official', 'url' => 'https://bandai-hobby.net', 'note' => 'Official Bandai Spirits assembly guides and safety standards'],
                    ['name' => 'Gunpla 101 Resource Portal', 'url' => 'https://www.gunpla101.com', 'note' => 'Industry standard tool reviews and panel-lining methodologies'],
                    ['name' => 'Dalong Gunpla Database', 'url' => 'http://www.dalong.net', 'note' => 'Runner sprue analysis and kit photo archives'],
                ],
            ];
        }

        if (str_contains($lowerTopic, 'humidity') || str_contains($lowerTopic, 'care') || str_contains($lowerTopic, 'protect') || str_contains($lowerTopic, 'storage') || $category === 'Care & Preservation') {
            return [
                'title' => 'Collector Protection Guide: Shielding TCG Cards & Graded Slabs from Tropical Humidity',
                'category' => 'Care & Preservation',
                'author' => 'Archival Specialist Leo',
                'summary' => 'Safeguard your valuable Pokémon and One Piece cards against tropical moisture, warping (foil curling), and mold growth in the Philippines.',
                'content' => "## The Climate Threat to TCG Collectibles\n\nIn tropical environments like the Philippines, average relative humidity regularly fluctuates between 70% and 90%. Cardboard stock and foil stamping absorb atmospheric moisture at different rates, resulting in unsightly forward-curling (concave) or backward-curling (convex) foils.\n\n### 1. The Double-Sleeving Gold Standard\n- **Inner Sleeve**: Use exact-fit sleeves (KMC Perfect Size or Dragon Shield Perfect Fit) inserted opening-side down.\n- **Outer Sleeve**: Slide into standard tournament matte sleeves (Dragon Shield, Ultimate Guard Katana) opening-side up. This creates a sealed air pocket that dramatically slows moisture absorption.\n\n### 2. Modern Storage Armor: Toploaders & Semi-Rigids\n- Encase your secret rares and alternative arts in 35pt rigid PVC toploaders or magnetic One-Touch holders.\n- Always apply a resealable team bag over magnetic cases to prevent dust and humidity seepage.\n\n### 3. Regulating Ambient Humidity (45% - 55% RH)\n- Store binder collections and decks inside airtight dry boxes (e.g. Pelican, Lock&Lock) paired with 62% Boveda 2-way humidity control packs or rechargeable silica gel canisters.\n- Avoid storing cards near exterior-facing concrete walls or direct sunlight to prevent thermal condensation.\n\n---\n\n## 📚 Trusted References & Official Sources\n- **Ultra PRO Archival Standards**: [ultrapro.com](https://www.ultrapro.com) — Acid-free, non-PVC polypropylene preservation specifications.\n- **Professional Sports Authenticator (PSA) Care Guidelines**: [psacard.com](https://www.psacard.com) — Museum-grade environmental handling recommendations.\n- **KMC Sleeves Japan Technical Specs**: [kmcsleeves.com](https://www.kmcsleeves.com) — Dual-barrier moisture sealing performance.",
                'sources' => [
                    ['name' => 'Ultra PRO Archival Guidelines', 'url' => 'https://www.ultrapro.com', 'note' => 'Polypropylene and PVC-free archival preservation data'],
                    ['name' => 'PSA Grading Care Standards', 'url' => 'https://www.psacard.com', 'note' => 'Environmental humidity tolerances for graded slabs'],
                    ['name' => 'KMC Card Barrier Japan', 'url' => 'https://www.kmcsleeves.com', 'note' => 'Perfect-fit sleeve moisture resistance standards'],
                ],
            ];
        }

        // Default: One Piece TCG / Pokémon TCG Competitive Strategy
        return [
            'title' => 'One Piece Card Game Meta Tier Breakdown: Dominant Archetypes & Matchup Tactics',
            'category' => 'TCG Strategy',
            'author' => 'Chief Deck Architect',
            'summary' => 'Comprehensive tournament breakdown of top-tier leaders, key staple ratios, mulligan priorities, and counter strategies.',
            'content' => "## The Current Competitive Landscape\n\nIn the modern One Piece Card Game (OPCG) tournament circuit, deck consistency and resource tempo dictate top-cut performance. As regional and flagship tournaments expand across Southeast Asia and the Philippines, understanding leader matchups is essential for tournament success.\n\n### 1. Top Tier Leaders & Engine Archetypes\n- **Black Lucci / Enies Lobby Engine**: Combines relentless stage searchers with aggressive cost-reduction staples like 4c Kuzan and 8c Gecko Moria to wipe opponent boards while establishing high-power attackers.\n- **Yellow Vegapunk & Enel**: Unmatched defensive resilience powered by Life-manipulation triggers, stalling opponents until boss drops like 9c Yamato and 10c Ace seal the late game.\n- **Green/Yellow & Blue Leaders**: Blue Doflamingo leverages warlord synergy to generate wide boards on turn 2-3 without expending card advantage in hand.\n\n### 2. Mulligan Strategy & Turn-1 Prioritization\n- When going first (Odd curve): Prioritize 1-cost searchers (e.g. Otama, Spandine, Jewelry Bonney) to filter into essential core curve units.\n- When going second (Even curve): Guarantee your 4c cost-reducer or mid-range removal unit in your opening hand to contest early aggressive pressure.\n\n### 3. Resource Management & Counter Math\n- Protect high-impact characters over face life cards during early turns. Life card count translates directly to late-game hand size and counter potential.\n\n---\n\n## 📚 Trusted References & Official Sources\n- **Official Bandai One Piece Card Game Portal**: [en.onepiece-cardgame.com](https://en.onepiece-cardgame.com) — Comprehensive tournament rules, card errata, and official deck registration.\n- **One Piece Top Decks**: [onepiecetopdecks.com](https://onepiecetopdecks.com) — Verified regional flagship and championship top-cut decklists.\n- **Limitless TCG Analytics**: [limitlesstcg.com](https://limitlesstcg.com) — Win rate statistics, matchup tables, and global tournament analytics.",
            'sources' => [
                ['name' => 'Official Bandai One Piece TCG Portal', 'url' => 'https://en.onepiece-cardgame.com', 'note' => 'Official card text, errata list, and championship floor rules'],
                ['name' => 'One Piece Top Decks', 'url' => 'https://onepiecetopdecks.com', 'note' => 'International and Asian championship top-cut decklists'],
                ['name' => 'Limitless TCG Meta Analytics', 'url' => 'https://limitlesstcg.com', 'note' => 'Tournament win rates, tier charts, and matchup data'],
            ],
        ];
    }
}
