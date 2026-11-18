<!-- Bundel kerja diperbarui 18 Nov 2026. Perubahan pada edisi ini: Mitra App Tahap 1 (fondasi) dan Tahap 2 (autentikasi) selesai diimplementasikan; commit e212646 sudah dipush dan hash remote diverifikasi sama dengan lokal. Pemulihan sesi setelah aplikasi dibuka ulang masih menjadi isu tertunda dan akan diperiksa bersama Customer App. Audit area backend dan Customer App tetap dibatasi pada bukti yang tersedia; status yang belum diperiksa tetap ditandai belum terverifikasi. -->
<!-- ===== FILE: AI_RULES.md ===== -->
# AI_RULES.md

Aturan kerja untuk semua AI (Claude, ChatGPT, dll.) yang membantu proyek
**Marketplace Jasa Rumahan**. Setiap aturan punya ID supaya gampang
menegur AI: *"kamu melanggar B-4"*.

> Revisi 11 Nov 2026: konteks dosen (§1 butir 4), G-9, G-12, B-5, B-13,
> Git-2, Git-4, Git-5, Git-6. `CLAUDE.md` dan `AGENTS.md` kini hanya
> pointer ke file ini; jangan disalin lagi.

## 0. Cara pakai (untuk pemilik proyek)

-   Claude Code / agent berbasis file: `CLAUDE.md` dan `AGENTS.md` di
    root repo menunjuk ke file ini, jadi tidak ada salinan yang bisa
    basi.
-   Chat biasa: unggah ke Project (Claude Project dan ChatGPT Project),
    lalu tempel Prompt Kickoff dari `docs/PROMPTS.md` §B di sesi baru.
    Project ChatGPT gratis hanya menampung 5 file: pakai bundel 3 file
    dari `docs/PROMPTS.md` §G (dibuat oleh
    `scripts/bundle-chatgpt.ps1`).
-   Tiap sesi baru, lampirkan: `AI_RULES.md`, `docs/PROGRESS.md`,
    `docs/PLAN_LANJUTAN.md`, bagian `docs/API_CONTRACT.md` yang relevan,
    `docs/DECISIONS.md` §5, dan file yang akan diubah. Jangan tempel
    seluruh dokumen desain kecuali diminta. Prompt siap tempel ada di
    `docs/PROMPTS.md` §E.7.
-   AI tidak punya ingatan antar chat. Satu-satunya "ingatan" proyek
    adalah `docs/PROGRESS.md`. Perbarui di akhir setiap sesi.

## 1. Proyek dalam 6 baris

1.  Marketplace jasa rumahan (Home Cleaning, Lawn Mowing, Tree Trimming,
    dll). Role: Customer (boleh guest), Mitra, Admin.
2.  Komponen: `customer_app` dan `mitra_app` (Flutter), `backend`
    (Laravel REST API + Admin Blade), MySQL 16 tabel, Dio, Midtrans
    (sandbox).
3.  Aturan inti: 1 pesanan = 1 jasa utama = 1 mitra. Add-on bagian dari
    pesanan. Harga dihitung ulang di server. Status pesanan terpisah
    dari status pembayaran.
4.  Ini tugas akhir Mobile Programming II (BSI). Konteks dari dosen
    (konsultasi 10 Nov 2026): Laravel + MySQL boleh, presentasi di
    **pertemuan 13**, luaran akhir **hanya Manual Book** (panduan
    pemakaian per role, tanpa template baku), **minimal 1 API
    eksternal** (Midtrans), proyek disarankan di GitHub. Lingkungan
    pemilik proyek: Windows, Laragon, VS Code, PowerShell, PHP 8.3,
    Laravel 10.50.4, MySQL 8.0.
5.  Dinilai lewat demo, kode, dan dokumen. Kode harus **bisa dipahami
    dan dijelaskan mahasiswa**; itu lebih penting daripada canggih.
6.  Fokus MVP. Fitur backlog dilarang kecuali diminta eksplisit (G-5).

## 2. Sumber rujukan utama dan aturan sinkronisasi

Sumber rujukan utama proyek adalah **tiga dokumen yang dikenali dari
pola nama berikut**, tanpa bergantung pada lokasi penyimpanannya:

1.  `1_ATURAN_STATUS_RENCANA_UPDATED_(TGL).md`
2.  `2_KONTRAK_API_UPDATED_(TGL).md`
3.  `3_KEPUTUSAN_DAN_DESAIN_UPDATED_(TGL).md`

Ketiganya dapat berada di GitHub, GPT Project Resources, atau lokasi
penyimpanan proyek lain. **Lokasi tidak menentukan otoritas dokumen.**

Aturan pemilihan dan penggunaannya:

-   Jika tersedia beberapa edisi dari pola nama yang sama, gunakan edisi
    dengan tanggal `(TGL)` paling baru.
-   Jika tanggal sama tetapi isi berbeda, jangan memilih berdasarkan
    lokasi atau dugaan. Laporkan perbedaannya dan minta penetapan
    pemilik proyek.
-   Dokumen pertama menjadi rujukan aturan kerja, status, dan rencana;
    dokumen kedua menjadi rujukan kontrak API; dokumen ketiga menjadi
    rujukan keputusan serta desain sistem/database.
-   GitHub repository tetap menjadi bukti **kondisi implementasi
    aktual**: kode, migration, test, konfigurasi, dan commit. GitHub
    tidak otomatis mengalahkan ketiga dokumen rujukan untuk menetapkan
    desain atau kontrak.
-   Jika implementasi GitHub berbeda dari dokumen rujukan, catat sebagai
    discrepancy. Jangan mengubah kode, kontrak, keputusan, atau dokumen
    secara diam-diam. Ikuti aturan CHANGE REQUEST dan minta persetujuan
    bila perubahan kontrak/desain diperlukan.
-   Dokumen repository lain seperti `README.md`, `AI_RULES.md`,
    `API_CONTRACT.md`, `DECISIONS.md`, atau `PROGRESS.md` boleh dipakai
    sebagai bahan pemeriksaan dan riwayat, tetapi tidak menggantikan
    tiga dokumen utama di atas kecuali pemilik proyek menetapkan
    demikian.

Prinsip ringkas: **dokumen UPDATED = apa yang seharusnya dibangun dan
aturan yang berlaku; GitHub = apa yang benar-benar sudah dibangun.**
Jika keduanya berbeda, identifikasi dan laporkan lebih dahulu.

## 3. Pembagian kepemilikan (default, boleh ditukar)

  ------------------------------------------------------------------------
  Area                     Penulis                 Reviewer
  ------------------------ ----------------------- -----------------------
  `backend/`,              Claude                  ChatGPT
  `docs/API_CONTRACT.md`                           

  `packages/core/`,        ChatGPT                 Claude
  `apps/*/lib/ui`,                                 
  `apps/*/lib/service`                             
  ------------------------------------------------------------------------

-   AI hanya mengubah file di area miliknya. Butuh perubahan di area
    lain atau di kontrak? Tulis **CHANGE REQUEST** (§9), jangan
    mengedit.
-   Kontrak hanya berubah setelah disetujui pemilik proyek (manusia).

## 4. Aturan umum (G)

-   **G-1 Preflight.** Sebelum kode, tulis 3 baris: *Tugas* / *File yang
    disentuh* / *Asumsi & potensi konflik dengan kontrak*.
-   **G-2 Ambigu.** Tanya maksimal 1 pertanyaan. Kalau tidak blocking,
    pakai default dari `DECISIONS.md` dan tulis asumsinya.
-   **G-3 Scope.** Kerjakan hanya yang diminta. Tidak ada refactor di
    luar scope. Dependency baru butuh izin (sebut nama paket, alasan,
    dan alternatif tanpa paket).
-   **G-4 Tidak mengarang.** Endpoint, tabel, kolom, dan status hanya
    dari kontrak atau dokumen desain. Butuh yang baru → CHANGE REQUEST.
-   **G-5 Backlog dilarang** (dokumen desain §16.2): COD, komplain,
    rating, verifikasi mitra lengkap, saldo/pencairan, push
    notification, live chat, pembatalan kompleks, GPS tracking, voucher,
    loyalty.
-   **G-6 Format copy-paste.** Setiap blok kode diawali path file. File
    ≤ 200 baris ditulis utuh. Lebih panjang: tulis per bagian dengan
    lokasi jelas dan 3 baris konteks. Dilarang
    `// ... kode lainnya tetap`.
-   **G-7 Jujur soal verifikasi.** Kode yang tidak dijalankan harus
    ditulis "belum dijalankan". Dilarang bilang "sudah dites" tanpa
    bukti output.
-   **G-8 Versi.** Jangan menebak versi paket/API. Baca `composer.json`
    / `pubspec.yaml`. Kalau tidak tahu, minta output `php -v`,
    `composer show laravel/framework`, `flutter --version`. Pakai API
    yang tidak deprecated (contoh: Dio `DioException`, bukan `DioError`;
    `ElevatedButton.styleFrom(backgroundColor:)`, bukan `primary:`).
    Kode di modul kuliah sebagian memakai API lama.
