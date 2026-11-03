# QueueBites – Your Table, Your Time

Sistem Manajemen Antrean Restoran berbasis **PHP Native MVC** dan **MySQL**.

## Struktur Bagian Database & Model

```
app/
└── models/
    ├── User.php            # Model Autentikasi & Data Pengguna (Admin, Staff, Customer)
    ├── Restaurant.php      # Model Profil & Status Restoran
    ├── Queue.php           # Model Antrean, Penomoran Otomatis & Posisi
    └── QueueHistory.php    # Model Audit Perubahan Status Antrean

config/
└── database.php            # Koneksi Database PDO (Singleton Pattern)

database/
└── queuebites.sql          # Skrip DDL Database, Relasi, Indeks & Data Pengujian

test_run.php                # Skrip Pengujian Fungsionalitas Database & Model
```

## Panduan Memulai

### 1. Impor Database
1. Nyalakan **Apache** dan **MySQL** di **XAMPP Control Panel**.
2. Akses phpMyAdmin di `http://localhost/phpmyadmin`.
3. Buat database baru bernama `queuebites` (atau langsung gunakan menu **Import**).
4. Pilih file `database/queuebites.sql` dan klik **Import**.

### 2. Menguji Model
Jalankan perintah berikut di terminal:
```bash
php test_run.php
```
Atau melalui browser dengan menjalankan:
```bash
php -S localhost:8000
```
Lalu akses `http://localhost:8000/test_run.php`.

## Akun Pengujian (Seed Data)
- **Admin**: `admin@queuebites.com` | Password: `admin123`
- **Staff**: `staff.sambal@queuebites.com` | Password: `staff123`
- **Customer**: `budi@gmail.com` | Password: `user123`

