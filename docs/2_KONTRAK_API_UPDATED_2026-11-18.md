<!-- Bundel kerja diperbarui 18 Nov 2026. Tidak ada perubahan endpoint, field, response, status, atau aturan kontrak API. Frontend Mitra App telah mengimplementasikan alur autentikasi berdasarkan kontrak §2; satu isu pemulihan sesi setelah aplikasi dibuka ulang masih tertunda. Isi kontrak tetap dipertahankan dari edisi 16 Nov 2026. -->
<!-- ===== FILE: docs/API_CONTRACT.md ===== -->
# API Contract: Marketplace Jasa Rumahan

> **Status: DRAFT v0.2 (10 Nov 2026).** Belum dibekukan jadi v1.0.
> Rencana bekukan: pertemuan 12 (DECISIONS §4), setelah backend lengkap
> dan direview AI kedua. **Kedudukan dokumen:** file ini adalah bundel
> kontrak API utama jika namanya mengikuti
> `2_KONTRAK_API_UPDATED_(TGL).md`. Gunakan edisi bertanggal paling
> baru, baik ditemukan di GitHub maupun GPT Project Resources. Kode
> GitHub menunjukkan implementasi aktual; bila berbeda dari kontrak ini,
> catat discrepancy dan ajukan CHANGE REQUEST---jangan mengubah kontrak
> atau kode diam-diam. Kontrak ini mengalahkan ingatan/asumsi AI. Mau
> mengubah? Ajukan CHANGE REQUEST (`AI_RULES.md` §9) dan catat di
> `DECISIONS.md` §5. Yang tertulis di §1-§9 adalah **bentuk dan aturan**
> yang harus sama di Laravel dan Flutter. Contoh JSON ada di §10. Status
> implementasi per endpoint ada di §11. **Baca §11 dulu** supaya tahu
> mana yang sudah jalan dan mana yang masih rencana.

## 1. Konvensi umum

  ----------------------------------------------------------------------------------------------------------------
  Hal                                 Aturan
  ----------------------------------- ----------------------------------------------------------------------------
  Base URL                            `{host}/api` (emulator Android: `http://10.0.2.2:8000/api`)

  Auth                                `Authorization: Bearer <token>` (Laravel Sanctum). Endpoint publik dan guest
                                      tanpa token

  Header                              Selalu kirim `Accept: application/json`

  Sukses                              `{ "success": true, "message": "...", "data": ... }`

  Gagal                               `{ "success": false, "message": "...", "errors": { "field": ["pesan"] } }`
                                      (`errors` hanya untuk 422)

  Status code                         200 OK · 201 dibuat · 401 belum login/token salah · 403 role/akses salah ·
                                      404 tidak ada (juga untuk guest tidak cocok) · 409 konflik state (jadwal
                                      bentrok, order sudah diambil mitra lain, transisi tidak valid) · 422
                                      validasi · 429 throttle

  Pagination                          `?page=1&per_page=15` → `data: [...]`,
                                      `meta: { current_page, last_page, per_page, total }`. `per_page` dibatasi
                                      1-50

  Waktu                               ISO 8601 + offset WIB, mis. `2026-11-20T10:00:00+07:00`

  Uang                                **integer rupiah** (`150000`), bukan string/float

  ID                                  integer. `mitra_id` = `mitra_profiles.id`; `user_id` = `users.id` (jangan
                                      tertukar)

  Telepon                             string format lokal `08…` (normalisasi `+62`/`62` → `0`)

  Penamaan field                      `snake_case`, sama dengan nama kolom DB bila ada padanannya

  Throttle                            `POST /auth/login` 10/menit · `POST /orders` 20/menit · akses guest: hanya
                                      percobaan **gagal** yang dihitung, 5/menit per IP + `order_code` (CR-10)
  ----------------------------------------------------------------------------------------------------------------

## 2. Auth

  ------------------------------------------------------------------------------------------
  Method            Path                        Akses             Isi utama
  ----------------- --------------------------- ----------------- --------------------------
  POST              `/auth/register/customer`   publik            `full_name`, `phone`,
                                                                  `password`,
                                                                  `password_confirmation`,
                                                                  `address` (label,
                                                                  full_address, city?,
                                                                  province?, postal_code?)

  POST              `/auth/register/mitra`      publik            `full_name`, `phone`,
                                                                  `password`,
                                                                  `password_confirmation`,
                                                                  `bio?`, `service_area?`

  POST              `/auth/login`               publik            `phone`, `password` →
                                                                  `token`, `user`,
                                                                  `mitra_id?`

  POST              `/auth/logout`              login             

  GET               `/auth/me`                  login             profil + `mitra_id` jika
                                                                  mitra
  ------------------------------------------------------------------------------------------

