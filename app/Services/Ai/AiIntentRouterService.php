<?php

namespace App\Services\Ai;

class AiIntentRouterService
{
    /**
     * Resolve the user's intent from their message.
     *
     * Priority order matters! More specific intents (website_help with multi-word
     * phrases) must be checked BEFORE broader intents (store_info with single-word
     * matches like "alamat").
     */
    public function resolveIntent(string $message): string
    {
        $normalizedMessage = strtolower(trim($message));

        if ($normalizedMessage === '') {
            return 'faq';
        }

        if ($this->containsOrderTrackingHint($normalizedMessage)) {
            return 'order_tracking';
        }

        // ── Website Help MUST come before Store Info ──
        if ($this->containsWebsiteHelpHint($normalizedMessage)) {
            return 'website_help';
        }

        // ── Conversational / greeting messages should go to FAQ ──
        // "halo jualan kabel ga?" is a greeting, NOT a product search.
        // Without this check, "kabel" would trigger product_recommendation.
        if ($this->isConversationalMessage($normalizedMessage)) {
            return 'faq';
        }

        if ($this->containsRecommendationHint($normalizedMessage)) {
            return 'product_recommendation';
        }

        if ($this->containsStoreInfoHint($normalizedMessage)) {
            return 'store_info';
        }

        // ── Troubleshooting: user has a problem and needs a solution ──
        if ($this->containsTroubleshootingHint($normalizedMessage)) {
            return 'troubleshooting';
        }

        // ── Emotional support: user is venting, frustrated, sad, or confused ──
        if ($this->containsEmotionalCue($normalizedMessage)) {
            return 'emotional_support';
        }

        // ── Off-topic: questions unrelated to electrical products/store ──
        if ($this->isOffTopicMessage($normalizedMessage)) {
            return 'off_topic';
        }

        // ── Newbie detection: first-time users needing extra guidance ──
        if ($this->isNewbieMessage($normalizedMessage)) {
            return 'faq';
        }

        return 'faq';
    }

