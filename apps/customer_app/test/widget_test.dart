import 'package:flutter_test/flutter_test.dart';
import 'package:customer_app/main.dart';

void main() {
  testWidgets('Customer App menampilkan halaman beranda', (tester) async {
    await tester.pumpWidget(const MyApp());

    expect(find.text('Jasa Rumahan'), findsOneWidget);
    expect(find.text('Selamat datang!'), findsOneWidget);
    expect(find.text('Kategori layanan'), findsOneWidget);
  });

  testWidgets('Customer dapat membuka halaman pesanan', (tester) async {
    await tester.pumpWidget(const MyApp());

    await tester.tap(find.text('Pesanan'));
    await tester.pumpAndSettle();

    expect(find.text('Pesanan kamu'), findsOneWidget);
  });

  testWidgets('Customer dapat membuka halaman akun', (tester) async {
    await tester.pumpWidget(const MyApp());

    await tester.tap(find.text('Akun'));
    await tester.pumpAndSettle();

    expect(find.text('Akses akun'), findsOneWidget);
  });
}