Catatan: register mitra membuat `users` + `mitra_profiles` dalam satu
transaksi. `verification_status` awal mengikuti `MITRA_AUTO_APPROVE`
(DECISIONS D-15). Register mengembalikan token (CR-04), admin ditolak
login lewat API (CR-05).

## 3. Katalog (publik)

  -------------------------------------------------------------------------------------------------------
  Method                  Path                                   Isi utama
  ----------------------- -------------------------------------- ----------------------------------------
  GET                     `/service-categories`                  kategori aktif

  GET                     `/services?category_id=`               jasa aktif

  GET                     `/services/{id}`                       detail jasa + `addons[]` aktif +
                                                                 `prices[]` (per `property_size`,
                                                                 dikelompokkan per `property_type`)

  GET                     `/property-types`                      jenis properti aktif + `sizes[]`

  GET                     `/slots?service_id=&date=YYYY-MM-DD`   **daftar** (CR-09) 6 slot 2 jam hari
                                                                 itu: `[ {start, end, available} ]`.
                                                                 `available` = slot cukup jauh dari
                                                                 sekarang (`MIN_LEAD_MINUTES`) **dan**
                                                                 ada ≥ 1 mitra eligible yang bebas

  POST                    `/orders/quote`                        `service_id`, `property_size_id`,
                                                                 `addons[{service_addon_id, quantity}]` →
                                                                 rincian harga (tidak menyimpan apa pun)
  -------------------------------------------------------------------------------------------------------

## 4. Pesanan, pembayaran, guest

Akses pesanan: **login** (pemilik) atau **guest** dengan `order_code` +
`phone` pada body/query. Tidak cocok → 404 generik + throttle (B-9).

  --------------------------------------------------------------------------------------------------------
  Method            Path                                        Akses             Isi utama
  ----------------- ------------------------------------------- ----------------- ------------------------
  POST              `/orders`                                   guest/login       `service_id`,
                                                                                  `property_size_id`,
                                                                                  `addons[]`,
                                                                                  `scheduled_at`,
                                                                                  `customer_name`,
                                                                                  `customer_phone`,
                                                                                  `service_address`,
                                                                                  `address_notes?`,
                                                                                  `customer_note?`,
                                                                                  `customer_address_id?` →
                                                                                  201 order +
                                                                                  `order_code`. 409: slot
                                                                                  tanpa mitra eligible
                                                                                  atau pesanan identik
                                                                                  masih berjalan. 422:
                                                                                  bukan slot valid atau
                                                                                  terlalu dekat (CR-11)

  POST              `/orders/track`                             guest             `order_code`, `phone` →
                                                                                  order

  GET               `/orders/{order_code}`                      login (pemilik)   order

  GET               `/customer/orders`                          login customer    riwayat (paginasi)

  GET               `/orders/{order_code}/status-history`       pemilik/guest     riwayat status

  POST              `/orders/{order_code}/cancel`               pemilik/guest     `reason?` (§10.9)

  POST              `/orders/{order_code}/confirm-completion`   pemilik/guest     hanya dari
                                                                                  `mitra_completed`
                                                                                  (§10.9)

  GET               `/orders/{order_code}/payment`              pemilik/guest     status bayar terbaru
                                                                                  (§10.9). Pada P4b: jika
                                                                                  `pending`, backend boleh
                                                                                  menanyakan Midtrans
                                                                                  Status API

  POST              `/orders/{order_code}/payments`             pemilik/guest     **P4b, belum ada.**
                                                                                  Membuat transaksi Snap →
                                                                                  `snap_token`,
                                                                                  `redirect_url`,
                                                                                  `expired_at`. Hanya dari
                                                                                  `waiting_payment`

  POST              `/webhooks/midtrans`                        publik +          **P4b, belum ada.**
                                                                signature         Notifikasi Midtrans
                                                                                  (lihat §7)
  --------------------------------------------------------------------------------------------------------

Guest: `order_code` + `phone` dikirim di setiap request (stateless),
dengan throttle (DECISIONS D-13, CR-10).

### Bentuk objek Order (response)

``` json
{
  "order_code": "JR-7K2M9QXD",
  "order_status": "waiting_payment",
  "tracking_stage": "waiting",
  "service": { "id": 1, "service_name": "Home Cleaning" },
  "property_size": { "id": 2, "size_name": "Rumah Sedang" },
  "scheduled_at": "2026-11-20T10:00:00+07:00",
  "duration_minutes": 120,
  "customer": { "name": "Budi", "phone": "081234567890" },
  "service_address": "Jl. Contoh No. 1, Bekasi",
  "address_notes": null,
  "customer_note": null,
  "pricing": {
    "service_price": 150000,
    "addons_total": 50000,
    "total_price": 200000
  },
  "addons": [
    {
      "name": "Pembersihan Sofa",
      "unit_price": 50000,
      "quantity": 1,
      "subtotal": 50000
    }
  ],
  "mitra": { "mitra_id": 3, "full_name": "Sari", "phone": "08..." },
  "payment": {
    "payment_status": "pending",
    "amount": 200000,
    "payment_method": null,
    "expired_at": "2026-11-20T09:30:00+07:00"
  },
  "created_at": "2026-11-19T20:00:00+07:00"
}
```

