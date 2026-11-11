import 'package:shared_preferences/shared_preferences.dart';

class UserInfo {
  static const String _tokenKey = 'auth_token';
  static const String _orderCodeKey = 'guest_order_code';
  static const String _phoneKey = 'guest_phone';

  // Menyimpan token setelah login atau registrasi berhasil.
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

  // Menyimpan identitas guest untuk kebutuhan tracking pesanan.
  static Future<void> saveGuestOrder({
    required String orderCode,
    required String phone,
  }) async {
    final prefs = await SharedPreferences.getInstance();

    await prefs.setString(_orderCodeKey, orderCode);
    await prefs.setString(_phoneKey, phone);
  }

  static Future<String?> getGuestOrderCode() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_orderCodeKey);
  }

  static Future<String?> getGuestPhone() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_phoneKey);
  }

  static Future<void> clearGuestOrder() async {
    final prefs = await SharedPreferences.getInstance();

    await prefs.remove(_orderCodeKey);
    await prefs.remove(_phoneKey);
  }
}