-   **G-9 Rahasia.** Jangan pernah menulis kunci Midtrans, `APP_KEY`,
    atau password asli. Pakai `.env` dan `.env.example`. `.env.example`
    wajib berisi `APP_KEY=` **kosong** dan kunci Midtrans kosong.
    Sebelum commit: `git diff --cached` dan cari `APP_KEY=base64`,
    `SB-Mid-server`, `SB-Mid-client`. Kunci yang pernah ter-commit
    dianggap bocor (ganti, jangan sekadar dihapus dari file).
-   **G-10 Postflight.** Akhiri setiap jawaban dengan: (a) langkah
    menjalankan/menguji, (b) yang belum terverifikasi, (c) baris
    siap-tempel untuk `docs/PROGRESS.md`, (d) **Catatan Presentasi** 3-5
    baris.
-   **G-11 Bisa dijelaskan.** Nama jelas, fungsi pendek, komentar Bahasa
    Indonesia pada logika bisnis penting (bukan tiap baris). Hindari
    abstraksi yang tidak perlu.
-   **G-12 Berhenti dan lapor** jika: dokumen saling bertentangan, perlu
    mengubah skema DB yang sudah disepakati, ada risiko keamanan, atau
    permintaan keluar dari MVP. Termasuk **dokumen basi**: kalau
    `PROGRESS.md`, `API_CONTRACT.md` §11, atau README bilang satu hal
    dan kode/tes bilang hal lain, laporkan dulu dan pakai kode sebagai
    bukti, jangan menimpa salah satunya diam-diam.

## 5. Backend Laravel (B)

-   **B-1 Struktur.** Controller tipis. Logika di `app/Services/*`.
    Validasi di FormRequest. Output lewat API Resource. Blade admin
    memakai Service yang sama (tidak memanggil API sendiri lewat HTTP).
-   **B-2 Skema.** Ikuti dokumen desain §13 persis (16 tabel, tipe, FK,
    UNIQUE, indeks §15.14). Setelah Fase P0 selesai, jangan edit
    migration lama; perubahan = migration baru + CHANGE REQUEST.
-   **B-3 Uang & harga.** DB `DECIMAL(12,2)`; API dan Midtrans memakai
    **integer rupiah**. Hitung ulang harga di server dari
    `service_prices` dan `service_addons`; abaikan total dari Flutter.
    Add-on harus milik jasa yang dipilih, dan semua master harus
    `is_active`.
-   **B-4 Satu pintu status.** Status pesanan hanya berubah lewat
    `OrderStatusService::transition(order, to, actor, note)`, yang
    memvalidasi transisi (tabel di kontrak §6) dan menulis
    `order_status_histories`. Dilarang
    `$order->update(['order_status' => ...])` di tempat lain.
-   **B-5 Accept aman.** Penetapan mitra dalam `DB::transaction` dengan
    `lockForUpdate` (order dan mitra_profile), lalu cek ulang: status
    masih `searching_mitra`, `mitra_id` masih NULL, attempt masih
    `offered` dan belum kedaluwarsa, jadwal tidak bentrok. Implementasi
    (`MitraOfferService`): urutan kunci **order → mitra_profile →
    attempt** (selalu urutan yang sama, cegah deadlock), lalu re-check.
-   **B-6 Matching.** Sekuensial: satu mitra per attempt, tiap attempt
    dicatat di `order_mitra_attempts`. Timeout dan batas percobaan dari
    `config/marketplace.php` (.env), bukan angka literal.
-   **B-7 Midtrans.**
    -   `gateway_order_id` unik per percobaan bayar
        (`{order_code}-{n}`).
    -   Verifikasi `signature_key` = SHA512(`order_id` + `status_code` +
        `gross_amount` + ServerKey), memakai string persis seperti
        diterima.
    -   Idempotent: notifikasi berulang atau out-of-order (misal
        `pending` datang setelah `settlement`) tidak boleh menurunkan
        status.
    -   Cocokkan `gross_amount` dengan `payments.amount`.
    -   Balas HTTP 200 untuk notifikasi valid; tolak hanya jika
        signature salah.
    -   Simpan payload mentah.
    -   Redirect dari WebView/Flutter **bukan** bukti bayar.
    -   Sediakan fallback cek status lewat Midtrans Status API.
-   **B-8 Otorisasi.** Per role lewat middleware/policy: customer hanya
    datanya, mitra hanya offer dan order miliknya, admin lewat guard
    `web`. Telepon dan alamat lengkap customer tidak dibuka ke mitra
    sebelum accept (DECISIONS D-12).
-   **B-9 Guest.** Tracking dan aksi guest memeriksa `order_code` +
    `phone`, dengan throttle. Jika tidak cocok, balas 404 generik.
    `order_code` acak (bukan auto-increment).
-   **B-10 Waktu.** `APP_TIMEZONE=Asia/Jakarta`. API mengirim ISO 8601
    dengan offset `+07:00`. `day_of_week` mengikuti Carbon: 0 = Minggu
    ... 6 = Sabtu.
-   **B-11 Jangan tertukar ID.** `mitra_id` selalu `mitra_profiles.id`,
    bukan `users.id`. Response mitra menyertakan `user_id` dan
    `mitra_id` dengan nama jelas.
-   **B-12 Jangan hapus master.** Data master yang pernah dipakai
    transaksi dinonaktifkan (`is_active = false`), bukan dihapus. FK
    transaksi tanpa cascade delete.
-   **B-13 Tes minimal wajib** (feature/unit test): kalkulasi harga;
    bentrok jadwal; transisi status valid dan invalid; dua mitra accept
    bersamaan; webhook (signature salah, duplikat, out-of-order, amount
    beda); guest tracking tidak cocok.
    -   Tes memakai **MySQL terpisah** `jasa_rumahan_test`
        (`phpunit.xml` memaksa `DB_CONNECTION` dan `DB_DATABASE` dengan
        `force="true"`, `RefreshDatabase`). Jangan SQLite:
        `lockForUpdate` diabaikan di sana. Jangan `config:cache` di dev.
    -   Data awal tes lewat trait
        `tests/Concerns/BuildsMitraFixtures.php`; pakai ulang, jangan
        menulis ulang fixture.
    -   Tes "accept bersamaan" di PHPUnit (satu proses) hanya
        membuktikan re-check. Jangan menyebutnya bukti race nyata; bukti
        nyata = `dev:race-accept` (dua proses `php artisan`, P6).
    -   Tes yang bergantung waktu memakai `Carbon::setTestNow`, bukan
        `sleep`.
-   **B-14 Seeder demo.** Admin, 2 customer, 3 mitra approved,
    kategori/jasa/ukuran/harga/add-on, supaya demo tidak bergantung
    input manual.
-   **B-15 Expiry ganda.** Kedaluwarsa offer dan pembayaran diproses
    oleh scheduler (`schedule:work`) **dan** secara lazy saat endpoint
    baca dipanggil, sehingga demo tetap benar tanpa scheduler.

## 6. Flutter (F)

-   **F-1 Konvensi modul kuliah:** `lib/ui`, `lib/model`, `lib/service`,
    `lib/helpers`; file `snake_case`, kelas `PascalCase`. Di monorepo,
    `model` dan `helpers` bersama ada di `packages/core`; `ui` dan
    `service` ada di tiap app.
-   **F-2 State.** `StatefulWidget` + `FutureBuilder`/`StreamBuilder` +
    `setState` (gaya modul). Paket state management lain hanya dengan
    izin.
-   **F-3 Alur panggilan.** `ui → service → ApiClient → backend`. UI
    tidak memanggil Dio langsung. Service mengembalikan model bertipe,
    bukan `Map` mentah.
-   **F-4 ApiClient.** Satu instance Dio. Interceptor menambahkan Bearer
    token dari `UserInfo` (shared_preferences). `baseUrl` dari
    `--dart-define=API_BASE_URL`. Tangani `DioException` menjadi pesan
    Indonesia yang ramah (timeout, 401, 422 per field, 5xx).
-   **F-5 Tiga state.** Setiap layar data punya loading, kosong, dan
    error (+ tombol coba lagi). Tombol submit dinonaktifkan saat proses
    berjalan (cegah double submit, terutama buat pesanan dan bayar).
-   **F-6 Context.** Setelah `await`, cek `mounted` sebelum memakai
    `BuildContext`.
-   **F-7 Format.** Uang `Rp` tanpa desimal. Tanggal locale `id_ID`
    (panggil `initializeDateFormatting` di `main`). Waktu ditampilkan
    WIB.
-   **F-8 Pembayaran.** Buka `redirect_url` Snap di WebView. Setelah
    WebView ditutup, **wajib** polling status pesanan dari backend.
    Jangan menyimpulkan lunas dari URL redirect.
-   **F-9 Tracking.** Polling 5-10 detik, berhenti saat layar ditutup
    (`dispose`). Tidak ada push notification di MVP.
-   **F-10 Mode mock.** `--dart-define=USE_MOCK=true` membuat
    interceptor mengembalikan JSON dari `assets/mock/`. Gunanya: UI bisa
    dikerjakan sebelum backend siap, dan jadi cadangan demo.
-   **F-11 Jaringan dev.** Emulator Android →
    `http://10.0.2.2:8000/api`. HP fisik → IP LAN laptop atau tunnel
    https. Jika memakai http, aktifkan cleartext di `AndroidManifest`
    hanya untuk debug.
