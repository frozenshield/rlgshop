<?php

namespace App\Services;

use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiProductAnalyzer
{
    /**
     * Analyze a product image and generate product details using Gemini Vision.
     *
     * @param  UploadedFile|string  $image  UploadedFile instance, URL string, or raw file path
     * @return array<string, mixed>
     */
    public function analyze(UploadedFile|string $image): array
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.');
        }

        $model = config('services.gemini.model', 'gemini-3.8-flash');

        [$mimeType, $base64Data, $storedImageUrl] = $this->processImage($image);

        // Fetch categories, brands, and conditions to guide the model with current database options
        $categoriesList = $this->buildCategoriesContext();
        $brandsList = $this->buildBrandsContext();
        $conditionsList = $this->buildConditionsContext();

        $prompt = <<<EOT
You are an expert product catalog and listing assistant for "RLG Hobby Shop", an e-commerce store specializing in Japanese Anime Figures, Collectibles, Gunpla Model Kits, and Trading Card Games (TCG) such as Pokémon, One Piece, Yu-Gi-Oh!, Gundam, Hololive, Weiß Schwarz, etc.

Analyze the uploaded product image in detail.
Identify:
1. Exact product or character name, franchise/series, set/edition, scale or model grade.
2. Manufacturer or brand (e.g. Bandai, Good Smile Company, Bushiroad, Takara Tomy, Pokémon Company, Kotobukiya, MegaHouse, etc.).
3. Best matching category and subcategory from the store catalog list below.
4. Product condition from the available conditions list (e.g. Brandnew/MISB if sealed in box/pack, Near Mint for pristine raw cards, BIB for opened box, Loose for unboxed figure).
5. Estimated physical shipping dimensions (weight in grams, length/width/height in cm) typical for this type of box or item.
6. Estimated fair market price in Philippine Pesos (PHP ₱).

Store Catalog Categories and Subcategories:
{$categoriesList}

Available Brands/Manufacturers:
{$brandsList}

Available Item Conditions:
{$conditionsList}

