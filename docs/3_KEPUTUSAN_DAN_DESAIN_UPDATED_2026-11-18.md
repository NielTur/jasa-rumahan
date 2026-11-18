<!-- Bundel kerja diperbarui 18 Nov 2026. Tidak ada perubahan keputusan, aturan bisnis, arsitektur, atau skema database. Mitra App Tahap 2 autentikasi telah diimplementasikan sesuai desain dan kontrak yang ada; isu pemulihan sesi setelah aplikasi dibuka ulang masih tertunda. Catatan implementasi ditambahkan tanpa mengubah desain sistem. -->
<!-- ===== FILE: docs/DECISIONS.md ===== -->
# DECISIONS: Keputusan, Celah, dan Pertanyaan Terbuka

> Dibuat 7 Nov 2026 dari pembacaan
> `Dokumentasi_Sistem_Marketplace_Jasa_Rumahan.md`. **Diperbarui 10 Nov
> 2026** (hasil konsultasi dosen, P3a-P4a, CR-12..CR-15, D-22..D-24).
> **Kedudukan dokumen:** file ini adalah bundel keputusan dan desain
> utama jika namanya mengikuti
> `3_KEPUTUSAN_DAN_DESAIN_UPDATED_(TGL).md`. Gunakan edisi bertanggal
> paling baru, baik ditemukan di GitHub maupun GPT Project Resources.
> Implementasi GitHub menjadi bukti kondisi kode; jika berbeda dari
> keputusan/desain ini, catat discrepancy dan minta penetapan pemilik
> proyek sebelum mengubah desain, skema, atau kode. Semua baris di §1
> berstatus **USULAN** sampai kelompok mengubahnya jadi **DISEPAKATI**.
> Ubah kolom Status langsung di file ini. Status USULAN tidak
> menghalangi kode memakainya; lihat catatan "Dipakai di kode" di bawah
> tabel. Dokumen desain (§18 dan kesimpulan) menyebut: interval slot,
> batas percobaan mitra, waktu respons mitra, dan aturan refund *harus
> ditetapkan sebelum implementasi*. §1 mengisi itu dengan default yang
> bisa diubah lewat `.env`.

## 1. Keputusan parameter

  -----------------------------------------------------------------------------------------
  ID                Topik                Keputusan                        Status
  ----------------- -------------------- -------------------------------- -----------------
  D-01              Zona waktu           `Asia/Jakarta`; API pakai offset USULAN
                                         `+07:00`                         

  D-02              Slot waktu           Blok 2 jam sesuai jam            USULAN
                                         operasional 08.00-20.00: 08-10,  
                                         10-12, 12-14, 14-16, 16-18,      
                                         18-20. Customer memilih slot,    
                                         bukan jam bebas                  

  D-03              Lead time minimum    Slot harus mulai ≥ 60 menit dari USULAN
                                         sekarang (`MIN_LEAD_MINUTES`,    
                                         bisa dinaikkan)                  

  D-04              Timeout respons      3 menit                          USULAN
                    mitra                (`MITRA_RESPONSE_SECONDS=180`;   
                                         saat demo boleh 60)              

  D-05              Batas percobaan      3 mitra                          USULAN
                    mitra                (`MAX_MITRA_ATTEMPTS=3`), lalu   
                                         pesanan `cancelled`              

  D-06              Batas bayar setelah  30 menit                         USULAN
                    mitra accept         (`PAYMENT_EXPIRY_MINUTES=30`);   
                                         lewat → `expired`, order         
                                         `cancelled`, tanpa refund. Titik 
                                         awal 30 menit: lihat D-24        

  D-07              Customer tidak       Otomatis `completed` setelah 24  USULAN
                    konfirmasi selesai   jam (`AUTO_COMPLETE_HOURS=24`).  
                                         Admin juga bisa memaksa selesai  

  D-08              Refund MVP           Sistem hanya mencatat            USULAN
                                         `refund_pending`. Admin          
                                         memproses manual (via dashboard  
                                         Midtrans) lalu menekan "tandai   
                                         refunded"/"refund gagal"         

  D-09              `day_of_week`        Mengikuti Carbon: 0 = Minggu ... USULAN
                                         6 = Sabtu                        

  D-10              Format `order_code`  `JR-` + 8 karakter acak huruf    USULAN
                                         besar/angka tanpa karakter       
                                         ambigu (tanpa 0, O, 1, I).       
                                         Contoh `JR-7K2M9QXD`. Retry jika 
                                         bentrok UNIQUE                   

  D-11              `gateway_order_id`   `{order_code}-{n}`, `n` = urutan USULAN
                                         percobaan bayar. Midtrans        
                                         membatasi karakter/panjang       
                                         `order_id`; cek dokumen saat     
                                         implementasi. Untuk simulator    
                                         dev: `{kode}-DEV1` (CR-14)       

  D-12              Privasi di penawaran Offer ke mitra menampilkan:      DISEPAKATI (10
                                         jasa, ukuran, add-on, jadwal,    Nov)
                                         **durasi, harga, `expires_at`**. 
                                         **Tidak** menampilkan            
                                         kota/wilayah (tidak ada datanya  
                                         di order), `order_code`, nama,   
                                         telepon, atau alamat customer.   
                                         Semua itu baru terbuka **setelah 
                                         accept**                         

  D-13              Otorisasi guest      Aksi guest (bayar, batal,        USULAN
                                         konfirmasi, lacak) menyertakan   
                                         `order_code` + `phone` tiap      
                                         request. Throttle: hanya         
                                         percobaan **gagal** dihitung,    
                                         5/menit per IP + kode (CR-10)    

  D-14              Format telepon       Simpan lokal `08…`; normalisasi  USULAN
                                         `+62`/`62` → `0`                 

  D-15              Persetujuan mitra    `MITRA_AUTO_APPROVE=true` saat   USULAN
                                         dev/demo. `false` = admin harus  
                                         approve (mitra `pending` tidak   
                                         ditawari order)                  

  D-16              Urutan kandidat      Mitra eligible dengan order      USULAN
                    mitra                aktif paling sedikit di hari itu 
                                         lebih dulu, sisanya acak.        
                                         `service_area` (teks bebas)      
                                         **tidak dipakai** matching di    
                                         MVP                              

  D-17              Uang di API          Integer rupiah                   USULAN

  D-18              Pembaruan status ke  Polling 5-10 detik; tanpa push   USULAN
                    Flutter              notification                     

  D-19              Mitra batal setelah  Di luar MVP; admin menangani     USULAN
                    accept               manual                           

  D-20              Login admin          Nomor telepon + password         USULAN
                                         (konsisten dengan role lain),    
                                         akun dibuat lewat seeder         

  D-21              Arsitektur Flutter   Dua app terpisah +               USULAN
                                         `packages/core` (model, helpers) 
                                         bersama. Struktur folder         
                                         mengikuti modul kuliah. Tanpa    
                                         state management package         

  D-22              Cancel saat payment  Cancel customer **tidak          USULAN
                    masih `pending`      mengubah** baris payment         
                                         `pending` (P4a). Pada P4b:       
                                         backend mencoba membatalkan      
                                         transaksi di Midtrans            
                                         (best-effort); kalau gagal,      
                                         payment tetap `pending` dan akan 
                                         kedaluwarsa sendiri              

  D-23              Webhook `paid` untuk Order **tidak dihidupkan lagi**. USULAN
                    order yang sudah     Payment dicatat `paid`, lalu     
                    `cancelled`          ditandai `refund_pending`        
                                         (`refund_amount` = penuh) supaya 
                                         admin mengembalikan uang manual  
                                         (D-08)                           

  D-24              Titik awal batas     Dihitung dari waktu transisi     USULAN
                    bayar D-06           `waiting_payment` di             
                                         `order_status_histories`,        
                                         **bukan** dari                   
                                         `payments.expired_at`. Alasan:   
                                         order yang customer-nya tidak    
                                         pernah membuka pembayaran tidak  
                                         punya baris `payments`           
  -----------------------------------------------------------------------------------------

**Dipakai di kode (per 10 Nov):** D-01..D-05, D-08 (sebagian:
`refund_pending` saat cancel setelah bayar), D-09, D-10, D-12..D-17,
D-22 (sisi P4a), CR-14. **Belum dipakai:** D-06, D-07, D-24 (P4c), D-11
dan D-23 (P4b), D-20 (P5), D-18 dan D-21 (Flutter), D-19 (di luar MVP).

## 2. Celah di dokumen desain

Dokumen desain sudah kuat di domain, relasi, dan aturan bisnis. Celah
berikut muncul saat implementasi; kolom Status mencatat penutupannya.

  ---------------------------------------------------------------------------------------------------------------------
  ID             Celah                     Dampak                Usulan                               Status
  -------------- ------------------------- --------------------- ------------------------------------ -----------------
  G-01           `orders` tidak punya      Tidak tahu            Tambah `cancelled_at`,               TUTUP (CR-01)
                 kolom                     siapa/kapan/mengapa   `cancel_reason`, `cancelled_by`,     
                 pembatalan/penyelesaian   batal; auto-complete  `completed_at`                       
                                           sulit                                                      

  G-02           `payments` tidak punya    Debug webhook dan     Tambah `gateway_payload`,            TUTUP (CR-02)
                 tempat payload, refund,   refund sulit          `refund_amount`, `refunded_at`       
                 notifikasi terakhir                                                                  

  G-03           `orders` tidak menyimpan  Admin mengganti nama  Opsional: `service_name`,            TERBUKA
                 snapshot nama jasa dan    master → riwayat lama `property_size_name`                 (opsional;
                 ukuran                    ikut berubah                                               `OrderResource`
                                                                                                      masih membaca
                                                                                                      nama dari master)

  G-04           Diagram arsitektur: Admin Dua jalur logika      Blade memakai Service/Eloquent yang  TUTUP (aturan
                 Dashboard → API, padahal  bisnis                sama, tidak memanggil API sendiri    B-1; dibuktikan
                 Admin = Blade                                   (B-1)                                di P5)

  G-05           Guest tidak punya token,  Aksi guest tidak      D-13                                 TUTUP (kode P2a,
                 tapi harus                punya otorisasi                                            P4a)
                 bayar/batal/konfirmasi                                                               

  G-06           `verification_status`     Semua mitra baru      D-15                                 TUTUP
                 awal `pending`            tidak pernah dapat                                         
                                           order → demo mati                                          

  G-07           `mitra_completed` tanpa   Pesanan bisa          D-07                                 TERBUKA sampai
                 batas waktu konfirmasi    menggantung selamanya                                      P4c

  G-08           Slot mitra yang sudah     Dua order bisa        Jadwal terpakai sejak                TUTUP
                 accept tapi belum bayar   mengambil slot sama   `waiting_payment`                    
                                                                 (`OrderStatus::SCHEDULE_BLOCKING`)   

  G-09           Pembayaran kedaluwarsa    Order                 Kedaluwarsa → order `cancelled`      TERBUKA sampai
                 tidak punya jalur status  `waiting_payment`     (D-06, D-24)                         P4c
                 eksplisit                 menggantung                                                

  G-10           `users.id` dan            Data mitra salah      B-11                                 TUTUP
                 `mitra_profiles.id` mudah sasaran                                                    
                 tertukar                                                                             

  G-11           Telepon `UNIQUE` dan 1    Satu orang tidak bisa Diterima untuk MVP; gunakan nomor    DITERIMA
                 role per akun             jadi customer dan     berbeda                              
                                           mitra                                                      

  G-12           Mitra batal setelah       Customer tertahan     D-19                                 DITERIMA (di luar
                 accept tidak diatur       tanpa mitra                                                MVP)

  G-13           UI/UX belum disebut       Hasil AI generik;     Lihat Q-5                            TERBUKA
                 (wireframe/Figma)         Manual Book butuh                                          
                                           screenshot yang                                            
                                           konsisten                                                  
  ---------------------------------------------------------------------------------------------------------------------

