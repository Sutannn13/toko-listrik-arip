# Design System - Toko Listrik Arip

Dokumen ini merangkum desain aktual dari codebase saat ini sebagai referensi human-readable.
Kontrak machine-readable ada di `docs/design-intent.json`.

## 1) Arsitektur Frontend Saat Ini

- Rendering utama: server-rendered Blade (Laravel).
- Interaktivitas: Alpine.js (dropdown, modal, sidebar, toast, chat widget, transisi).
- Styling: Tailwind CSS + `@layer` custom (`base`, `components`, `utilities`) di `resources/css/app.css`.
- Bundling: Vite (`resources/css/app.css` + `resources/js/app.js`).
- React: tidak digunakan (tidak ada file JSX/TSX dan tidak ada import React pada `resources/js`).

Surface UI yang aktif:

- Storefront (customer-facing): `resources/views/layouts/storefront.blade.php` + halaman `resources/views/home/*`
- Admin panel: `resources/views/layouts/admin.blade.php` + halaman `resources/views/admin/*`
- Auth pages: `resources/views/auth/*`

## 2) Vibe dan Karakter Visual

Arah visual aplikasi adalah e-commerce listrik yang modern, clean, dan responsif:

- Storefront: ramah, cepat dipahami, aksen hijau untuk aksi beli/checkout.
- Admin: dashboard operasional, lebih netral dengan aksen biru untuk hierarki data dan kontrol.
- Auth: fokus konversi login/register lewat card terang di atas background gelap ber-image.

## 3) Color System

Sumber token: `tailwind.config.js` + CSS custom vars di `resources/css/app.css`.

### Brand/Primary Families

- Storefront primary (green): `primary.50` sampai `primary.950`
    - Core CTA: `primary.500` (`#03ac0e`)
    - Hover/active utama: `primary.600` / `primary.700`
- Admin brand (blue-indigo): `brand.25` sampai `brand.950`
    - Core admin action: `brand.500` (`#3B82F6`)
    - Hover/active admin: `brand.600` / `brand.700`

### Semantic Colors

- Success: `success.*`
- Warning: `warning.*`
- Error: `error.*`
- Neutral grayscale: `gray.50` sampai `gray.950`

### Custom CSS Variables (Legacy Compat)

Di `:root`:

- `--ui-brand-navy: #0b1f3a`
- `--ui-brand-cyan: #03ac0e`
- `--ui-brand-cyan-soft: #f2fdf4`
- `--ui-surface: #ffffff`
- `--ui-border: #e2e8f0`

## 4) Typography

Sumber font:

- `resources/css/app.css` mengimpor Plus Jakarta Sans + Inter.
- Beberapa layout auth masih eksplisit pakai Inter.

Konfigurasi:

- Tailwind `fontFamily.sans`: Plus Jakarta Sans -> Inter -> sans fallback
- Tailwind `fontFamily.body`: Inter -> sans fallback
- Base HTML: Plus Jakarta Sans, Inter, Segoe UI, sans-serif

Karakter tipografi:

- Body letter spacing: `0.01em`
- Heading letter spacing: `-0.01em`
- Weight yang sering dipakai: 500, 600, 700, 800, 900

## 5) Spacing, Layout Rhythm, dan Density

Tidak ada custom spacing scale di `tailwind.config.js`, sehingga mengandalkan default Tailwind.

Rhythm yang paling sering muncul:

- Gap: `gap-2`, `gap-3`, `gap-4`
- Padding horizontal: `px-3`, `px-4`, `px-5`, `px-6`
- Padding vertical: `py-2`, `py-2.5`, `py-3`, `py-4`
- Block spacing: `mb-4`, `mb-6`, `mb-8`

Layout constants (admin):

- `--ta-sidebar-width: 18.125rem` (290px)
- `--ta-header-height: 4rem` (64px)

## 6) Border Radius, Shadow, dan Depth

### Border Radius Pattern

- Input/button umum: `rounded-lg`
- Panel/alert/dropdown: `rounded-xl`
- Card/container utama: `rounded-2xl`
- Badge/chip/status dot: `rounded-full`

### Shadow Pattern

Token di `tailwind.config.js`:

- `shadow-tailadmin`
- `shadow-tailadmin-md`
- `shadow-tailadmin-lg`
- `shadow-sidebar`