-   **F-12 Guest.** Simpan `order_code` + `phone` secara lokal untuk
    riwayat guest. Jangan log token atau password.
-   **F-13 Bahasa.** Teks UI Bahasa Indonesia. Identifier kode Bahasa
    Inggris, mengikuti nama di kontrak API.

## 7. Admin Blade (A)

-   **A-1** Guard `web` (session) + middleware role admin. CSRF aktif.
-   **A-2** Resource controller + satu layout. Tidak perlu SPA. Tabel
    dengan pagination dan filter status dasar.
-   **A-3** Master yang sudah dipakai tidak dihapus; sediakan tombol
    "Nonaktifkan".
-   **A-4** Harga diubah lewat form admin, bukan lewat kode atau seeder.

## 8. Git & sesi

-   **Git-1** Satu tugas = satu branch `feat/<area>-<ringkas>`. Commit
    kecil, contoh: `feat(order): hitung harga di server`.
-   **Git-2** Jangan commit `.env`, kunci, `vendor/`, `build/`,
    `.phpunit.result.cache` (sudah di `.gitignore`). `.env.example`
    boleh, dengan nilai rahasia kosong (G-9).
-   **Git-3** Awal sesi baca `docs/PROGRESS.md`. Akhir sesi berikan
    baris pembaruannya.
-   **Git-4** Merge ke `main` hanya jika: sudah jalan di
    emulator/Postman, tes backend hijau, sudah direview AI lain
    (`PROMPTS.md` §E), dan disetujui manusia. Branch boleh **ditumpuk**
    (stacked: tiap branch dibuat dari branch sebelumnya); review
    dilakukan **sekali di branch teratas** dengan
    `git diff main...<branch-teratas>`, dan review ditunda sampai
    backend selesai (keputusan 10 Nov).
-   **Git-5 Push tiap tahap hijau.** Setelah satu tahap (mis. P4a) lulus
    `php artisan test` dan sudah di-commit, langsung
    `git push -u origin <branch>`. Remote: `NielTur/jasa-rumahan`, harus
    **Private**. GitHub dipakai sebagai rekaman dokumentasi (saran
    dosen).
-   **Git-6 Perintah git per blok kecil.** PowerShell tetap menjalankan
    baris berikutnya setelah ada error. Jadi tulis perintah git dalam
    blok pendek (maks 3-4 baris), minta user menempel hasilnya, dan
    **berhenti jika ada `error:` atau `fatal:`**. Sebelum pindah branch:
    `git status` harus bersih (commit dokumen dulu). Perbaikan kecil
    dimulai dari `main` yang bersih, bukan dari branch fitur.

## 9. Format CHANGE REQUEST

    CHANGE REQUEST
    Area: <kontrak | skema DB | aturan | area file milik AI lain>
    Yang diubah: <satu kalimat>
    Alasan: <masalah nyata yang ditemui, bukan preferensi>
    Dampak: <backend / flutter / dokumen / data yang sudah ada>
    Usulan: <before → after, ringkas>
    Alternatif tanpa perubahan: <ada / tidak, dan kenapa kurang baik>

Manusia memutuskan. Setelah disetujui, penulis area yang bersangkutan
yang mengedit.

<!-- ===== FILE: docs/PROGRESS.md ===== -->
# PROGRESS: Catatan Serah-Terima Antar Sesi

> Bagian ini adalah catatan status implementasi dan serah-terima, bukan
> pengganti tiga dokumen rujukan utama pada AI_RULES.md §2. Ringkas,
> faktual, **terbaru di atas**. Tempel bagian **Sekarang** ke prompt
> tugas (`PROMPTS.md` §C), atau pakai prompt lanjut sesi (`PROMPTS.md`
> §E.7). Tambahkan entri baru di paling atas **Log** setiap akhir sesi
> (`PROMPTS.md` §E.4). Rencana kerja berikutnya yang rinci ada di
> [`PLAN_LANJUTAN.md`](PLAN_LANJUTAN.md).

## Catatan terbaru · 18 Nov 2026 · Mitra App Tahap 2 autentikasi diimplementasikan dan dipush

-   Branch kerja Mitra App: `feat/mitra-app`.
-   Tahap 1 fondasi tetap selesai: `MitraApp`, tema Material 3
    (`AppColors` dan `AppTheme`), `AuthGate`, serta widget test halaman awal.
-   Tahap 2 autentikasi sudah diimplementasikan: `AuthService` untuk
    login, registrasi Mitra, pemulihan profil melalui `/auth/me`, dan
    logout; halaman login dan registrasi; serta `AuthGate` untuk
    menentukan tampilan berdasarkan token dan hasil validasi sesi.
-   Model bersama `AuthUser` dan `AuthSession` ditambahkan di
    `packages/core`. `ApiException` juga memiliki pesan fallback 401
    yang lebih sesuai. Tidak ada perubahan backend, endpoint, payload,
    aturan bisnis, atau skema database.
-   Verifikasi dari folder `apps/mitra_app`: `flutter analyze` →
    **No issues found**; `flutter test` → **semua tes lulus (1 test)**;
    `git diff --cached --check` → tidak ada masalah whitespace.
-   Uji manual: buka tanpa token, login Mitra, logout, password salah,
    penolakan akun Customer, registrasi Mitra, dan penolakan nomor
    telepon duplikat berhasil. **Pemulihan sesi setelah aplikasi dibuka
    ulang belum berhasil**; isu ini ditunda untuk diperiksa bersama
    Customer App. Jangan menyatakan seluruh integrasi autentikasi sudah
    lolos tanpa catatan tersebut.
-   Commit: `e212646 feat(mitra): implement authentication flow`.
    Push ke `origin/feat/mitra-app` berhasil. `git ls-remote` dan
    `git rev-parse HEAD` menghasilkan hash yang sama:
    `e212646e1dea687e995662618db31c7335538d88`.
-   Commit autentikasi berisi 11 file. Perubahan lain yang belum
    di-commit tetap terpisah: penghapusan lokal `docs/PLAN_LANJUTAN.md`
    dan `docs/PROGRESS.md`, perubahan `packages/core/lib/model/user_info.dart`
    dan `packages/core/lib/service/api_client.dart`, `README.txt`, serta
    tiga dokumen sumber. Jangan reset, hapus, atau ikut-sertakan perubahan
    tersebut tanpa audit dan persetujuan terpisah.
-   Kontrak API dan keputusan/desain tidak berubah. Dokumen 2 dan 3
    hanya menerima catatan status implementasi frontend; tidak ada
    CHANGE REQUEST.

## Sekarang

  --------------------------------------------------------------------------------------------------------------------------------------------
  Item                                Isi
  ----------------------------------- --------------------------------------------------------------------------------------------------------
  Fase aktif                          Flutter Mitra App --- **Tahap 1 dan implementasi Tahap 2 (autentikasi) selesai; pemulihan sesi masih perlu diperbaiki**.

  Terakhir diperbarui                 18 Nov 2026. Status Mitra App diperbarui berdasarkan output tes, uji manual, commit, push, dan verifikasi hash remote.

  Status Mitra App                    Fondasi aplikasi dan alur autentikasi sudah dipush di branch `feat/mitra-app`; commit terbaru `e212646`.
                                      `flutter test` semua lulus (1 test), `flutter analyze` bersih.

  Status autentikasi Mitra            Login, registrasi, logout, validasi role, dan penolakan input tidak valid telah diimplementasikan.
                                      Pemulihan sesi setelah aplikasi dibuka ulang gagal pada uji manual dan ditunda untuk investigasi bersama Customer App.

  Status backend terakhir             Snapshot historis 11 Nov: P1 auth + katalog + quote · P2 slots + buat order + matching · P3a-c sisi
  terdokumentasi                      mitra + simulate-paid · P4a cancel + confirm-completion + GET payment. Saat itu `php artisan test`: **55
                                      lulus, 239 assertion**. Belum dijalankan ulang dalam sesi 18 Nov.

  Status Customer App                 Catatan terakhir 14 Nov: Home/katalog dan Detail Jasa tersedia; terdapat blocker XHR katalog yang belum
                                      diketahui penyebabnya. Status perbaikan belum diverifikasi dalam sesi 18 Nov.

  Belum terkonfirmasi                 1\) Status aktual P4b/P4c/P5/P6 dan hasil tes backend terbaru. 2) Status terkini bug XHR katalog
                                      Customer App. 3) Isi dan test terbaru branch Customer App. 4) Implementasi Mitra App di luar fondasi
                                      Tahap 1. 5) Status akun Midtrans Sandbox dan konfigurasi lingkungan terbaru.

  Bug yang diketahui                  Bug XHR katalog Customer App per 14 Nov: `GET /api/service-categories` dibatalkan sekitar 13,35 detik
                                      saat dipanggil dari Flutter Web. Penyebab dan status terkini belum diverifikasi.

  3 langkah berikutnya                1\) Investigasi pemulihan sesi Mitra App bersama isu serupa di Customer App; audit `UserInfo`, `ApiClient`,
                                      dan `AuthGate` berdasarkan bukti runtime. 2) Setelah penyebab terisolasi, perbaiki dan uji pemulihan sesi pada
                                      kedua app tanpa mengubah kontrak API. 3) Lanjutkan Tahap 3 Mitra App (dashboard) setelah blocker sesi ditangani.

  Cara menjalankan Mitra App          Dari `apps/mitra_app`:
                                      `flutter run -d chrome --web-hostname=127.0.0.1 --dart-define=API_BASE_URL=http://127.0.0.1:8000/api`.
                                      Backend Laravel harus aktif pada alamat tersebut.
  --------------------------------------------------------------------------------------------------------------------------------------------

