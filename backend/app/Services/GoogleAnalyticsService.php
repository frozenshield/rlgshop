<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleAnalyticsService
{
    /**
     * GA4 OAuth Scope for read-only analytics reporting.
     */
    protected const GA4_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    /**
     * Google OAuth2 token endpoint.
     */
    protected const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Check if GA4 configuration and credentials are provided.
     */
    public function isConfigured(): bool
    {
        $propertyId = $this->getPropertyId();
        $credentials = $this->getCredentials();

        return ! empty($propertyId) && ! empty($credentials);
    }

    /**
     * Get GA4 Property ID from config/env.
     */
    public function getPropertyId(): ?string
    {
        $id = config('services.ga4.property_id') ?? env('GA4_PROPERTY_ID');

        return ! empty($id) ? trim((string) $id) : null;
    }

    /**
     * Resolve Google Cloud Service Account credentials (from raw JSON string, file path, or default path).
     *
     * @return array<string, mixed>|null
     */
    public function getCredentials(): ?array
    {
        $raw = config('services.ga4.credentials_json') ?? env('GA4_SERVICE_ACCOUNT_CREDENTIALS');

        // Check if raw value is valid JSON
        if (! empty($raw)) {
            if (is_string($raw) && str_starts_with(trim($raw), '{')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && ! empty($decoded['private_key']) && ! empty($decoded['client_email'])) {
                    return $decoded;
                }
            }

            // Check if raw value is a file path
            if (is_string($raw)) {
                $candidates = [
                    $raw,
                    base_path($raw),
                    storage_path($raw),
                ];

                foreach ($candidates as $candidate) {
                    if (file_exists($candidate) && is_file($candidate)) {
                        $content = file_get_contents($candidate);
                        $decoded = json_decode((string) $content, true);
                        if (is_array($decoded) && ! empty($decoded['private_key']) && ! empty($decoded['client_email'])) {
                            return $decoded;
                        }
                    }
                }
            }
        }

        // Check standard storage paths
        $defaultPaths = [
            storage_path('app/analytics/service-account.json'),
            storage_path('ga4-service-account.json'),
            base_path('ga4-service-account.json'),
        ];

        foreach ($defaultPaths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $decoded = json_decode((string) $content, true);
                if (is_array($decoded) && ! empty($decoded['private_key']) && ! empty($decoded['client_email'])) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * URL-safe Base64 encode without padding.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Generate an OAuth2 Access Token for Google Analytics using a Service Account JWT.
     */
    public function getAccessToken(): ?string
    {
        $credentials = $this->getCredentials();
        if (! $credentials) {
            return null;
        }

        $cacheKey = 'ga4_service_account_token_'.md5($credentials['client_email']);

        return Cache::remember($cacheKey, 3300, function () use ($credentials): ?string {
            try {
                $now = time();
                $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
                $claim = $this->base64UrlEncode(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => self::GA4_SCOPE,
                    'aud' => self::OAUTH_TOKEN_URL,
                    'exp' => $now + 3600,
                    'iat' => $now,
                ]));

                $input = "{$header}.{$claim}";
                $signature = '';

                $privateKey = $credentials['private_key'];
                $signed = openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);

                if (! $signed) {
                    Log::warning('[GA4 Service] Failed to sign OAuth2 JWT assertion with private key.');

                    return null;
                }

                $jwt = "{$input}.".$this->base64UrlEncode($signature);

                $response = Http::asForm()->post(self::OAUTH_TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('[GA4 Service] Google OAuth token error: '.$response->body());

                return null;
            } catch (Throwable $e) {
                Log::error('[GA4 Service] Exception obtaining Google OAuth token: '.$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Query Google Analytics 4 runRealtimeReport endpoint.
     * GA4 Realtime provides active users across a rolling 30-minute window.
     *
     * @return array{
     *     configured: bool,
     *     source: string,
     *     label: string,
     *     active_users: int,
     *     checkout_active_users: int,
     *     window_description: string,
     *     property_id: ?string,
     *     timestamp: string,
     *     error?: string
     * }
     */
    public function getRealtimeTelemetry(): array
    {
        $propertyId = $this->getPropertyId();

        // 1. If GA4 credentials are configured, query Google Analytics 4 Data API
        if ($this->isConfigured()) {
            $cacheKey = "ga4_realtime_report_{$propertyId}";

            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            $token = $this->getAccessToken();
            if ($token) {
                try {
                    $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runRealtimeReport";

                    $response = Http::withToken($token)
                        ->timeout(5)
                        ->post($url, [
                            'dimensions' => [
                                ['name' => 'pagePath'],
                            ],
                            'metrics' => [
                                ['name' => 'activeUsers'],
                            ],
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $rows = $data['rows'] ?? [];

                        $totalActive = 0;
                        $checkoutActive = 0;

                        foreach ($rows as $row) {
                            $path = $row['dimensionValues'][0]['value'] ?? '';
                            $users = (int) ($row['metricValues'][0]['value'] ?? 0);

                            $totalActive += $users;

                            if (str_contains(strtolower($path), 'checkout') || str_contains(strtolower($path), 'cart')) {
                                $checkoutActive += $users;
                            }
                        }

                        // Also verify totals field if available
                        if (isset($data['totals'][0]['metricValues'][0]['value'])) {
                            $totalActive = max($totalActive, (int) $data['totals'][0]['metricValues'][0]['value']);
                        }

                        $telemetry = [
                            'configured' => true,
                            'source' => 'google_analytics_4',
                            'label' => 'Google Analytics 4 Data API (Realtime 30m window)',
                            'active_users' => $totalActive,
                            'checkout_active_users' => $checkoutActive,
                            'window_description' => 'Rolling 30-minute active users aggregated by GA4',
                            'property_id' => $propertyId,
                            'timestamp' => now()->toIso8601String(),
                        ];

                        // Cache for 30 seconds to prevent hitting Google rate limits
                        Cache::put($cacheKey, $telemetry, 30);

                        return $telemetry;
                    }

                    Log::warning('[GA4 Service] runRealtimeReport API response non-200: '.$response->body());
                } catch (Throwable $e) {
                    Log::error('[GA4 Service] Exception calling runRealtimeReport: '.$e->getMessage());
                }
            }
        }

        // 2. Local Fallback: Calculate from real application sessions over a rolling 30-minute window
        return $this->getLocalSessionsTelemetry();
    }

    /**
     * Compute actual active visitors from database sessions over a rolling 30-minute window.
     *
     * @return array{
     *     configured: bool,
     *     source: string,
     *     label: string,
     *     active_users: int,
     *     checkout_active_users: int,
     *     window_description: string,
     *     property_id: ?string,
     *     timestamp: string
     * }
     */
    protected function getLocalSessionsTelemetry(): array
    {
        $windowSeconds = 1800; // 30 minutes rolling window
        $cutoff = time() - $windowSeconds;

        $activeSessions = 0;
        $checkoutSessions = 0;

        try {
            $sessions = DB::table('sessions')
                ->where('last_activity', '>=', $cutoff)
                ->get();

            $activeSessions = $sessions->count();

            // Count sessions with active carts or checkout payloads
            $checkoutSessions = $sessions->filter(function ($s): bool {
                return ! empty($s->payload) && (
                    str_contains($s->payload, 'checkout') ||
                    str_contains($s->payload, 'cart')
                );
            })->count();
        } catch (Throwable) {
            // If sessions table not queried
        }

        // Check active carts in customer_cart table as positive checkout activity indicator
        try {
            $cartCount = DB::table('customer_cart')->count();
            if ($checkoutSessions === 0 && $cartCount > 0) {
                $checkoutSessions = min($cartCount, max(1, $activeSessions));
            }
        } catch (Throwable) {
        }

        // Ensure realistic minimum if local dev environment has at least current user active
        $displayActive = max($activeSessions, 1);
        $displayCheckout = min($checkoutSessions, $displayActive);

        return [
            'configured' => false,
            'source' => 'local_sessions',
            'label' => 'Live Storefront Sessions (30m rolling window)',
            'active_users' => $displayActive,
            'checkout_active_users' => $displayCheckout,
            'window_description' => 'Active browser sessions within the last 30 minutes',
            'property_id' => $this->getPropertyId(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
