# IM Cash Tracker — Implementation Plan

## 1. Ringkasan Proyek

Aplikasi web mobile-first untuk mencatat uang cash di kasir minimarket pada jam tertentu. User login, lalu mengisi jumlah lembar per pecahan uang. Data disimpan ke MySQL.

## 2. Tech Stack

| Komponen | Pilihan | Alasan |
|---|---|---|
| Backend | PHP 7.4+ vanilla | Tanpa framework, simpel, tersedia di VPS |
| Database | MySQL / MariaDB | Standar pairing dengan PHP |
| CSS | Tailwind CSS (static build) | Built sekali, hasilnya `assets/css/app.css` (22KB) |
| Font | Inter (self-hosted woff2) | Di-download lokal, tidak perlu CDN |
| Icons | Material Symbols Outlined (self-hosted woff2) | Di-download lokal, tidak perlu CDN |
| JavaScript | Vanilla JS | Auto-calculate total, toggle password, dsb |
| Build tools | npm + Tailwind CLI (satu kali saja) | Untuk build CSS, tidak diperlukan di production |

Semua asset (CSS, font, icons) di-host lokal di `assets/`. Tidak ada CDN external reference. CSP hanya `'self'`.

## 3. Struktur File

```
/ospos root/
├── .htaccess                        ← DIMODIFIKASI (tambah RewriteRule utk /cekkasir/)
├── public/
│   ├── .htaccess                    ← OSPOS (tidak diubah)
│   ├── index.php                    ← OSPOS front controller (tidak diubah)
│   └── cekkasir/                    ← BARU - semua file cekkasir disini
│       ├── .htaccess                ← Override CSP (hanya 'self'), rewrite rules
│       ├── db.php                   ← Koneksi DB (ospos), session, helper
│       ├── index.php                ← Halaman login
│       ├── dashboard.php            ← Halaman input kas
│       ├── logout.php               ← Hapus session + cookie
│       ├── assets/
│       │   ├── css/
│       │   │   └── app.css          ← Tailwind CSS compiled (22KB minified)
│       │   └── fonts/
│       │       ├── inter.css        ← @font-face untuk Inter (4 weights)
│       │       ├── Inter-*.woff2    ← Inter font files (Latin + Latin-ext)
│       │       ├── material-symbols.css ← @font-face untuk Material Symbols
│       │       └── MaterialSymbolsOutlined.woff2 ← Icon font (1092KB)
│       └── build/                   ← Build tools (tidak di-upload ke production)
│           ├── tailwind.config.js
│           ├── input.css
│           ├── package.json
│           └── node_modules/
├── im_cash_tracker.sql              ← SQL dump (di luar public/)
└── ... (file OSPOS lainnya)
```

### Konfigurasi `.htaccess` (root)

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  # Serve /cekkasir requests internally from /public/cekkasir
  RewriteRule ^cekkasir(/.*)?$ /public/cekkasir$1 [L]

  # Redirect everything else to /public/
  RewriteCond %{REQUEST_URI} !^public$
  RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge [NC]
  RewriteRule "^(.*)$" "/public/" [R=301,L]
