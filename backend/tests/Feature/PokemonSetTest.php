<?php

namespace Tests\Feature;

use App\Models\RefPokemonSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PokemonSetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test listing all Pokemon sets returns all seeded reference expansions.
     */
    public function test_can_list_all_pokemon_sets(): void
    {
        $response = $this->getJson('/api/pokemon-sets');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'count',
                'data' => [
                    '*' => [
                        'id',
                        'series',
                        'series_years',
                        'japanese_set',
                        'japanese_code',
                        'english_set',
                        'set_type',
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(90, $response->json('count'));
    }

    /**
     * Test filtering Pokemon sets by series.
     */
    public function test_can_filter_pokemon_sets_by_series(): void
    {
        $response = $this->getJson('/api/pokemon-sets?series='.urlencode('Scarlet & Violet'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sets = $response->json('data');
        $this->assertNotEmpty($sets);
        foreach ($sets as $set) {
            $this->assertEquals('Scarlet & Violet', $set['series']);
        }
    }

    /**
     * Test searching Pokemon sets by Japanese code (e.g., SV2a).
     */
    public function test_can_search_pokemon_sets_by_code(): void
    {
        $response = $this->getJson('/api/pokemon-sets?search=SV2a');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sets = $response->json('data');
        $this->assertNotEmpty($sets);
        $this->assertEquals('SV2a', $sets[0]['japanese_code']);
        $this->assertEquals('Pokémon Card 151', $sets[0]['japanese_set']);
        $this->assertEquals('Scarlet & Violet—151', $sets[0]['english_set']);
    }

    /**
     * Test searching Pokemon sets by English set name (e.g., Evolving Skies).
     */
    public function test_can_search_pokemon_sets_by_english_name(): void
    {
        $response = $this->getJson('/api/pokemon-sets?search='.urlencode('Evolving Skies'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sets = $response->json('data');
        $this->assertNotEmpty($sets);

        $eeveeHeroes = collect($sets)->firstWhere('japanese_set', 'Eevee Heroes');
        $this->assertNotNull($eeveeHeroes);
        $this->assertEquals('S6a', $eeveeHeroes['japanese_code']);
    }

    /**
     * Test fetching a single Pokemon set by ID and by code.
     */
    public function test_can_get_single_pokemon_set_by_id_and_code(): void
    {
        $first = RefPokemonSet::where('japanese_code', 'SV2a')->firstOrFail();

        // By ID
        $responseById = $this->getJson("/api/pokemon-sets/{$first->id}");
        $responseById->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.japanese_code', 'SV2a');

        // By Code
        $responseByCode = $this->getJson('/api/pokemon-sets/SV2a');
        $responseByCode->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.english_set', 'Scarlet & Violet—151');
    }

    /**
     * Test fetching summary series list.
     */
    public function test_can_get_pokemon_series_summary(): void
    {
        $response = $this->getJson('/api/pokemon-sets/series');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'series',
                        'series_years',
                        'sets_count',
                    ],
                ],
            ]);

        $seriesNames = collect($response->json('data'))->pluck('series')->all();
        $this->assertContains('Scarlet & Violet', $seriesNames);
        $this->assertContains('Sword & Shield', $seriesNames);
        $this->assertContains('Sun & Moon', $seriesNames);
        $this->assertContains('XY', $seriesNames);
        $this->assertContains('Black & White', $seriesNames);
    }

    /**
     * Test storefront chatbot can answer Pokemon Japanese set equivalent questions.
     */
    public function test_chatbot_can_answer_pokemon_set_equivalent_question(): void
    {
        $response = $this->postJson('/api/ai/chat', [
            'message' => 'What is the English set equivalent of Japanese SV2a or Eevee Heroes?',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $reply = $response->json('data.reply');
        $this->assertNotEmpty($reply);
        $this->assertTrue(
            str_contains($reply, '151') ||
            str_contains($reply, 'Evolving Skies') ||
            str_contains($reply, 'Eevee Heroes') ||
            str_contains($reply, 'SV2a')
        );
    }
}
