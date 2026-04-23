---
name: "Analisis Website Bug"
description: "Gunakan ketika ingin audit website untuk bug, keamanan dasar, performa, dan aksesibilitas/UX dengan output bahasa Indonesia."
argument-hint: "Masukkan URL/halaman/fitur yang ingin dianalisis, plus konteks (opsional)"
agent: "agent"
---
Analisis website sesuai konteks yang diberikan pengguna (halaman/fitur/flow).
Gunakan bahasa Indonesia untuk seluruh output.

Tujuan:
- Temukan bug fungsional, potensi error runtime, edge case, dan perilaku yang tidak konsisten.
- Sertakan temuan lain yang relevan: risiko keamanan dasar, masalah aksesibilitas, masalah performa frontend, dan UX yang membingungkan.
- Prioritaskan berdasarkan dampak pengguna dan risiko bisnis.

Batasan:
- Jangan menebak tanpa bukti. Jika data kurang, tulis asumsi dengan jelas.
- Jika tidak ada bug yang terbukti, nyatakan secara eksplisit dan sebutkan risiko residual/testing gap.

Format output wajib:
1. Ringkasan cepat (1-3 kalimat).
2. Daftar temuan berurutan dari severity tertinggi ke terendah.
   Untuk setiap temuan, tulis:
   - Judul temuan
   - Severity: Critical/High/Medium/Low
   - Lokasi: file/route/fitur (jika tersedia)
   - Langkah reproduksi singkat
   - Dampak
   - Rekomendasi perbaikan
3. Pertanyaan klarifikasi (jika ada).
4. Saran next steps terukur (mis. test tambahan, monitoring, atau hardening).

Jika ditemukan temuan severity Critical atau High, sertakan patch kode minimal untuk memperbaiki 1-2 temuan paling kritis.