### Status terbaru · 14 Nov 2026 · Integrasi Customer App

-   **Frontend yang sudah tersedia:** Customer App dapat dijalankan
    melalui Flutter Web di Chrome. Halaman Home memanggil
    `GET /service-categories` dan `GET /services` melalui
    `CatalogService` → `ApiClient` (Dio). Halaman Detail Jasa juga sudah
    ditambahkan.
-   **Verifikasi Flutter:** `flutter analyze` menghasilkan
    `No issues found`; `flutter test` dari `apps/customer_app`
    menghasilkan **3 tests passed**. Ini hanya memverifikasi analisis
    statis dan widget test, bukan seluruh alur aplikasi.
-   **Verifikasi API manual:** URL
    `http://127.0.0.1:8000/api/service-categories` dan
    `http://127.0.0.1:8000/api/services` berhasil dibuka langsung di
    Chrome dan mengembalikan JSON sukses dengan data kategori/jasa.
-   **BUG PRIORITAS TINGGI --- BELUM SELESAI:** Saat Customer App
    dijalankan di Chrome, request XHR
    `GET http://127.0.0.1:8000/api/service-categories` berstatus
    `(canceled)` setelah sekitar **13,35 detik**. UI menampilkan
    `Koneksi timeout. Periksa jaringan internet kamu.`. `ApiClient`
    memakai timeout 15 detik. Root cause belum diketahui; jangan
    menganggap CORS atau backend sebagai penyebab pasti.
-   **Kondisi pemeriksaan:** `backend/config/cors.php` mengizinkan
    `paths: api/*`, semua origin, method, dan header. Tidak ada error
    CORS merah di DevTools; satu-satunya issue Console yang terlihat
    adalah peringatan `Intl.v8BreakIterator is deprecated`, yang tidak
    terbukti terkait masalah API.
-   **Pemeriksaan lanjutan yang tertunda:** analisis tab Network →
    Timing, cek apakah request dari tombol `Coba lagi` tercatat di
    terminal Laravel, lalu telusuri Dio/XHR dan konfigurasi Flutter Web.
    `HomePage` yang diperiksa memulai pemuatan katalog dari
    `initState()` dan tidak menunjukkan request yang dibuat ulang pada
    setiap rebuild.
-   **Lingkungan uji saat ini:** Flutter dijalankan sebagai
    **Chrome/Web**, bukan Android Emulator. Base URL yang digunakan:
    `http://127.0.0.1:8000/api` melalui
    `--dart-define=API_BASE_URL=...`. Backend dijalankan dari
    `C:\laragon\www\jasa-rumahan\backend` dengan
    `php artisan serve --host=0.0.0.0 --port=8000`; proses server harus
    tetap aktif saat aplikasi diuji.
-   **Prioritas berikutnya:** bug koneksi katalog Customer App wajib
    diperbaiki sebelum pengujian fitur Customer App dilanjutkan. Untuk
    sementara statusnya tetap **OPEN / BLOCKER pengujian katalog**,
    bukan dianggap selesai atau diabaikan sampai backend selesai.

## Versi terpasang (dari output perintah)

  -----------------------------------------------------------------------
  Alat                                Versi
  ----------------------------------- -----------------------------------
  PHP                                 8.3.32

  Laravel                             10.50.4

  Laravel Sanctum                     3.3.3

  Composer                            2.11.1

  MySQL (Laragon)                     8.0.30

  PHPUnit                             \^10 (dari `composer.json`)

  HeidiSQL                            12.1.0.6537

  Flutter / Dart                      3.41.6 / 3.11.4

  Dio / shared_preferences            sudah digunakan pada
                                      `packages/core` menurut audit
                                      percakapan; versi terpasang perlu
                                      dicek dari `pubspec.yaml` aktual

  intl / webview_flutter              status pemasangan perlu diaudit
                                      dari `pubspec.yaml` aktual
  -----------------------------------------------------------------------

Lingkungan: Windows, Laragon, VS Code, PowerShell.

## Repo dan branch

GitHub: https://github.com/NielTur/jasa-rumahan. Tabel branch di bawah
adalah snapshot historis; periksa `git branch -vv`, commit head, dan
working tree sebelum melanjutkan. Branch dapat ditumpuk (stacked), jadi
review/merge mengikuti struktur aktual, bukan asumsi dari tabel lama.

  -----------------------------------------------------------------------------------------
  Branch                          Commit            Isi                   Tes setelah
                                                                          branch ini
  ------------------------------- ----------------- --------------------- -----------------
  `main`                          `09a0859`         P1 + P2 (sebelumnya   belum ada tes
                                                    `97e25bc`) +          otomatis
                                                    perbaikan             
                                                    `.env.example`        
                                                    (APP_KEY dikosongkan) 

  `feat/mitra-setup`              `f9bbefd`         P3a profil, jasa,     12
                                                    jadwal mitra +        
                                                    `phpunit.xml` ke DB   
                                                    test                  

  `feat/mitra-offers`             `52991be`         P3b offers            24
                                                    accept/reject (B-5)   

  `feat/mitra-orders`             `3c64ced`         P3c order mitra,      38
                                                    progres,              
                                                    simulate-paid         

  `feat/order-customer-actions`   `c04bcac` (P4a),  P4a cancel,           **55**
                                  lalu `adefd70`    confirm-completion,   
                                  (perbaikan        GET payment           
                                  `.env.example`,                         
                                  fast-forward) dan                       
                                  `ee82fbd`                               
                                  (dokumen)                               

  `feat/customer-eco-green`       `38e2f4f` (commit Pekerjaan Customer    belum diaudit
                                  yang tercatat     App; update tracking  
                                  saat percakapan   Customer App tercatat 
                                  16 Nov;           pada commit ini       
                                  verifikasi head                         
                                  terbaru tetap                           
                                  diperlukan)                             

  `feat/mitra-app`                `3cb779f`         Fondasi Mitra App     Flutter test 1
                                                    Tahap 1: root app,    lulus; analyze
                                                    tema, placeholder     bersih; Chrome
                                                    AuthGate, widget      berhasil
                                                    test; push berhasil   dijalankan
                                                    18 Nov                
  -----------------------------------------------------------------------------------------

Cek keadaan sebenarnya dengan `git branch -vv` dan
`git log --oneline -5` sebelum mulai; tabel ini ditulis dari output yang
ditempel di chat.

## Catatan teknis yang perlu diingat

-   **Tes memakai MySQL terpisah** `jasa_rumahan_test` (bukan SQLite,
    karena `lockForUpdate` diabaikan di SQLite). `phpunit.xml` memaksa
    `DB_CONNECTION` dan `DB_DATABASE` dengan `force="true"`, tes memakai
    `RefreshDatabase`. DB demo `jasa_rumahan` tidak tersentuh
    (diverifikasi lewat HeidiSQL). Jangan `php artisan config:cache` di
    dev.
-   **Trait fixture tes:**
    `backend/tests/Concerns/BuildsMitraFixtures.php` (`seedFixtures`,
    `makeMitra`, `makeOrder`, `loginSebagai`, `jadwalBesok`). Dipakai
    ulang oleh tes baru.
-   **Tes "accept bersamaan"** berjalan satu proses PHPUnit, jadi hanya
    membuktikan re-check, bukan race nyata. Bukti nyata:
    `dev:race-accept` (P6).
-   **Kernel.php** baru menjadwalkan `orders:process-expired-offers`
    (tiap menit). `payments:expire-pending` dan `orders:auto-complete`
    ditambah di P4c.
-   **Akun demo** (`php artisan migrate:fresh --seed`, password semua
    `password123`): admin `081200000001`; customer `081200000011`,
    `081200000012`; mitra `081200000021`, `081200000022`,
    `081200000023`.
-   **Midtrans:** akun Sandbox belum dibuat. ngrok sudah ada di laptop
    (URL berubah tiap restart). Paket `midtrans/midtrans-php` disetujui,
    dipasang di awal P4b.
-   **Port 3306 sempat diblok Windows.** Fix di PowerShell admin:
    `net stop winnat`,
    `netsh int ipv4 add excludedportrange protocol=tcp startport=3306 numberofports=1`,
    `net start winnat`.

## Log sesi (terbaru di atas)

### 2026-11-18 · Mitra App: Tahap 2 autentikasi diimplementasikan dan dipush

-   AI: ChatGPT · Area: Mitra App Flutter / Shared Core
-   Selesai: `AuthService`, halaman login, halaman registrasi, integrasi
    `AuthGate`, model `AuthUser` dan `AuthSession`, serta fallback pesan
    401 pada `ApiException`.
