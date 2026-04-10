<?php

namespace Tests\Support;

use DOMDocument;
use DOMXPath;

trait AssertsStorefrontSnapshots
{
    protected function assertMatchesStorefrontSnapshot(string $html, string $snapshotName): void
    {
        $signature = $this->buildStorefrontSignature($html);

        $snapshotPath = base_path('tests/__snapshots__/storefront/' . $snapshotName . '.json');
        $snapshotDir = dirname($snapshotPath);

        if (!is_dir($snapshotDir)) {
            mkdir($snapshotDir, 0777, true);
        }

        if (!file_exists($snapshotPath)) {
            file_put_contents(
                $snapshotPath,
                json_encode($signature, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $this->fail('Snapshot created at ' . $snapshotPath . '. Review and rerun the tests.');
        }

        $expected = json_decode((string) file_get_contents($snapshotPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($expected, $signature, 'Storefront snapshot mismatch: ' . $snapshotName);
    }

    private function buildStorefrontSignature(string $html): array
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        return [
            'title' => $this->singleNodeText($xpath, '//title'),
            'brand' => $this->singleNodeText($xpath, '//header//p[contains(@class, "tracking-widest")]'),
            'headings' => $this->multiNodeTexts($xpath, '//main//*[self::h1 or self::h2 or self::h3]', 8),
            'primary_actions' => $this->multiNodeTexts(
                $xpath,
                "//main//*[self::a or self::button][contains(@class, 'bg-primary-600') or contains(@class, 'bg-gray-900')]",
                8
            ),
            'structure' => [
                'section_count' => (int) $xpath->evaluate('count(//main//section)'),
                'article_count' => (int) $xpath->evaluate('count(//main//article)'),
                'form_count' => (int) $xpath->evaluate('count(//main//form)'),
            ],
            'main_text_hash' => hash('sha256', $this->normalizeText($xpath->evaluate('string(//main)'))),
        ];
    }

    private function singleNodeText(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)?->item(0);

        return $node ? $this->normalizeText($node->textContent) : '';
    }

    private function multiNodeTexts(DOMXPath $xpath, string $query, int $limit): array
    {
        $nodes = $xpath->query($query);
        if (!$nodes) {
            return [];
        }

        $texts = [];
        foreach ($nodes as $node) {
            $text = $this->normalizeText($node->textContent ?? '');
            if ($text === '') {
                continue;
            }

            $texts[] = $text;
            if (count($texts) >= $limit) {
                break;
            }
        }

        return $texts;
    }

    private function normalizeText(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized ?? '';
    }
}
