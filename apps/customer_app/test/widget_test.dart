import 'package:flutter_test/flutter_test.dart';
import 'package:customer_app/main.dart';

void main() {
  testWidgets('Customer App menampilkan halaman beranda', (tester) async {
    await tester.pumpWidget(const MyApp());

    expect(find.text('Pilih layanan'), findsOneWidget);
    expect(find.text('Bersih-bersih rumah'), findsOneWidget);
    expect(find.text('Beranda'), findsOneWidget);
  });

  testWidgets('Navigasi membuka halaman pesanan', (tester) async {
    await tester.pumpWidget(const MyApp());
    await tester.tap(find.text('Pesanan'));
    await tester.pumpAndSettle();

    expect(find.text('Belum ada pesanan'), findsOneWidget);
  });

  testWidgets('Navigasi membuka halaman akun', (tester) async {
    await tester.pumpWidget(const MyApp());
    await tester.tap(find.text('Akun'));
    await tester.pumpAndSettle();

    expect(find.text('Pengguna tamu'), findsOneWidget);
  });
}
