<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## API Authentication (Sanctum)

Checkout dan Cart API menggunakan Bearer Token Sanctum.

### Generate Token

- Method: `POST`
- Path: `/api/auth/token`

Request body:

```json
{
    "email": "mobile-user@example.com",
    "password": "secret-pass-123",
    "device_name": "android-app"
}
```

### Revoke Current Token

- Method: `DELETE`
- Path: `/api/auth/token`
- Auth: `Authorization: Bearer {token}`

### Get Current User Profile

- Method: `GET`
- Path: `/api/me`
- Auth: `Authorization: Bearer {token}`

## Checkout API Endpoint

Endpoint ini dipakai untuk membuat order checkout via JSON.

- Method: `POST`
- Path: `/api/checkout`
- Auth: wajib Bearer Token (`auth:sanctum`)
- Throttle: `12 request / menit`

### Request Body

```json
{
    "payment_method": "bank_transfer",
    "customer_name": "Budi Santoso",
    "customer_email": "budi@example.com",
    "customer_phone": "081234567890",
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ],
    "address_label": "Rumah",
    "recipient_name": "Budi Santoso",
    "address_phone": "081234567890",
    "address_line": "Jl. Merdeka No. 10",
    "city": "Bandung",
    "province": "Jawa Barat",
    "postal_code": "40111",
    "address_notes": "Patokan dekat minimarket",
    "set_as_default": true,
    "notes": "Tolong kirim sore hari"
}
```

Catatan:

- Field `items` boleh dikosongkan jika user sudah punya item di persistent cart API.
- Jika `address_id` tidak dikirim, maka endpoint akan pakai alamat default user atau membuat alamat baru dari field alamat.
- `payment_method` yang didukung: `cod`, `bank_transfer`, `ewallet`, `dummy`, `bayargg`.

### Response 201 (Created)

```json
{
    "message": "Checkout berhasil dibuat.",
    "data": {
        "order_id": 123,
        "order_code": "ORD-ARIP-20260413-ABC123",
        "status": "pending",
        "payment_status": "pending",
        "payment": {
            "payment_code": "PAY-ARIP-20260413-XYZ789",
            "method": "bank_transfer",
            "status": "pending",
            "amount": 40000,
            "gateway_provider": null,
            "gateway_invoice_id": null,
            "gateway_status": null,
            "gateway_payment_url": null,
            "gateway_expires_at": null
        }
    }
}
```

## Integrasi Bayar.gg (Payment Gateway Otomatis)

Project ini sudah mendukung metode `bayargg` untuk pembayaran otomatis.

### 1) Konfigurasi Environment

Isi variabel berikut pada file `.env`:

```env
BAYARGG_BASE_URL=https://www.bayar.gg/api
BAYARGG_API_KEY=isi_api_key_anda
BAYARGG_WEBHOOK_SECRET=isi_webhook_secret_anda
BAYARGG_WEBHOOK_TOLERANCE_SECONDS=300
BAYARGG_WEBHOOK_REPLAY_TTL_SECONDS=600
BAYARGG_PAYMENT_METHOD=qris
BAYARGG_USE_QRIS_CONVERTER=false
BAYARGG_TIMEOUT=15
```

Keterangan cepat:

- `BAYARGG_API_KEY`: ambil dari dashboard Bayar.gg.
- `BAYARGG_WEBHOOK_SECRET`: ambil dari menu Pengaturan Webhook Bayar.gg.
- `BAYARGG_WEBHOOK_TOLERANCE_SECONDS`: batas toleransi usia timestamp callback (detik).
- `BAYARGG_WEBHOOK_REPLAY_TTL_SECONDS`: masa simpan nonce callback untuk proteksi replay (detik).
- `BAYARGG_PAYMENT_METHOD`: salah satu dari `qris`, `qris_user`, `gopay_qris`, `ovo`.

### 2) Endpoint Callback yang Harus Dipasang di Bayar.gg

Set callback URL ke endpoint berikut:

- Method: `POST`
- URL: `/api/webhooks/bayar-gg`

Endpoint ini sudah memverifikasi signature `X-Webhook-Signature` dan `X-Webhook-Timestamp`, termasuk validasi window timestamp dan proteksi replay callback.

### 3) Cara Pakai di Storefront

1. User checkout dan pilih metode `Bayar.gg`.
2. Sistem membuat order + payment lokal, lalu meminta invoice ke Bayar.gg.
3. User diarahkan ke halaman tracking order dan klik tombol `Bayar Sekarang di Bayar.gg`.
4. Saat Bayar.gg kirim callback status `paid`, sistem otomatis update:
    - payment `status = paid`
    - order `payment_status = paid`

