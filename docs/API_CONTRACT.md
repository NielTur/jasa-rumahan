# API Contract: Marketplace Jasa Rumahan

> **Status: DRAFT v0.1 (7 Nov 2026).** Dibekukan jadi v1.0 di akhir Fase P0, setelah direview AI kedua dan disetujui kelompok.
> Kontrak ini mengalahkan ingatan/asumsi AI. Mau mengubah? Ajukan CHANGE REQUEST (`AI_RULES.md` §9).
> Contoh JSON lengkap per endpoint diisi bertahap di P0-P2 oleh AI backend, bukan ditebak di sini. Yang tertulis di sini adalah **bentuk dan aturan** yang harus sama di Laravel dan Flutter.

## 1. Konvensi umum

| Hal | Aturan |
|---|---|
| Base URL | `{host}/api` (emulator Android: `http://10.0.2.2:8000/api`) |
| Auth | `Authorization: Bearer <token>` (Laravel Sanctum). Endpoint publik dan guest tanpa token |
| Header | Selalu kirim `Accept: application/json` |
| Sukses | `{ "success": true, "message": "...", "data": ... }` |
| Gagal | `{ "success": false, "message": "...", "errors": { "field": ["pesan"] } }` (`errors` hanya untuk 422) |
| Status code | 200 OK · 201 dibuat · 401 belum login/token salah · 403 role/akses salah · 404 tidak ada (juga untuk guest tidak cocok) · 409 konflik state (jadwal bentrok, order sudah diambil mitra lain, transisi tidak valid) · 422 validasi · 429 throttle |
| Pagination | `?page=1&per_page=15` → `data: [...]`, `meta: { current_page, last_page, per_page, total }` |
| Waktu | ISO 8601 + offset WIB, mis. `2026-11-20T10:00:00+07:00` |
| Uang | **integer rupiah** (`150000`), bukan string/float |
| ID | integer. `mitra_id` = `mitra_profiles.id`; `user_id` = `users.id` (jangan tertukar) |
| Telepon | string format lokal `08…` (normalisasi `+62`/`62` → `0`) |
| Penamaan field | `snake_case`, sama dengan nama kolom DB bila ada padanannya |

## 2. Auth

| Method | Path | Akses | Isi utama |
|---|---|---|---|
| POST | `/auth/register/customer` | publik | `full_name`, `phone`, `password`, `password_confirmation`, `address` (label, full_address, city?, province?, postal_code?) |
| POST | `/auth/register/mitra` | publik | `full_name`, `phone`, `password`, `password_confirmation`, `bio?`, `service_area?` |
| POST | `/auth/login` | publik | `phone`, `password` → `token`, `user`, `mitra_id?` |
| POST | `/auth/logout` | login | |
| GET | `/auth/me` | login | profil + `mitra_id` jika mitra |

Catatan: register mitra membuat `users` + `mitra_profiles` dalam satu transaksi. `verification_status` awal mengikuti `MITRA_AUTO_APPROVE` (DECISIONS D-15).

## 3. Katalog (publik)

| Method | Path | Isi utama |
|---|---|---|
| GET | `/service-categories` | kategori aktif |
| GET | `/services?category_id=` | jasa aktif |
| GET | `/services/{id}` | detail jasa + `addons[]` aktif + `prices[]` (per `property_size`, dikelompokkan per `property_type`) |
| GET | `/property-types` | jenis properti aktif + `sizes[]` |
| GET | `/slots?service_id=&date=YYYY-MM-DD` | daftar slot hari itu: `start`, `end`, `available` (ada ≥ 1 mitra eligible yang bebas) |
| POST | `/orders/quote` | `service_id`, `property_size_id`, `addons[{service_addon_id, quantity}]` → rincian harga (tidak menyimpan apa pun) |

## 4. Pesanan, pembayaran, guest

Akses pesanan: **login** (pemilik) atau **guest** dengan `order_code` + `phone` pada body/query.