-   Verifikasi: dari `apps/mitra_app`, `flutter analyze` bersih dan
    `flutter test` seluruh tes lulus (1 test). Pemeriksaan staged diff
    `git diff --cached --check` bersih.
-   Uji manual lulus: aplikasi tanpa token menuju login; login Mitra;
    logout; password salah; akun Customer ditolak; registrasi Mitra;
    nomor telepon duplikat ditolak.
-   Isu tertunda: pemulihan sesi setelah aplikasi dibuka ulang gagal.
    Perbaikan ditunda untuk investigasi bersama Customer App; penyebab
    belum dipastikan.
-   Git: commit `e212646 feat(mitra): implement authentication flow`
    berhasil dipush. Hash remote dan lokal sama:
    `e212646e1dea687e995662618db31c7335538d88`.
-   Perubahan lokal lain tidak ikut commit: perubahan `UserInfo` dan
    `ApiClient`, file `README.txt`, penghapusan dua dokumen lama, serta
    tiga dokumen sumber. Pertahankan apa adanya.
-   Berikutnya: telusuri penyebab pemulihan sesi gagal; jangan mengubah
    kontrak API atau backend tanpa bukti dan persetujuan.

### 2026-11-14 · Customer App: katalog gagal dimuat di Flutter Web

-   AI: ChatGPT · Area: Flutter Customer App / integrasi API
-   Selesai: Flutter Web berhasil dijalankan di Chrome;
    `flutter analyze` bersih; `flutter test` dari `apps/customer_app`
    menghasilkan 3 tes lulus. Endpoint kategori dan jasa berhasil
    mengembalikan JSON saat URL dibuka langsung di Chrome.
-   Bug terbuka: XHR `GET /api/service-categories` dari aplikasi
    berstatus `(canceled)` setelah ±13,35 detik; UI menampilkan pesan
    timeout. CORS sudah diperiksa secara konfigurasi dan tidak ada error
    CORS merah di Console, tetapi penyebab belum terisolasi.
-   Keputusan sementara: **jangan mengubah kontrak API, skema database,
    atau konfigurasi backend secara spekulatif**. Tandai sebagai bug
    prioritas tinggi yang wajib diperbaiki sebelum pengujian katalog
    dilanjutkan. Investigasi dilanjutkan setelah akses/unggahan file
    tersedia.
-   Belum diverifikasi: request `/api/services` dari aplikasi, tab
    Timing, log request saat tombol `Coba lagi` ditekan, dan penyebab
    pembatalan XHR.
-   Berikutnya: lanjutkan investigasi Dio/Flutter Web ↔ Laravel;
    pertahankan bukti hasil tes yang sudah ada dan jangan klaim
    integrasi katalog berhasil.

### 2026-11-11 · Update paket dokumen (tanpa kode)

-   AI: Claude · Fase: P4 (antara P4a dan tes catch-up)
-   Selesai: menulis ulang API_CONTRACT (v0.2, +§10.10, +§11 status
    implementasi), DECISIONS (D-22..D-24, Q-8/Q-9, CR-12..CR-15, jawaban
    dosen), PROGRESS, AI_RULES, PROMPTS, README, dan `PLAN_LANJUTAN.md`
    baru. Dirampingkan: `CLAUDE.md` dan `AGENTS.md` jadi pointer ke
    `AI_RULES.md` (satu salinan aturan); `scripts/bundle-chatgpt.ps1`
    (belum dijalankan di Windows) menggabung dokumen jadi 3 file untuk
    ChatGPT (`PROMPTS.md` §G). `PROMPTS.md` §0 berisi prompt siap tempel
    tanpa isian (T1, T2, Flutter); `scripts/dump-code.ps1` (juga belum
    dijalankan) menggabung kode backend jadi `KODE_BACKEND.txt` untuk
    lampiran.
-   Setengah jadi: dokumen belum di-commit. Empat file tes catch-up (34
    tes) masih hanya ada di chat lama.
-   Keputusan baru: tidak ada keputusan baru, hanya mencatat yang sudah
    disepakati. Item bertanda USULAN tetap USULAN.
-   Bug diketahui: lihat "Sekarang".
-   Berikutnya: 1) T0 housekeeping. 2) T1 tes catch-up. 3) T2 P4c.

### 2026-11-10 · GitHub, push per tahap, dan APP_KEY bocor

-   AI: Claude · Fase: P4
-   Selesai: remote `NielTur/jasa-rumahan` terpasang, semua branch
    di-push. Aturan baru Git-5 (push tiap tahap hijau) dan Git-6
    (perintah git per blok kecil, berhenti jika ada `error:`/`fatal:`).
-   Insiden: `APP_KEY` asli terkirim di `backend/.env.example` dan sudah
    ter-push. Dikosongkan (`APP_KEY=`), di-cherry-pick ke `main`
    (`09a0859`) dan ke branch fitur (`adefd70`); diverifikasi kosong di
    `main` dan `origin/main`. **Belum terkonfirmasi:** user sudah
    menjalankan `php artisan key:generate`, dan repo Private. Riwayat
    git lama masih memuat key lama, jadi key lama harus dianggap bocor
    dan tidak dipakai lagi.
-   Insiden kecil: `git checkout main` gagal karena dokumen belum
    di-commit, PowerShell lanjut menjalankan perintah berikutnya, branch
    fix terbentuk dari branch fitur. Dibereskan dengan fast-forward,
    commit dokumen `ee82fbd`, cherry-pick, hapus branch fix.
-   Keputusan baru: push ke GitHub setelah tiap tahap hijau. Review
    ChatGPT ditunda sampai backend selesai, lalu dilakukan sekali di
    branch teratas.
-   Berikutnya: 1) key:generate + cek Private. 2) Tes catch-up. 3) P4c.

### 2026-11-10 · P4a hijau + hasil konsul dosen

-   AI: Claude · Fase: P4
-   Selesai: P4a (`php artisan test`: 55 lulus, 239 assertion, commit
    `c04bcac`, branch `feat/order-customer-actions`, ditumpuk di
    `feat/mitra-orders`): cancel (`refund_pending` kalau sudah bayar),
    confirm-completion, GET payment (`payment: null` kalau belum ada).
    17 tes `CustomerOrderActionTest`.
-   Setengah jadi: tes catch-up B-13 untuk P1/P2; P4b Midtrans; P4c
    expiry + auto-complete; review ChatGPT.
-   Keputusan baru (konsul dosen 10 Nov): Q-1 Laravel + MySQL boleh.
    Presentasi pertemuan 13. Luaran hanya Manual Book. Minimal 1 API
    eksternal (Midtrans), jadi `simulate-paid` bukan pengganti.
    Disarankan masuk GitHub.
-   Bug diketahui: (kosong)
-   Berikutnya: 1) Daftar Midtrans Sandbox + push GitHub. 2) P4b. 3)
    Skeleton Flutter paralel (ChatGPT).

### 2026-11-10 · P3b hijau, P3c progres mitra + simulate-paid

-   AI: Claude · Fase: P3
-   Selesai: P3b hijau (24 lulus, commit `52991be`,
    `feat/mitra-offers`). P3c hijau (38 lulus, commit `3c64ced`,
    `feat/mitra-orders`): GET `/mitra/orders` (+filter status koma), GET
    `/mitra/orders/{kode}`, POST depart/arrive/start/finish lewat
    `OrderStatusService` (B-4, depart wajib payment paid);
    `POST /dev/orders/{kode}/simulate-paid`; `MitraOrderService`,
    `DevPaymentService`; `MitraOrderTest` (14 tes) + trait
    `BuildsMitraFixtures`.
-   Keputusan baru: CR-14 (rute simulate-paid selalu terdaftar, service
    membalas 404 di luar local). Kontrak §10.6-10.8 ditempel.
-   Bug diketahui: (kosong)
-   Catatan P4c: order `waiting_payment` yang customer-nya tidak pernah
    membuka pembayaran tidak punya baris `payments`. Batas D-06 dihitung
    dari waktu transisi `waiting_payment` di `order_status_histories`
    (D-24).
-   Berikutnya: 1) P4a. 2) Review ChatGPT (ditunda). 3) P4b/P4c.

### 2026-11-10 · P3b offers accept/reject

-   AI: Claude · Fase: P3
-   Selesai: GET `/mitra/offers`, POST accept/reject;
    `MitraOfferService` (B-5: kunci order → mitra_profile → attempt,
    lalu re-check status/mitra_id/attempt/expiry/jadwal);
    `MitraOfferResource`; `MitraOfferTest` (12 tes). Branch
    `feat/mitra-offers`.
-   Keputusan baru: offer menampilkan `expires_at`, tanpa `order_code`
    (D-12, CR-13). Accept membalas `OrderResource`.
-   Berikutnya: P3c.

### 2026-11-10 · P3a mitra setup

-   AI: Claude · Fase: P3
-   Selesai: GET/PUT `/mitra/profile`, `/mitra/services`,
    `/mitra/availabilities`; `MitraSetupService`; `CurrentMitra`;
    `phpunit.xml` dipaksa ke DB `jasa_rumahan_test` (MySQL).
    `php artisan test`: 12 lulus (10 tes `MitraSetupTest` + 2
    `ExampleTest`). Branch `feat/mitra-setup`, commit `f9bbefd`.
