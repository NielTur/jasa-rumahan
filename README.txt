PATCH DETAIL JASA — CUSTOMER APP

File yang perlu disalin ke root repository Flutter (pertahankan struktur folder):

1. packages/core/lib/model/service_detail.dart       [BARU]
2. packages/core/lib/core.dart                       [GANTI]
3. apps/customer_app/lib/service/catalog_service.dart [GANTI]
4. apps/customer_app/lib/ui/service_detail_page.dart  [BARU]
5. apps/customer_app/lib/ui/widgets/catalog_widgets.dart [GANTI]

Tidak perlu mengubah home_page.dart. ServiceTile sekarang membuka ServiceDetailPage saat ditekan.

Setelah menyalin file:
- Jalankan `dart format packages/core/lib/model/service_detail.dart packages/core/lib/core.dart apps/customer_app/lib/service/catalog_service.dart apps/customer_app/lib/ui/service_detail_page.dart apps/customer_app/lib/ui/widgets/catalog_widgets.dart`
- Jalankan `flutter pub get` dari root workspace jika diperlukan.
- Jalankan `flutter analyze` dari root workspace.
- Jalankan test yang sudah ada.

Catatan:
- Endpoint yang dipakai hanya GET /services/{id}.
- Model mengikuti bentuk detail pada docs/API_CONTRACT.md: addons[] dan prices[] yang berisi sizes[].
- Service list tidak menampilkan harga; harga hanya ditampilkan di halaman detail.
- Belum ada POST /orders/quote atau proses pemesanan pada patch ini.
- Kode belum diuji di repository aktual karena working tree proyek tidak tersedia pada sesi ini.