| Method | Path | Akses | Isi utama |
|---|---|---|---|
| POST | `/orders` | guest/login | `service_id`, `property_size_id`, `addons[]`, `scheduled_at`, `customer_name`, `customer_phone`, `service_address`, `address_notes?`, `customer_note?`, `customer_address_id?` → order + `order_code` |
| POST | `/orders/track` | guest | `order_code`, `phone` → order |
| GET | `/orders/{order_code}` | login (pemilik) | order |
| GET | `/customer/orders` | login customer | riwayat (paginasi) |
| GET | `/orders/{order_code}/status-history` | pemilik/guest | riwayat status |
| POST | `/orders/{order_code}/cancel` | pemilik/guest | `reason?` |
| POST | `/orders/{order_code}/confirm-completion` | pemilik/guest | hanya dari `mitra_completed` |
| POST | `/orders/{order_code}/payments` | pemilik/guest | membuat transaksi Snap → `snap_token`, `redirect_url`, `expired_at`. Hanya dari `waiting_payment` |
| GET | `/orders/{order_code}/payment` | pemilik/guest | status bayar terbaru. Jika `pending`, backend boleh menanyakan Midtrans Status API |
| POST | `/webhooks/midtrans` | publik + signature | notifikasi Midtrans (lihat §7) |

Guest: `order_code` + `phone` dikirim di setiap request (stateless), dengan throttle (DECISIONS D-13).

### Bentuk objek Order (response)

```json
{
  "order_code": "JR-7K2M9QXD",
  "order_status": "waiting_payment",
  "tracking_stage": "waiting",
  "service": { "id": 1, "name": "Home Cleaning" },
  "property_size": { "id": 2, "name": "Rumah Sedang" },
  "scheduled_at": "2026-11-20T10:00:00+07:00",
  "duration_minutes": 120,
  "customer": { "name": "Budi", "phone": "081234567890" },
  "service_address": "Jl. Contoh No. 1, Bekasi",
  "address_notes": null,
  "customer_note": null,
  "pricing": { "service_price": 150000, "addons_total": 50000, "total_price": 200000 },
  "addons": [ { "name": "Pembersihan Sofa", "unit_price": 50000, "quantity": 1, "subtotal": 50000 } ],
  "mitra": { "mitra_id": 3, "full_name": "Sari", "phone": "08..." },
  "payment": { "payment_status": "pending", "amount": 200000, "payment_method": null, "expired_at": "2026-11-20T09:30:00+07:00" },
  "created_at": "2026-11-19T20:00:00+07:00"
}
```

`mitra` dan `payment` bernilai `null` selama belum ada. Telepon mitra tampil ke customer setelah mitra ditetapkan.

## 5. Mitra

| Method | Path | Isi utama |
|---|---|---|
| GET / PUT | `/mitra/profile` | `bio`, `service_area`, `is_available` |
| GET | `/mitra/services` | semua jasa katalog + flag `selected` |
| PUT | `/mitra/services` | `service_ids[]` (sinkronisasi `mitra_services`; yang dilepas jadi `inactive`) |
| GET / PUT | `/mitra/availabilities` | daftar `day_of_week` (0=Minggu), `start_time`, `end_time`, `is_active` |
| GET | `/mitra/offers` | penawaran berstatus `offered` (tanpa telepon/alamat lengkap, DECISIONS D-12) |
| POST | `/mitra/offers/{attempt_id}/accept` | 409 jika sudah diambil/kedaluwarsa/bentrok |
| POST | `/mitra/offers/{attempt_id}/reject` | |
| GET | `/mitra/orders?status=` | pesanan yang ditugaskan (paginasi) |
| GET | `/mitra/orders/{order_code}` | detail (alamat lengkap + telepon customer tampil di sini) |
| POST | `/mitra/orders/{order_code}/depart` | `confirmed → on_the_way` |
| POST | `/mitra/orders/{order_code}/arrive` | `on_the_way → arrived` |
| POST | `/mitra/orders/{order_code}/start` | `arrived → in_progress` |
| POST | `/mitra/orders/{order_code}/finish` | `in_progress → mitra_completed` |

Khusus development (hanya `APP_ENV=local`): `POST /dev/orders/{order_code}/simulate-paid` untuk menguji alur mitra sebelum Midtrans siap. Tidak boleh aktif di luar local.

