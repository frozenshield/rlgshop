<?php

namespace App\Services;

use App\Models\RefCategory;
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

        // Fetch categories to guide the model with current database options
        $categoriesList = $this->buildCategoriesContext();

        $prompt = <<<EOT
You are an expert product catalog and listing assistant for "RLG Hobby Shop", an e-commerce store specializing in Japanese Anime Figures, Collectibles, Gunpla Model Kits, and Trading Card Games (TCG) such as Pokémon, One Piece, Hololive, Weiß Schwarz, etc.

Analyze the uploaded product image in detail.
Identify:
1. Exact product or character name, franchise/series, set/edition, scale or model grade.
2. Manufacturer or brand (e.g. Bandai, Good Smile Company, Bushiroad, Takara Tomy, Pokémon Company, Kotobukiya, MegaHouse, etc.).
3. Best matching category and subcategory from the store catalog list below.
4. Estimated fair market price in Philippine Pesos (PHP ₱).

Store Catalog Categories and Subcategories:
{$categoriesList}

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

        $response = Http::timeout(45)
            ->withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $errorMsg = $response->json('error.message') ?? 'Failed to communicate with Google Gemini API.';
            throw new RuntimeException("Google Gemini API error: {$errorMsg}");
        }

        $rawText = $response->json('candidates.0.content.parts.0.text');

        if (empty($rawText)) {
            throw new RuntimeException('Gemini did not return any content for this image.');
        }

        $result = json_decode($rawText, true);

        if (! is_array($result)) {
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
            return "- TCG (Trading Cards)\n- Anime Figures\n- Gunpla & Model Kits\n- Anime Merchandise\n- Hobby Supplies";
        }

        $lines = [];
        foreach ($categories as $cat) {
            $subNames = $cat->subcategories->map(fn ($s) => "{$s->desc} (ID: {$s->id})")->join(', ');
            $lines[] = "- {$cat->desc} (ID: {$cat->id}): [{$subNames}]";
        }

        return implode("\n", $lines);
    }
}