Respond ONLY with a JSON object strictly adhering to this structure:
{
  "title": "Full specific product title (include franchise, edition, character, or grade)",
  "short_summary": "1-2 sentence catchy summary highlighting key appeal",
  "description": "Engaging, well-structured HTML description using <h3>, <p>, <ul>, <li> tags explaining what the item is, what is included, authenticity, and highlights",
  "primary_category": "Name of best matching category from list above",
  "secondary_category": "Name of best matching subcategory from list above",
  "ref_category_id": <matching category id integer or null>,
  "ref_subcategory_id": <matching subcategory id integer or null>,
  "brand": "Manufacturer or Brand name",
  "ref_brand_id": <matching brand id integer from available brands list or null>,
  "condition": "Name of best matching condition from the available conditions list",
  "condition_id": <matching condition id integer from available conditions list or null>,
  "ref_condition_id": <matching condition id integer from available conditions list or null>,
  "weight": <estimated weight in grams as a number, e.g. 300>,
  "length": <estimated length in cm as a number, e.g. 14>,
  "width": <estimated width in cm as a number, e.g. 14>,
  "height": <estimated height in cm as a number, e.g. 4>,
  "hs_code": "Suggested HS tariff code, e.g. 9504.40.00 for TCG/playing cards, 9503.00.00 for figures/model kits/toys",
  "country_of_origin": "Country of manufacture or origin, e.g. Japan",
  "status": "active",
  "tags": ["array", "of", "6-10", "relevant", "search", "tags", "including", "franchise", "and", "type"],
  "suggested_price": <reasonable estimated price in Philippine Pesos (PHP) as a number, e.g. 3500>,
  "sku_suggestion": "A clean suggested SKU code, e.g. TCG-PKM-SV8-BOX or FIG-NEN-FRIEREN",
  "barcode_suggestion": "Suggested UPC/JAN code if visible on the box, otherwise null",
  "seo_title": "SEO title under 60 characters",
  "seo_description": "SEO meta description under 155 characters"
}
EOT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Data,
                            ],
                        ],
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.2,
            ],
        ];

        $primaryModel = config('services.gemini.model', 'gemini-3-flash-preview');
        $candidateModels = array_unique([$primaryModel, 'gemini-3-flash-preview', 'gemini-3.5-flash', 'gemini-flash-latest', 'gemini-3.1-pro-preview']);

        $lastError = null;
        $response = null;

        foreach ($candidateModels as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(45)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                break;
            }

            $lastError = $response->json('error.message') ?? 'HTTP '.$response->status();
            Log::warning("Gemini model {$model} failed: {$lastError}. Trying fallback model if available...");
        }

        if (! $response || $response->failed()) {
            throw new RuntimeException("Google Gemini API error: {$lastError}");
        }

        $rawText = $response->json('candidates.0.content.parts.0.text');

        if (empty($rawText)) {
            throw new RuntimeException('Gemini did not return any content for this image.');
        }

        $cleanJson = trim($rawText);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $cleanJson, $matches)) {
            $cleanJson = trim($matches[1]);
        }

        $result = json_decode($cleanJson, true);

        if (! is_array($result)) {
            $start = strpos($cleanJson, '{');
            if ($start !== false) {
                for ($len = strlen($cleanJson) - $start; $len > 0; $len--) {
                    $sub = substr($cleanJson, $start, $len);
                    $test = json_decode($sub, true);
                    if (is_array($test)) {
                        $result = $test;
                        break;
                    }
                }
            }
        }

        if (! is_array($result)) {
            Log::error('Failed to parse Gemini JSON', ['raw' => $rawText]);
            throw new RuntimeException('Failed to parse Gemini response as JSON.');
        }

        $result['image_url'] = $storedImageUrl;

        return $result;
    }

    /**
     * Process an image from UploadedFile, URL, or local path.
     *
     * @return array{0: string, 1: string, 2: ?string} [mimeType, base64Data, storedImageUrl]
     */
    protected function processImage(UploadedFile|string $image): array
    {
        if ($image instanceof UploadedFile) {
            $mimeType = $image->getMimeType() ?: 'image/jpeg';
            $base64Data = base64_encode($image->get());

            // Save image to public storage so it can be previewed/used
            $path = $image->store('products', 'public');
            $storedUrl = asset('storage/'.$path);

            return [$mimeType, $base64Data, $storedUrl];
        }

        // If string: could be a URL or local file path
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $imageResponse = Http::timeout(15)->withoutVerifying()->get($image);
            if ($imageResponse->failed()) {
                throw new RuntimeException('Failed to download image from the provided URL.');
            }

            $mimeType = $imageResponse->header('Content-Type') ?: 'image/jpeg';
            $base64Data = base64_encode($imageResponse->body());

            return [$mimeType, $base64Data, $image];
        }

        if (file_exists($image)) {
            $mimeType = mime_content_type($image) ?: 'image/jpeg';
            $base64Data = base64_encode(file_get_contents($image));

            return [$mimeType, $base64Data, null];
        }

        throw new RuntimeException('Invalid image provided.');
    }

    /**
     * Formats available store categories and subcategories for the Gemini prompt.
     */
    protected function buildCategoriesContext(): string
    {
        $categories = RefCategory::with('subcategories')->get();

        if ($categories->isEmpty()) {
            return "- TCG (Trading Cards)\n- Gunpla\n- Anime Figures\n- Anime Merch Collectibles";
        }

        $lines = [];
        foreach ($categories as $cat) {
            $subNames = $cat->subcategories->map(fn ($s) => "{$s->desc} (ID: {$s->id})")->join(', ');
            $lines[] = "- {$cat->desc} (ID: {$cat->id}): [{$subNames}]";
        }

        return implode("\n", $lines);
    }

    /**
     * Formats available store brands for the Gemini prompt.
     */
    protected function buildBrandsContext(): string
    {
        $brands = RefBrand::all();

        if ($brands->isEmpty()) {
            return '- The Pokémon Company, Bandai, Banpresto, Good Smile Company, Bushiroad, Takara Tomy, Konami, Kotobukiya';
        }

        return $brands->map(fn ($b) => "- {$b->name} (ID: {$b->id})")->join("\n");
    }

    /**
     * Formats available store item conditions for the Gemini prompt.
     */
    protected function buildConditionsContext(): string
    {
        $conditions = RefCondition::all();

        if ($conditions->isEmpty()) {
            return '- Near Mint, Damaged, Lightly Played, Moderately Played, Heavily Played, MISB, BIB, Loose, Brandnew';
        }

        return $conditions->map(fn ($c) => "- {$c->desc} (ID: {$c->id})")->join("\n");
    }
}