## 3. Pertanyaan terbuka

  --------------------------------------------------------------------------
  ID                Pertanyaan           Kepada            Status / jawaban
  ----------------- -------------------- ----------------- -----------------
  Q-1               Backend Laravel +    Dosen             **JAWAB (10
                    Midtrans boleh, atau                   Nov):** Laravel +
                    wajib mockapi.io?                      MySQL boleh,
                                                           tidak wajib
                                                           mockapi.io.
                                                           Minimal harus ada
                                                           1 API eksternal,
                                                           contohnya Payment
                                                           Gateway Midtrans.
                                                           Disarankan
                                                           memasukkan proyek
                                                           ke GitHub supaya
                                                           dokumentasi ikut
                                                           terekam

  Q-2               Rubrik: bobot        Dosen             TERBUKA. RTM:
                    tampilan, fungsi,                      kesesuaian luaran
                    dokumen?                               30%, kualitas
                                                           final project
                                                           50%, ketepatan
                                                           waktu 20%

  Q-3               Format luaran (karya Dosen             **JAWAB (10
                    tulis, video,                          Nov):** hanya
                    poster, presentasi)                    **Manual Book**;
                                                           tanpa laporan
                                                           akhir, tanpa
                                                           template baku,
                                                           isinya kira-kira
                                                           panduan cara
                                                           memakai aplikasi
                                                           (per role). Video
                                                           hanya jika
                                                           pertemuan 13
                                                           ternyata bukan
                                                           presentasi.
                                                           Poster dan karya
                                                           tulis ilmiah
                                                           **tidak diminta**

  Q-4               Lingkungan demo:     Kelompok          TERBUKA.
                    laptop + emulator,                     Menentukan tunnel
                    HP fisik, atau                         Midtrans dan
                    server online?                         `API_BASE_URL`

  Q-5               Sudah ada desain UI  Kelompok          TERBUKA
                    (Figma/wireframe)?                     

  Q-6               Siapa memegang:      Kelompok          TERBUKA (tabel
                    backend, customer                      tim di README
                    app, mitra app,                        masih kosong)
                    admin, dokumen                         

  Q-7               Tanggal pasti        Dosen             **JAWAB sebagian
                    pengumpulan dan                        (10 Nov):**
                    jadwal presentasi                      presentasi di
                                                           **pertemuan 13**.
                                                           Tanggal
                                                           kalendernya belum
                                                           diketahui

  Q-8               Apakah demo perlu    Dosen             TERBUKA (belum
                    file APK, atau                         ditanyakan).
                    emulator di laptop                     Mempengaruhi
                    cukup?                                 jadwal build dan
                                                           Manual Book (cara
                                                           instal)

  Q-9               Apakah Midtrans mode Dosen             TERBUKA
                    **Sandbox** sudah                      (pertanyaan
                    memenuhi syarat "API                   tambahan dari
                    eksternal" untuk                       Claude, belum
                    demo?                                  ditanyakan).
                                                           Asumsi kerja: ya,
                                                           karena Sandbox
                                                           adalah API
                                                           Midtrans
                                                           sungguhan, hanya
                                                           uangnya uji
  --------------------------------------------------------------------------

## 4. Jadwal, freeze, dan aturan kendali

Dipatok ke nomor **pertemuan**, bukan tanggal, karena tanggal pasti
belum diketahui (Q-7). Per 10 Nov 2026 proyek ada di sekitar pertemuan
8.

-   **Presentasi:** pertemuan 13 (dari dosen).
-   **Feature freeze:** akhir pertemuan 12 (**USULAN**; kalau presentasi
    mundur, freeze ikut mundur). Setelah itu hanya perbaikan bug, tes,
    dan Manual Book.
-   **Checkpoint Flutter:** akhir pertemuan 9, minimal layar login +
    katalog + buat pesanan jalan dari emulator ke backend lokal
    (**USULAN**). Kalau belum tercapai, putuskan memangkas layar, bukan
    memangkas backend.
-   **Plan B (mockapi.io / mode mock Flutter sebagai pengganti backend)
    dicabut.** Backend Laravel sudah diizinkan dosen dan alur P1-P4a
    sudah jalan. Mode mock F-10 tetap boleh sebagai alat bantu UI, bukan
    pengganti.
-   **Midtrans wajib** karena syarat dosen (minimal 1 API eksternal).
    `simulate-paid` hanya alat dev, **bukan** pengganti. Pemicu
    pemangkasan: jika Webhook belum stabil, pakai fallback Status API.
    Jika Midtrans Sandbox sendiri tidak bisa dipakai, demo memakai
    Payment Simulator Midtrans dan ditulis jujur di Manual Book.
-   **Urutan prioritas** bila waktu mepet: (1) auth + katalog, (2) buat
    order + harga server, (3) mitra accept + progres status, (4) bayar
    Midtrans, (5) admin CRUD katalog/harga, (6) konfirmasi selesai, (7)
    guest tracking. Jangan mengorbankan (1)-(3) demi (4).
-   **Urutan kerja backend yang sedang dijalankan:** tes catch-up B-13 →
    P4c → P5 → P4b → P6 → Flutter. Alasan P4b di belakang: butuh akun
    Sandbox dan tunnel; backend selain itu bisa selesai tanpa itu.
    Rincian di `docs/PLAN_LANJUTAN.md`.
-   **Dependency baru yang disetujui:** `midtrans/midtrans-php`,
    dipasang di awal P4b (bersamaan dengan pembuatan akun Sandbox).

### Catatan implementasi frontend · 14 Nov 2026

Catatan ini bersifat operasional dan **tidak mengubah keputusan bisnis,
arsitektur, skema database, maupun kontrak API**.

-   Customer App telah memiliki halaman Home/katalog dan Detail Jasa;
    Flutter Web dapat dijalankan di Chrome.
-   Endpoint katalog dapat mengembalikan JSON saat dibuka langsung
    melalui browser, tetapi request `GET /api/service-categories` dari
    aplikasi Flutter Web dibatalkan (XHR `(canceled)`) setelah sekitar
    13,35 detik dan UI menampilkan pesan timeout.
-   CORS Laravel telah diperiksa secara konfigurasi dan tidak ditemukan
    error CORS di Console. Akar masalah belum diketahui.
-   Status: **BUG PRIORITAS TINGGI / BLOCKER PENGUJIAN KATALOG**. Wajib
    diselesaikan sebelum alur katalog Customer App dianggap dapat diuji.
    Tidak boleh membuat keputusan desain atau perubahan kontrak
    berdasarkan dugaan sebelum penyebab teknis terisolasi.


### Catatan implementasi autentikasi Mitra App · 18 Nov 2026

Catatan ini bersifat operasional dan **tidak mengubah keputusan bisnis,
arsitektur, skema database, maupun kontrak API**.

-   Alur login, registrasi Mitra, pemeriksaan sesi melalui `/auth/me`,
    dan logout telah diimplementasikan pada Mitra App.
-   Implementasi memakai `AuthService`, `AuthGate`, halaman login dan
    registrasi, serta model `AuthUser` dan `AuthSession` di Shared Core.
    Role selain `mitra` ditolak pada alur Mitra App.
-   Uji manual login, logout, password salah, penolakan akun Customer,
    registrasi Mitra, dan penolakan nomor telepon duplikat berhasil.
-   Pemulihan sesi setelah aplikasi dibuka ulang belum berhasil pada uji
    manual. Penyebab belum diketahui; perbaikan ditunda agar investigasi
    dilakukan bersama Customer App. Ini merupakan isu implementasi,
    bukan perubahan desain atau kontrak.
-   Kode frontend dipush pada commit `e212646` di branch
    `feat/mitra-app`; hash remote sama dengan lokal. `flutter analyze`
    bersih dan `flutter test` dari `apps/mitra_app` seluruhnya lulus
    (1 test).

## 5. Log perubahan (CHANGE REQUEST)