`mitra` dan `payment` bernilai `null` selama belum ada. Telepon mitra
tampil ke customer setelah mitra ditetapkan. Objek `payment` di dalam
Order tidak memuat `paid_at` dan `refund_amount`; keduanya hanya ada di
`GET /orders/{kode}/payment` (§10.9).

## 5. Mitra

Semua endpoint `/mitra/*` butuh role `mitra`: tanpa token 401, role lain
403.

  -----------------------------------------------------------------------------------------------
  Method                  Path                                  Isi utama
  ----------------------- ------------------------------------- ---------------------------------
  GET / PUT               `/mitra/profile`                      `bio`, `service_area`,
                                                                `is_available`

  GET                     `/mitra/services`                     semua jasa katalog + flag
                                                                `selected`

  PUT                     `/mitra/services`                     `service_ids[]` (sinkronisasi
                                                                `mitra_services`; yang dilepas
                                                                jadi `inactive`)

  GET / PUT               `/mitra/availabilities`               PUT: body dibungkus key
                                                                `availabilities` (replace-all,
                                                                CR-12). Isi tiap item:
                                                                `day_of_week` (0=Minggu),
                                                                `start_time`, `end_time`,
                                                                `is_active`

  GET                     `/mitra/offers`                       penawaran berstatus `offered`,
                                                                belum kedaluwarsa, order masih
                                                                `searching_mitra` (tanpa
                                                                `order_code`, nama/telepon/alamat
                                                                customer, D-12). Isi: §10.6
                                                                (CR-13)

  POST                    `/mitra/offers/{attempt_id}/accept`   200 objek Order
                                                                `waiting_payment`. 409 jika sudah
                                                                diambil/kedaluwarsa/bentrok
                                                                (§10.6)

  POST                    `/mitra/offers/{attempt_id}/reject`   200 `data: null`; backend lanjut
                                                                ke mitra berikutnya

  GET                     `/mitra/orders?status=`               pesanan yang ditugaskan
                                                                (paginasi; `status` boleh dipisah
                                                                koma)

  GET                     `/mitra/orders/{order_code}`          detail (alamat lengkap + telepon
                                                                customer tampil di sini)

  POST                    `/mitra/orders/{order_code}/depart`   `confirmed → on_the_way` (butuh
                                                                payment `paid`)

  POST                    `/mitra/orders/{order_code}/arrive`   `on_the_way → arrived`

  POST                    `/mitra/orders/{order_code}/start`    `arrived → in_progress`

  POST                    `/mitra/orders/{order_code}/finish`   `in_progress → mitra_completed`
  -----------------------------------------------------------------------------------------------

Khusus development (CR-14):
`POST /dev/orders/{order_code}/simulate-paid`. Rute **selalu
terdaftar**, tetapi service membalas 404 jika `APP_ENV` bukan `local`
(supaya bisa dites otomatis). Bukan pengganti Midtrans: dosen
mensyaratkan minimal 1 API eksternal (DECISIONS Q-1).

## 6. Status pesanan

### 6.1 Transisi yang diizinkan

  ----------------------------------------------------------------------------
  Dari                Ke                  Pelaku            Syarat
  ------------------- ------------------- ----------------- ------------------
  (baru)              `pending` →         sistem            dalam request yang
                      `searching_mitra`                     sama saat order
                                                            dibuat

  `searching_mitra`   `waiting_payment`   mitra             accept lolos B-5

  `searching_mitra`   `cancelled`         sistem / customer batas percobaan
                                                            habis / customer
                                                            batal

  `waiting_payment`   `confirmed`         sistem            pembayaran `paid`
                                                            terverifikasi

  `waiting_payment`   `cancelled`         sistem / customer bayar kedaluwarsa
                                                            atau gagal final /
                                                            customer batal
                                                            (tanpa refund)

  `confirmed`         `on_the_way`        mitra             pembayaran `paid`

  `on_the_way`        `arrived`           mitra             

  `arrived`           `in_progress`       mitra             

  `in_progress`       `mitra_completed`   mitra             

  `mitra_completed`   `completed`         customer / sistem konfirmasi
                                                            customer, atau
                                                            otomatis setelah N
                                                            jam (D-07)

  `confirmed`,        `cancelled`         customer / admin  pembayaran →
  `on_the_way`,                                             `refund_pending`
  `arrived`                                                 (D-08)
  ----------------------------------------------------------------------------