## 6. Status pesanan

### 6.1 Transisi yang diizinkan

| Dari | Ke | Pelaku | Syarat |
|---|---|---|---|
| (baru) | `pending` → `searching_mitra` | sistem | dalam request yang sama saat order dibuat |
| `searching_mitra` | `waiting_payment` | mitra | accept lolos B-5 |
| `searching_mitra` | `cancelled` | sistem / customer | batas percobaan habis / customer batal |
| `waiting_payment` | `confirmed` | sistem | pembayaran `paid` terverifikasi |
| `waiting_payment` | `cancelled` | sistem / customer | bayar kedaluwarsa atau gagal final / customer batal (tanpa refund) |
| `confirmed` | `on_the_way` | mitra | pembayaran `paid` |
| `on_the_way` | `arrived` | mitra | |
| `arrived` | `in_progress` | mitra | |
| `in_progress` | `mitra_completed` | mitra | |
| `mitra_completed` | `completed` | customer / sistem | konfirmasi customer, atau otomatis setelah N jam (D-07) |
| `confirmed`, `on_the_way`, `arrived` | `cancelled` | customer / admin | pembayaran → `refund_pending` (D-08) |

Transisi lain **ditolak** dengan 409. Setiap transisi menulis `order_status_histories`.
Jadwal mitra dianggap terpakai sejak `waiting_payment` sampai `completed`/`cancelled`.

### 6.2 `tracking_stage` (turunan untuk UI customer, dokumen desain §11.2)

| `order_status` | `tracking_stage` | Label UI |
|---|---|---|
| `pending`, `searching_mitra` | `searching` | Mencari mitra |
| `waiting_payment` | `waiting` | Menunggu pembayaran |
| `confirmed` | `confirmed` | Pesanan Dikonfirmasi |
| `on_the_way`, `arrived`, `in_progress` | `working` | Pengerjaan |
| `mitra_completed` | `awaiting_confirmation` | Menunggu konfirmasi Anda |
| `completed` | `done` | Selesai |
| `cancelled` | `cancelled` | Dibatalkan |

## 7. Pembayaran Midtrans

- Snap dibuat dari backend (library resmi PHP `midtrans/midtrans-php`). `gross_amount` = integer rupiah = `payments.amount`.
- `payments.payment_status` dipetakan dari `transaction_status` Midtrans (verifikasi ulang ke dokumen Midtrans saat implementasi):

| Dari Midtrans | `payment_status` kita |
|---|---|
| `settlement`; `capture` dengan `fraud_status=accept` | `paid` |
| `pending` | `pending` |
| `deny`, `cancel`, `failure` | `failed` |
| `expire` | `expired` |
| `refund`, `partial_refund` | `refunded` |

- Urutan penanganan notifikasi: validasi signature → cari payment lewat `gateway_order_id` → abaikan jika status baru "lebih mundur" dari yang tersimpan → cocokkan amount → update (dalam transaksi) → jika `paid` dan order `waiting_payment`, panggil `OrderStatusService` ke `confirmed` → balas 200.
- Webhook butuh URL publik. Untuk dev lokal pakai tunnel (ngrok / Cloudflare Tunnel), atau gunakan fallback Status API.

## 8. Admin (Laravel Blade, bukan API)

Rute web di bawah `/admin` (guard `web`): `login`, `dashboard`, dan resource `users`, `mitra`, `service-categories`, `services`, `property-types`, `property-sizes`, `service-prices`, `service-addons`, `orders`, `payments`. Hanya lihat/ubah; master yang terpakai dinonaktifkan, bukan dihapus.

## 9. Command terjadwal

| Command | Fungsi | Jadwal |
|---|---|---|
| `orders:process-expired-offers` | tandai attempt `expired` lalu tawarkan ke mitra berikutnya atau batalkan | tiap menit |
| `payments:expire-pending` | `pending` yang lewat `expired_at` → `expired`; order → `cancelled` | tiap menit |
| `orders:auto-complete` | `mitra_completed` yang lewat N jam → `completed` | tiap jam |

Logika yang sama dipanggil secara lazy dari endpoint baca (B-15).
