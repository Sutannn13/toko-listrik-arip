<?php

return [
    'pass_threshold_percent' => 85,

    'cases' => [
        [
            'id' => 'website_help_shipping',
            'message' => 'Ongkir berapa untuk checkout?',
            'expected_intent' => 'website_help',
            'must_contain' => ['ongkir'],
        ],
        [
            'id' => 'store_info_address',
            'message' => 'Alamat toko dimana?',
            'expected_intent' => 'store_info',
            'must_contain' => ['alamat'],
        ],
        [
            'id' => 'order_tracking_requires_code',
            'message' => 'Tolong cek pesanan saya',
            'expected_intent' => 'order_tracking',
            'must_contain' => ['ord-arip'],
        ],
        [
            'id' => 'product_recommendation_budget',
            'message' => 'Rekomendasi lampu kamar tidur budget 40rb',
            'expected_intent' => 'product_recommendation',
        ],
        [
            'id' => 'off_topic_politics',
            'message' => 'Siapa presiden sekarang?',
            'expected_intent' => 'off_topic',
            'must_not_contain' => ['pilpres', 'partai'],
        ],
        [
            'id' => 'troubleshooting_payment',
            'message' => 'Pembayaran saya ditolak terus, solusi dong',
            'expected_intent' => 'troubleshooting',
            'must_contain' => ['solusi'],
        ],
        [
            'id' => 'website_help_transaction_history',
            'message' => 'Cara lihat riwayat barang saya di website gimana?',
            'expected_intent' => 'website_help',
            'must_contain' => ['riwayat transaksi'],
            'must_not_contain' => ['whatsapp admin'],
        ],
    ],

    'profiles' => [
        'hard_case' => [
            'pass_threshold_percent' => 75,
            'cases' => [
                [
                    'id' => 'hard_trouble_payment_rejected_shipping_delay',
                    'message' => 'Aku sudah transfer tadi pagi, bukti bayar dibilang tidak valid, sementara status order belum diproses dan paket belum dikirim. Ini gimana solusi paling cepat?',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_checkout_error_login_loop',
                    'message' => 'Dari tadi checkout gagal terus, habis login malah balik ke halaman awal, terus alamat pengiriman tidak bisa dipilih. Tolong bantu step by step.',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_wrong_item_and_warranty',
                    'message' => 'Barang yang datang beda varian, satu lagi retak, dan saya takut lewat masa garansi kalau nunggu lama. Mohon solusi jelas.',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_payment_proof_privacy',
                    'message' => 'Aku ragu upload bukti transfer karena takut data rekening tersebar, tapi pembayaran pending terus. Apa langkah aman supaya cepat diverifikasi?',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_qris_timeout_and_duplicate',
                    'message' => 'QRIS sempat error lalu saya coba lagi, sekarang takut ke-double charge karena statusnya pending semua. Harus cek kemana dulu?',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_order_cancelled_after_payment',
                    'message' => 'Kenapa pesanan saya dibatalkan padahal sudah bayar dan bukti transfer sudah diupload, saya jadi panik karena barangnya urgent.',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_claim_stuck_reviewing',
                    'message' => 'Klaim garansi status reviewing sudah lama sekali, sementara lampu utama di rumah mati total. Ada cara percepat prosesnya?',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_partial_delivery_missing_item',
                    'message' => 'Paket sudah sampai tapi isi kurang satu item, di invoice ada, di dus tidak ada. Proses komplain yang paling benar gimana?',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_address_mismatch_after_payment',
                    'message' => 'Saya sudah bayar tapi alamat di invoice salah kecamatan, takut paket nyasar dan tidak bisa diubah dari akun.',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_refund_confusing',
                    'message' => 'Saya mau refund karena produk tidak sesuai spesifikasi, tapi bingung urutan bukti yang harus dikirim biar tidak bolak-balik.',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_intermittent_upload_fail_mobile',
                    'message' => 'Di HP saya upload bukti bayar selalu gagal 80%, dicoba WiFi dan data tetap gagal. Ada workaround cepat?',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_trouble_no_actionable_answer_feedback',
                    'message' => 'Jawaban AI sebelumnya muter-muter, tidak ada langkah tindakan, padahal kasus saya gabungan pembayaran gagal plus order belum dikirim.',
                    'expected_intent' => 'troubleshooting',
                ],
                [
                    'id' => 'hard_web_help_newbie_checkout_cod_split',
                    'message' => 'Saya baru pertama kali belanja, caranya checkout pakai COD sambil simpan alamat rumah gimana ya?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_web_help_change_default_address',
                    'message' => 'Langkah ubah alamat default untuk pesanan berikutnya di akun itu lewat menu mana?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_web_help_upload_proof_after_transfer',
                    'message' => 'Sudah transfer manual, sekarang cara upload bukti bayar yang benar di website bagaimana?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_web_help_track_history_invoice',
                    'message' => 'Cara lihat riwayat transaksi sekaligus download invoice dari order lama dimana?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_web_help_reset_password_phone_changed',
                    'message' => 'Nomor lama sudah tidak aktif, gimana cara reset password akun tanpa kehilangan riwayat order?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_web_help_warranty_submit_flow',
                    'message' => 'Cara ajukan klaim garansi untuk satu item saja dari order yang isinya banyak itu gimana?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_web_help_edit_profile_checkout',
                    'message' => 'Sebelum checkout saya mau edit nama penerima dan nomor telepon di profil, langkahnya apa saja?',
                    'expected_intent' => 'website_help',
                ],
                [
                    'id' => 'hard_reco_budget_two_rooms_low_heat',
                    'message' => 'Tolong rekomendasi lampu untuk kamar tidur 3x3 dan ruang kerja kecil, budget total sekitar 180rb, maunya tidak panas di mata.',
                    'expected_intent' => 'product_recommendation',
                ],
                [
                    'id' => 'hard_reco_for_rental_durability',
                    'message' => 'Saya cari stop kontak dan saklar yang awet buat kontrakan, harga menengah tapi aman dipakai harian. Ada saran paket?',
                    'expected_intent' => 'product_recommendation',
                ],
                [
                    'id' => 'hard_reco_replace_old_bulb',
                    'message' => 'Bohlam rumah sering putus, minta saran LED hemat listrik yang tetap terang untuk ruang tamu.',
                    'expected_intent' => 'product_recommendation',
                ],
                [
                    'id' => 'hard_reco_budget_with_watt_limit',
                    'message' => 'Rekomendasi downlight untuk plafon rendah, daya listrik rumah cuma 900 watt dan pengen tetap nyaman buat baca.',
                    'expected_intent' => 'product_recommendation',
                ],
                [
                    'id' => 'hard_reco_combo_for_kitchen',
                    'message' => 'Kalau untuk dapur lembap, lebih cocok lampu jenis apa yang tahan lama dan mudah perawatan?',
                    'expected_intent' => 'product_recommendation',
                ],
                [
                    'id' => 'hard_order_tracking_with_code_mixed',
                    'message' => 'Status order ORD-ARIP-20260420-ABC123 saya sekarang dimana? Kemarin sempat pending pembayaran.',
                    'expected_intent' => 'order_tracking',
                    'must_contain' => ['ord-arip'],
                ],
                [
                    'id' => 'hard_order_tracking_no_code_need_help',
                    'message' => 'Saya mau cek pesanan saya yang kemarin malam, status ordernya sudah dipacking atau belum?',
                    'expected_intent' => 'order_tracking',
                ],
                [
                    'id' => 'hard_emotional_support_frustrated',
                    'message' => 'Jujur aku capek banget dan kecewa, rasanya semua jadi ribet dan pengen nyerah dulu.',
                    'expected_intent' => 'emotional_support',
                ],
                [
                    'id' => 'hard_emotional_support_stress',
                    'message' => 'Aku lagi stress dan mumet, takut salah langkah terus. Tolong tenangin dulu ya.',
                    'expected_intent' => 'emotional_support',
                ],
                [
                    'id' => 'hard_store_info_operational_and_maps',
                    'message' => 'Jam buka toko hari minggu berapa, dan lokasi di maps yang paling akurat di mana?',
                    'expected_intent' => 'store_info',
                ],
                [
                    'id' => 'hard_off_topic_sports_prediction',
                    'message' => 'Menurut kamu timnas menang berapa gol malam ini?',
                    'expected_intent' => 'off_topic',
                    'must_not_contain' => ['checkout', 'keranjang'],
                ],
            ],
        ],
    ],
];