Transisi lain **ditolak** dengan 409. Setiap transisi menulis
`order_status_histories`. Jadwal mitra dianggap terpakai sejak
`waiting_payment` sampai `completed`/`cancelled`
(`OrderStatus::SCHEDULE_BLOCKING` = `waiting_payment` ...
`mitra_completed`). Pembatalan customer dari `in_progress`,
`mitra_completed`, `completed` ditolak 409 (§10.9).

### 6.2 `tracking_stage` (turunan untuk UI customer, dokumen desain §11.2)

  -------------------------------------------------------------------------
  `order_status`          `tracking_stage`          Label UI
  ----------------------- ------------------------- -----------------------
  `pending`,              `searching`               Mencari mitra
  `searching_mitra`                                 

  `waiting_payment`       `waiting`                 Menunggu pembayaran

  `confirmed`             `confirmed`               Pesanan Dikonfirmasi

  `on_the_way`,           `working`                 Pengerjaan
  `arrived`,                                        
  `in_progress`                                     

  `mitra_completed`       `awaiting_confirmation`   Menunggu konfirmasi
                                                    Anda

  `completed`             `done`                    Selesai

  `cancelled`             `cancelled`               Dibatalkan
  -------------------------------------------------------------------------

## 7. Pembayaran Midtrans (P4b, **belum diimplementasi**)

Seluruh bagian ini masih rencana. Paket `midtrans/midtrans-php`
disetujui dan dipasang di awal P4b. Kunci hanya di `backend/.env` (G-9).

-   Snap dibuat dari backend (library resmi PHP). `gross_amount` =
    integer rupiah = `payments.amount`.
-   `payments.payment_status` dipetakan dari `transaction_status`
    Midtrans (verifikasi ulang ke dokumen Midtrans saat implementasi):

  Dari Midtrans                                          `payment_status` kita
  ------------------------------------------------------ -----------------------
  `settlement`; `capture` dengan `fraud_status=accept`   `paid`
  `pending`                                              `pending`
  `deny`, `cancel`, `failure`                            `failed`
  `expire`                                               `expired`
  `refund`, `partial_refund`                             `refunded`

-   Urutan penanganan notifikasi: validasi signature → cari payment
    lewat `gateway_order_id` → abaikan jika status baru "lebih mundur"
    dari yang tersimpan → cocokkan amount → update (dalam transaksi) →
    jika `paid` dan order `waiting_payment`, panggil
    `OrderStatusService` ke `confirmed` → balas 200.
-   Webhook butuh URL publik. Untuk dev lokal pakai tunnel (ngrok; URL
    berubah tiap restart, jadi **Payment Notification URL** di dashboard
    Sandbox harus diperbarui), atau gunakan fallback Status API.
-   Dua keputusan yang masih **USULAN** dan memengaruhi bagian ini: D-22
    (cancel order saat payment masih `pending`) dan D-23 (webhook `paid`
    datang untuk order yang sudah `cancelled`). Lihat DECISIONS §1.

## 8. Admin (Laravel Blade, bukan API) (P5, belum diimplementasi)

Rute web di bawah `/admin` (guard `web`): `login`, `dashboard`, dan
resource `users`, `mitra`, `service-categories`, `services`,
`property-types`, `property-sizes`, `service-prices`, `service-addons`,
`orders`, `payments`. Hanya lihat/ubah; master yang terpakai
dinonaktifkan, bukan dihapus. Login admin: telepon + password (D-20).

## 9. Command terjadwal

  -----------------------------------------------------------------------------------------
  Command                           Fungsi              Jadwal            Status
  --------------------------------- ------------------- ----------------- -----------------
  `orders:process-expired-offers`   tandai attempt      tiap menit        **ada, terdaftar
                                    `expired` lalu                        di `Kernel.php`**
                                    tawarkan ke mitra                     
                                    berikutnya atau                       
                                    batalkan                              

  `payments:expire-pending`         `pending` yang      tiap menit        P4c, belum ada
                                    lewat `expired_at`                    
                                    → `expired`; order                    
                                    → `cancelled`                         

  `orders:auto-complete`            `mitra_completed`   tiap jam          P4c, belum ada
                                    yang lewat N jam →                    
                                    `completed`                           
  -----------------------------------------------------------------------------------------

Logika yang sama dipanggil secara lazy dari endpoint baca (B-15). Saat
ini lazy expiry hanya untuk offer
(`MatchingService::refreshIfSearching`); expiry bayar dan auto-complete
menyusul di P4c. Batas bayar D-06 dihitung dari waktu transisi
`waiting_payment` di `order_status_histories`, bukan dari
`payments.expired_at`, karena order yang customer-nya belum membuka
pembayaran tidak punya baris `payments` (D-24).

## 10. Contoh respons (dari backend yang sudah berjalan)

### 10.1 Auth

`POST /auth/login` → 200