Jika link gateway gagal dibuat, user bisa klik tombol `Buat Link Bayar.gg Lagi` pada halaman tracking order.

### 4) Cara Pakai via API Checkout

Kirim `payment_method: "bayargg"` ke endpoint `POST /api/checkout`.

Pada response, periksa field berikut:

- `data.payment.gateway_payment_url`
- `data.payment.gateway_invoice_id`
- `data.payment.gateway_status`

Client mobile dapat membuka `gateway_payment_url` di browser/webview untuk menyelesaikan pembayaran.

## AI Assistant Phase 1 (FAQ + Tracking + Rekomendasi)

Project ini sudah memiliki endpoint backend awal untuk asisten AI:

- Method: `POST`
- Path: `/api/ai/chat`
- Route name: `api.ai.chat`

### Request Body

```json
{
    "session_id": "web-uuid",
    "message": "status pesanan ORD-ARIP-20260414-ABC123",
    "customer_email": "opsional@example.com",
    "customer_phone_last4": "opsional_4_digit",
    "budget_max": 50000,
    "category": "opsional",
    "context": {
        "locale": "id",
        "channel": "storefront"
    }
}
```

### Response Shape

```json
{
    "reply": "...",
    "intent": "faq|order_tracking|product_recommendation",
    "used_tools": ["FaqAnswerTool"],
    "suggestions": ["..."],
    "data": {
        "...": "..."
    }
}
```

### Catatan Keamanan Tracking

- Untuk guest, tracking order butuh verifikasi tambahan (`customer_email` atau 4 digit akhir nomor telepon).
- Untuk user login pemilik order, verifikasi tambahan tidak diwajibkan.

### Apakah Wajib API Key Gemini / DeepSeek?

- Phase 1 saat ini berjalan dengan mode `rule_based` (tanpa provider eksternal), jadi **tidak wajib** API key.
- Jika nanti ingin pakai model eksternal (Gemini/DeepSeek), isi env berikut:
    - `AI_PROVIDER`
    - `AI_GEMINI_API_KEY`
    - `AI_DEEPSEEK_API_KEY`

### Penempatan API Key yang Benar (`.env` vs `.env.example`)

- Simpan API key asli hanya di file `.env` lokal atau secret manager deployment.
- File `.env.example` wajib berisi placeholder kosong (tidak boleh berisi key asli).
- Jika API key sempat terekspos, lakukan rotasi key di dashboard provider lalu update file `.env`.

Contoh konfigurasi runtime provider eksternal di `.env`:

```env
AI_PROVIDER=gemini
AI_MODEL_FAST=gemini-2.5-flash
AI_MODEL_FALLBACK=deepseek-chat
AI_GEMINI_API_KEY=isi_key_baru_gemini
AI_DEEPSEEK_API_KEY=isi_key_baru_deepseek
```

Setelah ubah `.env`, jalankan:

```bash
php artisan config:clear
php artisan cache:clear
```

### Response 422 (Validation Error)

```json
{
    "message": "Validasi checkout gagal.",
    "errors": {
        "items": [
            "Keranjang kosong. Tambahkan item melalui API cart atau kirim field items."
        ]
    }
}
```

## Cart API Endpoints

Semua endpoint cart bersifat stateless dan wajib Bearer Token (`auth:sanctum`).

### 1) List Cart

- Method: `GET`
- Path: `/api/cart`

### 2) Add Item ke Cart

- Method: `POST`
- Path: `/api/cart/items`

Request body:

```json
{
    "product_id": 1,
    "quantity": 2
}
```

### 3) Update Quantity Item

- Method: `PATCH`
- Path: `/api/cart/items/{productId}`

Request body:

```json
{
    "quantity": 5
}
```

### 4) Remove Item dari Cart

- Method: `DELETE`
- Path: `/api/cart/items/{productId}`

## Mobile Flow (Token -> Cart -> Checkout)

Urutan integrasi yang disarankan untuk mobile app:

1. Login token via `POST /api/auth/token`.
2. Simpan `access_token` secara aman di client.
3. Panggil `GET /api/me` untuk bootstrap profil user.
4. Kelola keranjang via endpoint `/api/cart`.
5. Checkout via `POST /api/checkout` (boleh kirim `items`, atau biarkan checkout memakai isi cart yang sudah tersimpan).
6. Logout token via `DELETE /api/auth/token` jika user logout dari device.

Contoh header untuk semua endpoint yang butuh auth:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
