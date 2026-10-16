# SPMB - Sistem Penerimaan Murid Baru

Aplikasi pendaftaran siswa baru berbasis web. Frontend dibangun dengan React, Vite, dan Tailwind CSS. Backend dibangun dengan Laravel dan terhubung ke database PostgreSQL serta penyimpanan berkas yang dikelola melalui Supabase.

Dokumen ini memuat laporan progres pengembangan, rincian modul yang telah diselesaikan pada sisi backend, konfigurasi lingkungan, serta panduan pengujian dan rencana pengembangan selanjutnya.

---

## Daftar Isi

1. [Ringkasan Progres](#ringkasan-progres)
2. [Teknologi yang Digunakan](#teknologi-yang-digunakan)
3. [Peran Pengguna](#peran-pengguna)
4. [Fitur yang Telah Diselesaikan](#fitur-yang-telah-diselesaikan)
5. [Struktur Database](#struktur-database)
6. [Keamanan dan Otorisasi](#keamanan-dan-otorisasi)
7. [Perbaikan Teknis](#perbaikan-teknis)
8. [Konfigurasi Lingkungan](#konfigurasi-lingkungan)
9. [Instalasi dan Menjalankan Backend](#instalasi-dan-menjalankan-backend)
10. [Panduan Pengujian](#panduan-pengujian)
11. [Rencana Pengembangan Selanjutnya](#rencana-pengembangan-selanjutnya)

---

## Ringkasan Progres

| Modul                               | Status   | Keterangan                                                                 |
| ----------------------------------- | -------- | -------------------------------------------------------------------------- |
| Inisialisasi dan Migrasi Database   | Selesai  | 6 tabel utama beserta relasinya berhasil dimigrasi ke Supabase PostgreSQL  |
| Autentikasi dan Keamanan            | Selesai  | Register, OTP, login multi-kriteria, rate limiter, Sanctum, lupa password  |
| Manajemen Jurusan                   | Selesai  | CRUD untuk admin dan daftar jurusan publik                                 |
| Manajemen Biodata                   | Selesai  | Pembaruan data diri dengan sinkronisasi ke tabel pendaftaran               |
| Manajemen Berkas                    | Selesai  | Unggah dokumen persyaratan ke Supabase Storage                             |
| Pendaftaran dan Seleksi             | Selesai  | Status siswa, pilihan jurusan, rekapitulasi, dan status kelulusan          |
| Middleware Otorisasi                | Selesai  | Proteksi `auth:sanctum` dan pembatasan akses berbasis role                 |

---

## Teknologi yang Digunakan

- **Frontend**: React, Vite, Tailwind CSS
- **Backend**: Laravel (PHP)
- **Database**: PostgreSQL (Supabase)
- **Penyimpanan Berkas**: Supabase Storage
- **Autentikasi API**: Laravel Sanctum
- **Layanan Email**: Brevo SMTP
- **Pengujian API**: Postman

---

## Peran Pengguna

| Role      | Cakupan Akses                                                                                   |
| --------- | ----------------------------------------------------------------------------------------------- |
| `siswa`   | Mengisi biodata, mengunggah berkas, memilih jurusan, dan melihat status pendaftaran             |
| `panitia` | Memeriksa data pendaftar, melihat rekapitulasi, dan memperbarui status kelulusan                |
| `admin`   | Mengelola data jurusan dan seluruh fungsi administratif                                         |

---

## Fitur yang Telah Diselesaikan

### 1. Inisialisasi dan Migrasi Database Supabase

- Konfigurasi koneksi PostgreSQL Supabase pada backend Laravel.
- Pembuatan migrasi dan struktur 6 tabel utama beserta relasinya.
- Seluruh proses migrasi berjalan tanpa error.

### 2. Sistem Autentikasi dan Keamanan (`AuthController`)

**Register dan OTP**

- Pendaftaran akun dengan validasi input.
- Pembuatan kode OTP acak 6 digit.
- Pengiriman email nyata menggunakan Brevo SMTP.

**Verifikasi dan Pembuatan Data Otomatis**

Verifikasi OTP yang berhasil secara otomatis membuat data berikut dalam satu transaksi database (`DB::beginTransaction`):

- Record data user
- Biodata awal
- Template berkas
- Nomor pendaftaran unik dengan format `SPMB-YYYYMMDD-XXXX`

Penggunaan transaksi memastikan seluruh data tersimpan secara utuh atau dibatalkan seluruhnya apabila terjadi kegagalan.

**Login Multi-Kriteria dan Rate Limiter**

- Login dapat dilakukan menggunakan username, email, atau ID Panitia.
- Pembatasan jumlah percobaan login (Rate Limiter) untuk mencegah serangan brute-force.

**Token dan Cookies**

- Integrasi Laravel Sanctum untuk manajemen Bearer Token.
- Dukungan HttpOnly Cookie.

**Lupa Password**

- Pengiriman OTP untuk reset password.
- Verifikasi OTP.
- Pembaruan kata sandi baru.

### 3. Modul Manajemen Data dan Layanan

**Jurusan (`JurusanController`)**

- Endpoint CRUD data jurusan khusus admin.
- Endpoint pengambilan daftar jurusan yang dapat diakses publik.

**Biodata (`BiodataController`)**

- Pembaruan data diri siswa.
- Sinkronisasi otomatis dengan tabel pendaftaran.

**Berkas (`BerkasController`)**

- Unggah dokumen persyaratan langsung ke Supabase Storage.
- Jenis dokumen yang didukung:
  - Ijazah atau SKL
  - Kartu Keluarga (KK)
  - Akta Kelahiran
  - Pasfoto

**Pendaftaran dan Seleksi (`PendaftaranController`)**

- Pengelolaan status siswa.
- Pemilihan opsi jurusan.
- Rekapitulasi data pendaftar untuk panitia dan admin.
- Pembaruan status kelulusan.

---

## Struktur Database

| Tabel                  | Fungsi                                  |
| ---------------------- | --------------------------------------- |
| `tb_users`             | Data akun pengguna                      |
| `otps`                 | Kode OTP untuk verifikasi dan reset     |
| `tb_jurusan`           | Data jurusan                            |
| `tb_biodata`           | Data biodata calon siswa                |
| `tb_berkas`            | Data berkas pendaftaran                 |
| `tb_pendaftaran_spmb`  | Data pendaftaran dan status seleksi     |

---

## Keamanan dan Otorisasi

Poin penting:

- Seluruh endpoint privat dilindungi middleware `auth:sanctum`.
- Hak akses dibatasi berdasarkan role atau ability: `siswa`, `panitia`, dan `admin`.
- Login dilindungi Rate Limiter terhadap percobaan berulang.
- Token dikelola melalui Laravel Sanctum dengan dukungan HttpOnly Cookie.
- Pembuatan data pasca verifikasi OTP dijalankan dalam transaksi database untuk menjaga konsistensi data.

---

## Perbaikan Teknis

### Penyesuaian Model User

File `app/Models/User.php` telah disesuaikan agar terhubung ke tabel kustom `tb_users`.

**Masalah sebelumnya**

```
relation "users" does not exist
```

Laravel secara bawaan mencari tabel bernama `users`, sedangkan skema proyek ini menggunakan nama tabel kustom `tb_users`.

**Solusi**

Model User dikonfigurasi agar secara eksplisit menunjuk ke tabel `tb_users`.

---

## Konfigurasi Lingkungan

Salin berkas `.env.example` menjadi `.env`, lalu sesuaikan nilai berikut dengan kredensial milik Anda.

```env

```

Catatan: nama variabel Supabase Storage di atas bersifat contoh. Sesuaikan dengan nama variabel yang digunakan pada kode proyek.

Catatan keamanan:

- Jangan pernah mengunggah berkas `.env` ke repositori.
- Pastikan `.env` tercantum pada `.gitignore`.
- Jangan membagikan service role key Supabase kepada pihak lain.
- Gunakan alamat pengirim yang telah diverifikasi di Brevo agar email tidak ditolak.

---

## Instalasi dan Menjalankan Backend

```bash
# 1. Instal dependensi
composer install

# 2. Salin konfigurasi lingkungan
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Jalankan migrasi ke Supabase PostgreSQL
php artisan migrate

# 5. Jalankan server pengembangan
php artisan serve
```

Persyaratan:

- PHP dengan ekstensi `pdo_pgsql` aktif
- Composer
- Proyek Supabase aktif (database dan storage)
- Akun Brevo aktif dengan SMTP key

---

## Panduan Pengujian

Pengujian endpoint dilakukan melalui Postman dengan urutan berikut.

1. Registrasi akun baru dan pastikan email OTP diterima.
2. Verifikasi OTP, lalu pastikan data berikut terbentuk otomatis:
   - Record pada `tb_users`
   - Biodata awal pada `tb_biodata`
   - Template berkas pada `tb_berkas`
   - Nomor pendaftaran berformat `SPMB-YYYYMMDD-XXXX` pada `tb_pendaftaran_spmb`
3. Login menggunakan username, email, atau ID Panitia, lalu simpan token yang dikembalikan.
4. Uji endpoint privat dengan header `Authorization: Bearer <token>`.
5. Uji pembatasan akses per role: pastikan endpoint khusus `admin` dan `panitia` menolak akses dari role `siswa`.
6. Uji Rate Limiter dengan beberapa percobaan login yang gagal secara berturut-turut.
7. Uji alur lupa password: kirim OTP reset, verifikasi, lalu perbarui kata sandi.
8. Uji unggah berkas dan pastikan file tersimpan pada Supabase Storage.

Kriteria keberhasilan:

- Seluruh respons sesuai dengan kode status yang diharapkan.
- Data tersimpan dan dapat dilihat pada Supabase Table Editor.
- Akses tanpa token atau dengan role yang tidak sesuai ditolak.

---

## Rencana Pengembangan Selanjutnya

- Mengintegrasikan seluruh endpoint backend dengan antarmuka frontend React.
- Menyusun dashboard untuk masing-masing role (siswa, panitia, admin).
- Melakukan pengujian menyeluruh (QA) terhadap alur pendaftaran dari awal hingga hasil seleksi.
- Menambahkan dokumentasi API yang lebih rinci untuk setiap endpoint.
- Menyiapkan konfigurasi untuk lingkungan produksi.

---

## Status Dokumen
Dokumen ini mencerminkan kondisi pengembangan backend setelah modul database, autentikasi, manajemen data, dan otorisasi selesai diimplementasikan. Dokumen akan diperbarui seiring bertambahnya fitur.