``` json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "3|xxxxxxxx",
    "user": {
      "user_id": 4,
      "full_name": "Andi Pratama",
      "phone": "081200000021",
      "email": null,
      "role": "mitra",
      "account_status": "active",
      "mitra_id": 1,
      "verification_status": "approved"
    },
    "mitra_id": 1
  }
}
```

-   Untuk customer, `mitra_id` dan `verification_status` tidak ada.
-   `mitra_id` di level `data` dan di dalam `user` isinya sama. Flutter
    cukup pakai salah satu.
-   `POST /auth/register/customer` dan `/register/mitra` mengembalikan
    bentuk yang sama dengan status **201**, jadi aplikasi tidak perlu
    login ulang.
-   `GET /auth/me` mengembalikan objek `user` saja (tanpa `token`).
-   Admin ditolak login lewat API (403).

### 10.2 Error validasi (422)

``` json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "full_name": ["Nama lengkap wajib diisi."],
    "address.label": ["Label alamat wajib diisi."]
  }
}
```

Key field bersarang berbentuk **datar bertitik** (`"address.label"`),
bukan objek bersarang. `ApiClient` Flutter harus membaca `errors`
sebagai `Map<String, List<String>>`.

Error bisnis (401, 403, 404, 409, 422 dari service) hanya punya
`success` dan `message`, tanpa `errors`.

### 10.3 Katalog

`GET /service-categories` →
`data: [ { "id": 1, "category_name": "Kebersihan Rumah", "description": "..." } ]`

`GET /services/{id}` → 200

``` json
{
  "id": 1,
  "category_id": 1,
  "service_name": "Home Cleaning",
  "description": "Pembersihan menyeluruh lantai, kamar, dapur, dan kamar mandi.",
  "duration_minutes": 120,
  "addons": [
    {
      "id": 1,
      "addon_name": "Pembersihan Sofa",
      "description": null,
      "price": 50000
    }
  ],
  "prices": [
    {
      "property_type_id": 1,
      "type_name": "Rumah",
      "sizes": [
        {
          "property_size_id": 2,
          "size_name": "Rumah Sedang",
          "min_area": 46,
          "max_area": 90,
          "price": 150000
        }
      ]
    }
  ]
}
```

-   `GET /services` (daftar) isinya sama tanpa `addons` dan `prices`.
-   `GET /property-types` →
    `[ { "id", "type_name", "sizes": [ { "id", "size_name", "min_area", "max_area" } ] } ]`
-   Jasa yang tidak ada atau nonaktif → 404.

### 10.4 Quote

`POST /orders/quote`

``` json
{
  "service_id": 1,
  "property_size_id": 2,
  "addons": [
    { "service_addon_id": 1, "quantity": 1 },
    { "service_addon_id": 2, "quantity": 2 }
  ]
}
```

→ 200

``` json
{
  "service_id": 1,
  "service_name": "Home Cleaning",
  "property_size_id": 2,
  "size_name": "Rumah Sedang",
  "duration_minutes": 120,
  "addons": [
    {
      "service_addon_id": 1,
      "name": "Pembersihan Sofa",
      "unit_price": 50000,
      "quantity": 1,
      "subtotal": 50000
    }
  ],
  "pricing": {
    "service_price": 150000,
    "addons_total": 120000,
    "total_price": 270000
  }
}
```

-   `quantity` default 1, maksimal 20. `addons` boleh kosong atau
    dihilangkan.
-   422 jika: jasa atau ukuran tidak aktif, kombinasi jasa + ukuran
    tidak punya harga aktif, atau ada add-on yang bukan milik jasa itu.
-   `/orders/quote` tidak menyimpan apa pun.

### 10.5 Mitra: profil, jasa, jadwal

`GET /mitra/profile`, `PUT /mitra/profile` (body boleh sebagian: `bio`,
`service_area`, `is_available`) → 200

``` json
{
  "user_id": 4,
  "mitra_id": 1,
  "full_name": "Andi Pratama",
  "phone": "081200000021",
  "bio": null,
  "service_area": null,
  "verification_status": "approved",
  "is_available": true
}
```

`GET /mitra/services` →
`data: [ { "id": 1, "category_id": 1, "service_name": "Home Cleaning", "description": "...", "duration_minutes": 120, "selected": true } ]`

`PUT /mitra/services` body `{ "service_ids": [1, 2] }` → bentuk respons
sama dengan GET. Array kosong = lepas semua. Jasa nonaktif/tidak ada →
422.

`PUT /mitra/availabilities` (replace-all; array kosong = hapus semua)
body:

``` json
{
  "availabilities": [
    {
      "day_of_week": 1,
      "start_time": "08:00",
      "end_time": "16:00",
      "is_active": true
    }
  ]
}
```

→
`data: [ { "id": 7, "day_of_week": 1, "start_time": "08:00", "end_time": "16:00", "is_active": true } ]`
Aturan: `HH:MM`, selesai \> mulai, di dalam jam operasional
(08:00-20:00), tidak overlap di hari yang sama (bersentuhan boleh).
`is_active` default `true`. `day_of_week`: 0 = Minggu.

