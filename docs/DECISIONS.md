# DECISIONS: Keputusan, Celah, dan Pertanyaan Terbuka

> Dibuat 7 Nov 2026 dari pembacaan `Dokumentasi_Sistem_Marketplace_Jasa_Rumahan.md`.
> Semua baris berstatus **USULAN** sampai kelompok mengubahnya jadi **DISEPAKATI**. Ubah kolom Status langsung di file ini.
> Dokumen desain (§18 dan kesimpulan) menyebut: interval slot, batas percobaan mitra, waktu respons mitra, dan aturan refund *harus ditetapkan sebelum implementasi*. Bagian 1 mengisi itu dengan default yang bisa diubah lewat `.env`.

## 1. Keputusan parameter (default usulan)

| ID | Topik | Default usulan | Status |
|---|---|---|---|
| D-01 | Zona waktu | `Asia/Jakarta`; API pakai offset `+07:00` | USULAN |
| D-02 | Slot waktu | Blok 2 jam sesuai jam operasional 08.00-20.00: 08-10, 10-12, 12-14, 14-16, 16-18, 18-20. Customer memilih slot, bukan jam bebas | USULAN |
| D-03 | Lead time minimum | Slot harus mulai ≥ 60 menit dari sekarang (`MIN_LEAD_MINUTES`, bisa dinaikkan) | USULAN |
| D-04 | Timeout respons mitra | 3 menit (`MITRA_RESPONSE_SECONDS=180`; saat demo boleh 60) | USULAN |
| D-05 | Batas percobaan mitra | 3 mitra (`MAX_MITRA_ATTEMPTS=3`), lalu pesanan `cancelled` | USULAN |
| D-06 | Batas bayar setelah mitra accept | 30 menit (`PAYMENT_EXPIRY_MINUTES=30`); lewat → `expired`, order `cancelled`, tanpa refund | USULAN |
| D-07 | Customer tidak konfirmasi selesai | Otomatis `completed` setelah 24 jam (`AUTO_COMPLETE_HOURS=24`). Admin juga bisa memaksa selesai | USULAN |
| D-08 | Refund MVP | Sistem hanya mencatat `refund_pending`. Admin memproses manual (via dashboard Midtrans) lalu menekan "tandai refunded"/"refund gagal" | USULAN |
| D-09 | `day_of_week` | Mengikuti Carbon: 0 = Minggu … 6 = Sabtu | USULAN |
| D-10 | Format `order_code` | `JR-` + 8 karakter acak huruf besar/angka tanpa karakter ambigu (tanpa 0, O, 1, I). Contoh `JR-7K2M9QXD`. Retry jika bentrok UNIQUE | USULAN |
| D-11 | `gateway_order_id` | `{order_code}-{n}`, `n` = urutan percobaan bayar. Midtrans membatasi karakter/panjang `order_id`; cek dokumen saat implementasi | USULAN |
| D-12 | Privasi di penawaran | Offer ke mitra menampilkan jasa, ukuran, add-on, jadwal, kota/wilayah. Alamat lengkap dan telepon customer baru muncul **setelah accept** | USULAN |
| D-13 | Otorisasi guest | Aksi guest (bayar, batal, konfirmasi, lacak) menyertakan `order_code` + `phone` tiap request, throttle 5 percobaan/menit per IP + kode | USULAN |
| D-14 | Format telepon | Simpan lokal `08…`; normalisasi `+62`/`62` → `0` | USULAN |
| D-15 | Persetujuan mitra | `MITRA_AUTO_APPROVE=true` saat dev/demo. `false` = admin harus approve (mitra `pending` tidak ditawari order) | USULAN |
| D-16 | Urutan kandidat mitra | Mitra eligible dengan order aktif paling sedikit di hari itu lebih dulu, sisanya acak. `service_area` (teks bebas) **tidak dipakai** matching di MVP | USULAN |
| D-17 | Uang di API | Integer rupiah | USULAN |
| D-18 | Pembaruan status ke Flutter | Polling 5-10 detik; tanpa push notification | USULAN |
| D-19 | Mitra batal setelah accept | Di luar MVP; admin menangani manual | USULAN |
| D-20 | Login admin | Nomor telepon + password (konsisten dengan role lain), akun dibuat lewat seeder | USULAN |
| D-21 | Arsitektur Flutter | Dua app terpisah + `packages/core` (model, helpers) bersama. Struktur folder mengikuti modul kuliah. Tanpa state management package | USULAN |

## 2. Celah di dokumen desain (perlu keputusan kelompok)

Dokumen desain sudah kuat di domain, relasi, dan aturan bisnis. Yang berikut belum tertutup dan akan memunculkan bug atau diskusi saat implementasi.