-   Keputusan baru: body PUT availabilities dibungkus key
    `availabilities` (CR-12).
-   Bug diketahui: `.phpunit.result.cache` sempat ke-commit; dibuang
    (`git rm --cached`), di-gitignore, commit di-amend.
-   Berikutnya: P3b.

### 2026-11-09 · P1 + P2 selesai (backend)

-   AI: Claude · Fase: P2 selesai, berikutnya P3
-   Selesai (dites manual hijau, commit `97e25bc`, kini di `main`
    sebagai bagian `09a0859`):
    -   P1: auth (register/login/logout/me), katalog publik,
        `PricingService` + `/orders/quote`.
    -   P2a: `OrderStatusService` (satu pintu status),
        `SchedulingService` + `/slots`, `POST /orders` (guest/login,
        snapshot, harga server, anti-dobel), track (429 setelah 5
        gagal), status-history, `customer/orders`, middleware role.
    -   P2b: `MatchingService` (attempt sekuensial D-16), command
        `orders:process-expired-offers` + scheduler tiap menit, lazy
        expiry di endpoint baca. Attempt `offered` pada order yang sudah
        tidak mencari mitra ditutup `expired`.
    -   CR-01 s/d CR-11 tercatat di DECISIONS §5.
-   Setengah jadi: tes otomatis (B-13) belum ada untuk P1/P2 (dicatat
    sebagai tes catch-up).
-   Bug diketahui: (kosong)
-   Berikutnya: P3 backend, daftar Midtrans Sandbox, tanya dosen Q-1.

### 2026-11-09 · CR-01/02 + log perubahan

-   AI: Claude · Fase: P1 selesai, masuk P2
-   Selesai: DECISIONS §5 (CR-01..CR-08), migration kolom pembatalan
    `orders` + payload/refund `payments`, model Order/Payment
    diperbarui, kontrak v0.2 (nama field, contoh JSON §10).
-   Keputusan baru: CR-01..CR-08 disetujui.

### 2026-11-09 · P1 Auth + Katalog + Quote (backend)

-   AI: Claude · Fase: P1
-   Selesai: auth (register customer/mitra, login, logout, me; error
    401/403/422 dicoba), katalog publik, `PricingService` +
    `/orders/quote` (dicoba: 270000, 422 kombinasi tanpa harga, 422
    add-on jasa lain). 10 route api.
-   Keputusan baru: register mengembalikan token; admin ditolak login
    API; kombinasi jasa+ukuran tanpa harga = 422; error field bersarang
    berformat bertitik.

### 2026-11-08 · Backend P0 (migration + model)

-   AI: Claude · Fase: P0
-   Selesai: 16 migration (`migrate:fresh` hijau), 14 model + relasi,
    `config/marketplace.php`.
-   Keputusan baru: Laravel 10 dipasang pakai `--no-security-blocking`
    (EOL, demo lokal saja; CR-08).

### 2026-11-07 · Setup dokumen

-   AI: Claude · Fase: pra-P0
-   Selesai: menyusun AI_RULES, PROMPTS, API_CONTRACT (draft v0.1),
    DECISIONS, README.
-   Catatan: semua D-xx masih USULAN; kontrak belum dibekukan.

<!-- Salin blok ini untuk sesi berikutnya, tempel DI ATAS entri terbaru:
### YYYY-MM-DD · judul singkat
- AI: <nama> · Fase: <P?>
- Selesai: <file + status>
- Setengah jadi: <file + kondisinya>
- Keputusan baru: <ID + isi>
- Bug diketahui: <gejala + file>
- Berikutnya: <maks 3>
-->
<!-- ===== FILE: docs/PLAN_LANJUTAN.md ===== -->
# PLAN_LANJUTAN: Rencana Kerja Setelah P4a

> Dibuat 11 Nov 2026 untuk dilanjutkan di chat berikutnya. Kondisi
> terkini ada di [`PROGRESS.md`](PROGRESS.md); keputusan di
> [`DECISIONS.md`](DECISIONS.md). Kalau ada yang bertentangan,
> `PROGRESS.md` dan kode yang menang, lalu perbaiki file ini. Cara
> memulai chat baru: pakai prompt siap tempel di **`PROMPTS.md` §0**
> (tidak ada yang perlu diisi); untuk tugas lain pakai §E.7.

## Gambaran

Tujuan: demo di **pertemuan 13** dengan backend lengkap (termasuk
Midtrans Sandbox), dua app Flutter yang menjalankan alur utama, dan
**Manual Book** sebagai satu-satunya luaran tertulis.

  ---------------------------------------------------------------------------
  Kode           Pekerjaan       Bergantung     Perkiraan sesi Status
                                 pada           chat           
  -------------- --------------- -------------- -------------- --------------
  T0             Housekeeping    \-             0,5            belum
                 (commit                                       
                 dokumen,                                      
                 key:generate,                                 
                 cek Private)                                  

  T1             Tes catch-up    T0             1              kode tes ada
                 B-13 untuk                                    di chat lama,
                 P1/P2                                         belum disimpan

  T2             P4c: expiry     T1             1              belum
                 bayar +                                       
                 auto-complete                                 

  T3             P5: Admin Blade T2             2              belum

  T4             P4b: Midtrans   T3, akun       2              belum
                 Sandbox         Sandbox, ngrok                

  T5             P6: hardening   T4             1-2            belum
                 backend                                       

  T6             Flutter         T5 (sebagian   4+             belum
                 (customer +     bisa mulai                    
                 mitra), ChatGPT setelah T3)                   

  T7             Manual Book     T6             1-2            belum
  ---------------------------------------------------------------------------

Urutan T2 → T3 → T4 disengaja: P4b butuh akun Sandbox dan tunnel,
sedangkan P4c dan P5 tidak. Backend bisa jalan terus sementara akun
dibuat.

**Aturan di setiap tugas:** mulai dengan preflight 3 baris (G-1); kode
lengkap siap tempel dengan path file (G-6); jangan bilang "sudah dites"
tanpa output (G-7); akhiri dengan langkah uji, yang belum terverifikasi,
baris PROGRESS, dan Catatan Presentasi (G-10); satu tahap = satu branch
= satu commit, lalu push (Git-5); perintah git per blok kecil (Git-6).

**Branch:** tumpukkan di `feat/order-customer-actions` (branch teratas
saat ini). Nama yang diusulkan: `feat/test-catchup-b13` (T1),
`feat/payment-expiry` (T2), `feat/admin-dashboard` (T3),
`feat/midtrans-payment` (T4), `feat/hardening` (T5).

------------------------------------------------------------------------

## T0 · Housekeeping (kerjakan dulu, ±15 menit)

Semua perintah di PowerShell, dari `C:\laragon\www\jasa-rumahan`. Tempel
hasil tiap blok; berhenti kalau ada `error:` atau `fatal:`.

-   [ ] **Cek keadaan git.** `git status` lalu `git branch -vv`.
    Harapan: bersih, branch `feat/order-customer-actions` di commit
    `ee82fbd` (atau lebih baru).
-   [ ] **Masukkan dokumen baru.** Ekstrak zip dokumen ke root repo
    (struktur folder sudah sama, timpa jika ditanya): `README.md`,
    `AI_RULES.md`, `CLAUDE.md`, `AGENTS.md`,
    `scripts/bundle-chatgpt.ps1`, `scripts/dump-code.ps1` di root;
    `API_CONTRACT.md`, `DECISIONS.md`, `PROGRESS.md`, `PROMPTS.md`,
    `PLAN_LANJUTAN.md` di `docs/`. Tambahkan satu baris
    `chatgpt-bundle/` ke `.gitignore`. Lalu `git status` (harapan: 11
    file berubah/baru),
    `git add README.md AI_RULES.md CLAUDE.md AGENTS.md scripts docs .gitignore`,
    `git commit -m "docs: perbarui paket dokumen (kontrak v0.2, keputusan, rencana lanjutan)"`,
    `git push`.
-   [ ] **Buat APP_KEY baru.** `cd backend` lalu
    `php artisan key:generate`. Cek `.env.example` masih `APP_KEY=`
    kosong: `findstr APP_KEY .env.example`. Key lama pernah ter-push,
    jadi anggap sudah bocor; key baru hanya hidup di `.env` lokal. (Efek
    samping dev: token/session lama tidak valid, login ulang.)
-   [ ] **Pastikan repo Private.** Di GitHub: buka repo → Settings →
    General → bagian Danger Zone menampilkan "Change repository
    visibility"; badge di samping nama repo harus "Private". Kalau
    Public, ubah ke Private.
-   [ ] **Cek data master.** Di HeidiSQL (DB `jasa_rumahan`):
    `SELECT COUNT(*) FROM services;` Harapan: 3. Kalau bukan 3, catat
    angkanya di PROGRESS dan jangan lanjut asumsi tes sebelum dicek.
-   [ ] **Perbarui isi Project.** Claude Project: unggah file asli.
    ChatGPT: jalankan
    `powershell -ExecutionPolicy Bypass -File scripts/bundle-chatgpt.ps1`,
    lalu unggah 3 file di `chatgpt-bundle/` (`PROMPTS.md` §G). Isi
    Project tidak sinkron otomatis.

