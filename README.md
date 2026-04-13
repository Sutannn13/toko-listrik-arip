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
- `payment_method` yang didukung: `cod`, `bank_transfer`, `ewallet`, `dummy`.

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
            "amount": 40000
        }
    }
}
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
