<?php

namespace App\Services\Ai\Tools;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductRecommendationTool
{
    private const MAX_RESULT_COUNT = 5;

    private const CANDIDATE_LIMIT = 60;

    public function recommend(array $payload): array
    {
        $message = (string) ($payload['message'] ?? '');
        $contextProductText = $this->extractContextProductText($payload);
        $query = trim($message . ' ' . $contextProductText);

        $budgetMax = $this->extractBudget($payload, $message);
        $categoryHint = trim((string) ($payload['category'] ?? ''));
        $userContext = $this->extractUserContext($query);

        if ($categoryHint !== '') {
            $userContext['category_terms'][] = strtolower($categoryHint);
            $userContext['category_terms'] = array_values(array_unique($userContext['category_terms']));
        }

        $baseProductQuery = Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->latest('id');

        if ($budgetMax !== null) {
            $baseProductQuery->where('price', '<=', $budgetMax);
        }

        if ($categoryHint !== '') {
            $baseProductQuery->whereHas('category', function ($categoryQuery) use ($categoryHint) {
                $categoryQuery->where('name', 'like', '%' . $categoryHint . '%');
            });
        }

        $candidates = (clone $baseProductQuery)
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $rankedProducts = $this->rankProducts($candidates, $userContext, $budgetMax);
        $matchStrategy = 'ranked';

        if ($rankedProducts->isEmpty()) {
            $rankedProducts = (clone $baseProductQuery)
                ->orderBy('price')
                ->limit(self::MAX_RESULT_COUNT)
                ->get();

            $matchStrategy = $rankedProducts->isEmpty() ? 'none' : 'catalog_fallback';
        }

        $products = $rankedProducts
            ->take(self::MAX_RESULT_COUNT)
            ->map(function (Product $product): array {
                $desc = $product->description ? strip_tags((string) $product->description) : null;

                $specs = is_array($product->specifications) && count($product->specifications) > 0
                    ? $product->specifications
                    : null;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => (int) $product->price,
                    'unit' => $product->unit,
                    'stock' => (int) $product->stock,
                    'category' => $product->category?->name,
                    'description' => $desc,
                    'specifications' => $specs,
                ];
            })
            ->values()
            ->all();

        if (count($products) === 0) {
            return [
                'reply' => 'Belum ada produk yang cocok dengan kebutuhan Anda saat ini. Coba ubah kata kunci atau naikkan budget.',
                'products' => [],
                'suggestions' => [
                    'Rekomendasi produk elektronik',
                    'Produk paling murah yang tersedia',
                    'Cari produk tanpa batas budget',
                ],
                'meta' => [
                    'match_strategy' => 'none',
                    'search_terms' => $userContext['search_terms'],
                    'description_driven' => $userContext['is_description_driven'],
                ],
            ];
        }