**Selesai bila:** `git status` bersih, push berhasil, `key:generate`
sudah dijalankan, repo Private, COUNT dicatat.

------------------------------------------------------------------------

## T1 · Tes catch-up B-13 (P1/P2)

Konteks: P1 dan P2 dites manual saja. Empat file tes (34 tes) sudah
ditulis di chat sebelumnya tetapi **belum disimpan dan belum
dijalankan**. Target total: 55 + 34 = **89 tes**.

-   [ ] Cari empat file itu di chat lama (nama file ada di pesan itu).
    Kalau tidak ketemu, minta Claude menulis ulang berdasarkan daftar di
    bawah. Jangan menebak isinya dari ingatan.
-   [ ] Buat branch: `git checkout -b feat/test-catchup-b13` (dari
    branch teratas, `git status` bersih dulu).
-   [ ] Simpan ke `backend/tests/Feature/...`, pakai trait
    `BuildsMitraFixtures` bila cocok.
-   [ ] Jalankan `php artisan test`. Harapan: 89 lulus. Tes yang merah
    **bukan otomatis salah tes**: bisa jadi bug asli. Putuskan satu per
    satu (bug kode vs asumsi tes salah) dan catat.
-   [ ] Commit, push (Git-5).

Cakupan minimal yang harus ada:

  -----------------------------------------------------------------------
  Area                                Kasus
  ----------------------------------- -----------------------------------
  Auth                                register customer/mitra (201 +
                                      token), login benar/salah (401),
                                      telepon dinormalisasi `+62` → `0`,
                                      admin ditolak (403), logout
                                      mencabut token, `/auth/me`

  Katalog + quote                     harga benar (mis. 270000),
                                      kombinasi jasa+ukuran tanpa harga
                                      (422), add-on jasa lain (422),
                                      `quantity` \> 20 (422), jasa
                                      nonaktif (404), ubah harga di DB →
                                      quote ikut berubah

  Slots                               6 slot, slot terlalu dekat
                                      `available=false`, slot tanpa mitra
                                      `available=false`, mitra bentrok
                                      membuat slot penuh

  Buat order                          guest dan login (201), total palsu
                                      dari client diabaikan, bukan slot
                                      (422), dobel-tap (409), slot tanpa
                                      mitra (409)

  Lacak                               kode + telepon benar, telepon salah
                                      (404 generik), 5 gagal → 429,
                                      polling sukses tidak terkena 429
                                      (CR-10)

  Transisi status                     transisi valid dan invalid lewat
                                      `OrderStatusService` (409 lewat
                                      API)

  Matching                            attempt dibuat, expired → pindah ke
                                      mitra berikutnya, habis →
                                      `cancelled`
  -----------------------------------------------------------------------

**Dugaan bug yang harus dibuktikan dulu lewat tes:** (1) harga bertipe
string lolos cast di request; (2) `quantity` negatif belum ditolak.
Kalau terbukti, perbaiki di `FormRequest` dan catat sebagai bug yang
ditemukan tes.

------------------------------------------------------------------------

## T2 · P4c: expiry pembayaran + auto-complete

Aturan: D-06, D-07, D-24, B-15, B-4. Semua perubahan status lewat
`OrderStatusService`.

-   [ ] **Preflight.** Tentukan di preflight: sumber waktu
    `waiting_payment` (baris `order_status_histories` terakhir dengan
    `new_status = waiting_payment`); sumber waktu `mitra_completed`
    (idem).
-   [ ] **`PaymentExpiryService` + command `payments:expire-pending`.**
    Dua kasus: (a) `payments.payment_status = pending` dan `expired_at`
    lewat → `expired`, order `waiting_payment` → `cancelled`
    (`cancelled_by = system`, `cancelled_at`); (b) order
    `waiting_payment` yang lewat `PAYMENT_EXPIRY_MINUTES` sejak transisi
    dan **tidak punya payment** → `cancelled`. Jangan sentuh order yang
    payment-nya `paid`.
-   [ ] **`orders:auto-complete`.** `mitra_completed` yang lewat
    `AUTO_COMPLETE_HOURS` → `completed` (`completed_at` terisi, actor
    system).
-   [ ] **Lazy expiry (B-15).** Panggil logika yang sama dari endpoint
    baca: `show`, `track`, `history`, `payment`, `customer/orders`,
    `mitra/orders*`. Cukup per order yang dibaca, bukan scan seluruh
    tabel.
-   [ ] **`Kernel.php`:** `payments:expire-pending` tiap menit,
    `orders:auto-complete` tiap jam. Hapus komentar "menyusul di P4".
-   [ ] **Tes** dengan `Carbon::setTestNow`: bayar belum lewat → tidak
    berubah; lewat → `cancelled`; order tanpa payment lewat batas →
    `cancelled`; payment `paid` tidak dibatalkan; auto-complete
    lewat/belum lewat; lazy via GET; idempoten (command dijalankan dua
    kali). Target ±10 tes.
-   [ ] **Dokumen:** `API_CONTRACT` §9 dan §11; `DECISIONS` §1
    (D-06/D-07/D-24 pindah ke "dipakai di kode"), G-07 dan G-09 jadi
    TUTUP; baris PROGRESS.

**Selesai bila:** semua tes hijau; di Postman, order `waiting_payment`
yang dibiarkan lewat batas berubah `cancelled` hanya dengan memanggil
GET; `php artisan schedule:list` menampilkan 3 command.

------------------------------------------------------------------------

## T3 · P5: Admin Dashboard (Blade)

Aturan: A-1..A-4, B-1, B-12, D-20, G-04. Blade memakai Service yang sama
dengan API, tidak memanggil API lewat HTTP.

-   [ ] **Auth admin.** Guard `web` (session), login telepon + password,
    middleware role admin, CSRF aktif. Customer/mitra yang mencoba masuk
    ditolak. Layout Bootstrap 5 tunggal
    (`resources/views/layouts/admin.blade.php`).
-   [ ] **Dashboard** ringkas: jumlah order per status, order hari ini,
    payment `refund_pending`.
-   [ ] **CRUD master:** `service-categories`, `services`,
    `property-types`, `property-sizes`, `service-prices`,
    `service-addons`. Tombol **Nonaktifkan** (bukan hapus) untuk data
    yang pernah dipakai (A-3, B-12). Harga hanya lewat form (A-4).
-   [ ] **Akun dan mitra:** daftar `users`, daftar `mitra` +
    approve/suspend (relevan saat `MITRA_AUTO_APPROVE=false`).
-   [ ] **Orders dan payments:** daftar dengan pagination + filter
    status; detail order (riwayat status); tombol **paksa selesai**
    (lewat `OrderStatusService`, actor admin); tombol **tandai
    refunded** / **refund gagal** untuk payment `refund_pending` (D-08;
    isi `refunded_at`).
-   [ ] **Tes:** guest dialihkan ke login; customer/mitra tidak bisa
    membuka `/admin`; ubah harga lewat form → `/orders/quote` berubah
    (A-4); nonaktifkan jasa → tidak muncul di katalog publik; paksa
    selesai menulis riwayat status; tandai refunded mengisi
    `refunded_at`.
-   [ ] **Dokumen:** kontrak §8 jadi "sudah diimplementasi", §11,
    PROGRESS. Screenshot untuk Manual Book (T7).

**Selesai bila:** admin mengubah harga lewat form, dan customer yang
memanggil `/orders/quote` melihat harga baru tanpa menyentuh kode.

------------------------------------------------------------------------

## T4 · P4b: Midtrans Sandbox

Aturan: B-7, B-3, F-8, D-11, D-22, D-23. Ini satu-satunya API eksternal
yang dipersyaratkan dosen, jadi **bukan opsional**.

**Prasyarat (manusia, sebelum sesi):**

-   [ ] Buat akun Midtrans **Sandbox** (bukan Production). Ambil Server
    Key dan Client Key dari Settings → Access Keys.
-   [ ] Isi `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`,
    `MIDTRANS_IS_PRODUCTION=false` di `backend/.env` saja. **Jangan** di
    `.env.example`, jangan di chat, jangan di commit (G-9).
-   [ ] ngrok: `ngrok http 8000`. URL berubah tiap restart; isi
    **Payment Notification URL** di dashboard Sandbox dengan
    `https://<tunnel>/api/webhooks/midtrans` dan perbarui setiap tunnel
    baru.
-   [ ] Konfirmasi versi: `composer show laravel/framework` (G-8), lalu
    `composer require midtrans/midtrans-php` (sudah disetujui; catat
    versi terpasang di PROGRESS).

**Tugas:**

-   [ ] `config/midtrans.php` (dari `.env`); `MidtransService` yang
    membungkus library (buat Snap, ambil status). Dibungkus supaya tes
    bisa memalsukannya tanpa internet.
-   [ ] **`POST /orders/{kode}/payments`:** hanya dari
    `waiting_payment`; `gateway_order_id = {kode}-{n}` (D-11); `amount`
    = `total_price` order (integer); simpan `payments` `pending`,
    `expired_at`; balas `snap_token`, `redirect_url`, `expired_at`. Jika
    sudah ada payment `pending` yang masih berlaku, pakai ulang.