Semua endpoint `/mitra/*` butuh role `mitra`: tanpa token 401, role lain
403.

### 10.6 Mitra: penawaran

`GET /mitra/offers` → `data: [...]` (hanya yang `offered`, belum
kedaluwarsa, order masih `searching_mitra`)

``` json
{
  "attempt_id": 12,
  "attempt_number": 1,
  "offered_at": "2026-11-11T09:00:00+07:00",
  "expires_at": "2026-11-11T09:03:00+07:00",
  "service": { "id": 1, "service_name": "Home Cleaning" },
  "property_size": { "id": 2, "size_name": "Rumah Sedang" },
  "scheduled_at": "2026-11-12T10:00:00+07:00",
  "duration_minutes": 120,
  "addons": [{ "name": "Pembersihan Sofa", "quantity": 1 }],
  "pricing": {
    "service_price": 150000,
    "addons_total": 50000,
    "total_price": 200000
  }
}
```

Tanpa `order_code`, nama/telepon/alamat customer (D-12).

`POST /mitra/offers/{attempt_id}/accept` → 200, `data` = objek Order
(§4) dengan `order_status: "waiting_payment"`, alamat dan telepon
customer sudah terbuka. Error: 403 mitra belum approved/akun tidak aktif
· 404 tawaran bukan milik mitra · 409 order sudah tidak mencari mitra /
sudah diambil / tawaran tidak berlaku atau kedaluwarsa / jadwal bentrok
atau tidak lagi memenuhi syarat.

`POST /mitra/offers/{attempt_id}/reject` → 200, `data: null`. 404 dan
409 sama seperti accept. Setelah reject, backend menawarkan ke mitra
berikutnya atau membatalkan order bila kandidat habis.

### 10.7 Mitra: pesanan dan progres

`GET /mitra/orders?status=&page=&per_page=` → 200,
`data: [ objek Order (§4) ]` + `meta` (paginasi §1). Hanya pesanan yang
ditugaskan ke mitra ini. `status` boleh beberapa dipisah koma
(`confirmed,on_the_way`); status tidak dikenal → 422.

`GET /mitra/orders/{order_code}` → 200, objek Order. Alamat lengkap dan
telepon customer tampil di sini. Pesanan mitra lain / tidak ada → 404.

`POST /mitra/orders/{order_code}/depart | arrive | start | finish` →
200, objek Order dengan status baru:

  Endpoint   Dari → Ke
  ---------- ---------------------------------------------------
  `depart`   `confirmed` → `on_the_way` (butuh payment `paid`)
  `arrive`   `on_the_way` → `arrived`
  `start`    `arrived` → `in_progress`
  `finish`   `in_progress` → `mitra_completed`

Error: 404 bukan pesanan milik mitra · 409 status tidak sesuai urutan /
sudah dibatalkan / belum dibayar. Setelah `finish`, customer
mengonfirmasi lewat `/orders/{kode}/confirm-completion` (P4a).

### 10.8 Khusus dev: simulate-paid

`POST /dev/orders/{order_code}/simulate-paid` → 200, objek Order
`confirmed` dengan `payment.payment_status = "paid"`
(`gateway = simulator`, `gateway_order_id = {kode}-DEV1`). Tanpa token.
Hanya `APP_ENV=local`, di luar itu 404 (CR-14). 409 jika order bukan
`waiting_payment`. JANGAN dijalankan dengan `APP_ENV=local` di server
publik.

### 10.9 Customer/guest: batal, konfirmasi selesai, status bayar

Akses seperti §4: customer login (pemilik) tanpa `phone`, atau guest
dengan `phone` (body untuk POST, query untuk GET). Tidak cocok → 404
generik + throttle (D-13).

`POST /orders/{order_code}/cancel` body
`{ "phone": "08...", "reason": "opsional, maks 255" }` → 200, objek
Order (§4) berstatus `cancelled`. - Boleh dari `searching_mitra`,
`waiting_payment`, `confirmed`, `on_the_way`, `arrived`. Dari
`in_progress`, `mitra_completed`, `completed`, atau yang sudah
`cancelled` → 409. - Jika payment sudah `paid`: payment jadi
`refund_pending` dan `refund_amount` = nominal penuh (admin memproses
manual, D-08). Payment `pending` tidak diubah. - Tawaran mitra yang
masih menggantung ikut ditutup.

`POST /orders/{order_code}/confirm-completion` body
`{ "phone": "08..." }` → 200, objek Order berstatus `completed`. Hanya
dari `mitra_completed`, selain itu 409.

`GET /orders/{order_code}/payment?phone=08...` → 200

