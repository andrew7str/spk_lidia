# Cara Penggunaan Pertama - Setup Database
## 🚀 Langkah Penggunaan
1. **Aktifkan MySQL**
   - Jalankan **XAMPP Control Panel** → klik **Start** pada MySQL.
   - Atau pastikan MySQL service aktif di komputer Anda.
2. - Jalankan "SettingsDatabase.cmd" untuk mengatur database secara otomatis.
   - Pilih lokasi file settingan database nya yaitu "db_lidia.txt"
   - Cek pada "http://localhost/phpmyadmin/index.php" apakah database "spk_lidia_fashion" sudah ada dan konfigurasi lainnya.
3. - Langsung coba dengan membuka "http://localhost/spk_lidia_fashion/index.php" pada Web Browser
   - Untuk login default :
     - admin
     - password_admin
   - 

# 🏪 SPK Lidia Fashion - Sistem Pendukung Keputusan Seleksi Supplier

Sistem Pendukung Keputusan berbasis web untuk membantu Toko Lidia Fashion dalam memilih supplier terbaik menggunakan metode **AHP (Analytical Hierarchy Process)** dan **TOPSIS (Technique for Order Preference by Similarity to Ideal Solution)**.

## 📋 Deskripsi Sistem

Sistem ini dirancang untuk mengatasi permasalahan pemilihan supplier yang selama ini dilakukan secara manual dan subjektif. Dengan menggunakan kombinasi metode AHP dan TOPSIS, sistem dapat memberikan rekomendasi supplier terbaik berdasarkan kriteria yang telah ditentukan secara objektif dan terukur.

### 🎯 Fitur Utama
- **Manajemen Kriteria**: Kelola kriteria penilaian (Harga, Kualitas, Waktu, Pelayanan)
- **Manajemen Supplier**: Kelola data supplier yang akan dinilai
- **Perhitungan AHP**: Menentukan bobot prioritas kriteria dengan uji konsistensi
- **Perhitungan TOPSIS**: Menentukan ranking supplier berdasarkan kedekatan dengan solusi ideal
- **Hasil Seleksi**: Menampilkan ranking supplier dengan nilai preferensi
- **Riwayat Seleksi**: Menyimpan dan menampilkan riwayat hasil seleksi
- **Visualisasi Data**: Grafik hasil seleksi untuk analisis yang lebih mudah

### 🔧 Teknologi yang Digunakan
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Server**: Apache (XAMPP)

## 📊 Metode Perhitungan

### AHP (Analytical Hierarchy Process)
- Membuat matriks perbandingan berpasangan antar kriteria
- Normalisasi matriks dan perhitungan bobot prioritas
- Uji konsistensi dengan Consistency Index (CI) dan Consistency Ratio (CR)
- Formula sesuai dengan standar AHP: λmax, CI = (λmax-n)/(n-1), CR = CI/RI

### TOPSIS (Technique for Order Preference by Similarity to Ideal Solution)
- Normalisasi matriks keputusan: Rij = Xij / √(∑Xij²)
- Matriks terbobot: Vij = Rij × Wj (bobot dari AHP)
- Penentuan solusi ideal positif (A+) dan negatif (A-)
- Perhitungan jarak: D+i dan D-i menggunakan Euclidean distance
- Nilai preferensi: Vi = D-i / (D+i + D-i)

## 🚀 Cara Penggunaan - Setup Database

### Langkah Penggunaan
1. **Aktifkan MySQL**
   - Jalankan **XAMPP Control Panel** → klik **Start** pada MySQL
   - Atau pastikan MySQL service aktif di komputer Anda

2. **Setup Database**
   - Jalankan "SettingsDatabase.cmd" untuk mengatur database secara otomatis
   - Pilih lokasi file settingan database yaitu "db_lidia.txt"
   - Cek pada "http://localhost/phpmyadmin/index.php" apakah database "spk_lidia_fashion" sudah ada

3. **Akses Sistem**
   - Buka "http://localhost/spk_lidia_fashion/index.php" pada Web Browser
   - Login default:
     - **Username**: admin
     - **Password**: password_admin

## 📖 Panduan Penggunaan Sistem

### 1. Manajemen Kriteria
- Tambahkan kriteria penilaian (contoh: Harga, Kualitas, Waktu, Pelayanan)
- Tentukan tipe kriteria: **Benefit** (semakin tinggi semakin baik) atau **Cost** (semakin rendah semakin baik)

### 2. Manajemen Supplier
- Tambahkan data supplier yang akan dinilai
- Pastikan semua supplier yang akan dibandingkan sudah terdaftar

### 3. Input Perbandingan AHP
- Lakukan perbandingan berpasangan antar kriteria menggunakan skala Saaty (1-9)
- Sistem akan otomatis menghitung bobot prioritas dan melakukan uji konsistensi
- **CR ≤ 0.1** menunjukkan perbandingan konsisten

### 4. Input Nilai Supplier
- Berikan nilai untuk setiap supplier pada setiap kriteria (skala 1-9)
- Pastikan semua supplier memiliki nilai untuk semua kriteria

### 5. Perhitungan TOPSIS
- Jalankan perhitungan TOPSIS setelah bobot AHP tersedia
- Sistem akan menampilkan ranking supplier berdasarkan nilai preferensi
- Supplier dengan nilai preferensi tertinggi adalah yang terbaik

### 6. Analisis Hasil
- Lihat hasil seleksi dengan ranking dan nilai preferensi
- Analisis grafik untuk memahami perbandingan antar supplier
- Simpan atau export hasil untuk dokumentasi

## 🔍 Validasi Perhitungan

Sistem ini telah divalidasi menggunakan contoh perhitungan manual dari skripsi dengan data:
- **Kriteria**: Harga (cost), Kualitas (benefit), Waktu (cost), Pelayanan (benefit)
- **Supplier**: Rezeky, Duta Modren, Serasi, Umi Kids, Kids
- **Target Hasil**: Supplier Rezeky sebagai ranking 1 dengan nilai preferensi ~0.698

## 📁 Struktur File Penting

```
spk_lidia_fashion/
├── functions/
│   ├── ahp_logic.php          # Logic perhitungan AHP
│   └── topsis_logic.php       # Logic perhitungan TOPSIS
├── admin/
│   ├── perhitungan_ahp.php    # Interface perhitungan AHP
│   ├── perhitungan_topsis.php # Interface perhitungan TOPSIS
│   ├── kriteria.php           # Manajemen kriteria
│   ├── supplier.php           # Manajemen supplier
│   └── input_nilai.php        # Input nilai supplier
├── config/
│   └── database.php           # Konfigurasi database
└── README.md                  # Dokumentasi ini
```

## 🎓 Referensi

Sistem ini dikembangkan berdasarkan skripsi:
**"Perancangan dan Implementasi Sistem Pendukung Keputusan untuk Seleksi Supplier Baju pada Toko Lidia Fashion Menggunakan Metode AHP dan TOPSIS"**

## 📞 Support

Jika mengalami kendala dalam penggunaan sistem, pastikan:
- MySQL service berjalan dengan baik
- Database sudah ter-setup dengan benar
- Semua file PHP dapat diakses melalui web server
- Data kriteria dan supplier sudah diinput sebelum melakukan perhitungan

---
*Sistem SPK Lidia Fashion - Membantu pengambilan keputusan yang lebih objektif dan terukur*