Perubahan terhadap dokumen desain, skema, atau kontrak. Status:
DISETUJUI = sudah masuk kode atau disetujui pemilik proyek, USULAN =
menunggu kelompok.

  ----------------------------------------------------------------------------------------------------------------------------------
  ID          Tanggal      Area             Perubahan                                               Alasan               Status
  ----------- ------------ ---------------- ------------------------------------------------------- -------------------- -----------
  CR-01       2026-11-09   Skema `orders`   Tambah `cancelled_at`, `cancel_reason`, `cancelled_by`, Tanpa ini tidak ada  DISETUJUI
                           (G-01)           `completed_at` lewat migration baru                     catatan              (pemilik
                                                                                                    siapa/kapan/kenapa   proyek)
                                                                                                    batal, dan           
                                                                                                    auto-complete (D-07) 
                                                                                                    tidak punya waktu    
                                                                                                    acuan                

  CR-02       2026-11-09   Skema `payments` Tambah `gateway_payload` (JSON), `refund_amount`,       B-7 mewajibkan       DISETUJUI
                           (G-02)           `refunded_at` lewat migration baru                      payload mentah       (pemilik
                                                                                                    webhook disimpan,    proyek)
                                                                                                    dan D-08 butuh       
                                                                                                    catatan refund       

  CR-03       2026-11-09   Kontrak §4 objek `service.name` jadi `service.service_name`,             Seragam dengan       DISETUJUI
                           Order            `property_size.name` jadi `property_size.size_name`     katalog yang sudah   
                                                                                                    jalan dan nama kolom 
                                                                                                    DB                   

  CR-04       2026-11-09   Kontrak §2 Auth  Register customer/mitra mengembalikan `token` + `user`  Aplikasi tidak perlu DISETUJUI
                                            (201). Mitra punya `mitra_id` di `user` dan di level    login ulang setelah  
                                            `data`                                                  daftar               

  CR-05       2026-11-09   Kontrak §2 Auth  Akun `admin` ditolak login lewat API (403)              Admin memakai Blade  DISETUJUI
                                                                                                    (G-04)               

  CR-06       2026-11-09   Kontrak §1       Key `errors` untuk field bersarang berbentuk datar      Perilaku bawaan      DISETUJUI
                                            bertitik, contoh `"address.label"`                      validator Laravel    

  CR-07       2026-11-09   Dokumen desain   Migration `users` bawaan Laravel diganti sesuai desain; Login memakai        DISETUJUI
                           §13 `users`      `password_reset_tokens` dihapus                         telepon, tidak ada   
                                                                                                    reset lewat email    

  CR-08       2026-11-08   Stack            Laravel 10.50.4 (bukan 12), dipasang dengan             Laravel 10 sudah     DISETUJUI
                                            `--no-security-blocking`;                               EOL. Hanya untuk     
                                            `policy.advisories.block=false` di `composer.json`      demo lokal dan       
                                                                                                    Sandbox, jangan      
                                                                                                    di-deploy publik     

  CR-09       2026-11-09   Kontrak §3       `data` berupa daftar `[ {start, end, available} ]`,     Lebih sederhana      DISETUJUI
                           `/slots`         bukan objek                                             untuk Flutter        

  CR-10       2026-11-09   D-13 throttle    Hanya percobaan gagal yang dihitung, 5 per menit per    Polling tracking     DISETUJUI
                           guest            IP+kode                                                 guest tidak boleh    
                                                                                                    kena 429             

  CR-11       2026-11-09   Kontrak §4       409 jika slot tanpa mitra eligible atau pesanan identik Cegah dobel-tap dan  DISETUJUI
                           `POST /orders`   masih berjalan; 422 jika bukan slot valid atau terlalu  slot kosong          
                                            dekat                                                                        

  CR-12       2026-11-10   Kontrak §5       `PUT /mitra/availabilities` memakai body                Body array polos     DISETUJUI
                           jasa + jadwal    `{ "availabilities": [...] }` (replace-all, array       tidak bisa           
                           mitra            kosong = hapus semua). `PUT /mitra/services` memakai    divalidasi rapi dan  
                                            `{ "service_ids": [...] }` dan membalas bentuk yang     tidak extensible     
                                            sama dengan GET                                                              

  CR-13       2026-11-10   Kontrak §5/§10.6 Isi offer: `attempt_id`, `attempt_number`,              Menetapkan D-12      DISETUJUI
                           offers           `offered_at`, `expires_at`, jasa, ukuran,               dalam bentuk yang    
                                            `scheduled_at`, `duration_minutes`, add-on (nama +      bisa diuji           
                                            jumlah), `pricing`. Accept membalas objek Order                              
                                            `waiting_payment`. Kode error accept/reject: 403 belum                       
                                            approved, 404 bukan milik mitra, 409 tidak                                   
                                            berlaku/diambil/bentrok                                                      

  CR-14       2026-11-10   Kontrak §10.8    Rute `/dev/orders/{kode}/simulate-paid` **selalu        Supaya bisa dites    DISETUJUI
                           simulate-paid    terdaftar**; `DevPaymentService` membalas 404 jika      otomatis; versi      
                                            `APP_ENV` bukan `local`. Payment memakai                "rute hanya          
                                            `gateway=simulator`, `gateway_order_id={kode}-DEV1`     terdaftar di local"  
                                                                                                    tidak bisa dites di  
                                                                                                    lingkungan testing   

  CR-15       2026-11-10   Kontrak §10.9    `GET /orders/{kode}/payment` membalas                   Flutter perlu bentuk DISETUJUI
                           payment + cancel `{order_code, order_status, tracking_stage, payment}`   tetap untuk polling, 
                                            dengan `payment: null` jika belum ada transaksi (bukan  dan aturan           
                                            404). Cancel: boleh dari `searching_mitra`..`arrived`;  pembatalan harus     
                                            payment `paid` → `refund_pending` + `refund_amount`     eksplisit            
                                            penuh; payment `pending` tidak diubah (D-22); offer                          
                                            menggantung ikut ditutup                                                     
  ----------------------------------------------------------------------------------------------------------------------------------

Belum ada CR yang berstatus USULAN. Keputusan USULAN yang menunggu
kelompok ada di §1 (semua kecuali D-12).

<!-- ===== FILE: docs/Dokumentasi_Sistem_Marketplace_Jasa_Rumahan.md ===== -->
# Dokumentasi Sistem dan Struktur Database

## Aplikasi Marketplace Jasa Rumahan

**Status dokumen:** Rancangan awal yang telah disepakati kelompok\
**Database:** MySQL\
**Backend:** Laravel REST API\
**Aplikasi mobile:** Flutter (Customer dan Mitra)\
**Admin dashboard:** Laravel Blade

------------------------------------------------------------------------

## Daftar Isi

