# EC2 Load Balancer Test

Web PHP sederhana untuk mengetes AWS Application Load Balancer (ALB) ke dua EC2 Private Debian. Halaman utama menampilkan Instance ID, Private IP, Availability Zone, dan tipe EC2 yang melayani request menggunakan IMDSv2.

## Struktur project

```text
ec2-lb-test/
├── index.php
├── health.php
├── Dockerfile
├── docker-compose.yml
└── .dockerignore
```

## 1. Pengaturan AWS yang wajib

Lakukan pada **EC2 A dan EC2 B**:

1. Buka EC2 Console.
2. Pilih instance, lalu `Actions` → `Instance settings` → `Modify instance metadata options`.
3. Aktifkan Metadata endpoint.
4. Pilih IMDSv2 (`V2 only`).
5. Atur `Metadata response hop limit` menjadi **2** agar container Docker bridge dapat mengakses IMDS.
6. Simpan perubahan.

Security Group:
- ALB: izinkan inbound HTTP port 80 dari jaringan/client yang memang boleh mengakses aplikasi.
- EC2 A dan B: izinkan inbound TCP port 80 **hanya dari Security Group ALB**.
- Pastikan EC2 A dan B dapat mengakses registry Docker saat proses build/pull image.

> Catatan: aplikasi hanya membaca metadata instance. Jangan menaruh kredensial AWS atau secret di source code.

## 2. Install Docker di Debian (EC2 A dan EC2 B)

Jalankan pada masing-masing EC2:

```bash
sudo apt update
sudo apt install -y docker.io docker-compose
sudo systemctl enable --now docker
sudo docker version
```

Jika paket `docker-compose` tidak tersedia pada versi Debian yang digunakan, gunakan Docker Compose plugin sesuai repositori Docker yang terpasang, atau jalankan perintah `docker build` dan `docker run` pada bagian berikut.

## 3. Upload project ke GitHub

1. Ekstrak ZIP ini di komputer.
2. Buat repository GitHub baru, misalnya `ec2-lb-test`.
3. Upload seluruh isi folder project ke root repository (jangan hanya mengupload file ZIP).
4. Pastikan `Dockerfile`, `index.php`, dan `health.php` berada di root repository.

Contoh menggunakan Git dari komputer:

```bash
cd ec2-lb-test
git init
git add .
git commit -m "Initial EC2 load balancer test"
git branch -M main
git remote add origin https://github.com/USERNAME/ec2-lb-test.git
git push -u origin main
```

Ganti `USERNAME` dengan username GitHub dan sesuaikan nama repository.

## 4. Deploy ke EC2 A dan EC2 B

Lakukan langkah yang sama di **kedua EC2 Debian**. Ganti URL repository dengan URL GitHub milikmu.

```bash
sudo apt update
sudo apt install -y git
git clone https://github.com/USERNAME/ec2-lb-test.git
cd ec2-lb-test
sudo docker build -t ec2-lb-test .
```

Jalankan container:

```bash
sudo docker run -d \
  --name ec2-lb-test \
  --restart unless-stopped \
  -p 80:80 \
  ec2-lb-test
```

Jika container dengan nama tersebut sudah ada, hapus dulu sebelum membuat ulang:

```bash
sudo docker rm -f ec2-lb-test
```

Kemudian jalankan lagi perintah `docker run`.

## 5. Tes di masing-masing EC2

```bash
sudo docker ps
curl http://localhost/health.php
curl http://localhost/
```

Health check harus mengembalikan `OK`. Halaman utama harus menampilkan Instance ID dan Private IP EC2 yang bersangkutan.

## 6. Konfigurasi Target Group ALB

Gunakan pengaturan berikut:

| Pengaturan | Nilai |
|---|---|
| Target type | Instances |
| Protocol | HTTP |
| Port | 80 |
| Health check protocol | HTTP |
| Health check path | `/health.php` |
| Success code | `200` |

Daftarkan EC2 A dan EC2 B ke Target Group yang sama. Tunggu sampai status keduanya `Healthy`.

## 7. Uji Load Balancing

Buka DNS ALB melalui browser:

```text
http://NAMA-ALB.ap-southeast-1.elb.amazonaws.com
```

Refresh beberapa kali. Instance ID dan Private IP pada halaman akan menunjukkan EC2 yang melayani request tersebut.

ALB tidak menjamin request bergantian A-B-A-B. Distribusi bergantung pada algoritma ALB, kondisi target, koneksi, dan pengaturan stickiness. Untuk pengujian, pastikan stickiness dinonaktifkan pada Target Group.

## 8. Uji failover

1. Pastikan EC2 A dan B berstatus `Healthy`.
2. Stop container di EC2 A:

```bash
sudo docker stop ec2-lb-test
```

3. Tunggu sampai health check ALB menandai EC2 A sebagai `Unhealthy`.
4. Buka DNS ALB lagi. Request seharusnya dilayani EC2 B.
5. Hidupkan kembali container EC2 A:

```bash
sudo docker start ec2-lb-test
```

6. Tunggu sampai EC2 A kembali `Healthy`.

## Troubleshooting

- **Instance ID menampilkan `Metadata unavailable`:** periksa IMDS aktif, IMDSv2 diizinkan, dan hop limit bernilai 2.
- **Target Unhealthy:** pastikan container berjalan, port 80 terbuka dari Security Group ALB, dan `/health.php` mengembalikan HTTP 200.
- **Kedua EC2 menampilkan ID sama:** pastikan masing-masing container berjalan pada EC2 yang benar dan cek metadata lokal di masing-masing instance.
- **Tidak bisa clone GitHub:** pastikan EC2 memiliki akses internet keluar melalui NAT Gateway atau jalur egress lain yang sesuai.

Aplikasi ini tidak membutuhkan database.
# web-hallo-docker
