<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouteService
{
    protected string $apiKey;
    protected int $timeout;
    protected string $baseUrl = 'https://api.openrouteservice.org';

    public function __construct()
    {
        $this->apiKey = (string) (config('services.openrouteservice.api_key') ?: env('ORS_API_KEY', ''));
        $this->timeout = (int) (config('services.openrouteservice.timeout') ?: 5);
    }

    /**
     * Menghitung rute dan jarak tempuh berkendara (driving-car).
     *
     * @return array{
     *     distance_km: float,
     *     duration_minutes: int,
     *     source: string,
     *     polyline: ?array,
     *     success: bool
     * }
     */
    public function getDrivingRoute(float $startLat, float $startLng, float $endLat, float $endLng): array
    {
        // 1. Caching 24 jam dengan presisi koordinat 4 desimal (~11 meter)
        $cacheKey = sprintf(
            'ors_route_%.4f_%.4f_%.4f_%.4f',
            $startLat,
            $startLng,
            $endLat,
            $endLng
        );

        return Cache::remember($cacheKey, 86400, function () use ($startLat, $startLng, $endLat, $endLng) {
            // 2. Coba panggil OpenRouteService API resmi jika API key tersedia
            if (! empty($this->apiKey)) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => $this->apiKey,
                        'Accept' => 'application/json, application/geo+json',
                    ])
                    ->timeout($this->timeout)
                    ->post($this->baseUrl . '/v2/directions/driving-car', [
                        'coordinates' => [
                            [$startLng, $startLat],
                            [$endLng, $endLat],
                        ],
                        'instructions' => false,
                        'geometry' => true,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $route = $data['routes'][0] ?? null;

                        if ($route && isset($route['summary']['distance'])) {
                            $distMeters = (float) $route['summary']['distance'];
                            $durationSecs = (float) ($route['summary']['duration'] ?? 0);

                            return [
                                'distance_km' => round(max(0.2, $distMeters / 1000.0), 2),
                                'duration_minutes' => max(3, (int) ceil($durationSecs / 60.0)),
                                'source' => 'openrouteservice',
                                'polyline' => $route['geometry'] ?? null,
                                'success' => true,
                            ];
                        }
                    } else {
                        Log::warning('ORS API directions failed', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (Exception $e) {
                    Log::warning('ORS API directions exception: ' . $e->getMessage());
                }
            }

            // 3. Fallback Cerdas: Haversine dikali rasio kelokan jalan Indonesia (1.35x)
            return $this->haversineRoadEstimate($startLat, $startLng, $endLat, $endLng);
        });
    }

    /**
     * Melakukan pencarian alamat / nama tempat (Geocoding forward).
     *
     * @return array<int, array{title: string, description: string, lat: float, lng: float}>
     */
    public function geocode(string $query, float $focusLat = -7.372821, float $focusLng = 112.719681): array
    {
        $cleanQuery = trim($query);
        if ($cleanQuery === '') {
            return [];
        }

        $cacheKey = 'ors_geocode_' . md5(mb_strtolower($cleanQuery) . sprintf('_%.2f_%.2f', $focusLat, $focusLng));

        return Cache::remember($cacheKey, 86400, function () use ($cleanQuery, $focusLat, $focusLng) {
            // A. Coba OpenRouteService Geocode resmi jika API key ada
            if (! empty($this->apiKey)) {
                try {
                    $response = Http::timeout($this->timeout)
                        ->get($this->baseUrl . '/geocode/search', [
                            'api_key' => $this->apiKey,
                            'text' => $cleanQuery,
                            'boundary.country' => 'ID',
                            'focus.point.lat' => $focusLat,
                            'focus.point.lon' => $focusLng,
                            'size' => 6,
                        ]);

                    if ($response->successful()) {
                        $features = $response->json('features') ?? [];
                        if (! empty($features)) {
                            $results = [];
                            foreach ($features as $f) {
                                $props = $f['properties'] ?? [];
                                $coords = $f['geometry']['coordinates'] ?? null;
                                if ($coords && count($coords) >= 2) {
                                    $results[] = [
                                        'title' => $props['name'] ?? ($props['label'] ?? 'Lokasi'),
                                        'description' => $props['label'] ?? '',
                                        'lat' => (float) $coords[1],
                                        'lng' => (float) $coords[0],
                                    ];
                                }
                            }
                            if (! empty($results)) {
                                return $results;
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('ORS Geocode exception: ' . $e->getMessage());
                }
            }

            // B. Fallback ke Photon Komoot (prioritas Surabaya & Sidoarjo)
            try {
                $photonRes = Http::timeout(4)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get('https://photon.komoot.io/api/', [
                        'q' => $cleanQuery,
                        'lat' => $focusLat,
                        'lon' => $focusLng,
                        'limit' => 6,
                    ]);

                if ($photonRes->successful()) {
                    $features = $photonRes->json('features') ?? [];
                    if (! empty($features)) {
                        $results = [];
                        foreach ($features as $f) {
                            $p = $f['properties'] ?? [];
                            $coords = $f['geometry']['coordinates'] ?? null;
                            if ($coords && count($coords) >= 2) {
                                $name = $p['name'] ?? ($p['street'] ?? 'Lokasi');
                                $parts = array_filter([$p['street'] ?? null, $p['district'] ?? null, $p['city'] ?? ($p['locality'] ?? null), $p['state'] ?? null]);
                                $results[] = [
                                    'title' => $name,
                                    'description' => implode(', ', $parts) ?: ($p['country'] ?? ''),
                                    'lat' => (float) $coords[1],
                                    'lng' => (float) $coords[0],
                                ];
                            }
                        }
                        if (! empty($results)) {
                            return $results;
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('Photon fallback exception: ' . $e->getMessage());
            }

            // C. Fallback ke Nominatim OpenStreetMap
            try {
                $nomRes = Http::timeout(4)
                    ->withHeaders(['User-Agent' => 'BrowniesKelana/1.0 (info@brownieskelana.com)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'json',
                        'countrycodes' => 'id',
                        'limit' => 6,
                        'viewbox' => '112.50,-7.50,112.90,-7.10',
                        'bounded' => 0,
                        'q' => $cleanQuery,
                    ]);

                if ($nomRes->successful()) {
                    $items = $nomRes->json();
                    if (is_array($items) && ! empty($items)) {
                        $results = [];
                        foreach ($items as $item) {
                            $results[] = [
                                'title' => explode(',', $item['display_name'] ?? '')[0] ?? 'Lokasi',
                                'description' => $item['display_name'] ?? '',
                                'lat' => (float) ($item['lat'] ?? 0),
                                'lng' => (float) ($item['lon'] ?? 0),
                            ];
                        }
                        return $results;
                    }
                }
            } catch (Exception $e) {
                Log::warning('Nominatim search exception: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Melakukan reverse geocoding dari koordinat latitude & longitude menjadi alamat teks.
     *
     * @return array{
     *     display_name: string,
     *     street: ?string,
     *     district: ?string,
     *     city: ?string,
     *     province: ?string,
     *     postal_code: ?string
     * }
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        $cacheKey = sprintf('ors_reverse_%.4f_%.4f', $lat, $lng);

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            // A. Coba OpenRouteService Reverse Geocode jika API key ada
            if (! empty($this->apiKey)) {
                try {
                    $response = Http::timeout($this->timeout)
                        ->get($this->baseUrl . '/geocode/reverse', [
                            'api_key' => $this->apiKey,
                            'point.lat' => $lat,
                            'point.lon' => $lng,
                            'boundary.country' => 'ID',
                            'size' => 1,
                        ]);

                    if ($response->successful()) {
                        $feat = $response->json('features.0');
                        if ($feat) {
                            $p = $feat['properties'] ?? [];
                            return [
                                'display_name' => $p['label'] ?? ($p['name'] ?? 'Titik Terpilih'),
                                'street' => $p['street'] ?? null,
                                'district' => $p['locality'] ?? null,
                                'city' => $p['county'] ?? ($p['region'] ?? null),
                                'province' => $p['region'] ?? 'Jawa Timur',
                                'postal_code' => $p['postalcode'] ?? null,
                            ];
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('ORS Reverse Geocode exception: ' . $e->getMessage());
                }
            }

            // B. Fallback ke Nominatim OSM
            try {
                $nomRes = Http::timeout(4)
                    ->withHeaders(['User-Agent' => 'BrowniesKelana/1.0 (info@brownieskelana.com)'])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'json',
                        'lat' => $lat,
                        'lon' => $lng,
                        'zoom' => 18,
                        'addressdetails' => 1,
                    ]);

                if ($nomRes->successful()) {
                    $data = $nomRes->json();
                    $addr = $data['address'] ?? [];

                    return [
                        'display_name' => $data['display_name'] ?? 'Titik Koordinat Terpilih',
                        'street' => $addr['road'] ?? ($addr['suburb'] ?? null),
                        'district' => $addr['suburb'] ?? ($addr['neighbourhood'] ?? null),
                        'city' => $addr['city'] ?? ($addr['county'] ?? null),
                        'province' => $addr['state'] ?? 'Jawa Timur',
                        'postal_code' => $addr['postcode'] ?? null,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Nominatim reverse geocode exception: ' . $e->getMessage());
            }

            return [
                'display_name' => sprintf('Titik Koordinat (%.6f, %.6f)', $lat, $lng),
                'street' => null,
                'district' => null,
                'city' => null,
                'province' => 'Jawa Timur',
                'postal_code' => null,
            ];
        });
    }

    /**
     * Menghitung estimasi jalan raya menggunakan Haversine dengan faktor kelokan 1.35x.
     *
     * @return array{distance_km: float, duration_minutes: int, source: string, polyline: null, success: bool}
     */
    protected function haversineRoadEstimate(float $startLat, float $startLng, float $endLat, float $endLng): array
    {
        $straightKm = $this->calculateHaversineDistance($startLat, $startLng, $endLat, $endLng);
        $roadKm = round(max(0.5, $straightKm * 1.35), 2);

        // Estimasi kecepatan urban rata-rata ~28 km/jam di Surabaya/Sidoarjo + waktu buffer
        $durationMinutes = max(5, (int) round(($roadKm / 28.0) * 60));

        return [
            'distance_km' => $roadKm,
            'duration_minutes' => $durationMinutes,
            'source' => 'haversine_fallback',
            'polyline' => null,
            'success' => true,
        ];
    }

    /**
     * Rumus Haversine menghitung jarak garis lurus bola bumi.
     */
    public function calculateHaversineDistance(float $latFrom, float $lonFrom, float $latTo, float $lonTo): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($latTo - $latFrom);
        $dLon = deg2rad($lonTo - $lonFrom);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($latFrom)) * cos(deg2rad($latTo)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 3);
    }
}

