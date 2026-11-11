import 'package:core/core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  setUpAll(() async {
    await AppFormatters.initialize();
  });

  group('AppFormatters', () {
    final dateTime = DateTime.utc(2026, 11, 11, 3, 5);

    test('mengubah nominal menjadi format rupiah', () {
      final result = AppFormatters.currency(1500000);

      expect(result.replaceAll(RegExp(r'\s'), ''), 'Rp1.500.000');
    });

    test('mengubah tanggal ke format Indonesia', () {
      final result = AppFormatters.date(dateTime);

      expect(result, '11 November 2026');
    });

    test('mengubah waktu ke WIB', () {
      final result = AppFormatters.time(dateTime);

      expect(result, '10:05');
    });

    test('menggabungkan tanggal dan waktu WIB', () {
      final result = AppFormatters.dateTime(dateTime);

      expect(result, '11 November 2026, 10:05');
    });
  });
}