    /**
     * Detect conversational / greeting messages that should NOT be routed
     * to product_recommendation even if they contain product keywords.
     *
     * "halo, jualan kabel ga?" → FAQ (greeting + casual question)
     * "cari kabel 2x0.75" → product_recommendation (explicit search)
     */
    private function isConversationalMessage(string $message): bool
    {
        $greetings = [
            'halo',
            'hello',
            'hi',
            'hai',
            'hey',
            'assalamualaikum',
            'assalamu',
            'selamat pagi',
            'selamat siang',
            'selamat sore',
            'selamat malam',
            'permisi',
            'maaf',
            'misi',
        ];

        $hasGreeting = false;
        foreach ($greetings as $greeting) {
            if (str_contains($message, $greeting)) {
                $hasGreeting = true;
                break;
            }
        }

        if (!$hasGreeting) {
            return false;
        }

        // If it has a greeting + casual question pattern, route to FAQ
        $casualPatterns = [
            'jualan',
            'jual ga',
            'jual gak',
            'jual nggak',
            'jual tidak',
            'ada ga',
            'ada gak',
            'ada nggak',
            'ada tidak',
            'punya ga',
            'punya gak',
            'punya tidak',
            'boleh tanya',
            'boleh nanya',
            'mau tanya',
            'mau nanya',
            'apakah',
            'bisa chat',
            'boleh chat',
            'terima kasih',
            'makasih',
            'thanks',
            'thank you',
        ];

        foreach ($casualPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        // If message is only greeting (short) without product-specific search terms, it's conversational
        if (mb_strlen($message) < 50) {
            return true;
        }

        return false;
    }

    private function containsOrderTrackingHint(string $message): bool
    {
        if (preg_match('/ord-arip-\d{8}-[a-z0-9]{6}/i', $message)) {
            return true;
        }

        $trackingKeywords = [
            'tracking',
            'cek pesanan',
            'status pesanan',
            'lacak pesanan',
            'nomor resi',
            'order saya',
            'status order',
            'pesanan saya dimana',
        ];

        foreach ($trackingKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect website usage help questions — how to use features.
     *
     * This is checked BEFORE store_info and product_recommendation because it
     * contains multi-word phrases that would otherwise be swallowed by broader
     * single-keyword checks (e.g. "alamat" in store_info).
     */
    private function containsWebsiteHelpHint(string $message): bool
    {
        // ── High-priority multi-word phrases (check first) ──
        $preciseKeywords = [
            // Address management (NOT store address)
            'tambah alamat',
            'menambahkan alamat',
            'kelola alamat',
            'alamat pengiriman',
            'alamat default',
            'ganti alamat',
            'isi alamat',
            'set alamat',
            'simpan alamat',
            'hapus alamat',
            'edit alamat',
            'ubah alamat',
            'pilih alamat',
            'alamat baru',
            'alamat saya',
            'default alamat',

            // Delivery / shipping questions about process
            'apakah diantar',
            'diantar ke',
            'dikirim ke',
            'sesuai alamat',
            'kirim ke alamat',
            'antar ke alamat',
            'kirim ke rumah',
            'antar ke rumah',
            'kena ongkir',

            // COD process questions
            'cod',
            'cash on delivery',
            'bayar di tempat',
            'bayar ditempat',
            'diantar',
            'kurir',

            // How-to / tutorial phrases
            'cara beli',
            'cara belanja',
            'cara pesan',
            'cara order',
            'cara checkout',
            'bagaimana cara',
            'gimana cara',
            'caranya gimana',
            'caranya bagaimana',
            'cara pakai',
            'cara menggunakan',
            'cara menambahkan',
            'cara menghapus',
            'cara mengubah',
            'cara edit',

            // Auth & account
            'cara daftar',
            'cara login',
            'cara register',
            'register',
            'lupa password',
            'reset password',
            'ganti password',

            // Payment process
            'cara bayar',
            'upload bukti',
            'bukti bayar',
            'bukti transfer',

            // Warranty process
            'cara klaim',
            'klaim garansi',
            'ajukan garansi',
            'cara garansi',

            // Other feature guides
            'cara review',
            'beri ulasan',
            'cara keranjang',
            'edit profil',
            'ubah nama',
            'ganti email',
            'panduan',
            'tutorial',
            'checkout',
        ];

        foreach ($preciseKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function containsRecommendationHint(string $message): bool
    {
        if ($this->containsBudgetPhrase($message)) {
            return true;
        }

        if ($this->containsExternalSearchHint($message)) {
            return true;
        }

        $recommendationKeywords = [
            'rekomendasi',
            'saran produk',
            'produk',
            'product',
            'produk apa',
            'cari produk',
            'cari product',
            'deskripsi produk',
            'deskripsi',
            'spesifikasi',
            'fitur produk',
            'search engine',
            'cari di internet',
            'cari di google',
            'search produk',
            'search product',
            'budget',
            'murah',
            'stok',
            'uang',
            'lampu',
            'bohlam',
            'led',
            'kabel',
            'saklar',
            'stop kontak',
            'fitting',
            'ruangan',
            'kamar',
            'kamar tidur',
            'dapur',
            'ruang tamu',
            'watt',
            'daya',
            'mcb',
            'downlight',
            'cocok untuk',
            'bagus untuk',
            'nyaman',
            'adem',
            'terang',
            'hemat',
            'irit',
        ];

        foreach ($recommendationKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function containsExternalSearchHint(string $message): bool
    {
        $externalSearchKeywords = [
            'search engine',
            'search google',
            'cari di google',
            'cari di internet',
            'hasil pencarian',
            'googling',
            'googlekan',
            'cari web',
            'cek internet',
        ];

        foreach ($externalSearchKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        if (preg_match('/\b(?:produk|product)\s+[a-z0-9]{1,10}\b/i', $message) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Detect store info questions — address, contact, hours, social media, about.
     *
     * NOTE: This is checked AFTER website_help so that "cara menambahkan alamat"
     * or "alamat default" correctly route to website_help first.
     */
    private function containsStoreInfoHint(string $message): bool
    {
        $storeInfoKeywords = [
            'alamat toko',
            'alamat lokasi',
            'lokasi toko',
            'lokasi',
            'dimana toko',
            'toko dimana',
            'di mana',
            'dimana',
            'maps',
            'gmaps',
            'google maps',
            'whatsapp',
            'nomor hp',
            'nomor telepon',
            'kontak',
            'hubungi',
            'telepon',
            'telpon',
            'jam buka',
            'jam operasional',
            'jam kerja',
            'kapan buka',
            'kapan tutup',
            'hari libur',
            'instagram',
            'facebook',
            'tiktok',
            'medsos',
            'sosial media',
            'tentang toko',
            'toko apa',
            'jual apa',
            'rekening',
            'nomor rekening',
            'bank',
            'siapa kamu',
            'kamu siapa',
            'apa ini',
            // Broader "alamat" match — only triggers if website_help didn't match first
            'alamat',
        ];

        foreach ($storeInfoKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function containsBudgetPhrase(string $message): bool
    {
        return preg_match('/\b\d+(?:[\.,]\d+)?\s*(rb|ribu|ribuan|k|jt|juta)\b/i', $message) === 1
            || preg_match('/\b(?:rp|idr)\s*[0-9][0-9\.,]{2,}\b/i', $message) === 1;
    }

    /**
     * Detect troubleshooting / problem-solving messages.
     * User has a concrete issue and needs a solution, not just information.
     */
    private function containsTroubleshootingHint(string $message): bool
    {
        $troubleshootingKeywords = [
            // Payment issues
            'pembayaran ditolak',
            'bayar ditolak',
            'pembayaran gagal',
            'gagal bayar',
            'tidak bisa bayar',
            'ga bisa bayar',
            'gak bisa bayar',
            'bukti ditolak',
            'proof ditolak',
            'upload gagal',
            'gagal upload',
            'sudah transfer tapi',
            'sudah bayar tapi',
            'udah bayar tapi',
            'udah transfer tapi',

            // Order/delivery issues
            'pesanan hilang',
            'paket hilang',
            'barang hilang',
            'pesanan salah',
            'barang salah',
            'salah kirim',
            'salah alamat',
            'belum dikirim',
            'belum diproses',
            'lama sekali',
            'lama banget',
            'tidak direspon',
            'ga direspon',
            'gak direspon',
            'pesanan dibatalkan',
            'kenapa dibatalkan',
            'kenapa batal',

            // Product issues
            'barang rusak',
            'produk rusak',
            'cacat',
            'pecah',
            'retak',
            'tidak sesuai',
            'ga sesuai',
            'gak sesuai',
            'beda sama',
            'beda dengan',
            'kurang',
            'tidak lengkap',
            'ga lengkap',
            'kurang item',

            // Account issues
            'tidak bisa login',
            'ga bisa login',
            'gak bisa login',
            'akun diblokir',
            'akun disuspend',
            'tidak bisa masuk',
            'email salah',
            'verifikasi email',

            // General problem-solving triggers
            'masalah',
            'kendala',
            'error',
            'bermasalah',
            'trouble',
            'tidak bisa',
            'ga bisa',
            'gak bisa',
            'gabisa',
            'ngga bisa',
            'solusi',
            'gimana dong',
            'tolong bantu',
            'minta tolong',
            'kenapa',
            'mengapa',
        ];

        foreach ($troubleshootingKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect emotional cues — user is venting, sad, frustrated, or needs empathy.
     * These are NOT troubleshooting (no concrete problem to solve),
     * but the user needs emotional validation first.
     */
    private function containsEmotionalCue(string $message): bool
    {
        $emotionalKeywords = [
            // Frustration / anger
            'kesal',
            'kesel',
            'sebel',
            'jengkel',
            'emosi',
            'marah',
            'nyesel',
            'menyesal',
            'kapok',
            'kecewa berat',
            'sangat kecewa',
            'parah banget',
            'parah sih',
            'ampun deh',
            'ga becus',
            'gak becus',

            // Sadness / disappointment
            'kecewa',
            'sedih',
            'down',
            'bete',
            'bt',
            'bosen',
            'nangis',
            'mau nangis',
            'putus asa',
            'hopeless',
            'nyerah',
            'cape',
            'capek',
            'capek banget',
            'lelah',

            // Confusion / feeling lost
            'pusing',
            'mumet',
            'bingung banget',
            'frustasi',
            'stress',
            'ribet',
            'ribet banget',
            'susah banget',
            'rumit',
            'gajelas',
            'ga jelas',
            'gak jelas',

            // Curhat / venting patterns
            'curhat',
            'curcol',
            'pengen cerita',
            'mau cerita',
            'udah capek',
            'udah lelah',
            'udah bosen',
            'percuma',
            'sia-sia',
            'buang waktu',
            'rugi',

            // Seeking validation
            'wajar ga sih',
            'wajar gak sih',
            'normal ga',
            'normal gak',
            'apa cuma aku',
            'apa cuma saya',
        ];

        foreach ($emotionalKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect off-topic messages — politics, weather, entertainment, etc.
     * These should be deflected politely back to store-related topics.
     */
    private function isOffTopicMessage(string $message): bool
    {
        // Only match if message does NOT contain any store-related keywords
        $storeRelated = [
            'produk',
            'beli',
            'toko',
            'pesanan',
            'order',
            'bayar',
            'kirim',
            'lampu',
            'kabel',
            'saklar',
            'listrik',
            'garansi',
            'harga',
            'stok',
            'checkout',
            'keranjang',
            'alamat',
            'profil',
        ];

        foreach ($storeRelated as $keyword) {
            if (str_contains($message, $keyword)) {
                return false;
            }
        }

        $offTopicKeywords = [
            // Politics & news
            'politik',
            'pilpres',
            'pemilu',
            'presiden',
            'capres',
            'partai',
            'korupsi',
            'demonstrasi',
            'demo',

            // Weather
            'cuaca',
            'hujan',
            'panas',
            'mendung',
            'cerah',

            // Entertainment
            'film',
            'drama',
            'drakor',
            'anime',
            'game',
            'musik',
            'konser',
            'artis',
            'selebriti',
            'gosip',

            // Sports
            'bola',
            'sepak bola',
            'liga',
            'timnas',
            'piala',

            // Food (not products)
            'resep',
            'masak',
            'makanan',
            'restoran',
            'cafe',

            // General chit-chat
            'zodiak',
            'ramalan',
            'horoscope',
            'mimpi',
            'jodoh',
            'pacar',
            'gebetan',
            'mantan',

            // Tech unrelated
            'iphone',
            'samsung',
            'laptop',
            'komputer',
            'hp',
            'android',
            'ios',
            'windows',
        ];

        foreach ($offTopicKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect newbie/first-time user messages needing extra patience.
     */
    private function isNewbieMessage(string $message): bool
    {
        $newbieKeywords = [
            'pertama kali',
            'baru pertama',
            'baru pertama kali',
            'gak ngerti teknologi',
            'ga ngerti teknologi',
            'gaptek',
            'awam',
            'pemula',
            'newbie',
            'noob',
            'baru belajar',
            'belum pernah',
            'blm pernah',
            'ga pernah belanja online',
            'gak pernah belanja online',
            'pertama belanja',
            'baru mau coba',
            'gimana ya caranya',
            'cara nya gimana ya',
        ];

        foreach ($newbieKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
