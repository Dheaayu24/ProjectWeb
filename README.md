# 🍩 Mochi Mood Bakery – Smart Queue Management System

Aplikasi Web Sistem Manajemen Antrean Restoran & Bakery berbasis **PHP Native** dan **MySQL / SQLite Auto-Fallback**, diimplementasikan dengan presisi tinggi mengikuti desain resmi **Mochi Mood Bakery UI Collection**.

---

## 🎨 Tampilan & Fitur (8 Layar Sesuai Mockup UI)

| No | Halaman | Berkas | Deskripsi & Fitur Utama |
|---|---|---|---|
| 1 | **Landing Page** | [`index.php`](file:///d:/pemweb/ProjectWeb/index.php) | Tampilan Desktop resmi Mochi Mood, Navbar, Hero Section *"A Little Mochi, A Lot of Happiness ♡"*, 3-Step How It Works, Our Favorite Menu (Matcha, Choco, Berry, Lotus), dan Why Mochi Mood container. |
| 2 | **Mobile Login** | [`login.php`](file:///d:/pemweb/ProjectWeb/login.php) | Form *"Welcome back!"*, input Email & Password dengan tombol intip mata, login cepat 1-klik untuk Customer, Staff, & Admin. |
| 3 | **Mobile Register** | [`register.php`](file:///d:/pemweb/ProjectWeb/register.php) | Form *"Create Your Account"*, input Nama Lengkap, Email, Telepon, Password, & Konfirmasi Password. |
| 4 | **Join Waiting List** | [`queue.php`](file:///d:/pemweb/ProjectWeb/queue.php) | Pemesanan antrean mobile dengan toggle `[ Dine-In ]` / `[ Takeaway ]`, Nama, Telepon, dan Jumlah Tamu. |
| 5 | **Your Queue Number** | [`ticket.php`](file:///d:/pemweb/ProjectWeb/ticket.php) | Tiket konfirmasi antrean menampilkan kode antrean **A023**, badge *Dine-In • 2 People*, 3 kartu statistik (Posisi #5, Estimasi ≈ 10 min, Status Waiting), dan tombol ke Live Tracker. |
| 6 | **Track My Queue** | [`track.php`](file:///d:/pemweb/ProjectWeb/track.php) | Radar live antrean bertema Dark Forest Green, animasi gauge lingkaran **A023 (#3 in Queue)**, banner *🔔 Almost Your Turn!*, dan bel audio kristal ceria Web Audio API saat antrean dipanggil. |
| 7 | **Staff Dashboard** | [`staff.php`](file:///d:/pemweb/ProjectWeb/staff.php) | Dashboard operasional staf: 3 KPI ringkasan (Total 12, In Service 3, Waiting 9), tabel antrean lengkap dengan tombol *Call*, *Start Serving*, dan *Complete*. |
| 8 | **Admin Dashboard** | [`admin.php`](file:///d:/pemweb/ProjectWeb/admin.php) | Dashboard manajerial admin: Sidebar hijau gelap, 4 KPI (Total 125, Dine-In 57, Takeaway 67, Restoran 1), kelola restoran, riwayat waiting list, dan broadcast pengumuman. |
| ✨ | **Master UI Showcase** | [`showcase.php`](file:///d:/pemweb/ProjectWeb/showcase.php) | **Tampilan Panorama Koleksi UI Lengkap** menyandingkan seluruh 8 layar sekaligus dalam frame mock device interaktif persis seperti gambar yang Anda kirimkan! |

---

## 🚀 Panduan Menjalankan Aplikasi

### Opsi 1: Menjalankan Cepat (PHP Built-in Server)
Cukup buka terminal di folder proyek dan jalankan:
```bash
php -S localhost:8000
```
Lalu buka browser Anda:
- **Koleksi Lengkap (Mirip Screenshot)**: [http://localhost:8000/showcase.php](http://localhost:8000/showcase.php)
- **Landing Page**: [http://localhost:8000/index.php](http://localhost:8000/index.php)
- **Staff Dashboard**: [http://localhost:8000/staff.php](http://localhost:8000/staff.php)
- **Admin Dashboard**: [http://localhost:8000/admin.php](http://localhost:8000/admin.php)
- **Live Queue Radar (A023)**: [http://localhost:8000/track.php?number=A023](http://localhost:8000/track.php?number=A023)

> **Catatan Resiliensi**: Jika MySQL di XAMPP belum dinyalakan, sistem otomatis menggunakan SQLite lokal (`database/queuebites.sqlite`) sehingga aplikasi **langsung bekerja 100% tanpa error koneksi database**.

---

### Opsi 2: Menggunakan XAMPP (Apache & MySQL)
1. Buka **XAMPP Control Panel** dan klik **Start** pada Apache dan MySQL.
2. Buka phpMyAdmin di `http://localhost/phpmyadmin`.
3. Buat database baru bernama `queuebites` (atau langsung ke tab **Import**).
4. Impor file `database/queuebites.sql`.
5. Salin / letakkan folder proyek ini di `C:\xampp\htdocs\ProjectWeb`.
6. Akses di browser: `http://localhost/ProjectWeb/showcase.php`.

---

## 🔑 Akun Pengujian (Seed Data)
- **Customer**: `budi@gmail.com` | Password: `user123`
- **Staff**: `staff@mochimood.com` | Password: `staff123`
- **Admin**: `admin@mochimood.com` | Password: `admin123`

---

## 📁 Struktur Berkas Proyek

```
ProjectWeb/
├── assets/
│   ├── css/
│   │   └── mochi.css             # Desain tema Mochi Mood, palet warna, tipografi, phone shell
│   └── js/
│       └── mochi.js              # Synthesizer audio chime Web Audio API, live polling status
├── app/
│   ├── helpers/
│   │   └── Auth.php              # Pengelola sesi autentikasi pengguna
│   └── models/
│       ├── Announcement.php      # Model pengumuman dan broadcast banner
│       ├── Queue.php             # Model antrean, penomoran A023, estimasi waktu & posisi
│       ├── QueueHistory.php      # Model audit jejak status antrean
│       ├── Restaurant.php        # Model informasi dan operasional bakery
│       └── User.php              # Model data pengguna & verifikasi password
├── config/
│   └── database.php              # Koneksi PDO pintar (MySQL + SQLite Fallback)
├── database/
│   ├── queuebites.sql            # Skrip DDL MySQL & seed data Mochi Mood
│   └── queuebites.sqlite         # Database SQLite lokal self-contained
├── includes/
│   ├── demo_bar.php              # Navigator switcher cepat di bagian atas halaman
│   ├── header.php                # Template header HTML bersama
│   └── footer.php                # Template footer HTML bersama
├── api/
│   ├── queue_action.php          # Endpoint API untuk Call, Serve, Complete antrean
│   └── queue_status.php          # Endpoint API polling status posisi real-time
├── index.php                     # [Screen 1] Landing Page (Desktop)
├── login.php                     # [Screen 2] Welcome Back! (Mobile Login)
├── register.php                  # [Screen 3] Create Your Account (Mobile Register)
├── queue.php                     # [Screen 4] Join Waiting List (Mobile Booking)
├── ticket.php                    # [Screen 5] Your Queue Number (Ticket Confirmation A023)
├── track.php                     # [Screen 6] Track My Queue (Dark Live Radar Tracker)
├── staff.php                     # [Screen 7] Staff Dashboard
├── admin.php                     # [Screen 8] Admin Dashboard
├── showcase.php                  # [Master View] UI Collection Panorama (Semua 8 Layar)
├── test_run.php                  # Skrip unit test pengujian Model & Database
└── README.md                     # Dokumentasi proyek
```