</IfModule>
```

### Konfigurasi `.htaccess` (`public/cekkasir/`)

Override CSP dari OSPOS — karena semua asset sekarang lokal, hanya `'self'` yang diperlukan:

```apache
<IfModule mod_headers.c>
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'self'"
</IfModule>
```

Catatan: `Header always set` akan mengganti CSP header yang mungkin sudah diset oleh OSPOS di direktori parent.

### Build CSS (satu kali saja)

Jika ada perubahan Tailwind config atau class di PHP, jalankan ulang:

```bash
cd public/cekkasir/build
npx tailwindcss -i input.css -o ../assets/css/app.css --minify
```

Folder `build/` (berisi `node_modules/`, `package.json`, dll.) **tidak perlu di-upload ke production**. Hanya `assets/css/app.css` yang diperlukan.

### URL Akses

| URL | Yang di-serve |
|---|---|
| `example.com/cekkasir/` | `public/cekkasir/index.php` (login) |
| `example.com/cekkasir/dashboard.php` | `public/cekkasir/dashboard.php` |
| `example.com/cekkasir/logout.php` | `public/cekkasir/logout.php` |
| `example.com/` | redirect → `example.com/public/` (OSPOS) |

## 4. Database Schema

Aplikasi ini menggunakan tabel yang sudah ada di database OSPOS (`ospos`) untuk autentikasi, ditambah 1 tabel baru untuk pencatatan kas serta 2 kolom tambahan di `ospos_employees` untuk fitur remember me.

### Tabel yang sudah ada (dari OSPOS)

**`ospos_people`** — Data profil user:
- `person_id` (PK), `first_name`, `last_name`, `phone_number`, `email`, dll.

**`ospos_employees`** — Data login user:
- `person_id` (PK, FK ke `ospos_people`), `username`, `password`, `deleted`, `hash_version`
- `hash_version`: 1 = MD5, 2 = bcrypt (`password_hash()`)

Username dan password diambil dari OSPOS. Tidak perlu membuat user baru secara terpisah.

### Kolom tambahan di `ospos_employees` (untuk remember me)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `remember_token` | VARCHAR(128) DEFAULT NULL | Hash SHA-256 dari remember me cookie token |
| `remember_expires` | DATETIME DEFAULT NULL | Waktu kadaluarsa remember me token |

### Tabel baru `ospos_cash_records`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT AUTO_INCREMENT | Primary key |
| `person_id` | INT(10) | FK ke `ospos_employees.person_id` |
| `cashier` | VARCHAR(20) | Nama kasir, contoh: "Kasir 1", "Kasir 2" |
| `record_date` | DATE | Tanggal pencatatan |
| `record_time` | TIME | Jam pencatatan |
| `rp100k` | INT DEFAULT 0 | Jumlah lembar Rp 100.000 |
| `rp50k` | INT DEFAULT 0 | Jumlah lembar Rp 50.000 |
| `rp20k` | INT DEFAULT 0 | Jumlah lembar Rp 20.000 |
| `rp10k` | INT DEFAULT 0 | Jumlah lembar Rp 10.000 |
| `rp5k` | INT DEFAULT 0 | Jumlah lembar Rp 5.000 |
| `rp2k` | INT DEFAULT 0 | Jumlah lembar Rp 2.000 |
| `rp1k` | INT DEFAULT 0 | Jumlah lembar Rp 1.000 |
| `coin_total` | INT DEFAULT 0 | Total uang koin (dalam Rupiah) |
| `total_kutipan` | INT DEFAULT 0 | Total kutipan (100k x lembar + 50k x lembar) |
| `total_di_kasir` | INT DEFAULT 0 | Total uang di kasir (semua pecahan kecil + koin) |
| `created_at` | TIMESTAMP | Waktu record dibuat |

### Login flow yang digunakan

1. User memasukkan username dan password di form login
2. Query ke `ospos_employees` JOIN `ospos_people` WHERE `username = ?` AND `deleted = 0`
3. Verifikasi password:
   - `hash_version = 2`: menggunakan `password_verify()` (bcrypt)
   - `hash_version = 1`: menggunakan `md5()` (legacy OSPOS)
4. Jika valid, set session `user_id` = `person_id`, `user_name` = `first_name + last_name`
5. Catatan: `$DB_NAME` di `db.php` di-set ke `ospos` (database yang sama dengan OSPOS)

## 5. Detail Setiap File

### 5.1 `db.php`

Fungsi-fungsi yang disediakan:
- **Koneksi DB**: PDO connection ke database OSPOS (`ospos`) dengan error mode exception
- **Session start**: `session_start()` di awal
- **`requireLogin()`**: Cek apakah `$_SESSION['user_id']` ada. Jika tidak, redirect ke `index.php`
- **`verifyOposPassword($password, $hash, $hashVersion)`**: Verifikasi password OSPOS. `hash_version 2` = bcrypt (`password_verify()`), `hash_version 1` = MD5
- **`loginUser($personId, $name, $remember)`**: Set session `user_id` dan `user_name`. Jika `$remember` true, set cookie `remember_token` (random 64 char) selama 7 hari, simpan hash token ke `ospos_employees`
- **`checkRememberMe()`**: Jika tidak ada session tapi ada cookie `remember_token`, verifikasi token terhadap `ospos_employees`, jika valid auto-login (set session)
- **`logoutUser()`**: Hapus cookie `remember_token`, hapus token dari `ospos_employees`, destroy session, redirect ke `index.php`
- **`formatRupiah($amount)`**: Return string "Rp 50.000" dari integer

### 5.2 `index.php` (Halaman Login)

**GET request** — Tampilkan form login:
- Judul: "IM Cek Kasir" dengan ikon storefront
- Form dengan field:
  - Staff ID / Username (input text)
  - Password (input password + toggle visibility)
  - Checkbox "Keep me logged in"
  - Tombol "Login"
- Jika sudah login, redirect ke `dashboard.php`
- Jika ada cookie remember me, auto-login via `checkRememberMe()`

**POST request** — Proses login:
- Ambil `username` dan `password` dari POST
- Query ke `ospos_employees` JOIN `ospos_people` where `username = ?` AND `deleted = 0`
- Verifikasi password dengan `verifyOposPassword()` (mendukung bcrypt dan MD5 legacy)
- Jika sukses: panggil `loginUser($user['person_id'], $name, $remember)`
- Redirect ke `dashboard.php`
- Jika gagal: tampilkan pesan error "Username atau password salah"

**Desain visual** mengikuti `stitch_cashier_cash_tracker_login/code.html`

### 5.3 `dashboard.php` (Halaman Input Kas)

**GET request** — Tampilkan form input kas:
- Cek auth via `requireLogin()`
- Header: ikon storefront + "IM Cek Kasir" | Nama user + avatar + tombol Logout
- Info card: Tanggal & Jam (otomatis, read-only) | Dropdown Kasir (Kasir 1, Kasir 2 — statis)
- **Bagian 1: Kutipan Tunai** (pecahan besar):
  - Input jumlah lembar Rp 100.000
  - Input jumlah lembar Rp 50.000
  - Total Kutipan (auto-calculate via JS)
  - Card biru (#1E40AF)
- **Bagian 2: Uang Di Kasir** (pecahan kecil):
  - Input jumlah lembar: Rp 20.000, 10.000, 5.000, 2.000, 1.000
  - Input Total Koin (dalam Rupiah)
  - Total Di Kasir (auto-calculate via JS)
  - Card abu-abu (#555f70)
- Tombol "Submit Pencatatan Kas" (full-width, sticky di bawah)
- Tidak ada bottom nav
- Jika submit sukses, tampilkan toast sukses

**POST request** — Simpan data:
- Ambil semua field dari POST
- Validasi: semua input harus numeric >= 0
- Hitung total_kutipan dan total_di_kasir di server-side
- INSERT ke tabel `cash_records`
- Set flash message sukses
- Redirect ke `dashboard.php` dengan pesan sukses

**Desain visual** mengikuti `stitch_cashier_cash_tracker/code.html`

### 5.4 `logout.php`

- Hapus cookie `remember_token`
- Hapus `remember_token` dan `remember_expires` di DB (SET NULL)
- `session_destroy()`
- Redirect ke `index.php`

## 6. Fitur Remember Me — Detail Implementasi

### Skema Cookie
- Cookie name: `remember_token`
- Value: random 64-character string (`bin2hex(random_bytes(32))`)
- Expiry: 7 hari
- Path: `/`
- HttpOnly: true
- SameSite: Lax

### Skema Database
Kolom tambahan di `ospos_employees`:
- `remember_token VARCHAR(128) DEFAULT NULL` — menyimpan SHA-256 hash dari token cookie
- `remember_expires DATETIME DEFAULT NULL` — waktu kadaluarsa token

### Alur
1. **Login dengan "Keep me logged in" dicentang**:
   - Generate random token
   - Simpan hash ke `ospos_employees`: `remember_token = hash('sha256', $token)`
   - Set expiry: `remember_expires = NOW() + 7 days`
   - Set cookie `remember_token = $token` (7 hari)

2. **Auto-login via cookie**:
   - `checkRememberMe()` dijalankan di `db.php`
   - Jika tidak ada session tapi ada cookie, ambil token
   - Cari di `ospos_employees` JOIN `ospos_people`: `WHERE remember_token = SHA256(token) AND remember_expires > NOW() AND deleted = 0`
   - Jika ditemukan, set session (auto-login)
   - Jika tidak, hapus cookie

3. **Logout**:
   - Hapus `remember_token` dan `remember_expires` di `ospos_employees` (SET NULL)
   - Hapus cookie
   - Destroy session

## 7. JavaScript Client-Side

Semua JS ditulis inline dalam `<script>` tag di masing-masing file PHP.

### `index.php` — JS
- Toggle password visibility (ikon eye/eye_off)

### `dashboard.php` — JS
- Auto-calculate `total_kutipan`: `rp100k x 100000 + rp50k x 50000`
- Auto-calculate `total_di_kasir`: `rp20k x 20000 + rp10k x 10000 + rp5k x 5000 + rp2k x 2000 + rp1k x 1000 + coin_total`
- Format angka ke Rupiah (titik sebagai pemisah ribuan)
- Update tampilan total secara real-time saat user mengetik

## 8. Keamanan

| Aspek | Implementasi |
|---|---|
| Password hashing | Mendukung 2 metode OSPOS: `hash_version=2` (bcrypt via `password_verify()`), `hash_version=1` (MD5 legacy) |
| SQL injection | PDO dengan prepared statements |
| XSS | `htmlspecialchars()` pada semua output dari user/DB |
| CSRF | Tidak diimplementasi di versi awal |
| Session hijacking | Cookie `HttpOnly` + `SameSite=Lax` |
| Remember me token | Disimpan sebagai SHA-256 hash di DB |
| Soft-delete check | Login hanya untuk user dengan `deleted = 0` di `ospos_employees` |

## 9. Urutan Implementasi

1. Import `im_cash_tracker.sql` ke database OSPOS (database `ospos`)
2. Pastikan ada setidaknya 1 user aktif di `ospos_employees` + `ospos_people` (gunakan admin bawaan OSPOS)
3. Sesuaikan `$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS` di `db.php` (default: database `ospos`)
4. Upload semua file PHP ke VPS / akses via lokal
5. Buka `index.php` di browser, login dengan username/password OSPOS
6. Test: login, input data kas, remember me, logout