1.  [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2.  [Teknologi yang Digunakan](#2-teknologi-yang-digunakan)
3.  [Role dan Hak Akses](#3-role-dan-hak-akses)
4.  [Konsep Mitra dan Jasa](#4-konsep-mitra-dan-jasa)
5.  [Aturan Bisnis Pemesanan](#5-aturan-bisnis-pemesanan)
6.  [Desain Customer App](#6-desain-customer-app)
7.  [Desain Mitra App](#7-desain-mitra-app)
8.  [Desain Admin Dashboard](#8-desain-admin-dashboard)
9.  [Harga, Jadwal, dan Pencarian
    Mitra](#9-harga-jadwal-dan-pencarian-mitra)
10. [Pembayaran dan Penyelesaian
    Pesanan](#10-pembayaran-dan-penyelesaian-pesanan)
11. [Status Pesanan dan Status
    Pembayaran](#11-status-pesanan-dan-status-pembayaran)
12. [Arsitektur dan Alur Data
    Sistem](#12-arsitektur-dan-alur-data-sistem)
13. [Rancangan Struktur Database](#13-rancangan-struktur-database)
14. [Relasi Antar Tabel dan ERD](#14-relasi-antar-tabel-dan-erd)
15. [Aturan Teknis Database](#15-aturan-teknis-database)
16. [Batasan MVP dan Pengembangan
    Berikutnya](#16-batasan-mvp-dan-pengembangan-berikutnya)
17. [Prioritas Implementasi](#17-prioritas-implementasi)
18. [Keputusan Sistem yang Telah
    Disepakati](#18-keputusan-sistem-yang-telah-disepakati)

------------------------------------------------------------------------

## 1. Gambaran Umum Sistem

Aplikasi ini merupakan **marketplace jasa rumahan** yang menghubungkan
customer dengan mitra penyedia layanan rumah tangga. Customer dapat
memilih jasa, menentukan ukuran properti, menambahkan layanan tambahan,
memilih jadwal, lalu membuat pesanan. Sistem akan mencarikan mitra yang
menyediakan jasa tersebut.

Contoh jasa utama:

-   Home Cleaning (pembersihan rumah).
-   Lawn Mowing (pemotongan rumput).
-   Tree Trimming (pemangkasan pohon).
-   Jasa perawatan dan perbaikan rumah lainnya.

Aplikasi memiliki tiga bagian utama:

1.  **Customer App:** aplikasi mobile untuk customer.
2.  **Mitra App:** aplikasi mobile untuk mitra penyedia jasa.
3.  **Admin Dashboard:** dashboard web untuk mengelola pengguna, mitra,
    jasa, harga, pesanan, dan transaksi.

Satu backend Laravel dan satu database MySQL digunakan bersama oleh
kedua aplikasi mobile dan dashboard admin.

### Tujuan sistem

-   Memudahkan customer menemukan dan memesan jasa rumahan.
-   Memungkinkan mitra memilih jenis jasa yang ingin ditawarkan.
-   Mencocokkan pesanan dengan mitra yang sesuai dan tersedia.
-   Mengelola harga dan katalog jasa melalui admin.
-   Menyimpan data pesanan, pembayaran, dan riwayat status secara
    terstruktur.

------------------------------------------------------------------------

## 2. Teknologi yang Digunakan

  -----------------------------------------------------------------------
  Komponen                Teknologi               Fungsi
  ----------------------- ----------------------- -----------------------
  Customer App            Flutter                 Aplikasi mobile
                                                  customer

  Mitra App               Flutter                 Aplikasi mobile mitra

  Admin Dashboard         Laravel Blade           Dashboard pengelolaan
                                                  sistem

  Backend/API             Laravel                 Logika bisnis dan REST
                                                  API

  Database                MySQL                   Penyimpanan data sistem

  Komunikasi API          REST API                Pertukaran data antara
                                                  Flutter dan backend

  HTTP Client             Dio                     Mengirim request API
                                                  dari Flutter

  Payment Gateway         Midtrans                Memproses pembayaran
                                                  customer

  API Testing             Postman                 Menguji endpoint API
  -----------------------------------------------------------------------

**Catatan implementasi:** Laravel Sanctum dapat digunakan untuk
autentikasi token pada aplikasi mobile. Token dapat disimpan di Flutter
menggunakan Shared Preferences atau penyimpanan aman yang sesuai.
Dashboard Blade dapat menggunakan autentikasi web berbasis session.

------------------------------------------------------------------------

## 3. Role dan Hak Akses

Sistem memiliki tiga role utama.

### 3.1 Customer

Customer dapat:

-   Melihat halaman utama dan katalog jasa.
-   Melihat detail jasa dan harga.
-   Memilih ukuran properti dan add-on.
-   Memilih tanggal serta slot waktu pengerjaan.
-   Membuat pesanan sebagai guest atau pengguna terdaftar.
-   Melakukan pembayaran melalui Midtrans.
-   Melihat status dan riwayat pesanan sesuai jenis akunnya.
-   Mengonfirmasi pekerjaan telah selesai.

### 3.2 Mitra

Mitra dapat:

-   Mendaftar dan login.
-   Melengkapi profil mitra.
-   Memilih lebih dari satu jenis jasa yang ingin ditawarkan.
-   Mengatur ketersediaan dan jadwal operasional.
-   Melihat pesanan yang ditawarkan kepadanya.
-   Menerima atau menolak pesanan.
-   Memperbarui status perjalanan dan pengerjaan.
-   Melihat riwayat pesanan yang pernah ditangani.

Mitra hanya boleh menerima pesanan dari jasa yang terdaftar pada profil
layanannya dan sesuai dengan ketersediaan jadwal.

### 3.3 Admin

Admin dapat:

-   Mengelola akun customer dan mitra.
-   Mengelola kategori dan katalog jasa.
-   Mengelola jenis serta ukuran properti.
-   Mengatur harga jasa berdasarkan ukuran properti.
-   Mengelola add-on dan harganya.
-   Melihat dan memantau pesanan.
-   Melihat transaksi dan status pembayaran.
-   Mengelola status akun serta, apabila diaktifkan, verifikasi mitra.

------------------------------------------------------------------------

## 4. Konsep Mitra dan Jasa

Konsep pemilihan layanan mitra mengadaptasi gagasan marketplace seperti
Gojek: mitra dapat memilih layanan apa saja yang ingin disediakan.
Sistem tidak menganggap semua mitra otomatis menyediakan seluruh jenis
jasa.

Satu mitra dapat menyediakan banyak jasa, dan satu jasa dapat disediakan
oleh banyak mitra. Relasi ini adalah **many-to-many** dan disimpan pada
tabel penghubung `mitra_services`.

Contoh:

  Mitra     Jasa yang Disediakan
  --------- -------------------------------------------
  Mitra A   Home Cleaning, Lawn Mowing
  Mitra B   Home Cleaning, Tree Trimming
  Mitra C   Lawn Mowing, Tree Trimming, Home Cleaning

Ketentuan:

-   Mitra memilih jasa dari katalog yang disediakan sistem.
-   Mitra hanya ditawari pesanan untuk jasa yang telah dipilihnya.
-   Mitra dapat menonaktifkan jasa yang tidak lagi ingin ditawarkan.
-   Tidak ada batas maksimal jumlah jasa yang dipilih mitra untuk MVP.
-   Status verifikasi dan ketersediaan mitra tetap diperiksa sebelum
    pesanan ditawarkan.

------------------------------------------------------------------------

## 5. Aturan Bisnis Pemesanan

Aturan utama pemesanan adalah:

> **1 Customer → Banyak Pesanan**\
> **1 Pesanan → 1 Jasa Utama → 1 Mitra**\
> **1 Mitra → Banyak Jasa**\
> **1 Jasa → Banyak Mitra**

### 5.1 Pesanan lebih dari satu jasa

Jika customer ingin memesan dua jasa utama, sistem membuat dua pesanan
terpisah.

Contoh customer memesan Home Cleaning dan Lawn Mowing:

  Kode Pesanan   Customer     Jasa Utama      Mitra
  -------------- ------------ --------------- ---------
  #001           Customer A   Home Cleaning   Mitra X
  #002           Customer A   Lawn Mowing     Mitra Y

Dua pesanan tersebut dapat ditangani oleh dua mitra berbeda. Secara
teori, mitra yang sama juga dapat menerima keduanya apabila menyediakan
kedua jasa dan jadwalnya memungkinkan. Namun, setiap jasa tetap menjadi
pesanan terpisah dengan status, jadwal, serta proses pembayaran
masing-masing.

Untuk MVP, customer melakukan checkout untuk setiap jasa utama secara
terpisah.

### 5.2 Jasa utama dan add-on

Add-on merupakan layanan tambahan yang masih menjadi bagian dari satu
jasa utama, bukan jasa utama baru.

Contoh:

-   Jasa utama: Home Cleaning.
-   Add-on: Pembersihan sofa.
-   Add-on: Pembersihan kulkas.

Semua pilihan tersebut masuk ke satu pesanan Home Cleaning dan ditangani
satu mitra.

Jika customer memesan Home Cleaning sekaligus Lawn Mowing, sistem
membuat dua pesanan.

### 5.3 Aturan kepemilikan pesanan

-   Satu pesanan hanya memiliki satu customer, baik customer terdaftar
    maupun guest.
-   Satu pesanan hanya memiliki satu jasa utama.
-   Satu pesanan hanya ditangani maksimal satu mitra pada satu waktu.
-   `mitra_id` pada pesanan dapat kosong selama sistem masih mencari
    mitra.
-   Setelah mitra menerima pesanan, sistem menetapkan mitra tersebut
    pada pesanan.
-   Satu mitra boleh menangani banyak pesanan selama jadwalnya tidak
    bentrok dan kapasitasnya memungkinkan.

------------------------------------------------------------------------

## 6. Desain Customer App

### 6.1 Guest Customer

Customer dapat langsung masuk ke halaman utama tanpa diwajibkan login
atau registrasi.

Guest dapat:

-   Melihat halaman utama.
-   Melihat katalog dan detail jasa.
-   Memilih jasa dan add-on.
-   Memilih jadwal.
-   Mengisi nama, nomor telepon, dan alamat secara manual.
-   Membuat pesanan.
-   Melacak pesanan menggunakan kode pesanan dan nomor telepon.

Guest tidak memiliki akun dan tidak dapat mengakses riwayat akun seperti
customer terdaftar. Data kontak dan alamat guest disimpan sebagai
snapshot pada pesanan.

### 6.2 Registered Customer

Registrasi menggunakan:

-   Nama lengkap.
-   Nomor telepon.
-   Alamat utama.
-   Password dan konfirmasi password.

Login menggunakan nomor telepon dan password.

Ketika checkout:

-   Nama, nomor telepon, dan alamat dapat diisi otomatis dari profil.
-   Customer tetap dapat mengubah detail kontak atau alamat untuk
    pesanan tersebut.
-   Perubahan alamat checkout tidak otomatis mengganti alamat utama pada
    profil.
-   Untuk MVP, satu alamat utama cukup; struktur database dapat
    mendukung beberapa alamat di masa mendatang.

### 6.3 Alur pemesanan customer

1.  Customer membuka aplikasi dan masuk ke halaman utama.
2.  Customer memilih satu jasa utama.
3.  Customer memilih jenis atau ukuran properti.
4.  Customer memilih add-on jika diperlukan.
5.  Customer memilih tanggal dan slot waktu.
6.  Sistem menghitung harga jasa dan add-on.
7.  Customer mengisi atau memeriksa nama, nomor telepon, serta alamat.
8.  Customer memeriksa ringkasan pesanan.
9.  Customer mengirim pesanan.
10. Sistem mencari mitra yang sesuai.
11. Mitra menerima pesanan.
12. Customer melakukan pembayaran melalui Midtrans.
13. Backend memverifikasi pembayaran.
14. Setelah pembayaran berhasil, mitra dapat berangkat ke lokasi.
15. Mitra mengerjakan pesanan dan menandai pekerjaan selesai.
16. Customer memeriksa hasil pekerjaan dan mengonfirmasi penyelesaian.

Untuk jasa utama lain, customer membuat pesanan baru dengan alur yang
sama.

------------------------------------------------------------------------

## 7. Desain Mitra App

Fitur utama Mitra App:

-   Registrasi dan login.
-   Melengkapi profil mitra.
-   Memilih jasa yang ingin disediakan.
-   Mengatur ketersediaan dan jadwal operasional.
-   Melihat pesanan yang ditawarkan.
-   Melihat detail jasa, jadwal, dan alamat customer.
-   Menerima atau menolak pesanan.
-   Mengubah status menjadi sedang menuju lokasi.
-   Mengonfirmasi sudah tiba di lokasi.
-   Memulai pengerjaan.
-   Menandai pekerjaan selesai.
-   Melihat riwayat pesanan.

Mitra tidak boleh memulai perjalanan atau pengerjaan berdasarkan pesanan
yang belum memenuhi kondisi pembayaran berhasil, sesuai alur MVP yang
disepakati. Backend menjadi sumber kebenaran status pembayaran; aplikasi
tidak boleh hanya mengandalkan hasil redirect dari halaman pembayaran.

------------------------------------------------------------------------

## 8. Desain Admin Dashboard

Admin Dashboard dibuat menggunakan Laravel Blade, bukan aplikasi Flutter
ketiga.

Fitur yang direncanakan:

-   Dashboard ringkasan.
-   Manajemen akun customer.
-   Manajemen akun mitra.
-   Manajemen profil dan status mitra.
-   Manajemen kategori jasa.
-   Manajemen katalog jasa.
-   Manajemen jenis dan ukuran properti.
-   Manajemen harga jasa.
-   Manajemen add-on dan harga add-on.
-   Melihat daftar pesanan.
-   Melihat transaksi dan status pembayaran.

Admin mengubah harga melalui dashboard. Perubahan harga tidak memerlukan
perubahan kode program.

------------------------------------------------------------------------

## 9. Harga, Jadwal, dan Pencarian Mitra

### 9.1 Sistem harga

Harga jasa ditentukan berdasarkan jasa utama dan ukuran properti melalui
tabel `service_prices`.

Contoh ilustrasi:

  Jasa            Ukuran Properti         Harga
  --------------- ----------------- -----------
  Home Cleaning   Rumah Kecil         Rp100.000
  Home Cleaning   Rumah Sedang        Rp150.000
  Home Cleaning   Rumah Besar         Rp200.000

Harga add-on disimpan pada tabel `service_addons`.

Saat pesanan dibuat, harga jasa, harga add-on, dan total pembayaran
disalin ke tabel transaksi sebagai snapshot. Dengan demikian, perubahan
harga di kemudian hari tidak mengubah nilai pesanan lama.

### 9.2 Sistem jadwal

-   Jam operasional layanan yang direncanakan: 08.00--20.00.
-   Customer memilih tanggal dan slot waktu yang disediakan aplikasi.
-   Customer tidak memasukkan waktu secara bebas.
-   Sistem memeriksa jadwal operasional mitra dan pesanan aktif agar
    tidak terjadi bentrok.
-   Interval slot dan aturan durasi perlu ditetapkan saat implementasi.

Keterangan pada halaman pemesanan:

> Estimasi pengerjaan paling lama 1--2 jam setelah mitra tiba di lokasi.

Keterangan tersebut merupakan estimasi, bukan jaminan. Durasi aktual
dapat berbeda berdasarkan ukuran properti dan kombinasi layanan.

### 9.3 Pencarian mitra

Sistem mencari mitra berdasarkan beberapa kondisi:

1.  Mitra memiliki jasa yang sesuai pada `mitra_services`.
2.  Jasa yang ditawarkan mitra berstatus aktif.
3.  Profil mitra memenuhi status yang disyaratkan sistem.
4.  Mitra sedang tersedia.
5.  Jadwal mitra sesuai dengan waktu pesanan.
6.  Mitra tidak memiliki pesanan lain yang menyebabkan bentrok jadwal.

Alur pencarian:

1.  Pesanan dibuat dengan status pencarian mitra.
2.  Sistem menawarkan pesanan ke mitra yang memenuhi syarat.
3.  Mitra menerima atau menolak.
4.  Jika ditolak atau tidak merespons sampai batas waktu, sistem mencoba
    mitra lain.
5.  Setiap percobaan dicatat pada `order_mitra_attempts`.
6.  Jika tidak ada mitra yang menerima setelah batas percobaan, pesanan
    dibatalkan.

Penetapan mitra harus ditangani secara aman di backend agar dua mitra
tidak sama-sama berhasil mengambil pesanan yang sama akibat request
bersamaan.

------------------------------------------------------------------------

## 10. Pembayaran dan Penyelesaian Pesanan

### 10.1 Pembayaran

Untuk MVP, pembayaran menggunakan Midtrans Payment Gateway. COD belum
termasuk versi awal.

Alur pembayaran:

1.  Customer membuat pesanan.
2.  Sistem mencari mitra.
3.  Mitra menerima pesanan.
4.  Customer menerima opsi pembayaran melalui Midtrans.
5.  Backend membuat transaksi dan menyimpan data pembayaran.
6.  Customer menyelesaikan pembayaran.
7.  Midtrans mengirim notifikasi/webhook ke backend.
8.  Backend memverifikasi notifikasi dan memperbarui status pembayaran.
9.  Jika pembayaran berhasil, mitra dapat berangkat ke lokasi.

**Penting:** Status pembayaran harus diverifikasi melalui
notifikasi/webhook Midtrans di backend. Redirect kembali ke aplikasi
saja tidak cukup sebagai bukti pembayaran berhasil.

### 10.2 Pembayaran bukan pencairan dana mitra

Midtrans memproses pembayaran customer kepada merchant/platform. Proses
pencairan dana dari platform kepada mitra merupakan proses terpisah.

Untuk MVP:

-   Sistem mencatat pembayaran customer.
-   Sistem mencatat status penyelesaian pesanan.
-   Saldo mitra dan pencairan dana otomatis belum dibuat.
-   Pencairan kepada mitra dapat dikelola terpisah terlebih dahulu.

Jangan mengasumsikan dana otomatis diteruskan kepada mitra saat customer
menekan tombol konfirmasi selesai.

### 10.3 Pembatalan dan pengembalian dana

-   Jika pesanan belum dibayar, pesanan dapat dibatalkan tanpa refund.
-   Jika pembayaran sudah berhasil lalu pesanan dibatalkan, sistem
    mengajukan refund sesuai dukungan metode pembayaran dan ketentuan
    Midtrans.
-   Status refund dicatat pada data pembayaran.
-   Jika refund otomatis tidak tersedia atau gagal, admin menangani
    proses lanjutan secara manual.

Dukungan refund dan waktu proses dapat berbeda menurut metode
pembayaran; jangan menganggap semua metode mendukung refund instan.

### 10.4 Konfirmasi penyelesaian pekerjaan

1.  Mitra menekan tombol **Tugas Selesai**.
2.  Status pesanan menjadi menunggu konfirmasi customer atau
    `mitra_completed`.
3.  Customer memeriksa hasil pekerjaan.
4.  Customer menekan tombol **Konfirmasi Selesai**.
5.  Sistem mengubah status pesanan menjadi `completed`.

Pesanan tidak langsung dianggap selesai hanya karena mitra menekan
tombol penyelesaian.

------------------------------------------------------------------------

## 11. Status Pesanan dan Status Pembayaran

Status pesanan dan status pembayaran merupakan dua hal berbeda dan harus
disimpan terpisah.

### 11.1 Status pesanan

Status internal yang direncanakan:

  -----------------------------------------------------------------------
  Status                              Arti
  ----------------------------------- -----------------------------------
  `pending`                           Pesanan baru dibuat

  `searching_mitra`                   Sistem sedang mencari mitra

  `waiting_payment`                   Mitra menerima pesanan dan customer
                                      perlu membayar

  `confirmed`                         Pembayaran berhasil dan pesanan
                                      dikonfirmasi

  `on_the_way`                        Mitra sedang menuju lokasi

  `arrived`                           Mitra sudah tiba di lokasi

  `in_progress`                       Pengerjaan sedang berlangsung

  `mitra_completed`                   Mitra menandai pekerjaan selesai

  `completed`                         Customer telah mengonfirmasi
                                      pekerjaan selesai

  `cancelled`                         Pesanan dibatalkan
  -----------------------------------------------------------------------

Nama status dapat disesuaikan saat implementasi, tetapi alur bisnisnya
harus tetap konsisten.

### 11.2 Tampilan tracking customer

Customer melihat tiga tahap utama:

1.  **Pesanan Dikonfirmasi:** mitra menerima pesanan dan pembayaran
    berhasil.
2.  **Pengerjaan:** mitra menuju lokasi, tiba, dan mulai bekerja.
3.  **Selesai:** mitra menandai pekerjaan selesai dan customer
    mengonfirmasi.

Status internal yang lebih rinci tetap disimpan di database.

### 11.3 Status pembayaran

Status pembayaran yang direncanakan:

-   `pending`
-   `paid`
-   `failed`
-   `expired`
-   `refund_pending`
-   `refunded`
-   `refund_failed`

Status pembayaran disimpan pada tabel `payments`, bukan digabung ke
`orders.order_status`.

------------------------------------------------------------------------

## 12. Arsitektur dan Alur Data Sistem

### 12.1 Arsitektur umum

``` mermaid
flowchart TD
    C[Customer App - Flutter] --> API[Laravel REST API]
    M[Mitra App - Flutter] --> API
    A[Admin Dashboard - Laravel Blade] --> API
    API --> DB[(MySQL Database)]
    API <--> PG[Midtrans Payment Gateway]
    PG -->|Payment Notification / Webhook| API
```

Customer App dan Mitra App berkomunikasi dengan backend Laravel melalui
REST API menggunakan Dio. Laravel mengakses MySQL untuk membaca dan
menyimpan data. Admin menggunakan dashboard Laravel Blade. Backend
berkomunikasi dengan Midtrans untuk transaksi dan menerima notifikasi
pembayaran.

### 12.2 Alur data pembuatan pesanan

1.  Aplikasi mengirim data pesanan ke REST API.
2.  Backend memvalidasi jasa, ukuran properti, add-on, harga, alamat,
    dan jadwal.
3.  Backend menghitung ulang total harga; jangan hanya mempercayai total
    yang dikirim aplikasi.
4.  Backend menyimpan pesanan pada `orders`.
5.  Backend menyimpan add-on pada `order_addons`.
6.  Backend mencari mitra melalui `mitra_services` dan memeriksa
    ketersediaan.
7.  Backend mencatat penawaran mitra pada `order_mitra_attempts`.
8.  Setelah mitra menerima, backend menetapkan `orders.mitra_id`.
9.  Backend membuat transaksi Midtrans dan menyimpan data pada
    `payments`.
10. Backend memperbarui status berdasarkan notifikasi pembayaran.
11. Perubahan status pesanan dicatat pada `order_status_histories`.

### 12.3 Contoh kelompok endpoint API

Daftar berikut merupakan kelompok endpoint yang dapat dikembangkan;
bukan daftar endpoint final.

  Kelompok         Contoh fungsi
  ---------------- -----------------------------------------------------------
  Authentication   Register, login, logout, profil
  Services         Daftar jasa, detail jasa, kategori, harga, add-on
  Customer         Alamat, profil, riwayat pesanan
  Mitra            Profil, jasa yang ditawarkan, ketersediaan, pesanan masuk
  Orders           Buat pesanan, detail, daftar, pembatalan, tracking
  Payments         Membuat transaksi, memeriksa pembayaran, webhook Midtrans
  Admin            CRUD pengguna, mitra, jasa, harga, pesanan, transaksi

------------------------------------------------------------------------

## 13. Rancangan Struktur Database

Database dirancang dengan **16 tabel**. Nama tabel menggunakan istilah
`mitra`, bukan `provider`, agar konsisten dengan istilah di aplikasi.

### Konvensi umum

-   Seluruh primary key menggunakan `BIGINT UNSIGNED AUTO_INCREMENT`.
-   Foreign key menggunakan tipe data yang sama dengan primary key
    tujuan.
-   Kolom waktu umum menggunakan `created_at` dan `updated_at`.
-   Kolom status dapat disimpan sebagai `VARCHAR` dan divalidasi di
    aplikasi/backend agar penambahan status lebih mudah.
-   `NULL` hanya digunakan jika memang ada kondisi bisnis yang
    memperbolehkannya.

### A. Tabel pengguna dan mitra

#### 1. `users` --- data akun pengguna

Menyimpan akun Customer, Mitra, dan Admin.

  ------------------------------------------------------------------------
  Kolom              Tipe Data         Key / Constraint  Keterangan
  ------------------ ----------------- ----------------- -----------------
  `id`               BIGINT UNSIGNED   PK                ID akun

  `full_name`        VARCHAR(100)      NOT NULL          Nama lengkap

  `phone`            VARCHAR(20)       UNIQUE, NOT NULL  Nomor telepon

  `email`            VARCHAR(100)      UNIQUE, NULL      Email jika
                                                         tersedia

  `password`         VARCHAR(255)      NOT NULL          Password hash

  `role`             VARCHAR(20)       NOT NULL          `customer`,
                                                         `mitra`, atau
                                                         `admin`

  `account_status`   VARCHAR(20)       NOT NULL          `active`,
                                                         `inactive`,
                                                         `suspended`

  `created_at`       TIMESTAMP                           Waktu dibuat

  `updated_at`       TIMESTAMP                           Waktu diperbarui
  ------------------------------------------------------------------------

Catatan: Guest tidak dibuatkan akun di tabel `users`.

#### 2. `customer_addresses` --- alamat customer

Menyimpan alamat customer terdaftar. Untuk MVP satu alamat utama sudah
cukup, tetapi tabel memungkinkan pengembangan beberapa alamat.

  Kolom               Tipe Data         Key / Constraint   Keterangan
  ------------------- ----------------- ------------------ ----------------------
  `id`                BIGINT UNSIGNED   PK                 ID alamat
  `customer_id`       BIGINT UNSIGNED   FK → `users.id`    Pemilik alamat
  `address_label`     VARCHAR(50)       NOT NULL           Contoh: Rumah
  `recipient_name`    VARCHAR(100)      NOT NULL           Nama penerima
  `recipient_phone`   VARCHAR(20)       NOT NULL           Nomor penerima
  `full_address`      TEXT              NOT NULL           Alamat lengkap
  `city`              VARCHAR(100)      NULL               Kota
  `province`          VARCHAR(100)      NULL               Provinsi
  `postal_code`       VARCHAR(10)       NULL               Kode pos
  `is_primary`        BOOLEAN           DEFAULT FALSE      Penanda alamat utama
  `created_at`        TIMESTAMP                            Waktu dibuat
  `updated_at`        TIMESTAMP                            Waktu diperbarui

#### 3. `mitra_profiles` --- profil mitra

Menyimpan informasi khusus mitra yang terhubung dengan akun pada
`users`.

  -----------------------------------------------------------------------------
  Kolom                   Tipe Data         Key / Constraint  Keterangan
  ----------------------- ----------------- ----------------- -----------------
  `id`                    BIGINT UNSIGNED   PK                ID profil mitra

  `user_id`               BIGINT UNSIGNED   FK → `users.id`,  Akun pemilik
                                            UNIQUE            profil

  `bio`                   TEXT              NULL              Deskripsi mitra

  `service_area`          VARCHAR(255)      NULL              Wilayah layanan

  `verification_status`   VARCHAR(20)       NOT NULL          `pending`,
                                                              `approved`,
                                                              `rejected`

  `is_available`          BOOLEAN           DEFAULT TRUE      Ketersediaan
                                                              menerima pesanan

  `created_at`            TIMESTAMP                           Waktu dibuat

  `updated_at`            TIMESTAMP                           Waktu diperbarui
  -----------------------------------------------------------------------------

#### 4. `mitra_availabilities` --- jadwal operasional mitra

Menyimpan hari dan jam operasional mitra.

  ---------------------------------------------------------------------------
  Kolom             Tipe Data         Key / Constraint      Keterangan
  ----------------- ----------------- --------------------- -----------------
  `id`              BIGINT UNSIGNED   PK                    ID jadwal

  `mitra_id`        BIGINT UNSIGNED   FK →                  Mitra pemilik
                                      `mitra_profiles.id`   jadwal

  `day_of_week`     TINYINT UNSIGNED  NOT NULL              Hari operasional,
                                                            misalnya 0--6

  `start_time`      TIME              NOT NULL              Jam mulai

  `end_time`        TIME              NOT NULL              Jam selesai

  `is_active`       BOOLEAN           DEFAULT TRUE          Status jadwal
  ---------------------------------------------------------------------------

### B. Tabel jasa dan harga

#### 5. `service_categories` --- kategori jasa

Menyimpan kategori utama jasa.

  Kolom             Tipe Data         Key / Constraint   Keterangan
  ----------------- ----------------- ------------------ --------------------
  `id`              BIGINT UNSIGNED   PK                 ID kategori
  `category_name`   VARCHAR(100)      NOT NULL           Nama kategori
  `description`     TEXT              NULL               Deskripsi kategori
  `is_active`       BOOLEAN           DEFAULT TRUE       Status kategori

Contoh: Kebersihan Rumah, Perawatan Taman, Perawatan Pohon.

#### 6. `services` --- daftar jasa utama

Menyimpan katalog jasa yang dapat dipesan customer.

  ----------------------------------------------------------------------------------
  Kolom                Tipe Data         Key / Constraint          Keterangan
  -------------------- ----------------- ------------------------- -----------------
  `id`                 BIGINT UNSIGNED   PK                        ID jasa

  `category_id`        BIGINT UNSIGNED   FK →                      Kategori jasa
                                         `service_categories.id`   

  `service_name`       VARCHAR(100)      NOT NULL                  Nama jasa

  `description`        TEXT              NULL                      Deskripsi jasa

  `duration_minutes`   INT UNSIGNED      NULL                      Estimasi durasi

  `is_active`          BOOLEAN           DEFAULT TRUE              Status jasa

  `created_at`         TIMESTAMP                                   Waktu dibuat

  `updated_at`         TIMESTAMP                                   Waktu diperbarui
  ----------------------------------------------------------------------------------

Contoh: Home Cleaning, Lawn Mowing, Tree Trimming.

#### 7. `mitra_services` --- relasi mitra dan jasa

Tabel penghubung many-to-many antara `mitra_profiles` dan `services`.

  ---------------------------------------------------------------------------
  Kolom             Tipe Data         Key / Constraint      Keterangan
  ----------------- ----------------- --------------------- -----------------
  `id`              BIGINT UNSIGNED   PK                    ID relasi

  `mitra_id`        BIGINT UNSIGNED   FK →                  Mitra
                                      `mitra_profiles.id`   

  `service_id`      BIGINT UNSIGNED   FK → `services.id`    Jasa yang
                                                            disediakan

  `status`          VARCHAR(20)       NOT NULL              `active` atau
                                                            `inactive`

  `created_at`      TIMESTAMP                               Waktu ditambahkan
  ---------------------------------------------------------------------------

**Constraint penting:** pasangan `mitra_id` dan `service_id` harus
UNIQUE agar mitra tidak mendaftarkan jasa yang sama berkali-kali.

#### 8. `property_types` --- jenis properti

Menyimpan pilihan jenis properti customer.

  -----------------------------------------------------------------------
  Kolom             Tipe Data         Key / Constraint  Keterangan
  ----------------- ----------------- ----------------- -----------------
  `id`              BIGINT UNSIGNED   PK                ID jenis properti

  `type_name`       VARCHAR(100)      NOT NULL          Contoh: Rumah,
                                                        Apartemen, Kantor

  `is_active`       BOOLEAN           DEFAULT TRUE      Status
  -----------------------------------------------------------------------

#### 9. `property_sizes` --- ukuran properti

Menyimpan pilihan ukuran berdasarkan jenis properti.

  ------------------------------------------------------------------------------
  Kolom                Tipe Data         Key / Constraint      Keterangan
  -------------------- ----------------- --------------------- -----------------
  `id`                 BIGINT UNSIGNED   PK                    ID ukuran

  `property_type_id`   BIGINT UNSIGNED   FK →                  Jenis properti
                                         `property_types.id`   

  `size_name`          VARCHAR(100)      NOT NULL              Nama ukuran

  `min_area`           DECIMAL(10,2)     NULL                  Luas minimum
                                                               dalam m²

  `max_area`           DECIMAL(10,2)     NULL                  Luas maksimum
                                                               dalam m²

  `is_active`          BOOLEAN           DEFAULT TRUE          Status
  ------------------------------------------------------------------------------

#### 10. `service_prices` --- harga jasa

Menyimpan harga jasa berdasarkan ukuran properti.

  ------------------------------------------------------------------------------
  Kolom                Tipe Data         Key / Constraint      Keterangan
  -------------------- ----------------- --------------------- -----------------
  `id`                 BIGINT UNSIGNED   PK                    ID harga

  `service_id`         BIGINT UNSIGNED   FK → `services.id`    Jasa

  `property_size_id`   BIGINT UNSIGNED   FK →                  Ukuran properti
                                         `property_sizes.id`   

  `price`              DECIMAL(12,2)     NOT NULL              Harga jasa

  `is_active`          BOOLEAN           DEFAULT TRUE          Status harga

  `created_at`         TIMESTAMP                               Waktu dibuat

  `updated_at`         TIMESTAMP                               Waktu diperbarui
  ------------------------------------------------------------------------------

**Constraint penting:** pasangan `service_id` dan `property_size_id`
dibuat UNIQUE agar satu kombinasi jasa dan ukuran memiliki satu harga
aktif. Ketika admin mengubah harga, baris harga dapat diperbarui; harga
transaksi lama tetap aman karena nilainya sudah disalin sebagai snapshot
ke tabel `orders`. Jika kelompok ingin menyimpan seluruh histori
perubahan harga master, histori tersebut dapat ditambahkan pada
pengembangan berikutnya.

#### 11. `service_addons` --- jasa tambahan

Menyimpan add-on yang hanya dapat dipilih untuk jasa utama tertentu.

  -----------------------------------------------------------------------
  Kolom             Tipe Data         Key / Constraint  Keterangan
  ----------------- ----------------- ----------------- -----------------
  `id`              BIGINT UNSIGNED   PK                ID add-on

  `service_id`      BIGINT UNSIGNED   FK →              Jasa utama
                                      `services.id`     terkait

  `addon_name`      VARCHAR(100)      NOT NULL          Nama add-on

  `description`     TEXT              NULL              Deskripsi

  `price`           DECIMAL(12,2)     NOT NULL          Harga add-on

  `is_active`       BOOLEAN           DEFAULT TRUE      Status add-on
  -----------------------------------------------------------------------

Contoh add-on Home Cleaning: Pembersihan Sofa dan Pembersihan Kulkas.

### C. Tabel pemesanan

#### 12. `orders` --- data pesanan

Tabel inti yang menyimpan setiap pesanan customer.

  --------------------------------------------------------------------------------------
  Kolom                   Tipe Data         Key / Constraint           Keterangan
  ----------------------- ----------------- -------------------------- -----------------
  `id`                    BIGINT UNSIGNED   PK                         ID pesanan

  `order_code`            VARCHAR(30)       UNIQUE, NOT NULL           Kode pesanan yang
                                                                       dapat digunakan
                                                                       untuk tracking
                                                                       guest

  `customer_id`           BIGINT UNSIGNED   FK → `users.id`, NULL      Customer
                                                                       terdaftar; NULL
                                                                       untuk guest

  `customer_address_id`   BIGINT UNSIGNED   FK →                       Referensi alamat
                                            `customer_addresses.id`,   tersimpan jika
                                            NULL                       digunakan

  `mitra_id`              BIGINT UNSIGNED   FK → `mitra_profiles.id`,  Mitra yang
                                            NULL                       ditetapkan; NULL
                                                                       saat pencarian

  `service_id`            BIGINT UNSIGNED   FK → `services.id`         Satu jasa utama

  `property_size_id`      BIGINT UNSIGNED   FK → `property_sizes.id`   Ukuran properti

  `scheduled_at`          DATETIME          NOT NULL                   Jadwal pengerjaan

  `duration_minutes`      INT UNSIGNED      NULL                       Snapshot estimasi
                                                                       durasi

  `customer_name`         VARCHAR(100)      NOT NULL                   Snapshot nama
                                                                       customer

  `customer_phone`        VARCHAR(20)       NOT NULL                   Snapshot nomor
                                                                       telepon

  `service_address`       TEXT              NOT NULL                   Snapshot alamat
                                                                       pengerjaan

  `address_notes`         TEXT              NULL                       Catatan alamat

  `service_price`         DECIMAL(12,2)     NOT NULL                   Snapshot harga
                                                                       jasa

  `addons_total`          DECIMAL(12,2)     NOT NULL                   Total snapshot
                                                                       add-on

  `total_price`           DECIMAL(12,2)     NOT NULL                   Total harga
                                                                       pesanan

  `order_status`          VARCHAR(30)       NOT NULL                   Status siklus
                                                                       pesanan

  `customer_note`         TEXT              NULL                       Catatan customer

  `created_at`            TIMESTAMP                                    Waktu dibuat

  `updated_at`            TIMESTAMP                                    Waktu diperbarui
  --------------------------------------------------------------------------------------

Catatan:

-   `customer_id` boleh NULL untuk guest.
-   `customer_address_id` boleh NULL jika guest mengisi alamat manual
    atau customer mengubah alamat khusus pesanan.
-   `mitra_id` boleh NULL ketika sistem masih mencari mitra.
-   `customer_name`, `customer_phone`, `service_address`, dan nilai
    harga adalah snapshot agar detail pesanan lama tidak berubah saat
    profil atau harga master diubah.
-   `order_code` harus unik, acak, dan tidak mudah ditebak. Untuk
    tracking guest, sistem memeriksa kombinasi kode pesanan dan nomor
    telepon serta menerapkan pembatasan percobaan.

#### 13. `order_addons` --- detail add-on pesanan

Menghubungkan pesanan dengan add-on pilihan customer.

  ------------------------------------------------------------------------------
  Kolom                Tipe Data         Key / Constraint      Keterangan
  -------------------- ----------------- --------------------- -----------------
  `id`                 BIGINT UNSIGNED   PK                    ID detail add-on

  `order_id`           BIGINT UNSIGNED   FK → `orders.id`      Pesanan

  `service_addon_id`   BIGINT UNSIGNED   FK →                  Add-on dari
                                         `service_addons.id`   katalog

  `addon_name`         VARCHAR(100)      NOT NULL              Snapshot nama
                                                               add-on

  `unit_price`         DECIMAL(12,2)     NOT NULL              Snapshot harga
                                                               satuan

  `quantity`           INT UNSIGNED      DEFAULT 1             Jumlah

  `subtotal`           DECIMAL(12,2)     NOT NULL              Snapshot subtotal
  ------------------------------------------------------------------------------

Nama dan harga add-on disalin ke tabel ini agar perubahan katalog tidak
mengubah rincian pesanan lama.

#### 14. `order_mitra_attempts` --- riwayat pencarian mitra

Mencatat mitra yang ditawari setiap pesanan.

  ----------------------------------------------------------------------------
  Kolom              Tipe Data         Key / Constraint      Keterangan
  ------------------ ----------------- --------------------- -----------------
  `id`               BIGINT UNSIGNED   PK                    ID percobaan

  `order_id`         BIGINT UNSIGNED   FK → `orders.id`      Pesanan

  `mitra_id`         BIGINT UNSIGNED   FK →                  Mitra yang
                                       `mitra_profiles.id`   ditawari

  `attempt_number`   INT UNSIGNED      NOT NULL              Urutan percobaan

  `status`           VARCHAR(20)       NOT NULL              `offered`,
                                                             `accepted`,
                                                             `rejected`,
                                                             `expired`

  `offered_at`       DATETIME          NOT NULL              Waktu penawaran

  `responded_at`     DATETIME          NULL                  Waktu respons
  ----------------------------------------------------------------------------

Constraint yang disarankan:

-   UNIQUE (`order_id`, `mitra_id`) agar mitra yang sama tidak ditawari
    berulang dalam satu proses pencarian.
-   UNIQUE (`order_id`, `attempt_number`) agar nomor percobaan tidak
    duplikat.

### D. Tabel pembayaran dan riwayat status

#### 15. `payments` --- data pembayaran

Menyimpan transaksi pembayaran customer melalui Midtrans.

  ----------------------------------------------------------------------------
  Kolom                Tipe Data         Key / Constraint  Keterangan
  -------------------- ----------------- ----------------- -------------------
  `id`                 BIGINT UNSIGNED   PK                ID pembayaran

  `order_id`           BIGINT UNSIGNED   FK → `orders.id`  Pesanan terkait

  `gateway`            VARCHAR(30)       NOT NULL          Nama payment
                                                           gateway, yaitu
                                                           Midtrans

  `gateway_order_id`   VARCHAR(100)      UNIQUE, NOT NULL  ID transaksi yang
                                                           dibuat sistem untuk
                                                           gateway

  `transaction_id`     VARCHAR(100)      NULL              ID transaksi dari
                                                           Midtrans

  `snap_token`         TEXT              NULL              Token transaksi
                                                           Snap

  `amount`             DECIMAL(12,2)     NOT NULL          Nominal pembayaran

  `payment_method`     VARCHAR(50)       NULL              Metode pembayaran

  `payment_status`     VARCHAR(30)       NOT NULL          Status
                                                           pembayaran/refund

  `paid_at`            DATETIME          NULL              Waktu pembayaran
                                                           berhasil

  `expired_at`         DATETIME          NULL              Waktu kedaluwarsa

  `created_at`         TIMESTAMP                           Waktu dibuat

  `updated_at`         TIMESTAMP                           Waktu diperbarui
  ----------------------------------------------------------------------------

Status pembayaran yang direncanakan:

-   `pending`
-   `paid`
-   `failed`
-   `expired`
-   `refund_pending`
-   `refunded`
-   `refund_failed`

Satu pesanan dapat memiliki beberapa percobaan pembayaran. Status
transaksi harus diperbarui secara idempotent berdasarkan notifikasi
gateway agar notifikasi yang dikirim berulang tidak menggandakan proses.

#### 16. `order_status_histories` --- riwayat status pesanan

Mencatat perubahan status pesanan.

  ----------------------------------------------------------------------------
  Kolom                  Tipe Data         Key / Constraint  Keterangan
  ---------------------- ----------------- ----------------- -----------------
  `id`                   BIGINT UNSIGNED   PK                ID riwayat

  `order_id`             BIGINT UNSIGNED   FK → `orders.id`  Pesanan terkait

  `changed_by_user_id`   BIGINT UNSIGNED   FK → `users.id`,  Pengguna yang
                                           NULL              melakukan
                                                             perubahan; NULL
                                                             jika sistem

  `old_status`           VARCHAR(30)       NULL              Status sebelumnya

  `new_status`           VARCHAR(30)       NOT NULL          Status baru

  `note`                 TEXT              NULL              Keterangan
                                                             perubahan

  `created_at`           TIMESTAMP                           Waktu perubahan
  ----------------------------------------------------------------------------

------------------------------------------------------------------------

## 14. Relasi Antar Tabel dan ERD

### 14.1 Ringkasan relasi

  --------------------------------------------------------------------------
  No.                     Relasi                     Jenis
  ----------------------- -------------------------- -----------------------
  1                       `users` →                  One-to-Many
                          `customer_addresses`       

  2                       `users` → `mitra_profiles` One-to-One (opsional
                                                     untuk setiap akun)

  3                       `mitra_profiles` →         One-to-Many
                          `mitra_availabilities`     

  4                       `service_categories` →     One-to-Many
                          `services`                 

  5                       `mitra_profiles` ↔         Many-to-Many
                          `services` melalui         
                          `mitra_services`           

  6                       `property_types` →         One-to-Many
                          `property_sizes`           

  7                       `services` →               One-to-Many
                          `service_prices`           

  8                       `property_sizes` →         One-to-Many
                          `service_prices`           

  9                       `services` →               One-to-Many
                          `service_addons`           

  10                      `users` → `orders`         One-to-Many;
                                                     `customer_id` dapat
                                                     NULL untuk guest

  11                      `customer_addresses` →     One-to-Many secara
                          `orders`                   referensi; FK pada
                                                     order dapat NULL

  12                      `mitra_profiles` →         One-to-Many; `mitra_id`
                          `orders`                   dapat NULL sebelum
                                                     penugasan

  13                      `services` → `orders`      One-to-Many

  14                      `property_sizes` →         One-to-Many
                          `orders`                   

  15                      `orders` → `order_addons`  One-to-Many

  16                      `orders` →                 One-to-Many
                          `order_mitra_attempts`     

  17                      `mitra_profiles` →         One-to-Many
                          `order_mitra_attempts`     

  18                      `orders` → `payments`      One-to-Many

  19                      `orders` →                 One-to-Many
                          `order_status_histories`   

  20                      `users` →                  One-to-Many; pelaku
                          `order_status_histories`   perubahan dapat NULL
                                                     jika sistem
  --------------------------------------------------------------------------

### 14.2 ERD Mermaid

``` mermaid
erDiagram
    USERS ||--o{ CUSTOMER_ADDRESSES : memiliki
    USERS ||--o| MITRA_PROFILES : memiliki
    MITRA_PROFILES ||--o{ MITRA_AVAILABILITIES : memiliki

    SERVICE_CATEGORIES ||--o{ SERVICES : mengelompokkan
    MITRA_PROFILES ||--o{ MITRA_SERVICES : menawarkan
    SERVICES ||--o{ MITRA_SERVICES : disediakan

    PROPERTY_TYPES ||--o{ PROPERTY_SIZES : memiliki
    SERVICES ||--o{ SERVICE_PRICES : memiliki
    PROPERTY_SIZES ||--o{ SERVICE_PRICES : menentukan
    SERVICES ||--o{ SERVICE_ADDONS : memiliki

    USERS o|--o{ ORDERS : membuat
    CUSTOMER_ADDRESSES o|--o{ ORDERS : referensi_alamat
    MITRA_PROFILES o|--o{ ORDERS : menangani
    SERVICES ||--o{ ORDERS : dipilih
    PROPERTY_SIZES ||--o{ ORDERS : digunakan

    ORDERS ||--o{ ORDER_ADDONS : memiliki
    SERVICE_ADDONS ||--o{ ORDER_ADDONS : dipilih

    ORDERS ||--o{ ORDER_MITRA_ATTEMPTS : pencarian
    MITRA_PROFILES ||--o{ ORDER_MITRA_ATTEMPTS : ditawari

    ORDERS ||--o{ PAYMENTS : memiliki
    ORDERS ||--o{ ORDER_STATUS_HISTORIES : mencatat
    USERS o|--o{ ORDER_STATUS_HISTORIES : mengubah
```

**Catatan ERD:** Relasi `users` ke `orders` bersifat opsional pada sisi
order karena guest tidak memiliki `customer_id`. Relasi `mitra_profiles`
ke `orders` juga opsional sebelum mitra ditemukan dan ditetapkan. Satu
order tetap hanya menyimpan satu `mitra_id` final.

------------------------------------------------------------------------

## 15. Aturan Teknis Database

1.  **Primary key dan foreign key:** semua relasi harus menggunakan tipe
    data yang konsisten.
2.  **Nominal uang:** gunakan `DECIMAL(12,2)`, bukan `FLOAT` atau
    `DOUBLE`.
3.  **Password:** simpan menggunakan hashing Laravel, bukan teks biasa.
4.  **Nomor telepon:** simpan sebagai string agar angka nol di depan
    tidak hilang.
5.  **Data snapshot:** simpan nama, telepon, alamat, nama add-on, harga,
    dan total pada pesanan agar transaksi lama tetap konsisten.
6.  **Soft delete/nonaktif:** jasa, kategori, add-on, ukuran properti,
    dan akun yang sudah dipakai transaksi sebaiknya dinonaktifkan, bukan
    dihapus permanen.
7.  **Status terpisah:** `orders.order_status` berbeda dari
    `payments.payment_status`.
8.  **Kode pesanan:** gunakan kode unik yang tidak mudah ditebak; guest
    tracking memeriksa kode pesanan dan nomor telepon.
9.  **Validasi harga:** backend menghitung ulang harga dari database.
    Jangan mempercayai nominal yang dikirim langsung oleh aplikasi
    Flutter.
10. **Validasi jadwal:** backend memeriksa jadwal operasional dan
    pesanan aktif untuk mencegah bentrok.
11. **Penetapan mitra:** gunakan transaksi database atau mekanisme
    penguncian yang sesuai agar satu pesanan tidak diambil dua mitra
    secara bersamaan.
12. **Webhook pembayaran:** verifikasi notifikasi Midtrans di backend
    dan tangani notifikasi berulang secara aman.
13. **Penghapusan data:** hindari penghapusan berantai pada data
    transaksi dan riwayat. Data master yang sudah dipakai sebaiknya
    dipertahankan.
14. **Indeks:** tambahkan indeks pada foreign key dan kolom yang sering
    dicari, seperti `order_code`, `phone`, `order_status`,
    `payment_status`, `service_id`, dan `mitra_id`.

------------------------------------------------------------------------

## 16. Batasan MVP dan Pengembangan Berikutnya

### 16.1 Fitur yang menjadi fokus MVP

-   Customer dapat masuk sebagai guest tanpa login paksa.
-   Registrasi dan login customer.
-   Registrasi dan login mitra.
-   Katalog jasa dan detail harga.
-   Mitra memilih jasa yang ingin disediakan.
-   Pemilihan jenis/ukuran properti.
-   Add-on untuk jasa utama tertentu.
-   Pemesanan satu jasa utama per pesanan.
-   Pencarian mitra dengan batas percobaan.
-   Pengaturan jadwal dasar dan pengecekan bentrok.
-   Pembayaran Midtrans.
-   Tracking status pesanan.
-   Konfirmasi penyelesaian oleh customer.
-   Admin mengelola akun, katalog, harga, pesanan, dan transaksi.

### 16.2 Fitur yang belum masuk MVP

**Prioritas pengembangan tinggi:**

-   Pembayaran COD.
-   Pelaporan masalah atau komplain.
-   Rating dan ulasan.
-   Verifikasi mitra yang lebih lengkap.

**Prioritas menengah:**

-   Saldo mitra dan pencairan dana otomatis.
-   Push notification.
-   Live chat.
-   Penanganan pembatalan yang lebih lengkap.

**Prioritas rendah:**

-   Live GPS tracking.
-   Voucher dan promosi.
-   Loyalty program.

Fitur-fitur tersebut dapat dimasukkan ke backlog setelah alur utama
aplikasi berjalan.

------------------------------------------------------------------------

## 17. Prioritas Implementasi

Urutan implementasi yang disarankan:

  --------------------------------------------------------------------------
  Tahap                   Tabel                      Fokus
  ----------------------- -------------------------- -----------------------
  1                       `users`,                   Akun dan profil
                          `customer_addresses`,      
                          `mitra_profiles`           

  2                       `service_categories`,      Katalog dan jasa mitra
                          `services`,                
                          `mitra_services`           

  3                       `property_types`,          Harga dan add-on
                          `property_sizes`,          
                          `service_prices`,          
                          `service_addons`           

  4                       `orders`, `order_addons`   Pemesanan

  5                       `mitra_availabilities`,    Jadwal dan pencarian
                          `order_mitra_attempts`     mitra

  6                       `payments`,                Pembayaran dan tracking
                          `order_status_histories`   
  --------------------------------------------------------------------------

Walaupun tabel sudah dirancang lengkap, implementasi dapat dilakukan
bertahap. Pastikan kelompok menyelesaikan alur inti terlebih dahulu
sebelum mengerjakan fitur backlog.

------------------------------------------------------------------------

## 18. Keputusan Sistem yang Telah Disepakati

Ringkasan keputusan final rancangan awal:

  -----------------------------------------------------------------------
  No.                     Keputusan               Status
  ----------------------- ----------------------- -----------------------
  1                       Customer dapat membuka  Disepakati
                          aplikasi tanpa login    
                          paksa                   

  2                       Guest dapat membuat     Disepakati
                          pesanan dan tracking    
                          dengan kode pesanan     
                          serta nomor telepon     

  3                       Customer terdaftar      Disepakati
                          dapat menggunakan data  
                          profil saat checkout    

  4                       Alamat checkout dapat   Disepakati
                          diubah tanpa mengubah   
                          alamat utama profil     

  5                       Satu mitra dapat        Disepakati
                          menyediakan banyak jasa 

  6                       Satu jasa dapat         Disepakati
                          disediakan banyak mitra 

  7                       Satu pesanan hanya      Disepakati
                          memiliki satu jasa      
                          utama                   

  8                       Satu pesanan hanya      Disepakati
                          ditangani satu mitra    

  9                       Beberapa jasa utama     Disepakati
                          yang dipesan customer   
                          menjadi beberapa        
                          pesanan terpisah        

  10                      Add-on tetap menjadi    Disepakati
                          bagian dari pesanan     
                          jasa utama              

  11                      Harga dikelola melalui  Disepakati
                          Admin Dashboard         

  12                      Sistem mencari mitra    Disepakati
                          yang sesuai dan mencoba 
                          mitra lain jika         
                          ditolak/tidak merespons 

  13                      Jika tidak ada mitra    Disepakati
                          yang menerima setelah   
                          batas percobaan,        
                          pesanan dibatalkan      

  14                      Pembayaran MVP          Disepakati
                          menggunakan Midtrans    

  15                      Status pesanan dan      Disepakati
                          pembayaran dipisahkan   

  16                      Penyelesaian pesanan    Disepakati
                          membutuhkan tindakan    
                          mitra dan konfirmasi    
                          customer                

  17                      Pencairan dana otomatis Disepakati
                          kepada mitra belum      
                          termasuk MVP            

  18                      Penamaan tabel          Disepakati
                          menggunakan `mitra`,    
                          bukan `provider`        
  -----------------------------------------------------------------------

### Kesimpulan

Sistem dirancang sebagai marketplace jasa rumahan dengan tiga role:
Customer, Mitra, dan Admin. Customer dapat memesan beberapa jasa, tetapi
setiap jasa utama diproses sebagai pesanan tersendiri dan ditangani satu
mitra. Mitra dapat memilih banyak jasa yang ingin ditawarkan. Laravel
menjadi backend dan pengelola dashboard, Flutter menjadi aplikasi
mobile, sedangkan MySQL menyimpan seluruh data sistem.

Rancangan ini menjadi dasar pembuatan ERD final, Laravel migration, REST
API, dan implementasi aplikasi. Detail teknis seperti interval slot,
batas percobaan pencarian mitra, waktu respons mitra, serta aturan
refund perlu ditetapkan sebelum fitur terkait diimplementasikan.
