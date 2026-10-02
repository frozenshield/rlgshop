<?php

namespace Database\Seeders;

use App\Models\HobbyArticle;
use Illuminate\Database\Seeder;

class HobbyArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'slug' => 'top-5-one-piece-meta-decks-op07-egghead',
                'title' => 'Top 5 One Piece Card Game Meta Decks in OP-07 Egghead',
                'category' => 'TCG Strategy',
                'author' => 'Chief Deck Architect',
                'published_at' => '2026-09-20',
                'status' => 'Published',
                'summary' => 'A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists.',
                'content' => "A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists. Learn matchup mulligans, leader powers, and counter strategies to pilot your deck into top cut.\n\n### 1. Yellow Vegapunk (Egghead Archetype)\nWith stage mechanics deploying 5-cost scientist characters from life cards, Vegapunk commands immense card advantage.\n\n### 2. Blue Doflamingo\nThe Seven Warlords leader returns to tier 1 contention with accelerated 4-cost Jinbe and Pacifista blockers.\n\n### 3. Green Bonney\nSupernovas rest control remains dominant in late game stalemates with Zoro and 8c Kid shields.\n\nKey staples like 8c Katakuri and 10c Ace remain dominant across regional qualifiers.",
                'image_url' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80',
                'sources' => [
                    [
                        'name' => 'OnePieceTopDecks Tournament Archive',
                        'url' => 'https://onepiecetopdecks.com',
                        'note' => 'Championship tournament results & meta breakdown',
                    ],
                    [
                        'name' => 'Bandai Official One Piece Card Game Rules',
                        'url' => 'https://en.onepiece-cardgame.com/rules/',
                        'note' => 'Comprehensive floor rules & errata',
                    ],
                ],
                'views_count' => 124,
                'is_featured' => true,
            ],
            [
                'slug' => 'beginner-guide-essential-tools-for-rg-gunpla',
                'title' => 'Beginner Guide: Essential Tools for Building Your First RG Gunpla',
                'category' => 'Gunpla & Modeling',
                'author' => 'Master Builder Ken',
                'published_at' => '2026-09-15',
                'status' => 'Published',
                'summary' => 'Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained.',
                'content' => "Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained. Protect your parts and achieve pristine nub removal on Real Grade kits without plastic stress marks.\n\n### Essential Tool Checklist:\n1. **Single-Blade Nippers**: Provides ultra-clean slice cuts on delicate runner gates without white stress marks.\n2. **High-Grit Glass Files & Sanding Sticks**: 600, 800, and 1200 grit progression leaves runner nubs completely flush.\n3. **Pour-Type Panel Line Accent**: Capillary action allows gray or black enamel fluid to bring out minute molded armor details.\n4. **Matte/Flat Topcoat**: Seals waterslide decals and transforms raw injection molded plastic into a museum-grade anime look.",
                'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=700&auto=format&fit=crop&q=80',
                'sources' => [
                    [
                        'name' => 'Bandai Hobby Site Official Tutorial Hub',
                        'url' => 'https://bandai-hobby.net',
                        'note' => 'Step-by-step Gunpla assembly guides',
                    ],
                    [
                        'name' => 'Gunpla 101 Builder Resource',
                        'url' => 'https://gunpla101.com',
                        'note' => 'Beginner equipment recommendations',
                    ],
                ],
                'views_count' => 89,
                'is_featured' => false,
            ],
            [
                'slug' => 'tcg-preservation-guide-humidity-sleeves-slabs',
                'title' => 'Collector Preservation: Shielding Foil Cards from Humidity & Curling',
                'category' => 'Care & Preservation',
                'author' => 'RLG Editorial Staff',
                'published_at' => '2026-09-28',
                'status' => 'Published',
                'summary' => 'How to double sleeve Pokémon and One Piece alt-arts, use desiccant silica packs, and store magnetic one-touches.',
                'content' => "In tropical climates, relative humidity above 60% causes cardstock to absorb atmospheric moisture while the non-porous metallic foil layer remains rigid, resulting in unwanted curling.\n\n### Best Practices:\n- **Exact-fit Inner Sleeves**: Top-loading or side-loading inner sleeves inserted inverted into a standard deck protector shield against airflow.\n- **Silica Gel Desiccant Packs**: Maintain 45-50% relative humidity inside sealed storage boxes.\n- **UV-Resistant Magnetic Cases**: Use 35pt magnetic holders with recessed card corners to protect valuable Secret Rares and Manga alts.",
                'image_url' => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=700&auto=format&fit=crop&q=80',
                'sources' => [
                    [
                        'name' => 'PSA Collector Preservation Guidelines',
                        'url' => 'https://www.psacard.com/resources/paper-preservation',
                        'note' => 'Archival storage recommendations',
                    ],
                ],
                'views_count' => 54,
                'is_featured' => false,
            ],
        ];

        foreach ($articles as $data) {
            HobbyArticle::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
