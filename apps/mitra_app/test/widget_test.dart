import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:mitra_app/main.dart';

void main() {
  testWidgets('Mitra App menampilkan halaman login', (
    WidgetTester tester,
  ) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const MitraApp());
    await tester.pumpAndSettle();

    expect(find.byType(TextFormField), findsNWidgets(2));
    expect(find.text('Belum memiliki akun?'), findsOneWidget);
    expect(find.text('Daftar'), findsOneWidget);
  });
}
