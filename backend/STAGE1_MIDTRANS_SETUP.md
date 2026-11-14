# Stage 1 — Midtrans Snap + Webhook

## Perubahan pada source

- Menambahkan `midtrans/midtrans-php` (`^2.6`) ke `composer.json`.
- Menambahkan `MidtransGateway` sebagai adapter SDK resmi.
- Menambahkan `MidtransPaymentService` untuk pembuatan Snap, verifikasi signature, pemetaan status, idempotensi/out-of-order, dan fallback Status API.
- Menambahkan `POST /api/orders/{order_code}/payments`.
- Menambahkan `POST /api/webhooks/midtrans`.
- Memperbarui `GET /api/orders/{order_code}/payment` agar mencoba Status API sebelum lazy expiry.
- Menambahkan feature tests untuk Snap dan webhook.

## Instalasi dependency

`composer.lock` belum dapat diperbarui di lingkungan penyusunan patch karena Composer tidak tersedia. Setelah file patch diterapkan pada backend utama, jalankan dari folder `backend`:

```bash
composer update midtrans/midtrans-php
```

Perintah tersebut akan memasang SDK dan memperbarui `composer.lock`. Jangan menjalankan `composer install` saja sebelum lock diperbarui karena paket Midtrans belum tercatat di lock file.

## Environment

Isi `.env` lokal (jangan commit atau bagikan nilai rahasianya):

```dotenv
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_IS_PRODUCTION=false
```

`config/marketplace.php` dan `.env.example` di source sudah memiliki konfigurasi/nama variabel ini. `MIDTRANS_SERVER_KEY` hanya boleh berada di backend. Flutter nantinya menggunakan `MIDTRANS_CLIENT_KEY`, bukan Server Key.

Setelah mengubah `.env`:

```bash
php artisan config:clear
```

## Endpoint

- `POST /api/orders/{order_code}/payments`
  - Guest: kirim `phone` pada body.
  - Customer login: tidak perlu `phone`.
  - Mengembalikan `snap_token`, `redirect_url`, `expired_at`, `gateway_order_id`.
  - Nilai `gross_amount` selalu berasal dari `orders.total_price`, bukan dari request client.

- `GET /api/orders/{order_code}/payment`
  - Guest: kirim `phone` pada query.
  - Jika status lokal `pending`, backend mencoba Midtrans Status API.

- `POST /api/webhooks/midtrans`
  - Endpoint publik, tanpa auth.
  - Atur URL notifikasi Midtrans ke `https://DOMAIN/api/webhooks/midtrans`.
  - Gunakan HTTPS publik; localhost tidak dapat menerima webhook Midtrans secara langsung. Untuk lokal gunakan tunnel.

## Verifikasi yang masih perlu dijalankan di mesin proyek

```bash
php artisan test --filter=MidtransPaymentTest
php artisan test
```

Tes belum dapat dijalankan di lingkungan penyusunan patch karena PHP tidak memiliki ekstensi `dom`, `mbstring`, dan `xmlwriter`. Setelah Composer dan ekstensi PHP lengkap di mesin proyek, jalankan kedua perintah tersebut. Tes sandbox end-to-end juga tetap perlu dilakukan menggunakan kredensial Sandbox milik pemilik proyek.

## Catatan

- Redirect dari Snap bukan bukti pembayaran; status hanya diubah dari webhook terverifikasi atau Status API.
- Status order hanya diubah melalui `OrderStatusService::transition()`.
- Tidak ada migration baru dan tidak ada perubahan API Contract.
