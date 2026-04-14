<?php

namespace App\Services\Ai\Tools;

use App\Models\Product;

class ProductRecommendationTool
{
    public function recommend(array $payload): array
    {
        $message = (string) ($payload['message'] ?? '');
        $query = trim($message);

        $budgetMax = $this->extractBudget($payload, $message);
        $categoryHint = trim((string) ($payload['category'] ?? ''));

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

        $productQuery = clone $baseProductQuery;
        $searchTerms = $this->extractSearchTerms($query);

        if (count($searchTerms) > 0) {
            $productQuery->where(function ($searchQuery) use ($searchTerms) {
                foreach ($searchTerms as $searchTerm) {
                    $searchQuery
                        ->orWhere('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', '%' . $searchTerm . '%'));
                }
            });
        }

        $products = $productQuery
            ->limit(5)
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => (int) $product->price,
                    'unit' => $product->unit,
                    'stock' => (int) $product->stock,
                    'category' => $product->category?->name,
                ];
            })
            ->values()
            ->all();

        if (count($products) === 0 && count($searchTerms) > 0) {
            $products = (clone $baseProductQuery)
                ->limit(5)
                ->get()
                ->map(function (Product $product): array {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (int) $product->price,
                        'unit' => $product->unit,
                        'stock' => (int) $product->stock,
                        'category' => $product->category?->name,
                    ];
                })
                ->values()
                ->all();
        }

        if (count($products) === 0) {
            return [
                'reply' => 'Belum ada produk yang cocok dengan kebutuhan Anda saat ini. Coba ubah kata kunci atau naikkan budget.',
                'products' => [],
                'suggestions' => [
                    'Rekomendasi produk elektronik',
                    'Produk paling murah yang tersedia',
                    'Cari produk tanpa batas budget',
                ],
            ];
        }

        $reply = 'Saya menemukan ' . count($products) . ' produk yang relevan untuk Anda.';
        if ($budgetMax !== null) {
            $reply .= ' Semua rekomendasi di bawah Rp ' . number_format($budgetMax, 0, ',', '.') . '.';
        }

        return [
            'reply' => $reply,
            'products' => $products,
            'suggestions' => [
                'Lihat detail produk pertama',
                'Filter berdasarkan kategori',
                'Tampilkan produk dengan budget lain',
            ],
        ];
    }

    private function extractBudget(array $payload, string $message): ?int
    {
        if (array_key_exists('budget_max', $payload) && $payload['budget_max'] !== null && $payload['budget_max'] !== '') {
            return max(0, (int) $payload['budget_max']);
        }

        if (preg_match('/(?:rp|idr)?\s*([0-9][0-9\.]{2,})/i', $message, $matches) === 1) {
            $normalized = preg_replace('/[^0-9]/', '', (string) $matches[1]);
            if ($normalized !== null && $normalized !== '') {
                return max(0, (int) $normalized);
            }
        }

        return null;
    }

    private function extractSearchTerms(string $message): array
    {
        $normalizedMessage = strtolower($message);
        $normalizedMessage = preg_replace('/[^a-z0-9\s]/', ' ', $normalizedMessage) ?? '';

        $rawTerms = preg_split('/\s+/', trim($normalizedMessage)) ?: [];

        $ignoredTerms = [
            'minta',
            'tolong',
            'rekomendasi',
            'produk',
            'budget',
            'dibawah',
            'diatas',
            'yang',
            'dan',
            'buat',
            'untuk',
            'rp',
        ];

        $searchTerms = [];

        foreach ($rawTerms as $rawTerm) {
            if ($rawTerm === '' || strlen($rawTerm) < 3 || in_array($rawTerm, $ignoredTerms, true)) {
                continue;
            }

            if (preg_match('/^[0-9]+$/', $rawTerm) === 1) {
                continue;
            }

            $searchTerms[] = $rawTerm;
        }

        return array_values(array_unique($searchTerms));
    }
}
