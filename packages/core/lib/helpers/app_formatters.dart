import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';

class AppFormatters {
  static Future<void> initialize() async {
    await initializeDateFormatting('id_ID');
  }

  // API menggunakan integer rupiah, tanpa desimal.
  static String currency(int amount) {
    final formatter = NumberFormat.currency(
      locale: 'id_ID',
      symbol: 'Rp ',
      decimalDigits: 0,
    );

    return formatter.format(amount);
  }

  static String date(DateTime dateTime) {
    return DateFormat('dd MMMM yyyy', 'id_ID').format(_toWib(dateTime));
  }

  static String time(DateTime dateTime) {
    return DateFormat('HH:mm', 'id_ID').format(_toWib(dateTime));
  }

  static String dateTime(DateTime dateTime) {
    return DateFormat('dd MMMM yyyy, HH:mm', 'id_ID').format(_toWib(dateTime));
  }

  // Konversi eksplisit ke WIB (UTC+7).
  static DateTime _toWib(DateTime dateTime) {
    return dateTime.toUtc().add(const Duration(hours: 7));
  }
}