Storefront juga memakai shadow utility untuk card produk dan CTA agar depth tetap ringan.

## 7) Dark Mode Strategy

- Strategi: class-based (`darkMode: 'class'`).
- Implementasi toggle: admin layout (`resources/views/layouts/admin.blade.php`) dengan Alpine + localStorage (`darkMode`).
- Cakupan dark mode: terutama admin panel (`dark:bg-dark-*`, `dark:border-dark-*`, dll).
- Storefront/auth: saat ini light-first, tidak ada toggle dark mode user-facing.

## 8) Komponen UI Utama yang Sering Dipakai

Frekuensi aktual dari Blade templates menunjukkan pola berikut.

### Utility-Based Component Classes (paling sering)

- `ui-card` (sangat dominan untuk container admin/profile)
- `ui-input` (field form lintas profile/admin/filter)
- `ui-btn` + varian (`ui-btn-primary`, `ui-btn-secondary`, `ui-btn-soft`)
- `ta-nav-item` (navigasi sidebar admin)
- `ui-alert`, `ui-badge`, `ta-dropdown`

### Blade Components (reusable)

- `x-input-error` (error state form)
- `x-input-label`, `x-text-input` (legacy auth/profile forms)
- `x-dropdown`, `x-responsive-nav-link`, `x-nav-link`
- `x-primary-button`, `x-secondary-button`, `x-danger-button`
- `x-ui.page-header` (header halaman admin/profile/home notifications)

## 9) Pattern UI yang Harus Konsisten

### Button

- Design-system: `ui-btn` + varian semantic (`primary`, `secondary`, `soft`, `success`, `danger`, `ghost`).
- Legacy auth/profile masih menggunakan komponen `x-*button` dengan gaya uppercase kecil.

### Card

- Struktur utama: `ui-card` + `ui-card-pad`.
- Panel kompak/filter: `ui-panel`.
- Empty state: `ui-empty`.

### Form

- Label: `ui-label`
- Input/select/textarea: `ui-input`, `ui-select`, `ui-textarea`
- Error text: `x-input-error` atau `<p class="text-xs text-red-600">...`

### Status/Feedback

- Badge status: `ui-badge` + semantic variant.
- Flash/alert: `ui-alert` + semantic variant.
- Tabel admin: `ta-table`.

## 10) Responsive Strategy

- Pendekatan mobile-first dengan breakpoint `sm`, `md`, `lg`, `xl`.
- Storefront memiliki mobile bottom nav (`lg:hidden`) dan safe-area iOS (`safe-area-inset-bottom`).
- Touch target coarse pointer dipaksa minimal 44px untuk elemen interaktif.
- Font-size disesuaikan untuk layar sangat kecil (`max-width: 374px`).

## 11) Motion dan Interaction

- Alpine `x-transition` dipakai untuk modal, dropdown, toast, panel chat, dan elemen status.
- Hover product card dibatasi ke pointer device yang mendukung hover (`@media (hover: hover) and (pointer: fine)`).
- Pada touch device, efek hover dinonaktifkan agar interaksi tidak terasa "lompat".

## 12) Guardrails untuk Perubahan UI Berikutnya

1. Gunakan class sistem (`ui-*`, `ta-*`) dulu sebelum menulis style ad-hoc baru.
2. Pertahankan pemisahan karakter visual:
    - Storefront CTA: dominan `primary` (hijau).
    - Admin CTA/navigation: dominan `brand` (biru).
3. Jangan menambahkan komponen React tanpa keputusan arsitektur resmi.
4. Jika mengubah dark mode, tetap class-based dan kompatibel dengan toggle admin existing.
5. Untuk elemen form/aksi baru, ikuti density dan radius yang sudah ada (`rounded-lg`, `py-2.5`, `px-4`).

## 13) Source of Truth

Dokumen ini diturunkan dari file berikut:

- `tailwind.config.js`
- `resources/css/app.css`
- `resources/views/layouts/storefront.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/components/*.blade.php`
- `resources/views/components/ui/page-header.blade.php`
- `resources/views/home/index.blade.php`
- `resources/views/home/cart.blade.php`
- `resources/views/home/tracking.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`

Tanggal verifikasi: 2026-04-20
