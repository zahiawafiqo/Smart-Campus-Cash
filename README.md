# Smart Campus Cash

Sistem Kas & Keuangan Kampus Berbasis Web dengan Arsitektur MVC & Integrasi AI (Tahap Fondasi).

## 📌 Ringkasan Proyek
Smart Campus Cash dirancang untuk mengelola keuangan kampus secara transparan, akurat, dan modern dengan dukungan multi-role (Admin, Mahasiswa, Kasir, dan Bagian Keuangan) serta kesiapan AI Financial Assistant & Insight.

---

## 🏛️ Arsitektur MVC & Struktur Direktori

```text
sistem keuangan/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php               # Landing page controller
│   │   ├── AuthController.php               # Login placeholder controller
│   │   ├── DashboardController.php          # Template dashboard & role switcher
│   │   └── ComponentShowcaseController.php  # UI Reusable components showcase
│   ├── Core/
│   │   ├── App.php                          # Router, dispatcher & URL resolver
│   │   ├── Controller.php                   # Base Controller (render, json, input)
│   │   ├── Database.php                     # PDO MySQL Singleton connection
│   │   ├── Env.php                          # Safe .env parser
│   │   ├── Model.php                        # Base Model PDO
│   │   └── View.php                         # View renderer with layouts & components
│   ├── Helpers/
│   │   └── ComponentHelper.php              # Global helpers (url, asset, render_component)
│   ├── Models/                              # (Siap untuk entitas User, Tagihan, Kas)
│   └── Views/
│       ├── layouts/
│       │   ├── app.php                      # Master Layout (Sidebar, Navbar, Content, Footer)
│       │   ├── guest.php                    # Guest Layout (Landing & Login)
│       │   └── partials/
│       │       ├── navbar.php               # Header navbar dengan role indicator
│       │       ├── sidebar.php              # Role-based navigation sidebar
│       │       └── footer.php               # Footer aplikasi
│       ├── components/                      # 7 Reusable UI Components
│       │   ├── button.php
│       │   ├── form.php
│       │   ├── table.php
│       │   ├── card.php
│       │   ├── modal.php
│       │   ├── alert.php
│       │   └── pagination.php
│       ├── home/
│       │   └── index.php                    # Landing page
│       ├── auth/
│       │   └── login.php                    # Login form placeholder
│       ├── dashboard/
│       │   └── index.php                    # Dashboard template kosong
│       ├── showcase/
│       │   └── index.php                    # Interaktif katalog komponen
│       └── errors/
│           └── 404.php                      # Error 404 page
├── config/
│   ├── app.php                              # Konfigurasi nama aplikasi & debug
│   └── database.php                         # Konfigurasi MySQL
├── public/
│   ├── assets/
│   │   ├── css/app.css                      # Custom styles & responsive utilities
│   │   └── js/app.js                        # Sidebar toggle, modal & toast helpers
│   ├── index.php                            # Front Controller tunggal
│   └── .htaccess                            # Clean URL rewrite
├── routes/
│   └── web.php                              # Definisi rute aplikasi
├── .env                                     # Environment variables (DB credentials)
├── .env.example                             # Template environment
├── .gitignore
├── .htaccess                                # Root rewrite ke public/
├── composer.json                            # PSR-4 autoloading
└── README.md
```

---

## 🚀 Cara Menjalankan Aplikasi

### Opsi 1: Menggunakan Laragon (Rekomendasi)
1. Buka aplikasi **Laragon** dan klik **Start All** (Apache & MySQL).
2. Akses aplikasi melalui browser:
   ```text
   http://localhost/sistem%20keuangan/
   ```
   *Atau jika Virtual Host aktif:*
   ```text
   http://sistem-keuangan.test
   ```

### Opsi 2: Menggunakan PHP Built-in Server
Jalankan perintah berikut di terminal:
```bash
php -S 127.0.0.1:8000 -t public
```
Lalu buka browser di: `http://127.0.0.1:8000`

---

## 🤖 Menjalankan Node.js AI Chatbot (NVIDIA NIM)

Chatbot asisten cerdas pada halaman **Beranda** ditenagai oleh **Node.js** dan model **NVIDIA NIM AI**:

1. **Jalankan via Windows Batch File (Otomatis):**
   Cukup klik dua kali file:
   ```text
   start-chatbot.bat
   ```

2. **Atau jalankan via Terminal:**
   ```bash
   node chatbot/server.js
   ```
   *Atau jika menggunakan Node di Laragon:*
   ```bash
   "C:\laragon\bin\nodejs\node-v22\node.exe" chatbot/server.js
   ```

3. **Status Endpoint:**
   - Server Chatbot: `http://localhost:3001`
   - Health check: `http://localhost:3001/api/status`
   - AI Engine: **NVIDIA NIM** (Model: `meta/llama-3.2-11b-vision-instruct`, Key ID: `2cbc5b5a-b68d-4bd0-be93-91fc2736b703`)
   - Chatbot Widget: Tersedia dengan ikon chat mengambang (FAB) di pojok kanan bawah halaman **Beranda**.

---

## 🧭 Rute & Navigasi

| Rute | Peruntukan |
|---|---|
| `/` | Landing Page utama sistem |
| `/login` | Form login placeholder dengan selector peran |
| `/dashboard` | Template dashboard kosong (Role Admin) |
| `/dashboard?role=admin` | Simulasi dashboard & navigasi Admin |
| `/dashboard?role=mahasiswa` | Simulasi dashboard & navigasi Mahasiswa |
| `/dashboard?role=kasir` | Simulasi dashboard & navigasi Kasir Loket |
| `/dashboard?role=keuangan` | Simulasi dashboard & navigasi Bagian Keuangan |
| `/components` | Showcase 7 Reusable UI Components |

---

## 🎨 Komponen UI Reusable

Semua komponen UI dapat dipanggil di view manapun menggunakan helper `render_component($nama, $props)`:
1. `button` &mdash; Variasi varian, ukuran, status loading/disabled, dan ikon.
2. `form` &mdash; Input text, password, select, textarea, switch/checkbox, dan floating label.
3. `table` &mdash; Tabel responsif, styled headers, badges status, dan aksi.
4. `card` &mdash; Kartu metrik, kartu konten berheader, dan statistik kas.
5. `modal` &mdash; Dialog popup interaktif Bootstrap 5.3.
6. `alert` &mdash; Banner notifikasi dismissible & toast popup.
7. `pagination` &mdash; Navigasi halaman dinamis dengan counter data.
