<p align="center">
  <img src="https://fonts.gstatic.com/s/i/short-term/v1/materialsymbolsoutlined/account_balance_wallet/v5/24px.svg" alt="logo" width="80" height="80">
</p>

<h1 align="center">IM Cek Kasir</h1>

<p align="center">
  <strong>Aplikasi Pencatatan Kas Kasir Minimarket</strong><br>
  Mobile-first cash tracking web app integrated with OSPOS
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-v3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## ✨ Fitur

- 🔐 **Login terintegrasi OSPOS** — Menggunakan tabel `ospos_employees` & `ospos_people`, mendukung bcrypt dan MD5 legacy
- 🍪 **Remember Me** — Cookie 7 hari dengan token SHA-256
- 📱 **Mobile-first** — Desain responsif untuk penggunaan di HP kasir
- 💰 **Pencatatan pecahan lengkap** — Rp 100.000, 50.000, 20.000, 10.000, 5.000, 2.000, 1.000 + koin
- ⚡ **Auto-calculate** — Total kutipan & total di kasir dihitung real-time
- 🎨 **Self-hosted assets** — Tailwind CSS, Inter font, Material Symbols semua lokal, tanpa CDN
- 🔒 **CSP-compliant** — Tidak ada external resource, semua dari `'self'`

## 📸 Tampilan

### Halaman Login
Halaman autentikasi dengan fitur Remember Me dan toggle password visibility.

### Halaman Dashboard
Form pencatatan kas dengan:
- Info tanggal/jam otomatis (WIB) dan pilihan kasir
- Bagian 1: Kutipan Tunai (pecahan besar 100rb & 50rb)
- Bagian 2: Uang di Kasir (pecahan kecil & koin)
- Auto-calculate total secara real-time
- Toast notifikasi sukses yang hilang otomatis

## 🗂 Struktur File

```
public/cekkasir/
├── .htaccess              ← CSP & rewrite rules
├── db.php                 ← Koneksi DB, session, helper functions
├── index.php              ← Halaman login
├── dashboard.php          ← Halaman input kas
├── logout.php             ← Hapus session & redirect
└── assets/
    ├── css/
    │   └── app.css        ← Tailwind CSS (compiled & minified)
    └── fonts/
        ├── inter.css      ← Inter @font-face definitions
        ├── Inter-*.woff2  ← Inter font files (4 weights)
        ├── material-symbols.css
        └── MaterialSymbolsOutlined.woff2
```

## 🚀 Instalasi

### 1. Persyaratan

- PHP 7.4+ dengan PDO MySQL
- MySQL/MariaDB (database OSPOS sudah ada)
- Apache dengan `mod_rewrite` dan `mod_headers`

### 2. Database

Jalankan SQL berikut di database OSPOS Anda:

```sql
USE ospos;

CREATE TABLE IF NOT EXISTS `ospos_cash_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `person_id` INT(10) NOT NULL,
    `cashier` VARCHAR(20) NOT NULL,
    `record_date` DATE NOT NULL,
    `record_time` TIME NOT NULL,
    `rp100k` INT DEFAULT 0,
    `rp50k` INT DEFAULT 0,
    `rp20k` INT DEFAULT 0,
    `rp10k` INT DEFAULT 0,
    `rp5k` INT DEFAULT 0,
    `rp2k` INT DEFAULT 0,
    `rp1k` INT DEFAULT 0,
    `coin_total` INT DEFAULT 0,
    `total_kutipan` INT DEFAULT 0,
    `total_di_kasir` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`person_id`) REFERENCES `ospos_employees`(`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ospos_employees`
  ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `remember_expires` DATETIME DEFAULT NULL;
```

File SQL juga tersedia di [`im_cash_tracker.sql`](im_cash_tracker.sql).

### 3. Deploy

Salin folder `public/cekkasir/` ke dalam folder `public/` OSPOS di server Anda:

```bash
cd /path/to/ospos/public/
# Salin seluruh folder cekkasir ke sini
```

### 4. Konfigurasi `.htaccess`

Tambahkan baris berikut di **root** `.htaccess` OSPOS (sebelum rule redirect yang sudah ada):

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  # Serve /cekkasir requests internally from /public/cekkasir
  RewriteRule ^cekkasir(/.*)?$ /public/cekkasir$1 [L]

  # ... rule redirect OSPOS yang sudah ada ...
</IfModule>
```

### 5. Konfigurasi Database

Edit `public/cekkasir/db.php` sesuai koneksi database Anda:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'ospos';
$DB_USER = 'your_db_user';
$DB_PASS = 'your_db_password';
```

### 6. Akses

Buka di browser: `http://your-server.com/cekkasir/`

Login menggunakan akun OSPOS yang sudah ada.

## 🔧 Rebuild CSS (Opsional)

Jika Anda mengubah class Tailwind di file PHP, rebuild CSS:

```bash
cd public/cekkasir/build/
npm install
npx tailwindcss -i input.css -o ../assets/css/app.css --minify
```

Folder `build/` tidak perlu di-upload ke production.

## 🔒 Keamanan

| Aspek | Implementasi |
|---|---|
| Password | Mendukung OSPOS `hash_version`: bcrypt (v2) dan MD5 (v1) |
| SQL Injection | PDO prepared statements |
| XSS | `htmlspecialchars()` pada semua output |
| Session | HttpOnly + SameSite=Lax |
| Remember Token | SHA-256 hash di database, bukan plain text |
| CSP | `default-src 'self'` — tidak ada external resource |

## 📋 Tech Stack

| Komponen | Pilihan |
|---|---|
| Backend | PHP 7.4+ vanilla |
| Database | MySQL / MariaDB |
| CSS Framework | Tailwind CSS v3 (self-hosted) |
| Font | Inter (self-hosted woff2) |
| Icons | Material Symbols Outlined (self-hosted woff2) |
| JavaScript | Vanilla JS |

## 📄 Lisensi

MIT License — bebas digunakan dan dimodifikasi.