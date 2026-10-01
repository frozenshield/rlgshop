<?php

namespace Tests\Feature;

use App\Models\RefCategory;
use App\Models\RefOnePieceSet;
use App\Models\RefPokemonSet;
use App\Models\RefSubcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnePieceSetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test listing all One Piece sets returns all seeded reference sets.
     */
    public function test_can_list_all_onepiece_sets(): void
    {
        $response = $this->getJson('/api/onepiece-sets');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'count',
                'data' => [
                    '*' => [
                        'id',
                        'subcategories_id',
                        'code',
                        'name',
                        'product_line',
                        'set_type',
                        'release_date',
                        'status',
                        'description',
                        'notes',
                        'chase_cards',
                    ],
                ],
            ]);

        $this->assertGreaterThanOrEqual(24, $response->json('count'));
    }

    /**
     * Test filtering One Piece sets by product line.
     */
    public function test_can_filter_onepiece_sets_by_product_line(): void
    {
        $response = $this->getJson('/api/onepiece-sets?product_line='.urlencode('Main Boosters (OP)'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sets = $response->json('data');
        $this->assertCount(17, $sets);
        $this->assertEquals('OP-01', $sets[0]['code']);
        $this->assertEquals('OP-17', $sets[16]['code']);
    }

    /**
     * Test searching One Piece sets by code or name.
     */
    public function test_can_search_onepiece_sets(): void
    {
        $response = $this->getJson('/api/onepiece-sets?search=Awakening');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sets = $response->json('data');
        $this->assertNotEmpty($sets);
        $this->assertEquals('OP-05', $sets[0]['code']);
        $this->assertEquals('Awakening of the New Era', $sets[0]['name']);
        $this->assertNotEmpty($sets[0]['chase_cards']);
    }

    /**
     * Test fetching a single One Piece set by code.
     */
    public function test_can_get_single_onepiece_set_by_code(): void
    {
        $response = $this->getJson('/api/onepiece-sets/OP-01');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'OP-01')
            ->assertJsonPath('data.name', 'Romance Dawn');

        $chaseCards = $response->json('data.chase_cards');
        $this->assertIsArray($chaseCards);
        $this->assertStringContainsString('Shanks', $chaseCards[0]['name']);
    }

    /**
     * Test product lines endpoint.
     */
    public function test_can_fetch_product_lines_summary(): void
    {
        $response = $this->getJson('/api/onepiece-sets/product-lines');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'product_line',
                        'set_type',
                        'sets_count',
                    ],
                ],
            ]);
    }

    /**
     * Test subcategories_id relationship for sets and subcategories.
     */
    public function test_sets_are_linked_to_subcategories(): void
    {
        $opSubcategory = RefSubcategory::where('desc', 'One Piece')->first();
        $this->assertNotNull($opSubcategory);

        $opSet = RefOnePieceSet::where('code', 'OP-01')->first();
        $this->assertNotNull($opSet);
        $this->assertEquals($opSubcategory->id, $opSet->subcategories_id);
        $this->assertEquals('One Piece', $opSet->subcategory->desc);

        $pokeSubcategory = RefSubcategory::where('desc', 'Pokemon')->first();
        $this->assertNotNull($pokeSubcategory);

        $pokeSet = RefPokemonSet::first();
        $this->assertNotNull($pokeSet);
        $this->assertEquals($pokeSubcategory->id, $pokeSet->subcategories_id);
        $this->assertEquals('Pokemon', $pokeSet->subcategory->desc);
    }

    /**
     * Test that Toys & Plushies category is seeded.
     */
    public function test_toys_and_plushies_category_is_seeded(): void
    {
        $category = RefCategory::where('desc', 'Toys & Plushies')->with('subcategories')->first();
        $this->assertNotNull($category);
        $this->assertGreaterThanOrEqual(4, $category->subcategories->count());
    }
}