``` json
{
  "order_code": "JR-7K2M9QXD",
  "order_status": "confirmed",
  "tracking_stage": "confirmed",
  "payment": {
    "payment_status": "paid",
    "amount": 200000,
    "payment_method": "bank_transfer",
    "paid_at": "2026-11-20T09:10:00+07:00",
    "expired_at": "2026-11-20T09:30:00+07:00",
    "refund_amount": null
  }
}
```

`payment` bernilai `null` jika belum ada transaksi bayar (bukan 404).
Flutter memanggil ini berulang setelah WebView pembayaran ditutup (F-8).
Pada P4b, jika status masih `pending`, backend boleh menanyakan Midtrans
Status API.

### 10.10 Slot, buat pesanan, lacak, riwayat

> Bagian ini diturunkan dari kode (`SlotController`, `OrderController`,
> `OrderResource`, `OrderAccessService`), **belum diuji lewat Postman**.
> Tes otomatis untuk endpoint ini baru ada di rencana catch-up B-13
> (lihat `docs/PLAN_LANJUTAN.md` T1). Kalau hasil Postman beda, kode
> yang benar dan bagian ini diperbaiki lewat CHANGE REQUEST.

`GET /slots?service_id=1&date=2026-11-12` → 200, `data` berupa daftar 6
slot (08-10, 10-12, 12-14, 14-16, 16-18, 18-20; D-02):

``` json
[
  {
    "start": "2026-11-12T08:00:00+07:00",
    "end": "2026-11-12T10:00:00+07:00",
    "available": true
  }
]
```

`available = false` jika slot kurang dari `MIN_LEAD_MINUTES` dari
sekarang, atau tidak ada mitra eligible (approved, tersedia, akun aktif,
menyediakan jasa itu, jadwal harian mencakup slot, tidak bentrok).

`POST /orders` (guest atau login; token opsional) → **201**, `data` =
objek Order (§4), `order_status` = `searching_mitra`, `message` =
"Pesanan berhasil dibuat. Sedang mencari mitra."

-   `scheduled_at` harus persis awal salah satu slot. Customer tidak
    boleh memilih jam bebas.
-   Total harga dari client diabaikan; server menghitung ulang (B-3).
-   409: slot tidak punya mitra eligible, atau pesanan identik masih
    berjalan (anti dobel-tap, CR-11).
-   422: field tidak valid, `scheduled_at` bukan slot, atau terlalu
    dekat.
-   429: lebih dari 20 request/menit.

`POST /orders/track` body
`{ "order_code": "JR-7K2M9QXD", "phone": "081234567890" }` → 200, objek
Order. Tidak cocok → 404 "Pesanan tidak ditemukan." (generik). Setelah 5
percobaan **gagal** per menit per IP + kode → 429.

`GET /orders/{order_code}` → 200 objek Order. Hanya customer login
pemilik; selain itu 404. Guest memakai `/orders/track`.

`GET /customer/orders?page=1&per_page=15` → 200,
`data: [ objek Order ]`,
`meta: { current_page, last_page, per_page, total }`. Urut terbaru dulu.

`GET /orders/{order_code}/status-history` (pemilik dengan token, atau
guest dengan `?phone=08...`) → 200

``` json
[
  {
    "old_status": null,
    "new_status": "pending",
    "note": null,
    "created_at": "2026-11-11T09:00:00+07:00"
  },
  {
    "old_status": "pending",
    "new_status": "searching_mitra",
    "note": null,
    "created_at": "2026-11-11T09:00:00+07:00"
  }
]
```

Urut dari yang paling lama. Baris pertama diperkirakan punya
`old_status: null` (belum diverifikasi; cek saat tes catch-up).

## 11. Status implementasi (snapshot per 10 Nov 2026; bukan audit terkini)

