<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GeminiVisionService
{
    /**
     * Ordered free-tier fallback models.
     * @var string[]
     */
    /**
     * Ordered supported models: prefers current GA Flash-Lite model for high-throughput,
     * low-cost structured vision analysis, with Flash and legacy fallbacks.
     * @var string[]
     */
    protected array $fallbackModels = [
        'gemini-3.5-flash-lite',
        'gemini-3.5-flash',
        'gemini-2.5-flash',
    ];

    /**
     * Maximum consecutive quota/rate-limit failures before failing fast to protect API quota.
     */
    protected int $maxConsecutiveQuotaFailures = 2;

    protected string $apiUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        // Base URL is built per-model when calling the Google Gemini REST endpoint.
        $this->apiUrl = (string) (config('services.gemini.api_url') ?: 'https://generativelanguage.googleapis.com/v1beta/models');
        $this->apiKey = config('services.gemini.api_key');
    }

    public function getFallbackModels(): array
    {
        return $this->fallbackModels;
    }

    public function setFallbackModels(array $models): self
    {
        $this->fallbackModels = $models;
        return $this;
    }

    /**
     * Determine if an HTTP response or body indicates a retryable rate limit or service error.
     */
    public function isRetryableRateLimit(int $status, string $body): bool
    {
        return $status === 429
            || $status === 503
            || stripos($body, 'RESOURCE_EXHAUSTED') !== false
            || stripos($body, 'UNAVAILABLE') !== false
            || stripos($body, 'quota') !== false
            || stripos($body, 'rate limit') !== false;
    }

    /**
     * Compute and execute controlled exponential backoff with jitter for retryable 429/503 responses.
     */
    public function handleRetryBackoff(?int $retryAfterHeader, int $attempt): float
    {
        if ($retryAfterHeader !== null && $retryAfterHeader > 0) {
            $delay = min(3.0, (float) $retryAfterHeader);
        } else {
            $base = 0.5 * (2 ** min($attempt, 3));
            $jitter = mt_rand(0, 150) / 1000;
            $delay = min(2.5, $base + $jitter);
        }

        Log::info("GeminiVisionService: Controlled backoff for {$delay}s (attempt {$attempt}) on rate limit/service busy.");
        $this->sleepBackoff($delay);

        return $delay;
    }

    /**
     * Sleep helper for backoff, bypassed during automated unit tests.
     */
    protected function sleepBackoff(float $seconds): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        usleep((int) ($seconds * 1000000));
    }

    /**
     * Validate whether an uploaded image is floral or event-related.
     *
     * Uses Gemini Vision to inspect the image and determine if it contains
     * flowers, floral arrangements, event decorations, bouquets, or related content.
     * Rejects electronic devices, vapes, random objects, memes, screenshots, etc.
     *
     * @param string $filePath Local filesystem path to the image
     * @return array{is_valid: bool, rejection_reason: string|null, model_used: string|null}
     */
    public function validateImage(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'is_valid' => false,
                'rejection_reason' => 'Image file not found.',
                'model_used' => null,
                'error_type' => 'analysis',
            ];
        }

        $validationPrompt = <<<'PROMPT'
You are a strict image content validator for a floral event services company called Raflora Enterprises.

Your ONLY task is to determine whether the uploaded image is relevant to floral arrangements, event decorations, or event planning.

The image must also be sufficiently usable for reliable material analysis. Reject images that are blurry, distorted, corrupted, extremely low-resolution, too dark, overexposed, obstructed, or have important arrangement details that are not visible.

ACCEPTED images (is_floral_or_event_related = true):
- Flower arrangements, bouquets, corsages, centerpieces
- Individual flowers or bunches of flowers (fresh, dried, or artificial)
- Event venue decorations (weddings, birthdays, corporate events, debuts, funerals)
- Floral-themed mood boards or collages
- Event setup references (arches, table settings, backdrops with floral elements)
- Plants, greenery, foliage used in event styling
- Handmade floral crafts (ribbon roses, fabric flowers, foam flowers)

REJECTED images (is_floral_or_event_related = false):
- Electronic devices (phones, laptops, vapes, e-cigarettes, gadgets)
- Random objects unrelated to events (food, cars, tools, clothing)
- Memes, screenshots, text-only images, selfies without floral context
- Explicit, offensive, or inappropriate content
- Animals (unless part of an event decoration context)
- Blank or corrupted images

Return ONLY valid JSON with this exact schema:
{
  "is_floral_or_event_related": true/false,
    "is_clear_usable": true/false,
  "rejection_reason": "string or null"
}