        return [
            'reply' => $this->buildRecommendationReply($products, $budgetMax, $userContext),
            'products' => $products,
            'suggestions' => $this->buildSuggestions($userContext, $budgetMax),
            'meta' => [
                'match_strategy' => $matchStrategy,
                'search_terms' => $userContext['search_terms'],
                'description_driven' => $userContext['is_description_driven'],
            ],
        ];
    }

    private function rankProducts(Collection $candidates, array $userContext, ?int $budgetMax): Collection
    {
        $scoredProducts = $candidates
            ->map(function (Product $product) use ($userContext, $budgetMax): array {
                return [
                    'product' => $product,
                    'score' => $this->calculateRelevanceScore($product, $userContext, $budgetMax),
                ];
            })
            ->filter(static fn(array $entry): bool => $entry['score'] > 0)
            ->sortByDesc('score')
            ->values();

        if ($scoredProducts->isEmpty()) {
            return collect();
        }

        return $scoredProducts->map(static fn(array $entry): Product => $entry['product']);
    }

    private function calculateRelevanceScore(Product $product, array $userContext, ?int $budgetMax): int
    {
        $score = 0;
        $name = strtolower((string) $product->name);
        $categoryName = strtolower((string) ($product->category?->name ?? ''));
        $description = strtolower((string) ($product->description ?? ''));
        $specificationText = $this->flattenSpecificationText($product->specifications);
        $combinedText = trim($name . ' ' . $categoryName . ' ' . $description . ' ' . $specificationText);

        foreach ($userContext['search_terms'] as $term) {
            if ($term === '') {
                continue;
            }

            if (str_contains($name, $term)) {
                $score += 8;

                continue;
            }

            if ($categoryName !== '' && str_contains($categoryName, $term)) {
                $score += 6;

                continue;
            }

            if ($specificationText !== '' && str_contains($specificationText, $term)) {
                $score += 6;

                continue;
            }

            if ($description !== '' && str_contains($description, $term)) {
                $score += 5;

                continue;
            }

            if ($combinedText !== '' && str_contains($combinedText, $term)) {
                $score += 3;
            }
        }

        if ($userContext['is_description_driven']) {
            $descriptionMatchCount = 0;

            foreach ($userContext['search_terms'] as $term) {
                if ($term === '') {
                    continue;
                }

                if (($description !== '' && str_contains($description, $term)) || ($specificationText !== '' && str_contains($specificationText, $term))) {
                    $descriptionMatchCount++;
                }
            }

            if ($descriptionMatchCount >= 2) {
                $score += 8;
            } elseif ($descriptionMatchCount === 1) {
                $score += 4;
            } else {
                $score -= 2;
            }
        }

        foreach ($userContext['product_terms'] as $productTerm) {
            if (str_contains($name, $productTerm) || ($categoryName !== '' && str_contains($categoryName, $productTerm))) {
                $score += 10;

                continue;
            }

            if ($combinedText !== '' && str_contains($combinedText, $productTerm)) {
                $score += 4;
            }
        }

        foreach ($userContext['room_terms'] as $roomTerm) {
            if ($combinedText !== '' && str_contains($combinedText, $roomTerm)) {
                $score += 5;
            }
        }

        foreach ($userContext['category_terms'] as $categoryTerm) {
            if ($categoryName !== '' && str_contains($categoryName, $categoryTerm)) {
                $score += 6;
            }
        }

        if ($budgetMax !== null && $budgetMax > 0) {
            $priceRatio = ((int) $product->price) / $budgetMax;

            if ($priceRatio <= 1 && $priceRatio >= 0.75) {
                $score += 4;
            } elseif ($priceRatio <= 0.75 && $priceRatio >= 0.5) {
                $score += 3;
            } elseif ($priceRatio < 0.5) {
                $score += 1;
            }
        }

        if ($userContext['prefers_efficient'] && preg_match('/\bled\b|\bhemat\b|\bwatt\b|\blumen\b/i', $combinedText) === 1) {
            $score += 2;
        }

        return $score;
    }

    private function buildRecommendationReply(array $products, ?int $budgetMax, array $userContext): string
    {
        $topProductSnippets = array_map(
            static function (array $product): string {
                $snippet = '• ' . $product['name'] . ' - Rp ' . number_format((int) $product['price'], 0, ',', '.');
                $desc = trim((string) $product['description']);
                if ($desc !== '') {
                    $snippet .= "\n  (" . mb_strimwidth($desc, 0, 80, '...') . ")";
                }
                return $snippet;
            },
            array_slice($products, 0, 5),
        );

        $focusSegments = [];

        if ($budgetMax !== null) {
            $focusSegments[] = 'budget sekitar Rp ' . number_format($budgetMax, 0, ',', '.');
        }

        if (count($userContext['product_terms']) > 0) {
            $focusSegments[] = 'kebutuhan ' . implode(', ', $userContext['product_terms']);
        }

        if (count($userContext['room_terms']) > 0) {
            $focusSegments[] = 'area ' . implode(', ', $userContext['room_terms']);
        }

        $intro = 'Wah ada nih kak! Aku nemu beberapa produk yang pas banget';
        if (count($focusSegments) > 0) {
            $intro .= ' buat ' . implode(' dan ', $focusSegments);
        }

        $reply = $intro . " ya:\n\n" . implode("\n", $topProductSnippets);

        // Room-specific advice
        $productTerms = $userContext['product_terms'] ?? [];
        $roomTerms = $userContext['room_terms'] ?? [];

        if (in_array('lampu', $productTerms, true)) {
            if (in_array('kamar tidur', $roomTerms, true)) {
                $reply .= "\n\nTips: Untuk kamar tidur, pilih lampu LED 5W-9W dengan warna warm white (kuning hangat) agar suasana lebih nyaman dan tidak silau.";
            } elseif (in_array('ruang tamu', $roomTerms, true)) {
                $reply .= "\n\nTips: Untuk ruang tamu, lampu LED 9W-15W dengan cahaya cool daylight (putih terang) cocok untuk suasana yang terang dan lapang.";
            } elseif (in_array('dapur', $roomTerms, true)) {
                $reply .= "\n\nTips: Untuk dapur, gunakan lampu 9W-12W cool daylight agar pencahayaan merata untuk aktivitas memasak.";
            } elseif (in_array('kamar mandi', $roomTerms, true)) {
                $reply .= "\n\nTips: Untuk kamar mandi, pilih lampu 5W-7W dengan sifat tahan lembab dan cahaya terang.";
            }

            if ($userContext['prefers_efficient']) {
                $reply .= " Pilih lampu LED untuk efisiensi energi yang lebih baik dibanding lampu pijar.";
            }
        }

        return $reply;
    }

    private function buildSuggestions(array $userContext, ?int $budgetMax): array
    {
        $suggestions = [
            'Lihat detail produk paling atas',
            'Bandingkan dengan budget yang lebih tinggi',
            'Tampilkan alternatif dengan stok lebih banyak',
        ];

        if (in_array('lampu', $userContext['product_terms'], true)) {
            $suggestions[1] = 'Tampilkan lampu dengan watt lebih rendah';
        }

        if ($budgetMax !== null && $budgetMax > 0) {
            $budgetStep = (int) max(10000, round($budgetMax * 0.25, -3));
            $newBudget = $budgetMax + $budgetStep;
            $suggestions[2] = 'Coba budget Rp ' . number_format($newBudget, 0, ',', '.');
        }

        return $suggestions;
    }

    private function extractUserContext(string $message): array
    {
        $normalizedMessage = strtolower($message);

        $roomTerms = [];
        $roomTermMap = [
            'kamar tidur' => ['kamar tidur', 'bedroom'],
            'ruang tamu' => ['ruang tamu', 'living room'],
            'dapur' => ['dapur', 'kitchen'],
            'kamar mandi' => ['kamar mandi', 'bathroom'],
            'teras' => ['teras', 'outdoor'],
        ];

        foreach ($roomTermMap as $canonicalRoom => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalizedMessage, $pattern)) {
                    $roomTerms[] = $canonicalRoom;

                    break;
                }
            }
        }

        $productTerms = [];
        $productTermMap = [
            'lampu' => ['lampu', 'bohlam', 'led', 'downlight'],
            'kabel' => ['kabel', 'nya', 'nym', 'nyy'],
            'saklar' => ['saklar', 'switch'],
            'stop kontak' => ['stop kontak', 'stopkontak', 'colokan', 'socket'],
            'fitting' => ['fitting', 'holder lampu'],
            'mcb' => ['mcb', 'breaker'],
        ];

        foreach ($productTermMap as $canonicalProduct => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalizedMessage, $pattern)) {
                    $productTerms[] = $canonicalProduct;

                    break;
                }
            }
        }

        $categoryTerms = [];
        if (count($productTerms) > 0) {
            $categoryTerms = $productTerms;
        }

        return [
            'search_terms' => $this->extractSearchTerms($normalizedMessage),
            'room_terms' => array_values(array_unique($roomTerms)),
            'product_terms' => array_values(array_unique($productTerms)),
            'category_terms' => array_values(array_unique($categoryTerms)),
            'prefers_efficient' => preg_match('/\bhemat\b|\birit\b|\bwatt\b|\bdaya\b/i', $normalizedMessage) === 1,
            'is_description_driven' => preg_match('/\bdeskripsi\b|\bspesifikasi\b|\bspek\b|\bspec\b|\bfitur\b|\bproduct\s+[a-z0-9]+\b|\bproduk\s+[a-z0-9]+\b/i', $normalizedMessage) === 1,
        ];
    }

    private function extractContextProductText(array $payload): string
    {
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        $segments = [];
        $productName = trim((string) ($context['product_name'] ?? ''));
        $productDescription = trim((string) ($context['product_description'] ?? ''));
        $pageTitle = trim((string) ($context['page_title'] ?? ''));

        if ($productName !== '') {
            $segments[] = $productName;
        }

        if ($productDescription !== '') {
            $segments[] = $productDescription;
        }

        if ($pageTitle !== '') {
            $segments[] = $pageTitle;
        }

        $keywords = $context['product_keywords'] ?? [];
        if (is_array($keywords)) {
            foreach ($keywords as $keyword) {
                $normalizedKeyword = trim((string) $keyword);
                if ($normalizedKeyword !== '') {
                    $segments[] = $normalizedKeyword;
                }
            }
        }

        return trim(implode(' ', $segments));
    }

    private function flattenSpecificationText(mixed $specifications): string
    {
        if (! is_array($specifications)) {
            return '';
        }

        $segments = [];

        foreach ($specifications as $key => $value) {
            if (is_string($key) && $key !== '') {
                $segments[] = strtolower($key);
            }

            if (is_scalar($value)) {
                $segments[] = strtolower((string) $value);
            }
        }

        return implode(' ', $segments);
    }

    private function extractBudget(array $payload, string $message): ?int
    {
        if (array_key_exists('budget_max', $payload) && $payload['budget_max'] !== null && $payload['budget_max'] !== '') {
            return max(0, (int) $payload['budget_max']);
        }

        if (preg_match('/\b([0-9]+(?:[\.,][0-9]+)?)\s*(rb|ribu|ribuan|k)\b/i', $message, $matches) === 1) {
            $rawAmount = str_replace(',', '.', (string) ($matches[1] ?? ''));
            $amountValue = (float) $rawAmount;

            if ($amountValue > 0) {
                return (int) round($amountValue * 1000);
            }
        }

        if (preg_match('/\b([0-9]+(?:[\.,][0-9]+)?)\s*(jt|juta)\b/i', $message, $matches) === 1) {
            $rawAmount = str_replace(',', '.', (string) ($matches[1] ?? ''));
            $amountValue = (float) $rawAmount;

            if ($amountValue > 0) {
                return (int) round($amountValue * 1000000);
            }
        }

        if (preg_match('/(?:rp|idr)?\s*([0-9][0-9\.]{2,})/i', $message, $matches) === 1) {
            $normalized = preg_replace('/[^0-9]/', '', (string) $matches[1]);
            if ($normalized !== null && $normalized !== '') {
                return max(0, (int) $normalized);
            }
        }

        if (preg_match('/\b([1-9][0-9]{3,})\b/', $message, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractSearchTerms(string $message): array
    {
        $normalizedMessage = strtolower($message);
        $normalizedMessage = preg_replace('/[^a-z0-9\s]/', ' ', $normalizedMessage) ?? '';

        $rawTerms = preg_split('/\s+/', trim($normalizedMessage)) ?: [];

        $ignoredTerms = [
            'saya',
            'aku',
            'punya',
            'ada',
            'kira',
            'kira-kira',
            'bisa',
            'dong',
            'nih',
            'ini',
            'itu',
            'minta',
            'tolong',
            'rekomendasi',
            'produk',
            'barang',
            'budget',
            'dibawah',
            'di',
            'diatas',
            'yang',
            'dan',
            'buat',
            'untuk',
            'cocok',
            'ruangan',
            'ruang',
            'apa',
            'dengan',
            'uang',
            'pakai',
            'rp',
            'idr',
            'rb',
            'ribu',
            'ribuan',
            'k',
            'jt',
            'juta',
            'product',
            'produk',
            'deskripsi',
            'spesifikasi',
            'fitur',
        ];

        $searchTerms = [];

        foreach ($rawTerms as $rawTerm) {
            if ($rawTerm === '') {
                continue;
            }

            $termWithoutDigits = preg_replace('/[0-9]+/', '', $rawTerm) ?? '';
            if ($termWithoutDigits === '') {
                continue;
            }

            if (strlen($termWithoutDigits) < 3 || in_array($termWithoutDigits, $ignoredTerms, true)) {
                continue;
            }

            $searchTerms[] = $termWithoutDigits;
        }

        if (str_contains($normalizedMessage, 'kamar tidur')) {
            $searchTerms[] = 'kamar tidur';
        }

        if (str_contains($normalizedMessage, 'ruang tamu')) {
            $searchTerms[] = 'ruang tamu';
        }

        if (str_contains($normalizedMessage, 'stop kontak')) {
            $searchTerms[] = 'stop kontak';
        }

        return array_values(array_unique($searchTerms));
    }
}
