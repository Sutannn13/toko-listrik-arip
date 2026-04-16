<?php

namespace App\Services\Ai\Tools;

use App\Models\Setting;

class FaqAnswerTool
{
    /**
     * Answer a FAQ question with comprehensive store knowledge.
     * Covers 25+ topic areas — store info, website features, policies, and more.
     */
    public function answer(string $question): array
    {
        $q = strtolower(trim($question));

        if ($q === '') {
            return $this->response(
                'Halo kak! Selamat datang di HS Electric. Ada yang bisa saya bantu rekomenin produk, pandu belanja, atau cek pesanan hari ini? Tanya santai aja ya!',
                'faq.generic.empty',
                0.5,
                ['Alamat toko dimana?', 'Cara belanja di website ini', 'Rekomendasi lampu LED'],
            );
        }

        // ╔═══════════════════════════════════════════════════════════════╗
        // ║  SPECIFIC MULTI-WORD PATTERNS MUST MATCH BEFORE BROAD ONES  ║
        // ╚═══════════════════════════════════════════════════════════════╝

        // ── ALAMAT PENGIRIMAN / KELOLA ALAMAT (MUST come before generic "alamat") ──
        if ($this->matches($q, ['tambah alamat', 'menambahkan alamat', 'alamat pengiriman', 'ganti alamat', 'kelola alamat', 'alamat default', 'default alamat', 'isi alamat', 'simpan alamat', 'hapus alamat', 'edit alamat', 'ubah alamat', 'pilih alamat', 'alamat baru', 'alamat saya', 'set alamat', 'address book'])) {
            return $this->response(
                'Untuk mengelola alamat pengiriman:
1. Login ke akun Anda.
2. Buka menu "Profil" → klik tab "Alamat".
3. Klik "Tambah Alamat Baru".
4. Isi data: label alamat, nama penerima, nomor HP, alamat lengkap, kota, provinsi, dan kode pos.
5. Centang "Jadikan Default" agar alamat ini otomatis terpilih saat checkout.
6. Klik "Simpan".

Jika Anda sudah punya alamat default, maka saat checkout alamat tersebut akan langsung terpakai — Anda tidak perlu mengisi ulang setiap kali pesan.

Anda juga bisa edit atau hapus alamat yang sudah ada kapan saja dari halaman Profil → Alamat.',
                'faq.guide.address_management',
                0.95,
                [
                    'Cara checkout',
                    'Apakah pengiriman sesuai alamat yang saya isi?',
                    'Ongkir berapa?',
                ],
            );
        }

        // ── PENGIRIMAN / DIANTAR / SESUAI ALAMAT / ONGKIR (delivery process questions) ──
        if ($this->matches($q, ['apakah diantar', 'diantar ke', 'dikirim ke', 'sesuai alamat', 'kirim ke alamat', 'antar ke alamat', 'kirim ke rumah', 'antar ke rumah', 'kena ongkir', 'apakah ada ongkir'])) {
            $shippingCost = $this->resolveShippingCostPerItem();

            return $this->response(
                'Ya, barang akan dikirim/diantar ke alamat yang Anda isi saat checkout. Pengiriman 100% sesuai alamat yang terdaftar di pesanan Anda.

Tentang ongkir:
• Ongkir dihitung per item, bukan per order.
• Tarif saat ini: Rp ' . number_format($shippingCost, 0, ',', '.') . ' per item.
• Contoh: beli 3 item = ongkir 3 × Rp ' . number_format($shippingCost, 0, ',', '.') . ' = Rp ' . number_format($shippingCost * 3, 0, ',', '.') . '.
• Ongkir sudah otomatis dihitung saat checkout, jadi Anda tinggal bayar total yang tertera.

Jika memilih COD, kurir akan antar ke rumah Anda dan pembayaran (termasuk ongkir) dilakukan langsung ke kurir saat barang diterima.',
                'faq.shipping.delivery_address',
                0.95,
                [
                    'Apakah bisa COD?',
                    'Cara tambah alamat pengiriman',
                    'Metode pembayaran',
                ],
            );
        }

        // ── COD (CASH ON DELIVERY) ──
        if ($this->matches($q, ['cod', 'cash on delivery', 'bayar di tempat', 'bayar ditempat', 'diantar', 'antar', 'kurir', 'delivery'])) {
            $shippingCost = $this->resolveShippingCostPerItem();

            return $this->response(
                'Ya, kami menyediakan COD (Cash on Delivery)! Berikut ketentuannya:
• Paket akan DIANTAR oleh kurir ke alamat yang Anda isi saat checkout.
• Anda TIDAK perlu datang ke toko — kurir yang akan mengantar langsung ke rumah Anda.
• Pembayaran (harga produk + ongkir) dilakukan langsung ke kurir saat paket diterima.
• Ongkir per item: Rp ' . number_format($shippingCost, 0, ',', '.') . ' (sudah termasuk dalam total tagihan).
• Siapkan uang pas karena kurir mungkin tidak punya kembalian.
• Kurir akan menghubungi Anda via nomor telepon yang Anda masukkan saat checkout.

Jadi kalau Anda pesan via COD, tinggal tunggu di rumah saja — kurir yang datang!',
                'faq.payment.cod_policy',
                0.96,
                [
                    'Ongkir berapa?',
                    'Metode pembayaran lainnya',
                    'Cara checkout',
                ],
            );
        }

        // ── ONGKIR / SHIPPING ──
        if ($this->matches($q, ['ongkir', 'ongkos kirim', 'biaya kirim', 'shipping', 'pengiriman', 'ekspedisi', 'gratis ongkir'])) {
            $shippingCost = $this->resolveShippingCostPerItem();

            return $this->response(
                'Ongkir dihitung per item, bukan per order. Tarif saat ini Rp ' . number_format($shippingCost, 0, ',', '.') . ' per item. Contoh: beli 3 item = ongkir 3 × Rp ' . number_format($shippingCost, 0, ',', '.') . ' = Rp ' . number_format($shippingCost * 3, 0, ',', '.') . '. Ongkir otomatis dihitung saat checkout dan masuk ke total pembayaran.',
                'faq.shipping.cost_per_item',
                0.93,
                [
                    'Bagaimana cara checkout?',
                    'Metode pembayaran yang tersedia',
                    'Apakah bisa COD?',
                ],
            );
        }

        // ── ALAMAT TOKO & LOKASI (generic "alamat" — only if not caught above) ──
        if ($this->matches($q, ['alamat toko', 'lokasi toko', 'lokasi', 'dimana', 'di mana', 'maps', 'gmaps', 'google maps', 'peta', 'alamat'])) {
            $address = (string) Setting::get('store_address', '');
            $mapsUrl = (string) Setting::get('store_maps_url', '');

            $answer = 'Alamat toko kami: ' . ($address !== '' ? $address : 'Hubungi kami untuk detail alamat.') . '.';
            if ($mapsUrl !== '') {
                $answer .= "\nLink Google Maps: " . $mapsUrl;
            }

            return $this->response($answer, 'faq.store.address', 0.95, [
                'Jam buka toko',
                'Nomor WhatsApp toko',
                'Cara pesan produk online',
            ]);
        }

        // ── WHATSAPP & KONTAK ──
        if ($this->matches($q, ['whatsapp', 'wa', 'nomor hp', 'nomor telepon', 'kontak', 'contact', 'hubungi', 'telepon', 'telpon'])) {
            $phone = (string) Setting::get('store_phone', '');
            $email = (string) Setting::get('store_email', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            $answer = '';
            if ($phone !== '') {
                $answer .= 'Nomor WhatsApp toko kami: ' . $phone . '.';
                if ($waLink !== '') {
                    $answer .= "\nKlik link ini untuk langsung chat: " . $waLink;
                }
            }
            if ($email !== '') {
                $answer .= ($answer !== '' ? "\n" : '') . 'Email toko: ' . $email . '.';
            }
            if ($answer === '') {
                $answer = 'Silakan hubungi admin melalui informasi kontak yang tersedia di footer website.';
            }

            return $this->response($answer, 'faq.store.contact', 0.95, [
                'Alamat toko',
                'Jam buka toko',
                'Cara cek pesanan',
            ]);
        }

        // ── JAM BUKA / OPERASIONAL ──
        if ($this->matches($q, ['jam buka', 'jam operasional', 'jam kerja', 'buka jam', 'tutup jam', 'jam berapa', 'kapan buka', 'kapan tutup', 'hari libur', 'buka hari', 'hari apa'])) {
            $weekday = (string) Setting::get('hours_weekday', '09:00 - 20:00');
            $saturday = (string) Setting::get('hours_saturday', '09:00 - 20:00');
            $sunday = (string) Setting::get('hours_sunday', '09:00 - 20:00');
            $note = (string) Setting::get('hours_note', '');

            $answer = "Jam operasional toko kami:\n• Senin–Jumat: {$weekday}\n• Sabtu: {$saturday}\n• Minggu: {$sunday}";
            if ($note !== '') {
                $answer .= "\nCatatan: {$note}";
            }

            return $this->response($answer, 'faq.store.hours', 0.93, [
                'Alamat toko',
                'Nomor WhatsApp',
                'Cara pesan online',
            ]);
        }

        // ── SOSIAL MEDIA ──
        if ($this->matches($q, ['instagram', 'ig', 'facebook', 'fb', 'tiktok', 'sosial media', 'social media', 'medsos'])) {
            $instagram = (string) Setting::get('social_instagram_url', '');
            $facebook = (string) Setting::get('social_facebook_url', '');
            $tiktok = (string) Setting::get('social_tiktok_url', '');

            $links = [];
            if ($instagram !== '') {
                $links[] = '• Instagram: ' . $instagram;
            }
            if ($facebook !== '') {
                $links[] = '• Facebook: ' . $facebook;
            }
            if ($tiktok !== '') {
                $links[] = '• TikTok: ' . $tiktok;
            }

            $answer = count($links) > 0
                ? "Berikut akun media sosial kami:\n" . implode("\n", $links) . "\nFollow untuk update produk terbaru dan promo!"
                : 'Saat ini informasi media sosial belum tersedia. Silakan hubungi WhatsApp kami.';

            return $this->response($answer, 'faq.store.social_media', 0.92, [
                'Nomor WhatsApp',
                'Alamat toko',
                'Lihat katalog produk',
            ]);
        }

        // ── GARANSI ──
        if ($this->matches($q, ['garansi', 'warranty', 'jaminan'])) {
            return $this->response(
                'Produk elektronik di toko kami memiliki garansi klaim 7 hari setelah pesanan selesai (status completed). Garansi otomatis aktif saat admin menyelesaikan pesanan. Produk non-elektronik seperti kabel, fitting, dll tidak termasuk garansi. Untuk mengecek status garansi produk Anda, buka menu "Garansi" di website.',
                'faq.warranty.electronic_7_days',
                0.9,
                [
                    'Cara klaim garansi',
                    'Syarat klaim garansi',
                    'Hubungi WhatsApp toko',
                ],
            );
        }

        // ── KLAIM GARANSI ──
        if ($this->matches($q, ['klaim garansi', 'ajukan garansi', 'claim warranty', 'cara garansi', 'proses garansi', 'kerusakan produk', 'produk rusak'])) {
            return $this->response(
                'Cara mengajukan klaim garansi:
1. Buka menu "Garansi" di website.
2. Pilih produk elektronik yang masih dalam masa garansi (7 hari).
3. Klik "Ajukan Klaim" pada item tersebut.
4. Isi alasan kerusakan (minimal 10 karakter).
5. Upload foto atau video bukti kerusakan (format: jpg, png, mp4, mov, webm, max 20MB).
6. Klik Submit — admin akan mereview klaim Anda.
7. Admin akan menghubungi via WhatsApp untuk proses penggantian.
Status klaim bisa dipantau di menu "Klaim Garansi".',
                'faq.warranty.claim_process',
                0.92,
                [
                    'Berapa lama masa garansi?',
                    'Nomor WhatsApp toko',
                    'Cek status klaim saya',
                ],
            );
        }

        // ── METODE PEMBAYARAN ──
        if ($this->matches($q, ['bayar', 'pembayaran', 'payment', 'qris', 'bayargg', 'bayar.gg', 'transfer', 'e-wallet', 'ewallet', 'gopay', 'ovo', 'dana', 'shopeepay', 'metode bayar'])) {
            $bank1 = (string) Setting::get('bank_1_name', '');
            $bank1Account = (string) Setting::get('bank_1_account', '');
            $bank1Holder = (string) Setting::get('bank_1_holder', '');

            $answer = 'Metode pembayaran yang tersedia:
1. COD (Cash on Delivery) — Bayar saat barang diantar kurir ke rumah Anda.
2. Transfer Bank — Transfer ke rekening toko, lalu upload bukti di halaman Cek Pesanan.';

            if ($bank1 !== '' && $bank1Account !== '') {
                $answer .= " (Rekening: {$bank1} {$bank1Account} a.n. {$bank1Holder})";
            }

            $answer .= '
3. E-Wallet — Bayar via dompet digital, upload bukti di halaman Cek Pesanan.
4. Bayar.gg (Otomatis) — Scan QRIS otomatis, tidak perlu upload bukti. Paling mudah dan cepat!';

            return $this->response($answer, 'faq.payment.methods', 0.9, [
                'Cara upload bukti pembayaran',
                'Apakah bisa COD?',
                'Rekening bank toko',
            ]);
        }

        // ── REKENING BANK ──
        if ($this->matches($q, ['rekening', 'nomor rekening', 'no rekening', 'bank', 'bca', 'mandiri', 'bri', 'transfer kemana'])) {
            $banks = [];
            for ($i = 1; $i <= 3; $i++) {
                $name = (string) Setting::get("bank_{$i}_name", '');
                $account = (string) Setting::get("bank_{$i}_account", '');
                $holder = (string) Setting::get("bank_{$i}_holder", '');
                if ($name !== '' && $account !== '') {
                    $banks[] = "• {$name}: {$account} a.n. {$holder}";
                }
            }

            $answer = count($banks) > 0
                ? "Berikut rekening bank toko untuk transfer:\n" . implode("\n", $banks) . "\n\nSetelah transfer, jangan lupa upload bukti pembayaran di halaman Cek Pesanan."
                : 'Informasi rekening bank belum tersedia. Silakan hubungi admin via WhatsApp.';

            return $this->response($answer, 'faq.payment.bank_accounts', 0.93, [
                'Cara upload bukti bayar',
                'Metode pembayaran lainnya',
                'Cara cek pesanan',
            ]);
        }

        // ── UPLOAD BUKTI BAYAR ──
        if ($this->matches($q, ['bukti bayar', 'upload bukti', 'bukti transfer', 'bukti pembayaran', 'proof', 'konfirmasi bayar'])) {
            return $this->response(
                'Cara upload bukti pembayaran:
1. Buka menu "Cek Pesanan" di website.
2. Cari pesanan Anda dan klik untuk lihat detail.
3. Di halaman detail pesanan, cari tombol "Upload Bukti Pembayaran".
4. Pilih foto bukti transfer/e-wallet Anda.
5. Klik upload — admin akan memverifikasi pembayaran Anda.
Setelah diverifikasi, status pembayaran berubah menjadi "Paid" dan pesanan akan diproses.',
                'faq.payment.upload_proof',
                0.92,
                [
                    'Status pesanan saya',
                    'Metode pembayaran',
                    'Hubungi WhatsApp',
                ],
            );
        }

        // ── TRACKING PESANAN / RESI ──
        if ($this->matches($q, ['resi', 'lacak', 'tracking', 'cek pesanan', 'status pesanan', 'order saya', 'pesanan saya', 'belum sampai', 'kapan sampai', 'dimana pesanan'])) {
            return $this->response(
                'Untuk mengecek status pesanan Anda:
1. Login ke akun Anda.
2. Buka menu "Cek Pesanan".
3. Semua pesanan Anda akan ditampilkan di sini.
4. Klik pesanan untuk lihat detail status, nomor resi, dan upload bukti bayar.

Atau kirimkan kode order Anda ke sini dengan format: ORD-ARIP-YYYYMMDD-XXXXXX, dan saya akan bantu cek statusnya.',
                'faq.order.tracking',
                0.88,
                [
                    'Pesanan belum sampai, hubungi siapa?',
                    'Cara upload bukti bayar',
                    'Nomor WhatsApp toko',
                ],
            );
        }

        // ── PESANAN BELUM SAMPAI / KOMPLAIN ──
        if ($this->matches($q, ['belum sampai', 'belum diterima', 'lama pengiriman', 'kapan sampai', 'komplain', 'keluhan', 'masalah pengiriman', 'paket hilang'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            $answer = 'Jika pesanan Anda belum sampai atau ada masalah dengan pengiriman, silakan hubungi kami langsung via WhatsApp untuk penanganan cepat.';
            if ($phone !== '') {
                $answer .= ' WhatsApp: ' . $phone;
                if ($waLink !== '') {
                    $answer .= ' (' . $waLink . ')';
                }
            }
            $answer .= ' Siapkan kode order Anda agar kami bisa cek segera.';

            return $this->response($answer, 'faq.order.complaint', 0.9, [
                'Cara cek status pesanan',
                'Cara klaim garansi',
            ]);
        }

        // ── CARA BELANJA / CHECKOUT ──
        if ($this->matches($q, ['cara beli', 'cara belanja', 'cara pesan', 'cara order', 'cara checkout', 'checkout', 'gimana caranya', 'panduan', 'tutorial', 'cara pakai', 'cara menggunakan'])) {
            return $this->response(
                'Cara berbelanja di website kami:
1. Daftar akun (Register) atau Login jika sudah punya akun.
2. Jelajahi katalog produk atau gunakan fitur pencarian.
3. Klik produk yang diinginkan → lihat detail → klik "Tambah ke Keranjang".
4. Buka Keranjang → cek item dan jumlah → klik "Checkout".
5. Isi data pemesan (nama, email, telepon).
6. Pilih atau tambah alamat pengiriman.
7. Pilih metode pembayaran (COD/Transfer/E-Wallet/Bayar.gg).
8. Klik "Proses Checkout" — selesai!
Pesanan Anda akan langsung diproses oleh admin.',
                'faq.guide.how_to_buy',
                0.92,
                [
                    'Metode pembayaran yang tersedia',
                    'Ongkir berapa?',
                    'Apakah bisa COD?',
                ],
            );
        }

        // ── REGISTER / DAFTAR ──
        if ($this->matches($q, ['register', 'daftar', 'buat akun', 'sign up', 'signup', 'cara daftar'])) {
            return $this->response(
                'Cara mendaftar akun:
1. Klik tombol "Register" di pojok kanan atas website.
2. Isi nama lengkap, email, dan password.
3. Klik "Register" — akun Anda langsung aktif!
Setelah terdaftar, Anda bisa login dan mulai berbelanja.',
                'faq.guide.register',
                0.92,
                [
                    'Cara login',
                    'Cara belanja',
                    'Lupa password',
                ],
            );
        }

        // ── LOGIN ──
        if ($this->matches($q, ['login', 'masuk', 'sign in', 'signin', 'cara login'])) {
            return $this->response(
                'Cara login:
1. Klik tombol "Login" di pojok kanan atas website.
2. Masukkan email dan password yang sudah terdaftar.
3. Klik "Login" — Anda akan langsung masuk ke dashboard belanja.
Jika lupa password, klik "Lupa Password?" di halaman login.',
                'faq.guide.login',
                0.92,
                [
                    'Cara daftar akun',
                    'Lupa password',
                    'Cara belanja',
                ],
            );
        }

        // ── LUPA PASSWORD ──
        if ($this->matches($q, ['lupa password', 'reset password', 'forgot password', 'ganti password', 'ubah password'])) {
            return $this->response(
                'Jika lupa password:
1. Buka halaman Login.
2. Klik link "Lupa Password?" di bawah form login.
3. Masukkan email yang terdaftar.
4. Cek email Anda untuk link reset password.
5. Klik link tersebut → buat password baru.
Jika tetap bermasalah, hubungi admin via WhatsApp.',
                'faq.guide.forgot_password',
                0.9,
                [
                    'Cara login',
                    'Nomor WhatsApp toko',
                ],
            );
        }

        // ── PROFILE / AKUN ──
        if ($this->matches($q, ['profil', 'profile', 'edit profil', 'ubah nama', 'ganti email', 'foto profil', 'akun saya'])) {
            return $this->response(
                'Untuk mengelola profil akun Anda:
1. Login ke akun.
2. Klik nama/avatar Anda di pojok kanan atas.
3. Pilih "Edit Profil".
4. Anda bisa mengubah: nama, email, dan foto profil.
5. Klik "Simpan" untuk menyimpan perubahan.',
                'faq.guide.profile',
                0.9,
                [
                    'Cara kelola alamat pengiriman',
                    'Cara ganti password',
                    'Cara cek pesanan',
                ],
            );
        }



        // ── KERANJANG ──
        if ($this->matches($q, ['keranjang', 'cart', 'troli', 'isi keranjang', 'hapus keranjang', 'tambah keranjang'])) {
            return $this->response(
                'Tentang keranjang belanja:
• Klik tombol "Tambah ke Keranjang" di halaman produk.
• Buka keranjang melalui ikon keranjang di header website.
• Di halaman keranjang, Anda bisa: ubah jumlah (quantity), hapus item, atau lanjut ke checkout.
• Total harga, ongkir, dan grand total otomatis dihitung.
• Keranjang tersimpan selama sesi login Anda aktif.',
                'faq.guide.cart',
                0.9,
                [
                    'Cara checkout',
                    'Ongkir berapa?',
                    'Metode pembayaran',
                ],
            );
        }

        // ── RETURN / REFUND / PENGEMBALIAN ──
        if ($this->matches($q, ['return', 'refund', 'pengembalian', 'kembalikan barang', 'tukar barang', 'batal pesan', 'cancel'])) {
            $phone = (string) Setting::get('store_phone', '');

            return $this->response(
                'Untuk pengembalian atau pembatalan pesanan, silakan hubungi admin toko langsung via WhatsApp' . ($phone !== '' ? ' di nomor ' . $phone : '') . '. Setiap kasus akan ditangani secara personal. Jika produk elektronik mengalami kerusakan dalam 7 hari setelah diterima, Anda bisa ajukan klaim garansi melalui menu "Garansi" di website.',
                'faq.policy.return',
                0.88,
                [
                    'Cara klaim garansi',
                    'Nomor WhatsApp toko',
                    'Cek status pesanan',
                ],
            );
        }

        // ── PRIVACY / TERMS ──
        if ($this->matches($q, ['privacy', 'privasi', 'kebijakan privasi', 'terms', 'syarat', 'ketentuan'])) {
            return $this->response(
                'Anda bisa membaca kebijakan privasi dan syarat & ketentuan kami di link berikut:
• Kebijakan Privasi: tersedia di footer website (Privacy Policy).
• Syarat & Ketentuan: tersedia di footer website (Terms and Conditions).
Kami menjaga kerahasiaan data pelanggan dengan serius.',
                'faq.policy.privacy_terms',
                0.88,
                [
                    'Cara daftar akun',
                    'Metode pembayaran',
                ],
            );
        }

        // ── REVIEW / ULASAN ──
        if ($this->matches($q, ['review', 'ulasan', 'rating', 'bintang', 'testimoni', 'feedback'])) {
            return $this->response(
                'Anda bisa memberikan review/ulasan untuk produk yang sudah Anda beli:
1. Buka halaman detail produk yang sudah Anda beli dan sudah selesai (status completed).
2. Scroll ke bagian "Ulasan".
3. Pilih rating bintang (1-5) dan tulis komentar (opsional).
4. Klik "Kirim Ulasan".
Catatan: Anda hanya bisa memberi ulasan untuk produk yang sudah pernah Anda beli dan pesanannya telah selesai.',
                'faq.guide.review',
                0.9,
                [
                    'Cara cek pesanan',
                    'Rekomendasi produk',
                ],
            );
        }

        // ── STOK / KETERSEDIAAN ──
        if ($this->matches($q, ['stok', 'tersedia', 'available', 'habis', 'kosong', 'ready stock', 'ready'])) {
            return $this->response(
                'Informasi stok ditampilkan di setiap halaman produk. Produk yang ditampilkan di katalog adalah produk yang masih tersedia (aktif dan ada stok). Jika stok habis, produk tidak akan muncul di katalog. Untuk menanyakan ketersediaan produk tertentu, silakan sebutkan nama produknya dan saya akan bantu cek.',
                'faq.product.stock',
                0.88,
                [
                    'Rekomendasi produk',
                    'Cari produk tertentu',
                    'Nomor WhatsApp toko',
                ],
            );
        }

        // ── PROMO / DISKON ──
        if ($this->matches($q, ['promo', 'diskon', 'potongan', 'voucher', 'kupon', 'cashback', 'sale'])) {
            return $this->response(
                'Saat ini informasi promo dan diskon bisa dilihat langsung di katalog produk. Untuk update promo terbaru, follow media sosial kami atau hubungi WhatsApp toko. Harga yang tertera di website sudah merupakan harga terbaik dari toko kami.',
                'faq.store.promo',
                0.85,
                [
                    'Media sosial toko',
                    'Nomor WhatsApp',
                    'Rekomendasi produk murah',
                ],
            );
        }

        // ── NOTIFIKASI ──
        if ($this->matches($q, ['notifikasi', 'notification', 'pemberitahuan'])) {
            return $this->response(
                'Website kami memiliki sistem notifikasi untuk memberitahu Anda tentang update pesanan. Buka menu "Notifikasi" untuk melihat semua pemberitahuan terbaru. Notifikasi akan muncul saat ada perubahan status pesanan, verifikasi pembayaran, dan informasi penting lainnya.',
                'faq.guide.notifications',
                0.88,
                [
                    'Cek status pesanan',
                    'Cara checkout',
                ],
            );
        }

        // ── RIWAYAT TRANSAKSI ──
        if ($this->matches($q, ['riwayat', 'transaksi', 'history', 'pesanan lama', 'pesanan sebelumnya'])) {
            return $this->response(
                'Untuk melihat riwayat transaksi Anda:
1. Login ke akun.
2. Buka menu "Riwayat Transaksi".
3. Semua pesanan yang pernah Anda buat akan ditampilkan di sini, termasuk yang sudah selesai, dibatalkan, atau sedang diproses.',
                'faq.guide.transaction_history',
                0.9,
                [
                    'Cek status pesanan',
                    'Cara klaim garansi',
                ],
            );
        }

        // ── SAPAAN + PERTANYAAN PRODUK KASUAL ("halo, jualan kabel ga?") ──
        if ($this->matchesGreeting($q)) {
            // Detect if the greeting also contains a product question
            $productMentions = $this->detectProductMentions($q);

            if ($productMentions !== null) {
                $storeName = (string) Setting::get('store_name', 'Toko HS ELECTRIC');

                return $this->response(
                    "Halo kak, selamat datang di {$storeName}! 😊\n\nIya kak, kita jualan {$productMentions} kok! Bisa langsung cek di katalog website ya kak. Tinggal scroll atau pakai fitur search di halaman utama, nanti ketemu semua pilihan yang tersedia lengkap sama harga dan spesifikasinya.\n\nKalau kakak bingung mau pilih yang mana, tanya aja ke saya, nanti saya bantu rekomendasiin yang paling cocok buat kebutuhan kakak! 💡",
                    'faq.greeting.product_inquiry',
                    0.92,
                    [
                        'Rekomendasi ' . $productMentions,
                        'Cara belanja di website ini',
                        'Ongkir berapa?',
                    ],
                );
            }

            return $this->response(
                'Halo kak! Selamat datang di HS Electric ⚡ Ada yang bisa dibantu? Mau cari produk listrik, tanya soal pesanan, atau butuh bantuan pakai websitenya? Tanya santai aja ya! 😊',
                'faq.generic.greeting',
                0.85,
                [
                    'Cara belanja di website ini',
                    'Rekomendasi produk',
                    'Alamat & kontak toko',
                ],
            );
        }

        // ── "APAKAH BOLEH CHAT?" / "APAKAH SAYA BISA CHAT?" ──
        if ($this->matches($q, ['boleh chat', 'bisa chat', 'boleh tanya', 'bisa tanya', 'boleh nanya', 'bisa nanya', 'mau tanya', 'mau nanya', 'izin tanya', 'izin bertanya', 'permisi'])) {
            return $this->response(
                'Tentu boleh dong kak! 😊 Silakan tanya apa aja, saya siap bantu. Mau tanya soal produk, cara belanja, pengiriman, atau hal lainnya — langsung aja ya!',
                'faq.generic.permission_to_chat',
                0.9,
                [
                    'Cara belanja di website ini',
                    'Rekomendasi produk',
                    'Ongkir berapa?',
                ],
            );
        }

        // ── KELUHAN / KOMPLAIN / MASALAH ──
        if ($this->matches($q, ['komplain', 'keluhan', 'kecewa', 'marah', 'kesal', 'belum sampai', 'belum dikirim', 'belum diproses', 'lama banget', 'lama sekali', 'ga direspon', 'tidak direspon', 'gak direspon', 'susah'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            $waInfo = $waLink !== '' ? "\n\nLangsung chat admin via WhatsApp ya kak biar cepat ditangani: {$waLink}" : "\n\nSilakan hubungi admin melalui kontak yang tersedia di website.";

            return $this->response(
                "Waduh, mohon maaf banget ya kak atas ketidaknyamanannya 🙏 Saya paham pasti frustasi. Biar masalah kakak bisa langsung ditangani sama tim kami, saya sarankan hubungi admin langsung supaya bisa dicek dan dibantu secepatnya.{$waInfo}\n\nKalau mau, kasih tau juga nomor pesanannya (format: ORD-ARIP-...) biar admin bisa langsung tracking.",
                'faq.complaint.general',
                0.93,
                [
                    'Cek status pesanan',
                    'Nomor WhatsApp toko',
                ],
            );
        }

        // ── BINGUNG / TIDAK TAHU CARA PAKAI ──
        if ($this->matches($q, ['bingung', 'ga ngerti', 'gak ngerti', 'tidak paham', 'tidak mengerti', 'ga paham', 'gak paham', 'gimana sih', 'gimana ya', 'cara nya gimana', 'caranya gimana'])) {
            return $this->response(
                "Tenang kak, saya bantu ya! 😊 Biar gampang, kasih tau aja kakak lagi bingung soal apa:\n\n• Bingung cara belanja? → Saya kasih panduan step-by-step\n• Bingung pilih produk? → Ceritain kebutuhannya, saya bantu cariin\n• Bingung soal pembayaran? → Saya jelasin semua metode yang tersedia\n• Bingung soal pengiriman/ongkir? → Saya jelasin sistemnya\n\nAtau langsung aja tanya spesifik, pasti saya bantu jawab!",
                'faq.help.confused',
                0.88,
                [
                    'Cara belanja di website ini',
                    'Metode pembayaran',
                    'Cara tambah alamat',
                    'Ongkir berapa?',
                ],
            );
        }

        // ── TENTANG TOKO / SIAPA ──
        if ($this->matches($q, ['tentang toko', 'about', 'siapa', 'apa ini', 'toko apa', 'jual apa', 'produk apa saja', 'jualan apa'])) {
            $storeName = (string) Setting::get('store_name', 'Toko HS ELECTRIC');
            $tagline = (string) Setting::get('store_tagline', 'Solusi Listrik Rumah & Industri');

            return $this->response(
                "Kenalin kak, ini {$storeName} — {$tagline} 😊\n\nKami jualan berbagai peralatan listrik berkualitas buat rumah tangga dan industri. Mulai dari lampu LED, kabel listrik, saklar, stop kontak, MCB, fitting, sampai antena TV — semua tersedia!\n\nBelanja bisa langsung dari website ini, tinggal pilih produk → checkout → bisa COD (bayar di tempat) atau transfer. Gampang banget kok kak!",
                'faq.store.about',
                0.9,
                [
                    'Lihat katalog produk',
                    'Cara belanja',
                    'Alamat toko',
                ],
            );
        }

        // ── KEAMANAN / AMAN ──
        if ($this->matches($q, ['aman', 'terpercaya', 'penipuan', 'tipu', 'scam', 'safe', 'beneran', 'asli'])) {
            $mapsUrl = (string) Setting::get('store_maps_url', '');
            $mapInfo = $mapsUrl !== '' ? "\n\nCek lokasi toko kami di Google Maps: {$mapsUrl}" : '';

            return $this->response(
                "Tenang kak, toko kami 100% terpercaya! 🛡️ Kita punya toko fisik yang bisa dikunjungi langsung. Ditambah lagi, kita support metode COD (bayar di tempat) — jadi kakak bisa pastikan barangnya sampai dulu baru bayar. Semua transaksi juga tercatat dan bisa dilacak lewat website.{$mapInfo}",
                'faq.store.trust',
                0.88,
                [
                    'Alamat toko & Google Maps',
                    'Metode pembayaran COD',
                    'Nomor WhatsApp toko',
                ],
            );
        }

        // ── TERIMA KASIH ──
        if ($this->matches($q, ['terima kasih', 'makasih', 'thanks', 'thank you', 'nuhun', 'tengkyu'])) {
            return $this->response(
                'Sama-sama kak! 🙏 Senang bisa bantu. Kalau ada pertanyaan lain kapan aja, langsung chat aja ya. Selamat berbelanja di HS Electric! ⚡',
                'faq.generic.thanks',
                0.9,
                [
                    'Lihat katalog produk',
                    'Rekomendasi produk',
                ],
            );
        }

        // ╔═══════════════════════════════════════════════════════════════╗
        // ║  TROUBLESHOOTING — PROBLEM-SOLVING (SOLUSI DULU, WA TERAKHIR) ║
        // ╚═══════════════════════════════════════════════════════════════╝

        // ── PAYMENT REJECTED / FAILED ──
        if ($this->matches($q, ['pembayaran ditolak', 'bayar ditolak', 'pembayaran gagal', 'gagal bayar', 'bukti ditolak', 'proof ditolak'])) {
            return $this->response(
                'Waduh, nggak enak banget ya kak 😔 Tenang, biasanya pembayaran ditolak karena salah satu alasan ini:

1. **Foto bukti transfer blur atau terpotong** — Pastikan seluruh bukti transfer terlihat jelas, termasuk nominal, tanggal, dan nama pengirim.
2. **Nominal tidak sesuai** — Pastikan jumlah transfer sama persis dengan total pesanan.
3. **Format file tidak sesuai** — Upload hanya menerima JPG atau PNG, maksimal 2MB.

Coba langkah ini ya kak:
1. Buka menu **Cek Pesanan** → cari pesanan kakak.
2. Klik **Ganti Bukti Pembayaran**.
3. Upload ulang screenshot yang jelas dan lengkap.
4. Tunggu admin memverifikasi (biasanya 1-3 jam).

Kalau sudah upload ulang tapi masih ditolak, langsung chat admin ya biar dicek manual.',
                'faq.troubleshoot.payment_rejected',
                0.95,
                [
                    'Nomor WhatsApp admin',
                    'Cara upload bukti bayar',
                    'Rekening bank toko',
                ],
            );
        }

        // ── SUDAH TRANSFER TAPI STATUS BELUM BERUBAH ──
        if ($this->matches($q, ['sudah transfer tapi', 'sudah bayar tapi', 'udah bayar tapi', 'udah transfer tapi', 'sudah transfer', 'udah transfer', 'sudah bayar', 'udah bayar'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                'Tenang ya kak, saya bantu cek 🙏

Jika kakak **sudah upload bukti pembayaran**, statusnya saat ini sedang menunggu verifikasi admin. Biasanya verifikasi butuh waktu **1-3 jam** (jam kerja).

Coba cek dulu ya kak:
1. Buka menu **Cek Pesanan** → klik pesanan kakak.
2. Pastikan status pembayaran menunjukkan **"Menunggu Verifikasi"** (berarti bukti sudah terkirim).
3. Kalau statusnya masih **"Belum Bayar"**, kemungkinan upload belum berhasil. Coba upload ulang.

Jika sudah lebih dari 3 jam dan belum dikonfirmasi, langsung hubungi admin:' . ($waLink !== '' ? "\nWhatsApp: {$waLink}" : '') . '

Siapkan kode pesanan (ORD-ARIP-...) agar admin langsung cek.',
                'faq.troubleshoot.payment_pending',
                0.94,
                [
                    'Cara upload bukti bayar',
                    'Nomor WhatsApp admin',
                    'Cek status pesanan',
                ],
            );
        }

        // ── UPLOAD GAGAL ──
        if ($this->matches($q, ['upload gagal', 'gagal upload', 'tidak bisa upload', 'ga bisa upload', 'gak bisa upload'])) {
            return $this->response(
                'Kalau uploadnya gagal, coba cek ini ya kak:

1. **Ukuran file** — Maksimal 2MB. Coba kompres foto dulu (bisa pakai aplikasi kompres foto gratis).
2. **Format file** — Hanya JPG dan PNG yang diterima. Coba screenshot ulang bukti transfer.
3. **Koneksi internet** — Pastikan sinyal stabil saat upload.
4. **Browser** — Coba clear cache browser atau gunakan browser lain.

Kalau sudah coba semua tapi masih gagal, coba dari HP lain atau langsung kirim buktinya ke admin via WhatsApp beserta kode pesanannya.',
                'faq.troubleshoot.upload_failed',
                0.93,
                [
                    'Nomor WhatsApp admin',
                    'Metode pembayaran lainnya',
                ],
            );
        }

        // ── PESANAN BELUM DIKIRIM / LAMA ──
        if ($this->matches($q, ['belum dikirim', 'belum diproses', 'lama sekali', 'lama banget', 'kapan dikirim', 'kapan diproses'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                'Waduh pasti nunggu nggak enak ya kak 😔 Coba kita cek bareng:

1. **Cek status pembayaran** — Buka menu Cek Pesanan. Pesanan baru diproses SETELAH pembayaran dikonfirmasi lunas.
2. **Jika status sudah "Lunas/Paid"** — Pesanan biasanya diproses 1-2 hari kerja (tidak termasuk weekend/libur).
3. **Jika status masih "Belum Bayar"** — Upload bukti pembayaran terlebih dahulu agar admin bisa memverifikasi.

Kalau sudah lunas lebih dari 2 hari kerja dan belum diproses, langsung hubungi admin ya kak:' . ($waLink !== '' ? "\nWhatsApp: {$waLink}" : '') . '

Sampaikan kode pesanan (ORD-ARIP-...) biar langsung dicek.',
                'faq.troubleshoot.order_delayed',
                0.94,
                [
                    'Cek status pesanan',
                    'Cara upload bukti bayar',
                    'Nomor WhatsApp admin',
                ],
            );
        }

        // ── BARANG RUSAK / TIDAK SESUAI ──
        if ($this->matches($q, ['barang rusak', 'produk rusak', 'cacat', 'pecah', 'retak', 'tidak sesuai', 'ga sesuai', 'gak sesuai', 'beda sama', 'beda dengan'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                'Aduh, mohon maaf banget ya kak atas ketidaknyamanannya 🙏

Kalau barangnya rusak/tidak sesuai, ini yang bisa dilakukan:

**Jika produk ELEKTRONIK (lampu, MCB, dll):**
1. Cek apakah masih dalam masa garansi (7 hari sejak pesanan selesai).
2. Buka menu **Garansi** di website.
3. Pilih produk yang bermasalah → klik **Ajukan Klaim**.
4. Isi alasan + upload foto/video bukti kerusakan.
5. Admin akan review dan menghubungi kakak via WhatsApp.

**Jika produk NON-ELEKTRONIK (kabel, fitting, dll):**
Langsung hubungi admin via WhatsApp dengan foto barang + kode pesanan, biar dibantu pengecekan.' . ($waLink !== '' ? "\n\nWhatsApp admin: {$waLink}" : ''),
                'faq.troubleshoot.product_defect',
                0.95,
                [
                    'Cara klaim garansi',
                    'Nomor WhatsApp admin',
                    'Berapa lama masa garansi?',
                ],
            );
        }

        // ── SALAH ALAMAT ──
        if ($this->matches($q, ['salah alamat', 'alamat salah', 'salah kirim', 'ganti alamat pesanan'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                'Oh no kak 😥 Tenang, tergantung status pesanannya:

**Jika pesanan masih Pending / Diproses:**
✅ Masih bisa diubah! Langsung hubungi admin via WhatsApp sekarang juga dengan info:
- Kode pesanan (ORD-ARIP-...)
- Alamat yang benar/terbaru

**Jika pesanan sudah Dikirim:**
❌ Sayangnya alamat sudah tidak bisa diubah. Hubungi admin agar dibantu koordinasi dengan kurir.' . ($waLink !== '' ? "\n\nChat admin sekarang: {$waLink}" : ''),
                'faq.troubleshoot.wrong_address',
                0.93,
                [
                    'Nomor WhatsApp admin',
                    'Cek status pesanan',
                    'Cara kelola alamat',
                ],
            );
        }

        // ── TIDAK BISA LOGIN ──
        if ($this->matches($q, ['tidak bisa login', 'ga bisa login', 'gak bisa login', 'tidak bisa masuk', 'ga bisa masuk', 'gabisa login'])) {
            return $this->response(
                'Nggak bisa login ya kak? Coba langkah berikut:

1. **Cek Caps Lock** — Pastikan Caps Lock mati saat mengetik password.
2. **Cek email** — Pastikan email yang dimasukkan benar dan sama dengan saat daftar.
3. **Reset password** — Klik "Lupa Password?" di halaman login → masukkan email → cek inbox email untuk link reset.
4. **Cek spam folder** — Kadang email reset masuk ke folder Spam/Junk.

Kalau tetap tidak bisa setelah reset password, kemungkinan akun kakak perlu dikonfirmasi oleh admin. Hubungi admin via WhatsApp dengan email yang terdaftar.',
                'faq.troubleshoot.login_failed',
                0.93,
                [
                    'Cara reset password',
                    'Nomor WhatsApp admin',
                    'Cara daftar akun baru',
                ],
            );
        }

        // ── PESANAN DIBATALKAN (KENAPA?) ──
        if ($this->matches($q, ['pesanan dibatalkan', 'kenapa dibatalkan', 'kenapa batal', 'order batal', 'dibatalkan admin'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                'Aduh, pesanan dibatalkan ya kak? 😔 Berikut kemungkinan alasannya:

1. **Belum bayar dalam batas waktu** — Jika pembayaran tidak diterima dalam waktu yang ditentukan, pesanan otomatis dibatalkan.
2. **Stok habis** — Produk yang dipesan sudah habis saat admin memproses.
3. **Pembatalan oleh admin** — Ada masalah teknis atau data pemesan tidak lengkap.

Yang bisa dilakukan:
• **Jika ingin pesan ulang** — Langsung buat pesanan baru lewat website.
• **Jika ingin tahu alasan pasti** — Hubungi admin via WhatsApp dengan kode pesanan kakak.' . ($waLink !== '' ? "\n\nWhatsApp admin: {$waLink}" : ''),
                'faq.troubleshoot.order_cancelled',
                0.93,
                [
                    'Cara belanja ulang',
                    'Nomor WhatsApp admin',
                    'Cek status pesanan',
                ],
            );
        }

        // ╔═══════════════════════════════════════════════════════════════╗
        // ║  EMOTIONAL SUPPORT — Curhat, Venting, Frustration              ║
        // ╚═══════════════════════════════════════════════════════════════╝

        // ── CURHAT / VENTING / EMOSI ──
        if ($this->matches($q, ['curhat', 'curcol', 'pengen cerita', 'mau cerita', 'capek', 'cape', 'lelah', 'bete', 'bt'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                "Wah kak, saya dengerin kok 🙏 Kalau ada pengalaman yang kurang menyenangkan terkait belanja di toko kami, saya benar-benar minta maaf ya kak. Perasaan kakak sangat valid dan kami serius menanggapinya.\n\nCerita aja kak, saya di sini buat bantu sebaik mungkin. Kalau ada masalah spesifik yang bisa saya bantu selesaikan (soal pesanan, produk, atau layanan), langsung aja ya kak — saya cariin solusinya." . ($waLink !== '' ? "\n\nAtau kalau mau ngobrol langsung sama tim kami, chat WhatsApp aja kak: {$waLink}" : ''),
                'faq.emotional.venting',
                0.88,
                [
                    'Cek status pesanan',
                    'Cara klaim garansi',
                    'Hubungi admin via WhatsApp',
                ],
            );
        }

        // ── KECEWA / SANGAT KECEWA ──
        if ($this->matches($q, ['kecewa', 'kecewa berat', 'sangat kecewa', 'sedih', 'nyesel', 'menyesal', 'kapok', 'rugi'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                "Aduh kak, saya ikut sedih dengernya 😔 Mohon maaf banget kalau ada pengalaman yang tidak menyenangkan. Serius deh kak, kami sangat menghargai kepercayaan kakak dan feedback ini penting banget buat kami.\n\nBoleh ceritain lebih detail apa yang terjadi? Biar saya bisa bantu cariin solusi yang terbaik buat kakak. Kalau soal pesanan, produk, atau layanan — saya usahain bantu selesaiin sekarang juga." . ($waLink !== '' ? "\n\nKalau mau langsung ditangani tim kami: {$waLink}" : ''),
                'faq.emotional.disappointed',
                0.9,
                [
                    'Cek status pesanan saya',
                    'Cara klaim garansi',
                    'Hubungi WhatsApp admin',
                ],
            );
        }

        // ── KESAL / MARAH ──
        if ($this->matches($q, ['kesal', 'kesel', 'sebel', 'jengkel', 'emosi', 'marah', 'ga becus', 'gak becus', 'parah banget', 'parah sih'])) {
            $phone = (string) Setting::get('store_phone', '');
            $waLink = $phone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) : '';

            return $this->response(
                "Kak, saya benar-benar minta maaf ya 🙏 Saya paham banget perasaan kakak dan itu sangat wajar. Kami ambil ini serius dan mau pastikan masalah kakak terselesaikan.\n\nCeritain aja kak masalahnya apa — pesanan bermasalah? produk tidak sesuai? layanan kurang memuaskan? Apapun itu, saya bakal bantu secepat mungkin." . ($waLink !== '' ? "\n\nUntuk penanganan prioritas, langsung chat admin: {$waLink}" : ''),
                'faq.emotional.angry',
                0.92,
                [
                    'Cek pesanan saya',
                    'Klaim garansi produk rusak',
                    'Hubungi admin sekarang',
                ],
            );
        }

        // ── FRUSTASI / PUSING / STRESS ──
        if ($this->matches($q, ['frustasi', 'stress', 'pusing', 'mumet', 'ribet', 'ribet banget', 'susah banget', 'rumit', 'gajelas', 'ga jelas', 'gak jelas'])) {
            return $this->response(
                "Tenang kak, saya bantu pelan-pelan ya 😊 Saya paham kadang website belanja bisa bikin pusing, apalagi kalau baru pertama kali.\n\nKasih tau aja kakak lagi stuck di bagian mana:\n• Mau belanja tapi bingung caranya?\n• Mau bayar tapi bingung metodenya?\n• Mau cek pesanan tapi ga ketemu?\n• Atau ada hal lain?\n\nApapun itu, saya jelaskan step-by-step dengan sabarrrr banget! Ga usah sungkan ya kak 💪",
                'faq.emotional.frustrated',
                0.88,
                [
                    'Panduan belanja step-by-step',
                    'Metode pembayaran',
                    'Cara cek pesanan',
                    'Hubungi admin WhatsApp',
                ],
            );
        }

        // ╔═══════════════════════════════════════════════════════════════╗
        // ║  NEWBIE — First-time users needing extra guidance             ║
        // ╚═══════════════════════════════════════════════════════════════╝

        if ($this->matches($q, ['pertama kali', 'baru pertama', 'gaptek', 'awam', 'pemula', 'newbie', 'baru belajar', 'belum pernah', 'blm pernah', 'ga pernah belanja online', 'gak pernah belanja online', 'pertama belanja', 'baru mau coba'])) {
            return $this->response(
                "Selamat datang kak! 🎉 Seneng banget ini kunjungan pertama kakak ke toko kami. Tenang, saya pandu dari awal sampai akhir ya!\n\nIni langkah super simpel buat mulai belanja:\n\n1️⃣ **Daftar dulu** → Klik tombol \"Daftar\" di pojok kanan atas. Tinggal isi nama, email, dan password. Cuma 30 detik!\n2️⃣ **Cari produk** → Scroll di halaman utama atau ketik nama produk di kolom pencarian.\n3️⃣ **Pilih & beli** → Klik produk yang mau dibeli → klik \"Tambah ke Keranjang\".\n4️⃣ **Checkout** → Buka keranjang → isi alamat → pilih cara bayar → selesai!\n\nMetode bayar paling gampang buat pemula: **COD (bayar di tempat)** — kakak tinggal tunggu di rumah, kurir yang antar. Bayar pas barang sampai. Zero ribet! 😊\n\nKalau masih bingung di langkah manapun, tanya aja ke saya ya. Saya jelasin pelan-pelan!",
                'faq.newbie.welcome',
                0.93,
                [
                    'Apa itu COD?',
                    'Cara daftar akun',
                    'Rekomendasi produk populer',
                    'Ongkir berapa?',
                ],
            );
        }

        // ╔═══════════════════════════════════════════════════════════════╗
        // ║  OFF-TOPIC — Polite deflection for unrelated questions       ║
        // ╚═══════════════════════════════════════════════════════════════╝

        if ($this->matches($q, ['politik', 'pilpres', 'pemilu', 'presiden', 'capres', 'partai'])) {
            return $this->response(
                "Wah kak, kalau soal politik saya kurang paham nih hehe 😅 Saya cuma jago soal listrik dan alat-alat kelistrikan aja!\n\nTapi kalau mau ngobrol soal lampu LED yang terang, kabel yang awet, atau MCB yang aman — itu baru deh spesialisasi saya! 💡⚡\n\nAda yang bisa dibantu soal produk listrik kak?",
                'faq.offtopic.politics',
                0.7,
                ['Rekomendasi produk', 'Cara belanja', 'Alamat toko'],
            );
        }

        if ($this->matches($q, ['cuaca', 'hujan', 'panas', 'mendung', 'cerah'])) {
            return $this->response(
                "Hehe kak, soal cuaca saya ga bisa prediksi nih 😄 Tapi yang bisa saya jamin: produk-produk listrik di toko kami tetap nyala terang hujan ataupun cerah! ⚡\n\nKalau lagi musim hujan, mungkin kakak butuh lampu emergency atau senter LED? Tanya aja ya!",
                'faq.offtopic.weather',
                0.7,
                ['Rekomendasi lampu emergency', 'Katalog produk', 'Alamat toko'],
            );
        }

        if ($this->matches($q, ['film', 'drama', 'drakor', 'anime', 'game', 'musik', 'konser', 'artis', 'gosip', 'selebriti'])) {
            return $this->response(
                "Wah kak, kalau soal hiburan saya kurang update nih 😄 Tapi kalau soal \"drama\" kabel yang sering putus, atau \"game\" pilih lampu yang hemat listrik — itu saya jagonya! 💡\n\nBoleh tanya soal produk listrik aja ya kak, saya pasti bantu!",
                'faq.offtopic.entertainment',
                0.7,
                ['Rekomendasi produk', 'Cara belanja', 'Cek pesanan'],
            );
        }

        if ($this->matches($q, ['bola', 'sepak bola', 'liga', 'timnas', 'piala', 'resep', 'masak', 'makanan', 'restoran', 'cafe', 'zodiak', 'ramalan', 'horoscope', 'mimpi', 'jodoh', 'pacar', 'gebetan', 'mantan', 'iphone', 'samsung', 'laptop', 'komputer', 'android', 'ios'])) {
            return $this->response(
                "Hehe kak, topik itu di luar keahlian saya nih 😊 Saya spesifiknya cuma paham soal dunia kelistrikan: lampu, kabel, saklar, MCB, dan segala kebutuhan listrik rumah tangga.\n\nTapi kalau mau ngobrol soal produk listrik, saya siap 24/7! Ada yang bisa dibantu kak? ⚡",
                'faq.offtopic.general',
                0.65,
                ['Rekomendasi produk listrik', 'Cara belanja', 'Alamat & kontak toko'],
            );
        }

        // ── DEFAULT / FALLBACK ──
        return $this->response(
            'Halo kak! 😊 Saya siap bantu nih. Mau tanya soal apa kak? Produk listrik, cara belanja, pengiriman, atau hal lainnya — langsung aja ya!',
            'faq.generic.default',
            0.65,
            [
                'Cara belanja di website ini',
                'Rekomendasi produk',
                'Alamat dan kontak toko',
                'Cek status pesanan saya',
            ],
        );
    }

    /**
     * Check if the question matches any of the given keywords.
     */
    private function matches(string $question, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a standardized FAQ response.
     */
    private function response(string $answer, string $sourceKey, float $confidence, array $suggestions): array
    {
        return [
            'answer' => $answer,
            'source_key' => $sourceKey,
            'confidence' => $confidence,
            'suggestions' => $suggestions,
        ];
    }

    private function resolveShippingCostPerItem(): int
    {
        $rawValue = (string) Setting::get('shipping_cost_per_item', '5000');
        $normalizedValue = preg_replace('/[^0-9]/', '', $rawValue);

        if ($normalizedValue === null || $normalizedValue === '') {
            return 5000;
        }

        return max(0, (int) $normalizedValue);
    }

    /**
     * Check if the message contains a greeting/salutation.
     */
    private function matchesGreeting(string $question): bool
    {
        $greetings = [
            'halo', 'hello', 'hi ', 'hai', 'hey',
            'assalamualaikum', 'assalamu',
            'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam',
            'permisi', 'misi',
        ];

        foreach ($greetings as $greeting) {
            if (str_contains($question, $greeting)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect product category mentions within a conversational message.
     * Returns a human-readable product label, or null if none found.
     */
    private function detectProductMentions(string $question): ?string
    {
        $productMap = [
            'kabel' => 'kabel listrik',
            'lampu' => 'lampu',
            'bohlam' => 'lampu/bohlam',
            'led' => 'lampu LED',
            'saklar' => 'saklar',
            'stop kontak' => 'stop kontak',
            'stopkontak' => 'stop kontak',
            'fitting' => 'fitting',
            'mcb' => 'MCB',
            'antena' => 'antena TV',
            'anten' => 'antena TV',
            'downlight' => 'downlight',
            'steker' => 'steker',
            'colokan' => 'stop kontak/colokan',
        ];

        $found = [];
        foreach ($productMap as $keyword => $label) {
            if (str_contains($question, $keyword)) {
                $found[] = $label;
            }
        }

        if (count($found) === 0) {
            return null;
        }

        return implode(', ', array_unique($found));
    }
}