| ID | Celah | Dampak | Usulan |
|---|---|---|---|
| G-01 | `orders` tidak punya kolom pembatalan/penyelesaian | Tidak tahu siapa/kapan/mengapa batal; auto-complete sulit | Tambah `cancelled_at`, `cancel_reason`, `cancelled_by` (`customer`/`mitra`/`admin`/`system`), `completed_at`. Migration baru, **perlu persetujuan** |
| G-02 | `payments` tidak punya tempat payload, refund, dan notifikasi terakhir | Debug webhook dan refund sulit | Tambah `gateway_payload` (JSON), `refund_amount`, `refunded_at`. **Perlu persetujuan** |
| G-03 | `orders` tidak menyimpan snapshot nama jasa dan ukuran | Admin mengganti nama master → riwayat lama ikut berubah | Opsional: `service_name`, `property_size_name` |
| G-04 | Diagram arsitektur: Admin Dashboard → API, padahal Admin = Blade | Dua jalur logika bisnis | Blade memakai Service/Eloquent yang sama, tidak memanggil API sendiri (B-1) |
| G-05 | Guest tidak punya token, tapi harus bayar/batal/konfirmasi | Aksi guest tidak punya otorisasi | D-13 |
| G-06 | `verification_status` awal `pending` | Semua mitra baru tidak pernah dapat order → demo mati | D-15 |
| G-07 | `mitra_completed` tanpa batas waktu konfirmasi | Pesanan bisa menggantung selamanya | D-07 |
| G-08 | Slot mitra yang sudah accept tapi belum bayar | Dua order bisa mengambil slot sama | Jadwal terpakai sejak `waiting_payment` (kontrak §6.1) |
| G-09 | Pembayaran kedaluwarsa tidak punya jalur status eksplisit | Order `waiting_payment` menggantung | Kedaluwarsa → order `cancelled` (D-06) |
| G-10 | `users.id` dan `mitra_profiles.id` mudah tertukar | Data mitra salah sasaran | B-11 |
| G-11 | Telepon `UNIQUE` dan 1 role per akun | Satu orang tidak bisa jadi customer dan mitra | Diterima untuk MVP; gunakan nomor berbeda |
| G-12 | Mitra batal setelah accept tidak diatur | Customer tertahan tanpa mitra | D-19 |
| G-13 | UI/UX belum disebut (wireframe/Figma) | Hasil AI generik; poster dan video desain tidak ada bahan | Lihat Q-5 |

## 3. Pertanyaan terbuka

Cek ke **dosen** dan **kelompok** secepatnya; jawabannya mengubah rencana.

| ID | Pertanyaan | Kepada | Kenapa penting |
|---|---|---|---|
| Q-1 | Backend Laravel + Midtrans boleh, atau final project harus memakai API mockapi.io seperti di modul? | Dosen pengampu (Ade Hendini, M.Kom, tertulis di RTM) | Silabus dan modul mengajarkan mockapi.io. Kalau Laravel tidak diterima, rencana berubah total (Plan B) |
| Q-2 | Rubrik final project: seberapa besar bobot tampilan, fungsi, dan dokumen? | Dosen | RTM: kesesuaian luaran 30%, kualitas final project 50%, ketepatan waktu 20%. RTM menyebut "tampilan aplikasi" |
| Q-3 | Format luaran: template karya tulis ilmiah, durasi video, ukuran poster, format presentasi | Dosen | Ketiganya dikumpulkan bersama kode |
| Q-4 | Lingkungan demo: laptop + emulator, HP fisik, atau server online? | Kelompok | Menentukan tunnel Midtrans dan `API_BASE_URL` |
| Q-5 | Apakah sudah ada desain UI (Figma/wireframe)? | Kelompok | Tanpa desain, AI menebak tampilan; video proses desain butuh bahan |
| Q-6 | Siapa memegang: backend, customer app, mitra app, admin, dokumen, video/poster/KTI | Kelompok | Hindari semua orang menyentuh semua hal |
| Q-7 | Tanggal pasti pengumpulan dan jadwal presentasi (RTM: pertemuan 14 dan 15) | Dosen/jadwal | Menentukan hari feature freeze |

## 4. Plan B dan aturan kendali

- **Feature freeze:** akhir minggu ke-3. Setelah itu hanya perbaikan bug, tes, dokumen, dan materi luaran.
- **Pemicu Plan B:** jika akhir minggu ke-2 alur *buat order → mitra accept* belum jalan end-to-end di backend, aktifkan mode mock di Flutter (F-10) untuk menyelesaikan seluruh UI, dan demokan backend sebisanya.
- **Pemicu pemangkasan:** jika Midtrans sandbox + webhook belum stabil di hari ke-3 minggu ke-3, pakai fallback Status API. Jika masih belum, demo pembayaran memakai Midtrans Payment Simulator dan/atau `simulate-paid` (dev), dan tulis jujur di dokumen.
- **Urutan prioritas fitur** bila waktu mepet: (1) auth + katalog, (2) buat order + harga server, (3) mitra accept + progres status, (4) bayar Midtrans, (5) admin CRUD katalog/harga, (6) konfirmasi selesai, (7) guest tracking. Jangan mengorbankan (1)-(3) demi (4).