-   [ ] **`POST /webhooks/midtrans`:** verifikasi `signature_key` =
    SHA512(`order_id` + `status_code` + `gross_amount` + ServerKey) dari
    string persis diterima; cari payment lewat `gateway_order_id`;
    abaikan status yang "lebih mundur"; cocokkan `gross_amount`; update
    dalam transaksi + simpan payload mentah (`gateway_payload`); `paid`
    dan order `waiting_payment` → `OrderStatusService` ke `confirmed`;
    balas 200. Signature salah → 403. D-23: `paid` untuk order yang
    sudah `cancelled` → order tetap `cancelled`, payment
    `refund_pending`.
-   [ ] **Fallback Status API:** `GET /orders/{kode}/payment` menanyakan
    Midtrans bila `pending`, memakai logika update yang sama dengan
    webhook.
-   [ ] **D-22:** cancel customer saat payment `pending` mencoba
    membatalkan transaksi di Midtrans (best-effort, gagal tidak
    menggagalkan cancel).
-   [ ] **Tes** (Midtrans dipalsukan): signature salah, duplikat,
    out-of-order (`pending` setelah `settlement`), amount beda,
    `order_id` tak dikenal (putuskan di preflight: 200 + log supaya
    Midtrans tidak retry terus), `paid` untuk order `cancelled` (D-23),
    mitra tidak bisa `depart` sebelum `paid`.
-   [ ] **Uji nyata di Sandbox** dengan Payment Simulator Midtrans: buat
    order → mitra accept → bayar via Snap → webhook masuk → order
    `confirmed`. Catat hasilnya (G-7).
-   [ ] **Dokumen:** kontrak §7 (hapus label "belum"), §10 contoh
    `POST /payments` dan webhook, §11; DECISIONS D-11/D-22/D-23 jadi
    dipakai; PROGRESS.

**Selesai bila:** satu pembayaran Sandbox sungguhan berhasil ujung ke
ujung dan semua tes webhook hijau.

------------------------------------------------------------------------

## T5 · P6: hardening backend

-   [ ] **`dev:race-accept`.** Command artisan yang menyiapkan data lalu
    menjalankan dua proses `php artisan` sekaligus (Symfony Process)
    memanggil accept untuk attempt yang sama; tepat satu boleh sukses.
    Skenario pasti (double submit satu mitra vs accept melawan
    `process-expired-offers`) ditetapkan di preflight karena matching
    sekuensial hanya menawari satu mitra per attempt. Hasil dicatat di
    PROGRESS sebagai bukti B-5 yang tidak diberikan tes PHPUnit.
-   [ ] **Postman collection** untuk alur utama (auth → katalog → quote
    → order → mitra accept → bayar → progres → konfirmasi) di
    `docs/postman/`. Ini sekaligus verifikasi kontrak §10.10 yang masih
    "belum diuji Postman"; perbaiki kontrak lewat CR jika ada beda.
-   [ ] **Review silang sekali** oleh ChatGPT: tempel
    `git diff main...<branch-teratas>` dengan prompt `PROMPTS.md` §E.1.
    Perbaiki Blocker dan Major; catat sisanya.
-   [ ] **Merge.** Dari `main` bersih:
    `git merge --ff-only <branch-teratas>`, lalu `git push`. Hapus
    branch lama hanya setelah `main` terbukti memuat semuanya.
-   [ ] **Uji "clone baru".** Clone ke folder kosong, ikuti README apa
    adanya sampai `migrate:fresh --seed` dan `php artisan test`.
    Perbaiki README untuk tiap langkah yang macet.
-   [ ] **Bekukan kontrak v1.0** (ubah header `API_CONTRACT`), dan ubah
    status D-xx yang sudah disetujui kelompok jadi DISEPAKATI.

**Selesai bila:** orang lain bisa clone → jalankan → demo hanya dengan
README; `main` = semua kode hijau.

------------------------------------------------------------------------

## T6 · Flutter (ChatGPT)

Penulis: ChatGPT; reviewer: Claude (kebalikan dari backend). Aturan:
F-1..F-13, D-21.

### Mitra App

**Tahap 1 --- Fondasi aplikasi: SELESAI (18 Nov 2026).**

-   [x] Struktur awal `apps/mitra_app`.
-   [x] `MitraApp` sebagai root aplikasi.
-   [x] Tema Material 3 melalui `AppColors` dan `AppTheme`.
-   [x] Placeholder `AuthGate` sebagai titik masuk alur autentikasi.
-   [x] Widget test halaman awal.
-   [x] `dart format`, `flutter test` (1 test lulus), `flutter analyze`
    (No issues found), dan uji jalan di Chrome.
-   [x] Commit `3cb779f` dan push ke `origin/feat/mitra-app`.

**Tahap 2 --- Autentikasi Mitra: IMPLEMENTASI SELESAI; satu isu integrasi tertunda.**

-   [x] Audit kontrak `POST /auth/register/mitra`, `POST /auth/login`,
    `GET /auth/me`, dan `POST /auth/logout`.
-   [x] Audit implementasi Shared Core yang digunakan untuk token,
    request API, dan exception.
-   [x] Implementasikan halaman login dan registrasi Mitra sesuai
    field dan response kontrak.
-   [x] Implementasikan penyimpanan token dan pemanggilan `/auth/me`
    untuk validasi sesi.
-   [x] Hubungkan `AuthGate` dengan status autentikasi, login,
    registrasi, retry, dan logout.
-   [x] Jalankan `flutter test` dan `flutter analyze` dari
    `apps/mitra_app`; seluruh tes lulus (1 test) dan analyzer bersih.
-   [x] Commit `e212646` dan push ke `origin/feat/mitra-app`; hash remote
    diverifikasi sama dengan `HEAD`.
-   [ ] Perbaiki pemulihan sesi setelah aplikasi dibuka ulang. Uji manual
    gagal; investigasi ditunda untuk dilakukan bersama Customer App.
-   [ ] Setelah isu sesi ditangani, lanjut ke Tahap 3: dashboard Mitra.

Perintah standar menjalankan Mitra App dari folder `apps/mitra_app`:

``` powershell
flutter run -d chrome --web-hostname=127.0.0.1 --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

Jangan mengubah kontrak API, skema database, atau aturan bisnis dalam
Tahap 2. Jika implementasi Shared Core atau backend berbeda dari
kontrak, catat discrepancy dan ajukan CHANGE REQUEST sesuai aturan.

## T7 · Manual Book

Format bebas (tidak ada template dari dosen); isi = panduan memakai
aplikasi. Gunakan `PROMPTS.md` §E.8 per bagian, hanya untuk fitur yang
benar-benar jalan.

1.  **Pendahuluan:** tujuan aplikasi, peran (Customer, Mitra, Admin),
    gambaran alur pesanan.
2.  **Persiapan:** instal/jalankan aplikasi, akun demo (README), catatan
    pembayaran Sandbox.
3.  **Panduan Customer:** daftar/masuk atau guest, cari jasa, pilih
    ukuran + add-on, pilih slot, buat pesanan, pantau status, bayar,
    konfirmasi selesai, batalkan, lacak pesanan sebagai guest.
4.  **Panduan Mitra:** daftar/masuk, atur profil, pilih jasa, atur
    jadwal, terima/tolak tawaran, jalankan pesanan (berangkat, tiba,
    mulai, selesai).
5.  **Panduan Admin:** login, kelola kategori/jasa/harga/add-on, approve
    mitra, pantau order dan payment, paksa selesai, tandai refund.
6.  **Troubleshooting:** pesan error umum (jadwal bentrok, tidak ada
    mitra, pembayaran kedaluwarsa) dan artinya.
7.  **Lampiran:** daftar akun demo, daftar status pesanan dan artinya,
    keterbatasan yang jujur (mis. refund manual, tanpa push
    notification).

**Selesai bila:** orang yang belum pernah melihat aplikasi bisa
menyelesaikan satu pesanan penuh hanya dengan membaca Manual Book.

------------------------------------------------------------------------

## Yang butuh tindakan manusia (bukan AI)

  -----------------------------------------------------------------------
  Apa                     Kapan                   Catatan
  ----------------------- ----------------------- -----------------------
  Tanya dosen: Q-8 (perlu secepatnya              jawab di DECISIONS §3
  APK?), Q-9 (Sandbox                             
  cukup?), tanggal pasti                          
  pertemuan 13                                    

  Isi tabel tim di README sebelum T7              Manual Book perlu nama
  (Q-6)                                           dan peran

  Buat akun Midtrans      sebelum T4              kunci hanya di `.env`
  Sandbox + ambil kunci                           

  Jawab Q-5 (desain UI)   sebelum T6              
  dan Q-4 (lingkungan                             
  demo)                                           

  Setujui atau ubah D-22, sebelum T2 dan T4       usulan Claude, belum
  D-23, D-24                                      disetujui

  Perbarui isi Project    tiap sesi besar         tidak sinkron otomatis
  Claude dan ChatGPT                              
  setelah dokumen berubah                         
  -----------------------------------------------------------------------
