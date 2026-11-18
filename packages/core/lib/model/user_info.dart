import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class UserInfo {
  static const String _tokenKey = 'auth_token';
  static const String _guestOrdersKey = 'guest_orders';

  // --- Token (login/register) ---

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  // --- Guest orders (F-12: simpan order_code + phone secara lokal) ---
  // Disimpan sebagai JSON list: [{"order_code":"JR-XXX","phone":"08..."},...]

  /// Menambahkan pesanan guest ke daftar lokal.
  /// Kalau kode pesanan sudah ada, tidak ditambah lagi (idempoten).
  static Future<void> saveGuestOrder({
    required String orderCode,
    required String phone,
  }) async {
    final orders = await getGuestOrders();

    // Cek duplikat berdasarkan order_code (case-insensitive).
    final exists = orders.any(
      (o) => o['order_code']?.toUpperCase() == orderCode.toUpperCase(),
    );

    if (!exists) {
      orders.insert(0, {'order_code': orderCode, 'phone': phone});
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_guestOrdersKey, jsonEncode(orders));
  }

  /// Mengambil seluruh daftar pesanan guest dari penyimpanan lokal.
  static Future<List<Map<String, String>>> getGuestOrders() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_guestOrdersKey);

    if (raw == null || raw.isEmpty) return [];

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) return [];

      return decoded
          .whereType<Map>()
          .map(
            (item) => {
              'order_code': item['order_code']?.toString() ?? '',
              'phone': item['phone']?.toString() ?? '',
            },
          )
          .where((item) => item['order_code']!.isNotEmpty)
          .toList();
    } catch (_) {
      return [];
    }
  }

  /// Mengambil kode pesanan guest terakhir (untuk backward-compatibility).
  static Future<String?> getGuestOrderCode() async {
    final orders = await getGuestOrders();
    return orders.isEmpty ? null : orders.first['order_code'];
  }

  /// Mengambil nomor HP guest terakhir (untuk backward-compatibility).
  static Future<String?> getGuestPhone() async {
    final orders = await getGuestOrders();
    return orders.isEmpty ? null : orders.first['phone'];
  }

  /// Menghapus seluruh daftar pesanan guest.
  static Future<void> clearGuestOrder() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_guestOrdersKey);
  }
}