Set is_clear_usable to true only when the image is clear enough to reliably identify relevant flowers, materials, or event-decor details. If either validation condition fails, set rejection_reason to a short, polite, user-friendly explanation (1-2 sentences max) of why the image was rejected.
PROMPT;

        $preparedImage = $this->prepareImageForGemini($filePath);
        if (is_array($preparedImage)) {
            $mimeType = $preparedImage['mime_type'];
            $base64 = $preparedImage['data'];
            $tempFilePath = null;
        } else {
            $tempFilePath = $preparedImage;
            $fileContents = file_get_contents($tempFilePath);
            $base64 = base64_encode($fileContents);
            $mimeType = mime_content_type($tempFilePath) ?: 'image/jpeg';
        }

        $lastException = null;
        $lastFailureType = 'analysis';
        $consecutiveQuotaFailures = 0;
        $attempt = 0;

        foreach ($this->fallbackModels as $model) {
            $attempt++;
            try {
                if (empty($this->apiKey)) {
                    throw new \Exception('GEMINI_API_KEY is not configured in environment.');
                }

                $endpoint = rtrim($this->apiUrl, '/') . "/{$model}:generateContent?key=" . urlencode($this->apiKey);

                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $validationPrompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data'      => $base64,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ];

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

                $status = $response->status();
                $body = $response->body();

                if ($this->isRetryableRateLimit($status, $body)) {
                    $lastFailureType = 'service';
                    $consecutiveQuotaFailures++;
                    Log::warning("Image validation: Quota/rate-limit reached for {$model} (status={$status}).");

                    if ($consecutiveQuotaFailures >= $this->maxConsecutiveQuotaFailures) {
                        Log::warning("Image validation: Quota exhausted across models; stopping early to protect API quota.");
                        break;
                    }

                    $retryAfter = $response->header('Retry-After');
                    $this->handleRetryBackoff($retryAfter ? (int) $retryAfter : null, $attempt);
                    continue;
                }

                if ($status === 404) {
                    Log::warning("Image validation: Model {$model} not found. Trying next model.");
                    continue;
                }

                if ($status >= 400) {
                    throw new \Exception("Gemini API error (model={$model}) status={$status}: {$body}");
                }

                $responseData = $response->json();
                $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (is_string($generatedText)) {
                    $generatedText = trim($generatedText);
                }

                if (empty($generatedText)) {
                    Log::warning("Image validation: Empty response from model {$model}.");
                    continue;
                }

                $parsed = $this->extractFirstJson($generatedText);

                if ($parsed === null) {
                    Log::warning("Image validation: Could not parse JSON from model {$model}. Raw: {$generatedText}");
                    continue;
                }

                $isValid = $parsed['is_floral_or_event_related'] ?? false;
                $isClearUsable = $parsed['is_clear_usable'] ?? false;
                $rejectionReason = $parsed['rejection_reason'] ?? null;

                if ($isValid !== true || $isClearUsable !== true) {
                    return [
                        'is_valid' => false,
                        'rejection_reason' => $rejectionReason ?? 'The image is not clear enough for reliable analysis. Please upload a clearer image.',
                        'model_used' => $model,
                        'error_type' => 'image_quality',
                    ];
                }

                return [
                    'is_valid' => true,
                    'rejection_reason' => null,
                    'model_used' => $model,
                    'error_type' => null,
                ];
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                $lastFailureType = 'connection';
                Log::warning("Image validation: Connection timeout for model {$model}.", ['message' => $e->getMessage()]);
                continue;
            } catch (\Exception $e) {
                $lastException = $e;
                $msg = $e->getMessage();
                if (stripos($msg, 'timeout') !== false || stripos($msg, 'timed out') !== false || stripos($msg, 'connect') !== false || stripos($msg, 'connection') !== false) {
                    $lastFailureType = 'connection';
                } elseif (stripos($msg, 'RESOURCE_EXHAUSTED') !== false || stripos($msg, 'UNAVAILABLE') !== false || stripos($msg, 'quota') !== false || stripos($msg, '429') !== false || stripos($msg, '503') !== false || stripos($msg, 'rate limit') !== false) {
                    $lastFailureType = 'service';
                    $consecutiveQuotaFailures++;
                    Log::warning("Image validation: Quota/limit for {$model}.");
                    if ($consecutiveQuotaFailures >= $this->maxConsecutiveQuotaFailures) {
                        Log::warning("Image validation: Quota exhausted across models; stopping early.");
                        break;
                    }
                    $this->handleRetryBackoff(null, $attempt);
                    continue;
                } else {
                    $lastFailureType = 'analysis';
                }
                Log::error("Image validation failed for model {$model}.", ['message' => $msg]);
                continue;
            } finally {
                if (isset($tempFilePath) && file_exists($tempFilePath) && $tempFilePath !== $filePath) {
                    @unlink($tempFilePath);
                }
            }
        }

        if (isset($tempFilePath) && file_exists($tempFilePath) && $tempFilePath !== $filePath) {
            @unlink($tempFilePath);
        }

        Log::warning('Image validation: All Gemini models failed.', [
            'last_exception' => $lastException?->getMessage(),
            'file' => $filePath,
        ]);

        return [
            'is_valid' => false,
            'rejection_reason' => self::failureMessageForType($lastFailureType),
            'model_used' => null,
            'error_type' => $lastFailureType,
        ];
    }

    public static function classifyFailure(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if ($exception instanceof \Illuminate\Http\Client\ConnectionException
            || stripos($message, 'timeout') !== false
            || stripos($message, 'timed out') !== false
            || stripos($message, 'connect') !== false
            || stripos($message, 'connection') !== false) {
            return 'connection';
        }

        if (stripos($message, 'RESOURCE_EXHAUSTED') !== false
            || stripos($message, 'UNAVAILABLE') !== false
            || stripos($message, 'quota') !== false
            || stripos($message, '429') !== false
            || stripos($message, '503') !== false
            || stripos($message, 'rate limit') !== false) {
            return 'service';
        }

        if (stripos($message, 'empty_or_invalid_analysis') !== false
            || stripos($message, 'empty') !== false
            || stripos($message, 'invalid json') !== false
            || stripos($message, 'missing generated text') !== false
            || stripos($message, 'no inspiration image available') !== false) {
            return 'analysis';
        }

        return 'analysis';
    }

    public static function failureMessageForType(string $type): string
    {
        return match ($type) {
            'image_quality' => 'The image could not be analyzed. Please choose a clearer floral or event photo.',
            'connection' => 'Image analysis could not be completed because of a connection problem. Please check your internet connection and try again.',
            'timeout' => 'Image analysis timed out. Please try again.',
            'service' => 'Image analysis could not be completed right now. Please try again.',
            'analysis' => 'Image analysis could not be completed. Please try again with a clearer image or a different photo.',
            default => 'Image analysis could not be completed. Please try again.',
        };
    }

    public function coerceAnalysisMaterials(mixed $materials): array
    {
        if (is_array($materials)) {
            return array_values(array_filter($materials, fn ($item) => is_array($item)));
        }

        if (is_object($materials)) {
            $decoded = json_decode(json_encode($materials), true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => is_array($item)));
            }
        }

        if (is_string($materials)) {
            $decoded = json_decode($materials, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => is_array($item)));
            }
        }

        return [];
    }

    public function normalizeAreaAnalysis(array $analysis): array
    {
        $materials = $this->coerceAnalysisMaterials($analysis['suggested_materials'] ?? []);
        if ($materials === []) {
            return [];
        }

        $groups = [];

        foreach ($materials as $material) {
            if (!is_array($material)) {
                continue;
            }

            $areaKey = strtolower(trim((string) ($material['area'] ?? $material['location'] ?? 'overall')));
            $areaKey = $areaKey === '' ? 'overall' : $areaKey;
            $areaLabel = $this->formatAreaLabel($areaKey);

            $quantity = $material['quantity'] ?? $material['estimated_quantity'] ?? 0;
            $unitType = (string) ($material['unit_type'] ?? $material['unit'] ?? 'pcs');
            $unitCost = $material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'] ?? 0;
            $detected = filter_var($material['is_detected'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $recommendation = filter_var($material['is_recommendation'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $visualLocation = $this->normalizeVisualLocation($material['visual_location'] ?? null);
            $quality = $this->deriveItemConfidenceMetadata($material, $detected, $recommendation, $visualLocation);

            $groups[$areaKey]['area_key'] = $areaKey;
            $groups[$areaKey]['area_label'] = $areaLabel;
            $normalizedItem = [
                'name' => trim((string) ($material['item_name'] ?? 'Unknown item')),
                'area_key' => $areaKey,
                'area_label' => $areaLabel,
                'category' => strtolower(trim((string) ($material['category'] ?? 'prop'))),
                'quantity' => is_numeric($quantity) ? (float) $quantity : 0.0,
                'unit_type' => trim($unitType) !== '' ? trim($unitType) : 'pcs',
                'unit_cost_php' => is_numeric($unitCost) ? (float) $unitCost : 0.0,
                'source_label' => $quality['source_label'],
                'source_type' => $quality['source_type'],
                'note' => trim((string) ($material['note'] ?? '')),
                'visual_location' => $visualLocation,
                'confidence' => $quality['confidence'],
                'confidence_percent' => $quality['confidence_percent'],
                'detected_regions' => $quality['detected_regions'],
                'needs_review' => $quality['needs_review'],
                'review_message' => $quality['review_message'],
                'is_estimated' => $quality['is_estimated'],
                'display_tag' => $quality['source_label'],
            ];
            $groups[$areaKey]['items'][] = $normalizedItem;
        }

        foreach ($groups as &$group) {
            $group['summary'] = count($group['items']) . ' item' . (count($group['items']) === 1 ? '' : 's');
        }
        unset($group);

        return array_values($groups);
    }

    protected function deriveItemConfidenceMetadata(array $material, bool $detected, bool $recommendation, ?array $visualLocation): array
    {
        $rawConfidence = $material['confidence'] ?? $material['ai_confidence'] ?? null;
        $confidence = is_numeric($rawConfidence) ? (float) $rawConfidence : null;
        if ($confidence !== null && $confidence > 1 && $confidence <= 100) {
            $confidence /= 100;
        }
        if ($confidence !== null && ($confidence < 0 || $confidence > 1)) {
            $confidence = null;
        }

        $detectedRegions = is_numeric($material['detected_regions'] ?? null) ? (int) $material['detected_regions'] : (($detected && $visualLocation !== null) ? 1 : 0);
        $reviewMessage = trim((string) ($material['review_message'] ?? $material['review_reason'] ?? ''));
        $hasExplicitReviewFlag = filter_var($material['needs_review'] ?? $material['review_required'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $baseMagnitude = min(1, max(0, $detectedRegions / 8));
        $locationEvidence = $visualLocation !== null ? 0.12 : 0.0;
        $boxEvidence = 0.0;
        if ($visualLocation !== null && isset($visualLocation['width'], $visualLocation['height'])) {
            $boxArea = (float) $visualLocation['width'] * (float) $visualLocation['height'];
            $boxEvidence = min(0.08, sqrt(max(0, $boxArea)) * 0.12);
        }
        $reviewPenalty = $hasExplicitReviewFlag || $reviewMessage !== '' ? 0.16 : 0.0;

        if ($confidence === null) {
            if ($recommendation) {
                $confidence = 0.45 + ($baseMagnitude * 0.08);
            } elseif ($visualLocation !== null) {
                $confidence = 0.75 + $locationEvidence + $boxEvidence + ($baseMagnitude * 0.08);
            } elseif ($detected) {
                $confidence = 0.56 + ($baseMagnitude * 0.12);
            } else {
                $confidence = 0.35 + ($baseMagnitude * 0.08);
            }

            $confidence -= $reviewPenalty;
        }

        $confidence = min(0.99, max(0.15, $confidence));
        $sourceLabel = ($detected && !$recommendation) ? 'AI Detected' : 'Estimated';
        $sourceType = ($detected && !$recommendation) ? 'detected' : 'recommendation';
        $needsReview = $recommendation
            || $confidence < 0.8
            || ($detected && $visualLocation === null)
            || $hasExplicitReviewFlag
            || $reviewMessage !== '';

        if ($reviewMessage === '') {
            if ($recommendation) {
                $reviewMessage = 'This item is estimated and may not be visible in the uploaded image.';
            } elseif ($detected && $visualLocation === null) {
                $reviewMessage = 'Some flowers are overlapping or the item is not clearly visible.';
            } elseif ($confidence < 0.8) {
                $reviewMessage = 'AI confidence is low for this item and it may need manual review.';
            }
        }

        return [
            'confidence' => round($confidence, 2),
            'confidence_percent' => (int) round($confidence * 100),
            'detected_regions' => max(0, $detectedRegions),
            'source_label' => $sourceLabel,
            'source_type' => $sourceType,
            'needs_review' => $needsReview,
            'review_message' => $reviewMessage,
            'is_estimated' => $recommendation || !($detected && !$recommendation),
        ];
    }

    protected function normalizeVisualLocation(mixed $location): ?array
    {
        if (!is_array($location) || !isset($location['x'], $location['y'])
            || !is_numeric($location['x']) || !is_numeric($location['y'])) {
            return null;
        }

        $x = (float) $location['x'];
        $y = (float) $location['y'];
        if (!is_finite($x) || !is_finite($y) || $x < 0 || $x > 1 || $y < 0 || $y > 1) {
            return null;
        }

        $normalized = ['x' => $x, 'y' => $y];
        foreach (['width', 'height'] as $dimension) {
            if (!array_key_exists($dimension, $location)) {
                continue;
            }

            if (!is_numeric($location[$dimension])) {
                return null;
            }

            $value = (float) $location[$dimension];
            if (!is_finite($value) || $value < 0) {
                return null;
            }

            $remaining = $dimension === 'width' ? 1 - $x : 1 - $y;
            $normalized[$dimension] = round(min($value, $remaining), 6);
        }

        return $normalized;
    }

    protected function formatAreaLabel(string $areaKey): string
    {
        $map = [
            'ceiling' => 'Ceiling',
            'stage' => 'Stage',
            'altar' => 'Altar',
            'entrance' => 'Entrance',
            'tables' => 'Table Styling',
            'table' => 'Table Styling',
            'backdrop' => 'Backdrop',
            'wall' => 'Wall Decor',
            'aisle' => 'Aisle',
            'overall' => 'Overall Design',
            'general' => 'Overall Design',
        ];

        $normalized = strtolower(trim($areaKey));
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        return ucwords(str_replace(['-', '_'], ' ', $normalized));
    }

    /**
     * Validate the minimum complete analysis contract before it can reach a booking.
     * Invalid rows reject the whole response so partial results cannot be persisted.
     */
    public function normalizeAiAnalysis(array $analysis): array
    {
        $materials = $analysis['suggested_materials'] ?? [];
        if (!is_array($materials)) {
            $materials = [];
        }

        $normalized = [];
        foreach ($materials as $material) {
            if (!is_array($material)) {
                continue;
            }

            $itemName = trim((string) ($material['item_name'] ?? ''));
            if ($itemName === '') {
                continue;
            }

            $category = strtolower(trim((string) ($material['category'] ?? '')));
            if (!in_array($category, ['flower', 'foliage', 'prop', 'supply'], true)) {
                $category = $this->inferCategoryFromItemName($itemName);
            }

            $unitType = strtolower(trim((string) ($material['unit_type'] ?? $material['unit'] ?? '')));
            if (!in_array($unitType, ['stem', 'bunch', 'piece', 'set', 'pcs'], true)) {
                $unitType = $this->defaultUnitForCategory($category);
            }

            $detected = filter_var($material['is_detected'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $recommended = filter_var($material['is_recommendation'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $quantity = is_numeric($material['quantity'] ?? $material['estimated_quantity'] ?? null)
                ? (float) ($material['quantity'] ?? $material['estimated_quantity'])
                : 1.0;
            $quantity = max(1.0, $quantity);

            $unitCost = is_numeric($material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'] ?? null)
                ? (float) ($material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'])
                : $this->estimateItemCostPhp($itemName, $category);

            $normalized[] = array_merge($material, [
                'item_name' => $itemName,
                'category' => $category,
                'unit_type' => $unitType,
                'quantity' => $quantity,
                'estimated_quantity' => $quantity,
                'unit_cost_php' => round($unitCost, 2),
                'estimated_unit_cost_php' => round($unitCost, 2),
                'is_detected' => $detected && !$recommended,
                'is_recommendation' => $recommended,
                'is_custom_item' => (bool) ($material['is_custom_item'] ?? false),
                'visual_location' => ($detected && !$recommended) ? $this->normalizeVisualLocation($material['visual_location'] ?? null) : null,
                'area' => $this->normalizeAreaKey($material['area'] ?? $material['location'] ?? 'overall'),
                'note' => trim((string) ($material['note'] ?? '')),
                'suggested_alternative' => !empty($material['suggested_alternative']) ? trim((string) $material['suggested_alternative']) : null,
                'alternative_reason' => !empty($material['alternative_reason']) ? trim((string) $material['alternative_reason']) : null,
                'seasonality_status' => !empty($material['seasonality_status']) ? trim((string) $material['seasonality_status']) : null,
                'seasonal_notes' => !empty($material['seasonal_notes']) ? trim((string) $material['seasonal_notes']) : null,
                'seasonal_sources' => !empty($material['seasonal_sources']) && is_array($material['seasonal_sources']) ? $material['seasonal_sources'] : null,
            ]);
        }

        $analysis['suggested_materials'] = $normalized;
        $analysis['pricing_summary'] = $this->buildPricingSummary($normalized);

        return $analysis;
    }

    public function buildPricingSummary(array $materials): array
    {
        $rawMaterialsTotal = 0.0;
        $itemizedBreakdown = [];

        foreach ($materials as $material) {
            if (!is_array($material)) {
                continue;
            }

            $quantity = is_numeric($material['quantity'] ?? $material['estimated_quantity'] ?? null)
                ? (float) ($material['quantity'] ?? $material['estimated_quantity'])
                : 0.0;
            $unitCost = is_numeric($material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'] ?? null)
                ? (float) ($material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'])
                : 0.0;

            if ($quantity <= 0 || $unitCost <= 0) {
                continue;
            }

            $subtotal = round($quantity * $unitCost, 2);
            $rawMaterialsTotal += $subtotal;
            $itemizedBreakdown[] = [
                'item_name' => trim((string) ($material['item_name'] ?? 'Unknown item')),
                'unit_type' => trim((string) ($material['unit_type'] ?? 'pcs')),
                'quantity' => $quantity,
                'estimated_quantity' => $quantity,
                'unit_cost_php' => round($unitCost, 2),
                'estimated_unit_cost_php' => round($unitCost, 2),
                'estimated_subtotal_php' => $subtotal,
            ];
        }

        $rawMaterialsTotal = round($rawMaterialsTotal, 2);
        $estimatedGrandTotal = round($rawMaterialsTotal * 3.0, 2);

        return [
            'markup_multiplier' => 3.0,
            'raw_materials_total_php' => $rawMaterialsTotal,
            'estimated_grand_total_php' => $estimatedGrandTotal,
            'itemized_breakdown' => $itemizedBreakdown,
        ];
    }

    protected function inferCategoryFromItemName(string $itemName): string
    {
        $lower = strtolower($itemName);

        if (preg_match('/rose|peony|lily|orchid|tulip|hydrangea|ranunculus|calla|daisy|sunflower|carnation|gerbera|baby\s*breath|cymbidium|alstroemeria|snapdragon|aster|lisianthus/i', $lower)) {
            return 'flower';
        }

        if (preg_match('/leaf|fern|foliage|greens|eucalyptus|salal|ruscus|palm|ivy|olive|monstera|aglaonema|bamboo|garland/i', $lower)) {
            return 'foliage';
        }

        if (preg_match('/vase|container|basket|base|stand|arch|frame|backdrop|linen|runner|candles|candlestick|furniture|chair|table|glass|candleholder|pedestal|signage/i', $lower)) {
            return 'prop';
        }

        return 'supply';
    }

    protected function defaultUnitForCategory(string $category): string
    {
        return match ($category) {
            'flower' => 'stem',
            'foliage' => 'bunch',
            'prop' => 'piece',
            'supply' => 'set',
            default => 'piece',
        };
    }

    protected function estimateItemCostPhp(string $itemName, string $category): float
    {
        $lower = strtolower($itemName);

        if ($category === 'flower') {
            if (preg_match('/rose|peony|orchid|lily|hydrangea|cymbidium|ranunculus|gerbera|calla|tulip|lisianthus/i', $lower)) {
                return 180.0;
            }
            if (preg_match('/baby\s*breath|eucalyptus|fern|greens|foliage|salal|ruscus|olive|lemon|pampas/i', $lower)) {
                return 85.0;
            }
            return 120.0;
        }

        if ($category === 'foliage') {
            return 75.0;
        }

        if ($category === 'prop') {
            if (preg_match('/vase|container|basket|pedestal|stand|frame|arch|backdrop|table|chair|lighting|candle/i', $lower)) {
                return 350.0;
            }
            return 180.0;
        }

        return 90.0;
    }

    protected function normalizeAreaKey(string $area): string
    {
        $areaKey = strtolower(trim($area));
        $areaKey = $areaKey === '' ? 'overall' : $areaKey;

        if (in_array($areaKey, ['tables', 'table'], true)) {
            return 'tables';
        }

        if (in_array($areaKey, ['wall', 'backdrop', 'stage', 'altar', 'entrance', 'aisle', 'ceiling', 'overall'], true)) {
            return $areaKey;
        }

        return 'overall';
    }

    public function validateAnalysisPayload(array $analysis): void
    {
        $materials = $analysis['suggested_materials'] ?? null;
        $allowedCategories = ['flower', 'foliage', 'prop', 'supply'];
        $allowedUnits = ['stem', 'bunch', 'piece', 'set', 'pcs'];

        if (!is_array($materials) || $materials === []) {
            throw new \RuntimeException('empty_or_invalid_analysis');
        }

        foreach ($materials as $material) {
            if (!is_array($material)) {
                throw new \RuntimeException('incomplete_analysis_item');
            }

            $name = trim((string) ($material['item_name'] ?? ''));
            $category = strtolower(trim((string) ($material['category'] ?? '')));
            $unit = strtolower(trim((string) ($material['unit_type'] ?? '')));
            $quantity = $material['quantity'] ?? $material['estimated_quantity'] ?? null;
            $unitCost = $material['unit_cost_php'] ?? $material['estimated_unit_cost_php'] ?? $material['estimated_unit_cost'] ?? null;

            if ($name === '' || !in_array($category, $allowedCategories, true) || !in_array($unit, $allowedUnits, true)
                || !is_numeric($quantity) || !is_finite((float) $quantity) || (float) $quantity <= 0
                || !is_numeric($unitCost) || !is_finite((float) $unitCost) || (float) $unitCost <= 0) {
                throw new \RuntimeException('incomplete_analysis_item');
            }
        }
    }

    /**
     * Analyze an image file path and return parsed JSON and model used.
     * @param string $filePath Local filesystem path to image
     * @return array ['model_used'=>string, 'analysis'=>array, 'raw_response'=>string]
     * @throws \Exception
     */
    public static function buildAnalysisDiagnostic(string $source, array $analysisResult, array $requestContext, ?string $imageHash, ?string $imagePath, bool $completedTemplateReused, array $imageMeta = [], array $generationConfig = [], ?string $requestId = null): array
    {
        $analysis = $analysisResult['analysis'] ?? $analysisResult;
        $materials = [];

        foreach (($analysis['suggested_materials'] ?? []) as $rawMaterial) {
            if (!is_array($rawMaterial)) {
                continue;
            }

            $category = trim((string) ($rawMaterial['category'] ?? ''));
            $sourceType = 'recommendation';
            if (!empty($rawMaterial['is_detected']) && empty($rawMaterial['is_recommendation'])) {
                $sourceType = 'detected';
            }

            $materials[] = [
                'name' => trim((string) ($rawMaterial['item_name'] ?? 'Unknown item')),
                'category' => $category,
                'quantity' => $rawMaterial['quantity'] ?? $rawMaterial['estimated_quantity'] ?? null,
                'unit' => $rawMaterial['unit_type'] ?? $rawMaterial['unit'] ?? null,
                'area' => $rawMaterial['area'] ?? null,
                'source' => $sourceType,
                'note' => trim((string) ($rawMaterial['note'] ?? '')),
                'visual_location' => is_array($rawMaterial['visual_location'] ?? null) ? $rawMaterial['visual_location'] : null,
                'suggested_alternative' => $rawMaterial['suggested_alternative'] ?? null,
                'alternative_reason' => $rawMaterial['alternative_reason'] ?? null,
                'seasonality_status' => $rawMaterial['seasonality_status'] ?? null,
                'seasonal_notes' => $rawMaterial['seasonal_notes'] ?? null,
            ];
        }

        $imageInfo = [
            'hash' => $imageHash,
            'mime_type' => $imageMeta['mime_type'] ?? (is_string($imagePath) && file_exists($imagePath) ? mime_content_type($imagePath) ?: null : null),
            'width' => $imageMeta['width'] ?? null,
            'height' => $imageMeta['height'] ?? null,
            'prepared_mime_type' => $imageMeta['prepared_mime_type'] ?? null,
            'prepared_width' => $imageMeta['prepared_width'] ?? null,
            'prepared_height' => $imageMeta['prepared_height'] ?? null,
        ];

        $normalizedRequestContext = [];
        foreach (['event_type', 'event_date', 'event_time', 'end_time', 'venue', 'venue_type', 'venue_size', 'guest_count', 'table_count', 'special_requests'] as $key) {
            if (array_key_exists($key, $requestContext) && $requestContext[$key] !== null && $requestContext[$key] !== '') {
                $normalizedRequestContext[$key] = $requestContext[$key];
            }
        }

        $generationConfig = is_array($generationConfig) ? $generationConfig : [];
        $diagnostic = [
            'diagnostic_id' => $requestId ?? (string) Str::uuid(),
            'source' => $source,
            'timestamp' => now()->toIso8601String(),
            'analysis_request_id' => $requestId ?? (string) Str::uuid(),
            'fresh_gemini_analysis' => !$completedTemplateReused,
            'completed_template_reused' => (bool) $completedTemplateReused,
            'image' => $imageInfo,
            'request_context' => $normalizedRequestContext,
            'gemini_config' => [
                'model' => $analysisResult['model_used'] ?? $analysisResult['model'] ?? null,
                'temperature' => $generationConfig['temperature'] ?? null,
                'response_mime_type' => $generationConfig['responseMimeType'] ?? $generationConfig['response_mime_type'] ?? 'application/json',
                'generation_config' => $generationConfig,
            ],
            'analysis_result' => [
                'suggested_materials' => $materials,
            ],
            'raw_response' => $analysisResult['raw_response'] ?? null,
        ];

        return $diagnostic;
    }

    public function analyzeImageFromPath(
        string $filePath,
        ?string $specialRequests = null,
        ?string $eventType = null,
        ?string $eventTime = null,
        ?string $venue = null,
        ?string $scaleContext = null,
        ?string $eventDate = null,
        ?string $endTime = null
    ): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $systemPrompt = <<<'PROMPT'
You are a senior floral appraiser specializing in the wholesale and retail flower markets of Metro Manila, Philippines (specifically Dangwa Flower Market), and custom artisan/craft arrangements.
Inspect user-uploaded images of flower arrangements or perishable floral items—whether they are professional stock images from the internet or raw, phone-captured snapshots of homemade or artisan arrangements (which may feature fresh flowers, handmade ribbon folds, fabric, foam, or custom craft materials). Identify the components, estimate standard Philippine Peso (PHP / ₱) market prices, and return ONLY a structured JSON response.

DYNAMIC VISUAL DETECTION & ANTI-HALLUCINATION RULES (CRITICAL):
1. Visual Grounding Only: ONLY classify an item as detected when that specific item is visibly present and reasonably locatable in the uploaded image. Perform a full-scene visual sweep across all areas (top, middle, ground), and for portrait or landscape images explicitly scan every visible zone from edge to edge before finalizing the list. Do not stop after the first obvious centerpiece. Include all clearly visible flower varieties, foliage, containers, backdrops, linens, furniture, and staged props in the scene. Do not under-detect merely because the image is portrait, landscape, dense, or visually complex.
2. Full Coverage Mandatory: Treat the entire image as a design inventory. If a visible element is meaningful to the arrangement or venue styling, include it. Do not omit obvious flowers in corners, background clusters, or secondary structures simply because the main item is in focus.
3. No Color Inventing: Restrict variations strictly to the image's dominant, visible color palette. NEVER introduce unrepresented colors.
4. No Presumptuous Bundling: Do NOT guess unseen accessories or "standard package add-ons" (no photo booths, welcome signages, or aisle carpets unless they physically appear in the photo).
5. Inferred Mechanics Are Recommendations: Behind-the-scenes mechanical supplies may be listed only as recommendations when they are not directly visible. They must use `is_detected: false`, `is_recommendation: true`, and `visual_location: null`.
6. Detection Granularity: Do not create multiple detected rows for the same physical visual element or near-duplicate descriptions. Separate rows are allowed only when the items are genuinely distinct and visually distinguishable.
7. Detection Versus Recommendation: Every row must be either a visible detection or a recommendation. A recommendation may support planning, but it is not a visual detection and must never receive a visual location merely because it appears in the material list.

CATEGORY CLASSIFICATION (STRICT):
Assign EVERY detected item to its most fitting functional category. The category output must NEVER be empty, null, or set to "misc". You must use one of the following exact string values:
- "flower": Any fresh, dried, artificial, or focal blooms, accent flowers, and floral fillers.
- "foliage": Any greenery, leaves, plants, or filler foliage present.
- "prop": Any functional, structural, decorative, lighting, or staging elements (containers, bases, backdrops, overhead fixtures, furniture, floor treatments).
- "supply": Any behind-the-scenes mechanical or structural supplies required to construct and secure the visible setup (foam, wiring, ties, mechanics).

ITEMIZATION RULES:
1. Prevent Floral Aggregation: Do NOT combine clearly distinguishable flower species or color variations into a single generic item line. Separate them only when the visual difference is clear and useful for planning; do not fragment one visual cluster into many arbitrary rows.
2. Annotation Eligibility: A row may receive a visual location only when the named item itself is visible and the approximate point/region can be tied to that item in the original image. If it is not visually identifiable, hidden, inferred, supporting, or uncertain, keep the row as a recommendation or detected-without-location only when justified, and set `visual_location` to null.
3. Coverage Audit: Before returning JSON, review the image a second time by zones and confirm that every clearly distinguishable visible material, object, or functional prop useful for the design breakdown was considered. Do not add a fixed number of rows and do not invent uncertain detections; use null locations or recommendations when a visible distinction cannot be supported.
4. Object-Specific Grounding Audit: For every detected row with a non-null visual_location, identify the actual visible object first, then place the point at that object's center or a clearly representative part of that object. Do not place a flower row on a neighboring flower, mixed cluster, background, garment, or general area. If the point cannot distinguish the named item from nearby items, set visual_location to null instead of returning a broad or misleading coordinate.
5. Non-Venue Composition Handling: If the upload is a floral garment, wearable arrangement, bouquet, headpiece, floral sculpture, product composition, or other non-venue floral design, analyze the visible composition itself. Do not force venue areas such as ceiling, stage, or tables; use `overall` when no approved area fits. Break out clearly distinguishable flower groups, foliage, structural visible materials, and decorative components, while keeping hidden mechanics as recommendations with null locations.

QUANTITY & SCALE GROUNDING:
1. Primary Anchors: The booked table count and guest count (provided in the Context block below) MUST be used as the absolute anchors for calculating the quantities of table linens, chairs, and centerpieces. Use the uploaded photo only for visual style, bloom density, and material selection, while multiplying those materials by the provided table/guest counts.
2. Provide realistic estimated quantities and prices in Philippine Pesos (PHP / ₱). Use realistic wholesale floor prices for flowers in the Dangwa/local market context.
3. Output raw baseline market costs ONLY. Do NOT apply labor, setup, or 3x markup in the item prices (the backend handles the 3x pricing rule).

SUGGESTED ALTERNATIVES & SEASONAL CONTEXT (DECISION-SUPPORT ONLY):
1. Alternatives: If a detected or recommended flower is expensive, delicate, or potentially difficult to source in the Philippines, you may suggest a practical, accessible floral substitute in `suggested_alternative` (e.g., Spray Rose for Garden Rose) and state why in `alternative_reason`. If no substitute is needed, set both to null.
2. Seasonality: Note general seasonal context relative to the event date/month in `seasonal_notes` and set `seasonality_status` (e.g. "year_round", "in_season", "off_season", "requires_validation") if known for the local Philippine market. This is decision-support only and never authoritative; authorized Raflora staff must validate availability.

REQUIRED JSON SCHEMA:
{
  "suggested_materials": [
    {
      "item_name": "string",
      "category": "flower|foliage|prop|supply",
      "quantity": number,
      "unit_type": "stem|bunch|piece|set",
      "unit_cost_php": number,
      "area": "ceiling|stage|altar|entrance|tables|backdrop|wall|aisle|overall",
      "is_detected": boolean,
      "is_recommendation": boolean,
      "is_custom_item": boolean,
      "confidence": number from 0.0 to 1.0,
      "detected_regions": integer,
      "needs_review": boolean,
      "review_message": "string or empty string",
      "note": "string",
      "suggested_alternative": "string or null",
      "alternative_reason": "string or null",
      "seasonality_status": "string or null",
      "seasonal_notes": "string or null",
      "visual_location": {
          "x": number,
          "y": number,
          "width": number,
          "height": number
      } or null
    }
  ]
}

When area information is available, group items by the area where the item actually appears and indicate whether each entry is an AI-detected element or an AI recommendation. For each visibly identifiable detection, provide one approximate normalized `visual_location` using x and y from 0.0 to 1.0 measured from the original image's left and top edges. Use the point or region that corresponds to the named item, not an arbitrary point in the area. Add width and height from 0.0 to 1.0 only when the visible region can be estimated reliably. Use null when the item cannot be visually grounded with reasonable confidence. Recommendations and non-visible supporting materials must use null. Never derive visual_location from area, item order, array position, a generic region, or another item's location; never invent coordinates. Before returning JSON, verify that every non-null visual_location belongs to a row with `is_detected: true` and `is_recommendation: false`, that every detected row with no reliable location uses null rather than a guess, and that each point visibly lands on the named item itself rather than only its surrounding area.

CONFIDENCE CALIBRATION:
- Return `confidence` for every row as your calibrated probability that the named item is actually visible and correctly identified in this image, not how certain you are that the item is commonly used in event design.
- Use the full 0.0-1.0 range. Clear, unobstructed, object-specific detections with a reliable location should generally be 0.85-0.98. Partially visible, overlapping, small, or ambiguous detections should generally be 0.55-0.84. Recommendations or items not visibly confirmed should generally be 0.30-0.60.
- Do not reuse the same confidence value across rows unless their visual evidence is genuinely equivalent. A recommendation must not receive the same confidence as a clearly visible detection.
- Set `needs_review` to true when confidence is below 0.80, the named item is partly obscured or overlapping, or the visual location cannot be tied to that item. Explain the reason briefly in `review_message`. Set it to false for clear, object-specific detections.
PROMPT;

        if (!empty($eventType) || !empty($eventDate) || !empty($eventTime) || !empty($endTime) || !empty($venue) || !empty($specialRequests) || !empty($scaleContext)) {
            $systemPrompt .= "\n\nContext:";
            if (!empty($eventType)) {
                $systemPrompt .= "\n- Event Type: " . trim($eventType);
            }
            if (!empty($eventDate)) {
                $systemPrompt .= "\n- Event Date: " . trim($eventDate);
            }
            if (!empty($eventTime)) {
                $systemPrompt .= "\n- Event Start Time: " . trim($eventTime);
            }
            if (!empty($endTime)) {
                $systemPrompt .= "\n- Event End Time: " . trim($endTime);
            }
            if (!empty($venue)) {
                $systemPrompt .= "\n- Venue: " . trim($venue);
            }
            if (!empty($scaleContext)) {
                $systemPrompt .= "\n- Scale: " . trim($scaleContext);
            }
            if (!empty($specialRequests)) {
                $systemPrompt .= "\n- Special Requests: " . trim($specialRequests);
            }
        }

        $preparedImage = $this->prepareImageForGemini($filePath);
        if (is_array($preparedImage)) {
            $mimeType = $preparedImage['mime_type'];
            $base64 = $preparedImage['data'];
            $tempFilePath = null;
        } else {
            $tempFilePath = $preparedImage;
            $fileContents = file_get_contents($tempFilePath);
            $base64 = base64_encode($fileContents);
            $mimeType = mime_content_type($tempFilePath) ?: 'image/jpeg';
        }

        $lastException = null;
        $consecutiveQuotaFailures = 0;
        $attempt = 0;

        foreach ($this->fallbackModels as $model) {
            $attempt++;
            try {
                // Build Google Gemini v1beta endpoint for the specific model and include API key in query
                if (empty($this->apiKey)) {
                    throw new \Exception('GEMINI_API_KEY is not configured in environment.');
                }

                $endpoint = rtrim($this->apiUrl, '/') . "/{$model}:generateContent?key=" . urlencode($this->apiKey);

                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data'      => $base64,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ];

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(60)->connectTimeout(60)->post($endpoint, $payload);

                $status = $response->status();

                // Detect quota / rate limit
                $body = $response->body();

                // Detect quota / rate limit or temporary unavailability by status or response content
                if ($this->isRetryableRateLimit($status, $body)) {
                    $consecutiveQuotaFailures++;
                    Log::warning("Quota/rate-limit reached for {$model} (status={$status}).");
                    if ($consecutiveQuotaFailures >= $this->maxConsecutiveQuotaFailures) {
                        Log::warning("Quota exhausted across models; failing fast to protect API quota.");
                        throw new \Exception("Gemini quota exhausted across models (status={$status}): {$body}");
                    }
                    $retryAfter = $response->header('Retry-After');
                    $this->handleRetryBackoff($retryAfter ? (int) $retryAfter : null, $attempt);
                    continue; // try next model
                }

                if ($status === 404) {
                    Log::warning("Gemini model {$model} not found or unavailable; trying next model.", [
                        'status' => $status,
                        'body' => $body,
                    ]);
                    continue;
                }

                if ($status >= 400) {
                    // Non-quota client/server error — bail out
                    throw new \Exception("Gemini API error (model={$model}) status={$status}: {$body}");
                }

                $responseData = $response->json();

                $generationConfig = [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                ];

                // Prefer the first candidate part text when available.
                $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (is_string($generatedText)) {
                    $generatedText = trim($generatedText);
                }

                if (empty($generatedText)) {
                    // Fallback to scanning all candidate parts for JSON.
                    $candidateContent = $responseData['candidates'][0]['content'] ?? [];
                    $candidateParts = [];

                    if (is_array($candidateContent)) {
                        foreach ($candidateContent as $contentSegment) {
                            if (is_array($contentSegment) && isset($contentSegment['parts']) && is_array($contentSegment['parts'])) {
                                $candidateParts = array_merge($candidateParts, $contentSegment['parts']);
                            } elseif (is_array($contentSegment) && isset($contentSegment['text'])) {
                                $candidateParts[] = $contentSegment;
                            } elseif (is_string($contentSegment)) {
                                $candidateParts[] = ['text' => $contentSegment];
                            }
                        }
                    }

                    if (empty($candidateParts) && is_string($candidateContent)) {
                        $candidateParts[] = ['text' => $candidateContent];
                    }

                    $allText = [];
                    foreach ($candidateParts as $part) {
                        if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                            $text = trim($part['text']);
                            if ($text !== '') {
                                $allText[] = $text;
                            }
                            if ($this->extractFirstJson($text) !== null) {
                                $generatedText = $text;
                                break;
                            }
                        }
                    }

                    if ($generatedText === null && !empty($allText)) {
                        $generatedText = implode("\n", $allText);
                    }
                }

                if (empty($generatedText)) {
                    throw new \Exception("Gemini response missing generated text (model={$model}). Raw: {$body}");
                }

                $parsed = $this->extractFirstJson($generatedText);
                if ($parsed === null) {
                    throw new \Exception("Generated content did not contain valid JSON (model={$model}). Raw generated: {$generatedText}");
                }

                $preparedMeta = [
                    'mime_type' => $mimeType,
                    'width' => null,
                    'height' => null,
                    'prepared_mime_type' => null,
                    'prepared_width' => null,
                    'prepared_height' => null,
                ];

                if (is_string($tempFilePath) && file_exists($tempFilePath)) {
                    $preparedInfo = @getimagesize($tempFilePath);
                    if (is_array($preparedInfo) && isset($preparedInfo[0], $preparedInfo[1])) {
                        $preparedMeta['prepared_mime_type'] = $preparedInfo['mime'] ?? $mimeType;
                        $preparedMeta['prepared_width'] = (int) $preparedInfo[0];
                        $preparedMeta['prepared_height'] = (int) $preparedInfo[1];
                    }
                }

                return [
                    'model_used' => $model,
                    'analysis' => $parsed,
                    'raw_response' => $body,
                    'generation_config' => $generationConfig,
                    'image_metadata' => $preparedMeta,
                ];
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                Log::warning('GeminiVisionService connection timeout or network issue.', [
                    'message' => $e->getMessage(),
                    'model' => $model,
                    'file' => $filePath,
                ]);

                continue; // Move to the next fallback model
            } catch (\Exception $e) {
                $lastException = $e;
                Log::error('GeminiVisionService analyzeImageFromPath failed.', [
                    'message' => $e->getMessage(),
                    'model' => $model,
                    'file' => $filePath,
                    'throwable' => $e,
                ]);

                // If exception message indicates quota or temporary capacity, continue; else rethrow
                $msg = $e->getMessage();
                if (stripos($msg, 'RESOURCE_EXHAUSTED') !== false || stripos($msg, 'UNAVAILABLE') !== false || stripos($msg, 'quota') !== false || stripos($msg, '429') !== false || stripos($msg, '503') !== false) {
                    $consecutiveQuotaFailures++;
                    Log::warning("Quota/limit detected for {$model}: {$msg}.");
                    if ($consecutiveQuotaFailures >= $this->maxConsecutiveQuotaFailures) {
                        Log::warning("Quota exhausted across models; failing fast to protect API quota.");
                        throw $e;
                    }
                    $this->handleRetryBackoff(null, $attempt);
                    continue;
                }

                throw $e;
            } finally {
                if (isset($tempFilePath) && file_exists($tempFilePath) && $tempFilePath !== $filePath) {
                    @unlink($tempFilePath);
                }
            }
        }

        if (isset($tempFilePath) && file_exists($tempFilePath) && $tempFilePath !== $filePath) {
            @unlink($tempFilePath);
        }

        Log::warning('All Gemini fallback models failed; throwing exception.', [
            'last_exception' => $lastException?->getMessage(),
            'file' => $filePath,
        ]);

        throw new \Exception('Failed to analyze image with AI after trying all fallback models: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));
    }

    /**
     * Prepare an image for Gemini by resizing and encoding to optimized JPEG.
     * @param string $filePath
     * @return string Temporary JPEG file path
     * @throws \Exception
     */
    protected function prepareImageForGemini(string $filePath): array|string
    {
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') || !function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            return [
                'mime_type' => mime_content_type($filePath) ?: 'image/jpeg',
                'data' => base64_encode(file_get_contents($filePath)),
            ];
        }

        $info = getimagesize($filePath);
        if ($info === false) {
            throw new \Exception('Unsupported image format for Gemini upload.');
        }

        [$width, $height, $type] = $info;
        $maxDimension = 1024;
        $ratio = min(1, $maxDimension / $width, $maxDimension / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filePath);
                break;
            case IMAGETYPE_BMP:
                $source = imagecreatefrombmp($filePath);
                break;
            default:
                throw new \Exception('Unsupported image type for Gemini upload.');
        }

        if ($source === false) {
            throw new \Exception('Failed to read source image for resizing.');
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            imagedestroy($source);
            throw new \Exception('Failed to create resized image canvas.');
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $tempFile = tempnam(sys_get_temp_dir(), 'gemini_img_');
        if ($tempFile === false) {
            imagedestroy($source);
            imagedestroy($resized);
            throw new \Exception('Unable to create temporary file for Gemini upload.');
        }

        if (!imagejpeg($resized, $tempFile, 80)) {
            imagedestroy($source);
            imagedestroy($resized);
            @unlink($tempFile);
            throw new \Exception('Failed to write resized JPEG image for Gemini upload.');
        }

        imagedestroy($source);
        imagedestroy($resized);

        return $tempFile;
    }

    /**
     * Try to extract the first JSON object or array from a string.
     * @param string $text
     * @return array|null
     */
    protected function extractFirstJson(string $text): ?array
    {
        // Attempt pure json decode first
        $clean = trim($text);
        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Find first JSON object or array using regex
        if (preg_match('/(\{.*\}|\[.*\])/s', $text, $m)) {
            $candidate = $m[0];
            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
