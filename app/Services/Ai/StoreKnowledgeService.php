<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Builds a comprehensive knowledge context about the store from system_settings.
 * This context is injected into the LLM system prompt so the AI understands
 * everything about Toko HS Electric — contact info, features, policies, etc.
 */
class StoreKnowledgeService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    /**
     * Get the full store knowledge document as a string for the LLM system prompt.
     */
    public function buildKnowledgeContext(): string
    {
        return Cache::remember('ai_store_knowledge_context', self::CACHE_TTL_SECONDS, function (): string {
            return $this->compileKnowledgeDocument();
        });
    }

    /**
     * Get a quick product catalog summary for the LLM (top products by category).
     */
    public function buildProductCatalogSummary(): string
    {
        return Cache::remember('ai_product_catalog_summary', self::CACHE_TTL_SECONDS, function (): string {
            return $this->compileProductCatalog();
        });
    }

    private function compileKnowledgeDocument(): string
    {
        $storeName = (string) Setting::get('store_name', 'Toko HS ELECTRIC');
        $storeTagline = (string) Setting::get('store_tagline', 'Solusi Listrik Rumah & Industri');
        $storeAddress = (string) Setting::get('store_address', '');
        $storePhone = (string) Setting::get('store_phone', '');
        $storeEmail = (string) Setting::get('store_email', '');
        $storeMapsUrl = (string) Setting::get('store_maps_url', '');
        $shippingCost = (string) Setting::get('shipping_cost_per_item', '5000');

        $hoursWeekday = (string) Setting::get('hours_weekday', '09:00 - 20:00');
        $hoursSaturday = (string) Setting::get('hours_saturday', '09:00 - 20:00');
        $hoursSunday = (string) Setting::get('hours_sunday', '09:00 - 20:00');
        $hoursNote = (string) Setting::get('hours_note', '');

        $instagram = (string) Setting::get('social_instagram_url', '');
        $facebook = (string) Setting::get('social_facebook_url', '');
        $tiktok = (string) Setting::get('social_tiktok_url', '');

        $bank1Name = (string) Setting::get('bank_1_name', '');
        $bank1Account = (string) Setting::get('bank_1_account', '');
        $bank1Holder = (string) Setting::get('bank_1_holder', '');
        $bank2Name = (string) Setting::get('bank_2_name', '');
        $bank2Account = (string) Setting::get('bank_2_account', '');
        $bank2Holder = (string) Setting::get('bank_2_holder', '');

        $whatsAppLink = $storePhone !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $storePhone) : '';

        $shippingFormatted = 'Rp ' . number_format((int) preg_replace('/[^0-9]/', '', $shippingCost), 0, ',', '.');

        $sections = [];

        // ── IDENTITAS TOKO ──
        $sections[] = "## IDENTITAS TOKO\n"
            . "- Nama Toko: {$storeName}\n"
            . "- Tagline: {$storeTagline}\n"
            . "- Toko ini adalah toko alat listrik online yang menjual berbagai kebutuhan listrik rumah tangga dan industri: lampu, kabel, saklar, stop kontak, MCB, fitting, dan lain-lain.\n"
            . "- Website ini dibuat khusus untuk memudahkan pelanggan berbelanja online dari {$storeName}.";

        // ── ALAMAT & KONTAK ──
        $contactLines = "## ALAMAT & KONTAK\n";
        if ($storeAddress !== '') {
            $contactLines .= "- Alamat fisik toko: {$storeAddress}\n";
        }
        if ($storeMapsUrl !== '') {
            $contactLines .= "- Link Google Maps: {$storeMapsUrl}\n";
        }
        if ($storePhone !== '') {
            $contactLines .= "- Nomor WhatsApp: {$storePhone}\n";
            if ($whatsAppLink !== '') {
                $contactLines .= "- Link WhatsApp langsung: {$whatsAppLink}\n";
            }
        }
        if ($storeEmail !== '') {
            $contactLines .= "- Email toko: {$storeEmail}\n";
        }
        $contactLines .= "- Jika pelanggan butuh bantuan urgent atau menanyakan pesanan yang belum sampai, arahkan untuk menghubungi WhatsApp toko.";
        $sections[] = $contactLines;

        // ── JAM OPERASIONAL ──
        $hoursSection = "## JAM OPERASIONAL\n"
            . "- Senin–Jumat: {$hoursWeekday}\n"
            . "- Sabtu: {$hoursSaturday}\n"
            . "- Minggu: {$hoursSunday}";
        if ($hoursNote !== '') {
            $hoursSection .= "\n- Catatan: {$hoursNote}";
        }
        $sections[] = $hoursSection;

        // ── MEDIA SOSIAL ──
        $socialLines = [];
        if ($instagram !== '') {
            $socialLines[] = "- Instagram: {$instagram}";
        }
        if ($facebook !== '') {
            $socialLines[] = "- Facebook: {$facebook}";
        }
        if ($tiktok !== '') {
            $socialLines[] = "- TikTok: {$tiktok}";
        }
        if (count($socialLines) > 0) {
            $sections[] = "## MEDIA SOSIAL\n" . implode("\n", $socialLines);
        }

        // ── METODE PEMBAYARAN ──
        $paymentSection = "## METODE PEMBAYARAN\n"
            . "Website mendukung 4 metode pembayaran:\n"
            . "1. **COD (Cash on Delivery)** — Bayar di tempat saat barang diantarkan kurir ke alamat pembeli. Paket AKAN DIANTAR ke alamat pembeli oleh kurir, dan pembeli membayar saat barang sampai. Pembeli TIDAK perlu datang ke toko.\n"
            . "2. **Transfer Bank** — Pembeli melakukan transfer ke rekening toko, lalu upload bukti transfer di halaman tracking pesanan. Admin akan verifikasi manual.\n"
            . "3. **E-Wallet** — Pembeli membayar via dompet digital, lalu upload bukti di halaman tracking pesanan. Admin verifikasi manual.\n"
            . "4. **Bayar.gg (Otomatis)** — Pembayaran otomatis via QRIS. Setelah checkout, pembeli klik link pembayaran Bayar.gg dan scan QRIS. Verifikasi otomatis tanpa upload bukti.";

        if ($bank1Name !== '' && $bank1Account !== '') {
            $paymentSection .= "\n\nRekening Transfer Bank:";
            $paymentSection .= "\n- {$bank1Name}: {$bank1Account} a.n. {$bank1Holder}";
            if ($bank2Name !== '' && $bank2Account !== '') {
                $paymentSection .= "\n- {$bank2Name}: {$bank2Account} a.n. {$bank2Holder}";
            }
        }
        $sections[] = $paymentSection;

        // ── ONGKOS KIRIM ──
        $sections[] = "## ONGKOS KIRIM (ONGKIR)\n"
            . "- Ongkir dihitung per item, bukan per order.\n"
            . "- Tarif saat ini: {$shippingFormatted} per item.\n"
            . "- Contoh: beli 3 item = ongkir 3 × {$shippingFormatted}.\n"
            . "- Ongkir sudah termasuk dalam total pembayaran saat checkout.";

        // ── GARANSI ──
        $sections[] = "## GARANSI\n"
            . "- Produk elektronik (berlabel elektronik) memiliki garansi klaim sesuai pengaturan produk, maksimal 365 hari setelah pesanan diterima.\n"
            . "- Garansi aktif setelah admin mengubah status pesanan menjadi 'completed'.\n"
            . "- Untuk klaim garansi: buka menu Garansi → pilih item → isi alasan kerusakan → upload foto/video bukti kerusakan.\n"
            . "- Produk non-elektronik (kabel, fitting, dll) tidak memiliki garansi.\n"
            . "- Status klaim: submitted → reviewing → approved/rejected/resolved.\n"
            . "- Admin akan review dan menghubungi via WhatsApp untuk proses penggantian.";

        // ── FITUR WEBSITE ──
        $sections[] = "## FITUR-FITUR WEBSITE\n"
            . "Berikut semua fitur yang tersedia di website {$storeName}:\n\n"

            . "### Registrasi & Login\n"
            . "- Pelanggan harus mendaftar akun (register) sebelum bisa belanja.\n"
            . "- Register hanya butuh: nama, email, dan password.\n"
            . "- Setelah register, pelanggan bisa langsung login dan mulai belanja.\n\n"

            . "### Katalog Produk (Halaman Utama)\n"
            . "- Menampilkan semua produk aktif dengan harga, kategori, dan rating.\n"
            . "- Bisa search produk berdasarkan nama, deskripsi, atau kategori.\n"
            . "- Bisa filter berdasarkan kategori.\n\n"

            . "### Detail Produk\n"
            . "- Klik produk untuk lihat detail: deskripsi lengkap, spesifikasi, harga, stok, ulasan pelanggan.\n"
            . "- Bisa tambahkan ke keranjang dari halaman ini.\n"
            . "- Pelanggan yang sudah membeli bisa beri ulasan dan rating bintang 1-5.\n\n"

            . "### Keranjang Belanja\n"
            . "- Menampilkan semua produk yang ditambahkan.\n"
            . "- Bisa ubah jumlah (quantity) atau hapus item.\n"
            . "- Menampilkan subtotal, ongkir, dan total pembayaran.\n\n"

            . "### Checkout\n"
            . "- Halaman checkout 1 langkah.\n"
            . "- Isi data: nama, email, nomor telepon.\n"
            . "- Pilih/tambah alamat pengiriman.\n"
            . "- Pilih metode pembayaran.\n"
            . "- Klik 'Proses Checkout' untuk buat pesanan.\n\n"

            . "### Cek Pesanan (Tracking)\n"
            . "- Setelah checkout, pelanggan mendapat kode order format: ORD-ARIP-YYYYMMDD-XXXXXX.\n"
            . "- Buka menu 'Cek Pesanan' untuk lihat semua pesanan.\n"
            . "- Klik pesanan untuk lihat detail status, resi, dan upload bukti bayar.\n"
            . "- Untuk metode transfer bank/e-wallet: upload bukti pembayaran di sini.\n\n"

            . "### Garansi\n"
            . "- Menu 'Garansi' untuk melihat daftar produk elektronik yang masih dalam masa garansi.\n"
            . "- Bisa ajukan klaim garansi dari menu ini.\n\n"

            . "### Klaim Garansi\n"
            . "- Bisa dilihat di menu 'Klaim Garansi'.\n"
            . "- Status klaim bisa dipantau di sini.\n\n"

            . "### Riwayat Transaksi\n"
            . "- Menu 'Riwayat Transaksi' menampilkan semua pesanan yang pernah dibuat.\n\n"

            . "### Profile & Alamat\n"
            . "- Edit profil: ubah nama, email, foto profil.\n"
            . "- Kelola alamat: tambah, edit, hapus alamat pengiriman.\n"
            . "- Set alamat default untuk checkout lebih cepat.\n\n"

            . "### Notifikasi\n"
            . "- Pelanggan mendapat notifikasi untuk update status pesanan dan info penting.";

        // ── KEBIJAKAN COD ──
        $sections[] = "## DETAIL KEBIJAKAN COD\n"
            . "- COD = Cash on Delivery = bayar saat barang sampai.\n"
            . "- Jika pelanggan memilih COD saat checkout, paket akan DIANTAR oleh kurir ke alamat pengiriman yang diisi saat checkout.\n"
            . "- Pelanggan TIDAK perlu datang ke toko untuk ambil barang.\n"
            . "- Pembayaran dilakukan langsung ke kurir saat paket diterima.\n"
            . "- Siapkan uang pas karena kurir mungkin tidak punya kembalian.\n"
            . "- Kurir akan menghubungi via nomor telepon yang diisi saat checkout.";

        // ── ALUR BELANJA ──
        $sections[] = "## ALUR BELANJA LENGKAP\n"
            . "1. Register/Login akun.\n"
            . "2. Cari produk di katalog atau gunakan search.\n"
            . "3. Klik produk → lihat detail → klik 'Tambah ke Keranjang'.\n"
            . "4. Buka Keranjang → cek item & jumlah → klik 'Checkout'.\n"
            . "5. Isi data pemesan & alamat → pilih metode pembayaran → klik 'Proses Checkout'.\n"
            . "6. Jika transfer bank/e-wallet: lakukan pembayaran → upload bukti di halaman 'Cek Pesanan'.\n"
            . "7. Jika COD: tunggu kurir mengantar, bayar saat barang tiba.\n"
            . "8. Jika Bayar.gg: klik link pembayaran → scan QRIS → otomatis terverifikasi.\n"
            . "9. Admin memproses pesanan → update status → kirim dengan resi.\n"
            . "10. Setelah barang diterima, admin ubah status menjadi 'completed'.";

        return implode("\n\n", $sections);
    }

    private function compileProductCatalog(): string
    {
        $categories = Category::query()
            ->withCount(['products as active_products_count' => fn($q) => $q->where('is_active', true)->where('stock', '>', 0)])
            ->orderBy('name')
            ->get();

        $lines = ["## RINGKASAN KATALOG PRODUK"];

        $totalProducts = Product::where('is_active', true)->where('stock', '>', 0)->count();
        $lines[] = "Total produk aktif tersedia: {$totalProducts} item.";

        if ($categories->isEmpty()) {
            $lines[] = "Belum ada kategori produk.";
            return implode("\n", $lines);
        }

        $lines[] = "\nKategori dan contoh produk:";

        foreach ($categories as $category) {
            $count = (int) $category->active_products_count;
            if ($count === 0) {
                continue;
            }

            $samples = Product::query()
                ->where('category_id', $category->id)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->orderByDesc('id')
                ->limit(3)
                ->get(['name', 'price', 'description']);

            $sampleTexts = $samples->map(function (Product $p): string {
                $price = 'Rp ' . number_format((int) $p->price, 0, ',', '.');
                $desc = $p->description ? ' — ' . \Illuminate\Support\Str::limit(strip_tags((string) $p->description), 80) : '';
                return "  • {$p->name} ({$price}){$desc}";
            })->implode("\n");

            $lines[] = "\n### {$category->name} ({$count} produk)";
            if ($sampleTexts !== '') {
                $lines[] = $sampleTexts;
            }
        }

        return implode("\n", $lines);
    }
}
