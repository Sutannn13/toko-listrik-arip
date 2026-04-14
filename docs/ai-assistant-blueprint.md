# AI Assistant Blueprint (FAQ + Tracking + Rekomendasi)

Tanggal: 2026-04-14
Project: toko-listrik-arip

## Tujuan

Menyediakan asisten AI di storefront untuk 3 kebutuhan inti:

1. Menjawab FAQ toko secara cepat.
2. Membantu cek status pesanan dengan aman.
3. Memberi rekomendasi produk sesuai kebutuhan user.

## Fakta yang Sudah Terkonfirmasi

1. Checkout dan tracking order sudah ada di alur web + API.
2. Bayar.gg webhook sudah dipakai untuk update status pembayaran otomatis.
3. Struktur backend Laravel sudah punya model `Order`, `Payment`, `Product`, `Category`, `Setting`.
4. Test suite saat ini hijau penuh (104 test pass).

## Asumsi yang Perlu Divalidasi

1. FAQ utama akan disimpan di tabel/setting internal, bukan dari dokumen eksternal.
2. Asisten dipakai di web terlebih dahulu, mobile menyusul melalui API yang sama.
3. User guest tetap boleh tanya FAQ, tetapi akses status order harus lewat verifikasi data order.

## Arsitektur V1

Client (Blade/Alpine chat widget)
-> `POST /api/ai/chat`
-> `AiAssistantController`
-> `AiAssistantOrchestratorService`
-> Intent Router + Tools
-> LLM Provider

Tools yang dipanggil router:

1. `FaqAnswerTool`
2. `OrderTrackingTool`
3. `ProductRecommendationTool`

## Batasan Scope V1

1. Tidak ada aksi mutasi order dari AI (read-only).
2. Tidak ada auto-refund atau action finansial.
3. Tidak ada akses data user lain.
4. Tidak menjalankan query bebas dari prompt.

## Desain Endpoint

## 1) `POST /api/ai/chat`

Request:

```json
{
    "session_id": "web-uuid",
    "message": "status pesanan ORD-ARIP-20260414-XXXXXX",
    "context": {
        "locale": "id",
        "channel": "storefront"
    }
}
```

Response:

```json
{
    "reply": "Pesanan Anda masih menunggu pembayaran.",
    "intent": "order_tracking",
    "used_tools": ["OrderTrackingTool"],
    "suggestions": ["Lihat detail pesanan", "Buka link pembayaran"]
}
```

## 2) `POST /api/ai/feedback` (opsional tapi disarankan)

Request:

```json
{
    "session_id": "web-uuid",
    "message_id": "msg-uuid",
    "rating": 1,
    "reason": "jawaban tidak sesuai"
}
```

## Kontrak Tool Internal

## `FaqAnswerTool`

Input:

1. `question`

Output:

1. `answer`
2. `source_key`
3. `confidence` (0-1)

## `OrderTrackingTool`

Input:

1. `order_code`
2. `customer_email` atau `customer_phone_last4`

Output:

1. `order_code`
2. `status`
3. `payment_status`
4. `tracking_number`
5. `latest_payment_method`
6. `latest_payment_url`

## `ProductRecommendationTool`

Input:

1. `query`
2. `budget_max` (opsional)
3. `category` (opsional)

Output:

1. daftar produk aktif (maks 5)
2. alasan singkat per produk

## Desain Keamanan

1. Order tracking wajib verifikasi minimal dua faktor data order:
    - `order_code` + (`email` atau 4 digit akhir nomor telepon).
2. Semua endpoint AI pakai rate limit.
3. Jawaban AI tidak boleh menampilkan API key, secret, path sensitif, atau stack trace.
4. Semua tool harus whitelist field database yang boleh keluar.
5. Logging simpan ringkasan intent, bukan payload sensitif penuh.

## Strategi Model Hemat Biaya

Gunakan 2 tingkat model:

1. Fast model (default): untuk intent routing, FAQ, dan rekomendasi ringan.
2. Fallback model (lebih kuat): hanya untuk kasus ambigu atau confidence rendah.

Aturan cost-control:

1. Batasi context window pesan (misal 8-12 pesan terakhir).
2. Terapkan semantic cache untuk FAQ umum.
3. Tetapkan max output tokens per intent.
4. Hindari pemanggilan model kedua jika tool sudah memberi jawaban pasti.

## Variabel Environment yang Disarankan

```env
AI_ASSISTANT_ENABLED=true
AI_PROVIDER=openrouter
AI_MODEL_FAST=google/gemini-2.5-flash
AI_MODEL_FALLBACK=deepseek/deepseek-chat
AI_REQUEST_TIMEOUT=20
AI_MAX_INPUT_TOKENS=2500
AI_MAX_OUTPUT_TOKENS=500
AI_DAILY_BUDGET_IDR=50000
AI_FAQ_CACHE_TTL_SECONDS=3600
```

## Struktur Kode yang Disarankan

```text
app/
  Services/Ai/
    AiAssistantOrchestratorService.php
    AiIntentRouterService.php
    Providers/
      LlmClientInterface.php
      OpenRouterClient.php
    Tools/
      FaqAnswerTool.php
      OrderTrackingTool.php
      ProductRecommendationTool.php
  Http/Controllers/Api/
    AiAssistantController.php
  Http/Requests/Api/
    AiChatRequest.php
```

## Prompt System V1 (Ringkas)

1. Jawab dalam Bahasa Indonesia.
2. Fokus pada data toko ini, jangan berhalusinasi.
3. Jika data order tidak cukup, minta data verifikasi.
4. Jangan memberi saran teknis yang berisiko untuk transaksi.
5. Prioritaskan jawaban singkat, jelas, dan dapat ditindaklanjuti.

## Tahapan Implementasi

## Phase 1 (MVP backend)

1. Buat endpoint `POST /api/ai/chat`.
2. Buat orchestrator + 3 tools.
3. Buat adapter provider LLM.
4. Tambah test feature untuk intent FAQ, tracking, rekomendasi.

## Phase 2 (Storefront widget)

1. Tambah widget chat di layout storefront.
2. Tampilkan quick actions: FAQ populer, cek pesanan, rekomendasi.
3. Tampilkan fallback human handoff saat error/timeouts.

## Phase 3 (Cost and quality guard)

1. Tambah cache FAQ.
2. Tambah budget guard harian.
3. Tambah feedback endpoint dan dashboard kualitas.

## Checklist Go-Live

1. `APP_DEBUG=false` di production.
2. Rate limit AI endpoint aktif.
3. Prompt injection basic filter aktif.
4. Audit log aktif.
5. Minimal 20 test skenario AI pass.

## Next Action

Implementasi langsung bisa dimulai dari Phase 1 dengan menambahkan:

1. `AiAssistantController`
2. `AiAssistantOrchestratorService`
3. `AiChatRequest`
4. 3 tool class read-only
5. test feature untuk 3 intent utama
