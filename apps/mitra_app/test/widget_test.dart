import 'package:flutter_test/flutter_test.dart';
import 'package:mitra_app/main.dart';

void main() {
  testWidgets('Mitra App menampilkan halaman awal', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const MitraApp());

    expect(find.text('Mitra Jasa Rumahan'), findsOneWidget);
    expect(find.text('Fondasi aplikasi berhasil disiapkan.'), findsOneWidget);
  });
}