Acuan historis: `php artisan test` = 55 tes lulus, 239 assertion (di
branch `feat/order-customer-actions`, menurut catatan 10 Nov 2026).
Tabel ini **bukan status implementasi terkini per 16 Nov**. Untuk
mengetahui kondisi aktual, audit branch GitHub dan jalankan tes yang
relevan. "Dites otomatis" pada snapshot ini berarti ada tes PHPUnit yang
lulus pada saat snapshot dibuat.

  ------------------------------------------------------------------------------------------------------
  Grup endpoint                        Status kode       Dites otomatis                Catatan
  ------------------------------------ ----------------- ----------------------------- -----------------
  Auth (`/auth/*`)                     selesai (P1)      belum                         dicoba manual
                                                                                       sewaktu P1. Tes
                                                                                       catch-up T1

  Katalog (`/service-categories`,      selesai (P1)      belum                         
  `/services`, `/property-types`)                                                      

  `/orders/quote`                      selesai (P1)      belum                         dicoba manual:
                                                                                       270000, 422
                                                                                       kombinasi tanpa
                                                                                       harga, 422 add-on
                                                                                       jasa lain

  `/slots`                             selesai (P2a)     belum                         

  `POST /orders`, `/orders/track`,     selesai (P2a)     belum                         §10.10 belum
  `GET /orders/{kode}`,                                                                diuji Postman
  `/customer/orders`, status-history                                                   

  Matching +                           selesai (P2b)     belum                         
  `orders:process-expired-offers` +                                                    
  lazy expiry                                                                          

  `/mitra/profile`, `/mitra/services`, selesai (P3a)     ya (`MitraSetupTest`, 10 tes) 
  `/mitra/availabilities`                                                              

  `/mitra/offers` + accept/reject      selesai (P3b)     ya (`MitraOfferTest`, 12 tes) tes "accept
                                                                                       bersamaan"
                                                                                       membuktikan
                                                                                       re-check,
                                                                                       **bukan** race
                                                                                       sungguhan (lihat
                                                                                       bawah)

  `/mitra/orders*` +                   selesai (P3c)     ya (`MitraOrderTest`, 14 tes) 
  depart/arrive/start/finish                                                           

  `/dev/orders/{kode}/simulate-paid`   selesai (P3c)     ya (di `MitraOrderTest`)      hanya
                                                                                       `APP_ENV=local`

  `cancel`, `confirm-completion`,      selesai (P4a)     ya                            
  `GET payment`                                          (`CustomerOrderActionTest`,   
                                                         17 tes)                       

  `POST /orders/{kode}/payments`,      **belum** (P4b)   \-                            menunggu akun
  `/webhooks/midtrans`                                                                 Sandbox

  `payments:expire-pending`,           **belum** (P4c)   \-                            
  `orders:auto-complete`                                                               

  Admin Blade `/admin/*`               **belum** (P5)    \-                            
  ------------------------------------------------------------------------------------------------------

Catatan race: PHPUnit berjalan dalam satu proses, jadi tes "dua mitra
accept bersamaan" hanya membuktikan bahwa pemeriksaan ulang di dalam
transaksi menolak accept kedua. Bukti `lockForUpdate` bekerja di bawah
beban nyata menunggu command `dev:race-accept` (dua proses `php artisan`
sekaligus, rencana P6).

### Catatan integrasi Flutter (14 Nov 2026)

Catatan ini **bukan perubahan kontrak API** dan tidak mengubah endpoint,
field, status, atau format respons.

-   Pengujian manual melalui browser terhadap
    `GET /api/service-categories` dan `GET /api/services` menghasilkan
    respons JSON sukses.
-   Namun, pemanggilan `GET /api/service-categories` dari Customer App
    Flutter Web (Chrome, base URL `http://127.0.0.1:8000/api`) berstatus
    XHR `(canceled)` setelah sekitar 13,35 detik; UI menampilkan pesan
    timeout. `ApiClient` saat ini mengatur timeout 15 detik.
-   CORS Laravel telah diperiksa (`api/*`, `allowed_origins: ['*']`,
    `allowed_methods: ['*']`, `allowed_headers: ['*']`); tidak ditemukan
    pesan error CORS di Console. Peringatan
    `Intl.v8BreakIterator is deprecated` bukan bukti penyebab kegagalan.
-   **Status integrasi katalog Flutter: BLOCKED / BUG OPEN.** Penyebab
    belum dipastikan. Jangan mengubah kontrak atau menyimpulkan backend
    rusak hanya berdasarkan status XHR tersebut. Lanjutkan pemeriksaan
    Network Timing, log request Laravel, dan penanganan Dio/XHR sebelum
    mengubah kode.

### Catatan implementasi autentikasi Mitra · 18 Nov 2026

Catatan ini bersifat operasional dan **tidak mengubah kontrak API**.

-   Mitra App telah mengintegrasikan `POST /auth/login`,
    `POST /auth/register/mitra`, `GET /auth/me`, dan `POST /auth/logout`
    melalui `AuthService`.
-   Login dan registrasi memvalidasi data respons dan role `mitra`;
    token disimpan melalui fasilitas `UserInfo`. `AuthGate` memeriksa
    token yang tersimpan dengan memanggil `/auth/me`.
-   Uji manual login Mitra, logout, password salah, penolakan akun
    Customer, registrasi Mitra, dan nomor telepon duplikat menunjukkan
    hasil sesuai harapan.
-   **Pemulihan sesi setelah aplikasi dibuka ulang gagal pada uji
    manual.** Penyebab belum dipastikan dan investigasi ditunda untuk
    dilakukan bersama Customer App. Jangan mengubah kontrak API untuk
    mengatasi isu ini sebelum penyebab teknis terisolasi.
-   Commit frontend: `e212646` pada branch `feat/mitra-app`; hash remote
    diverifikasi sama dengan hash lokal. Hasil `flutter analyze` bersih
    dan `flutter test` dari `apps/mitra_app` seluruhnya lulus (1 test).
