// apps/customer_app/test/widget_test.dart
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:core/core.dart';
import 'package:customer_app/main.dart';
import 'package:customer_app/service/catalog_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

class FakeCatalogService extends CatalogService {
  @override
  Future<List<ServiceCategory>> getCategories() async {
    return const [ServiceCategory(id: 1, categoryName: 'Perawatan Rumah')];
  }

  @override
  Future<List<ServiceItem>> getServices({int? categoryId}) async {
    return const [
      ServiceItem(
        id: 1,
        categoryId: 1,
        serviceName: 'Bersih-bersih rumah',
        durationMinutes: 120,
      ),
    ];
  }
}

void main() {
  // Penyimpanan lokal dipalsukan supaya halaman Pesanan tidak menunggu
  // SharedPreferences asli (yang tidak tersedia di lingkungan tes).
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  Widget createTestApp() {
    return MyApp(catalogService: FakeCatalogService());
  }

  testWidgets('Customer App menampilkan halaman beranda', (tester) async {
    await tester.pumpWidget(createTestApp());
    await tester.pumpAndSettle();

    expect(find.text('Pilih layanan'), findsOneWidget);
    expect(find.text('Bersih-bersih rumah'), findsOneWidget);
    expect(find.text('Beranda'), findsOneWidget);
  });

  testWidgets('Navigasi membuka halaman pesanan', (tester) async {
    await tester.pumpWidget(createTestApp());
    await tester.pumpAndSettle();

    await tester.tap(find.text('Pesanan'));
    await tester.pumpAndSettle();

    expect(find.text('Belum ada pesanan'), findsOneWidget);
  });

  testWidgets('Navigasi membuka halaman akun', (tester) async {
    await tester.pumpWidget(createTestApp());
    await tester.pumpAndSettle();

    await tester.tap(find.text('Akun'));
    await tester.pumpAndSettle();

    expect(find.text('Pengguna Tamu'), findsOneWidget);
  });
}
