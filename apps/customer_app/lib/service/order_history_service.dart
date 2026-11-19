import 'package:core/core.dart';

class OrderHistoryService {
  final ApiClient _apiClient = ApiClient.instance;

  /// Mengambil daftar pesanan customer.
  /// Login: mengambil dari server.
  /// Guest: mengambil berdasarkan identitas lokal.
  Future<List<Map<String, dynamic>>> getOrders() async {
    final token = await UserInfo.getToken();

    if (token != null && token.isNotEmpty) {
      final response = await _apiClient.get(
        '/customer/orders',
        queryParameters: {'per_page': 50},
      );

      final data = response.data;

      if (data is! Map || data['data'] is! List) {
        throw const ApiException(
          message: 'Format riwayat pesanan tidak sesuai.',
        );
      }

      return (data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item as Map))
          .toList();
    }

    final guestOrders = await UserInfo.getGuestOrders();
    if (guestOrders.isEmpty) return [];

    final results = <Map<String, dynamic>>[];

    for (final entry in guestOrders) {
      final code = entry['order_code'] ?? '';
      final phone = entry['phone'] ?? '';

      if (code.isEmpty || phone.isEmpty) continue;

      try {
        final response = await _apiClient.post(
          '/orders/track',
          data: {'order_code': code, 'phone': phone},
        );

        results.add(_extractOrder(response.data));
      } on ApiException {
        // Pesanan yang tidak ditemukan dilewati.
      }
    }

    return results.reversed.toList();
  }

  /// Mengambil detail pesanan berdasarkan kode.
  Future<Map<String, dynamic>> getOrderDetail(String orderCode) async {
    final token = await UserInfo.getToken();

    if (token != null && token.isNotEmpty) {
      final response = await _apiClient.get('/orders/$orderCode');
      return _extractOrder(response.data);
    }

    final phone = await _findGuestPhone(orderCode);

    if (phone == null) {
      throw const ApiException(
        message: 'Identitas pesanan guest tidak ditemukan.',
      );
    }

    final response = await _apiClient.post(
      '/orders/track',
      data: {'order_code': orderCode, 'phone': phone},
    );

    return _extractOrder(response.data);
  }

  /// Mengambil riwayat perubahan status.
  Future<List<Map<String, dynamic>>> getStatusHistory(String orderCode) async {
    final token = await UserInfo.getToken();
    Map<String, dynamic>? query;

    if (token == null || token.isEmpty) {
      final phone = await _findGuestPhone(orderCode);

      if (phone == null) {
        throw const ApiException(message: 'Nomor HP guest tidak ditemukan.');
      }

      query = {'phone': phone};
    }

    final response = await _apiClient.get(
      '/orders/$orderCode/status-history',
      queryParameters: query,
    );

    final data = response.data;

    if (data is! Map || data['data'] is! List) {
      throw const ApiException(message: 'Format riwayat status tidak sesuai.');
    }

    return (data['data'] as List)
        .map((item) => Map<String, dynamic>.from(item as Map))
        .toList();
  }

  /// Mencari nomor HP berdasarkan kode pesanan guest.
  Future<String?> _findGuestPhone(String orderCode) async {
    final guestOrders = await UserInfo.getGuestOrders();

    for (final entry in guestOrders) {
      final code = entry['order_code'] ?? '';

      if (code.toUpperCase() == orderCode.toUpperCase()) {
        final phone = entry['phone'] ?? '';
        return phone.isEmpty ? null : phone;
      }
    }

    return null;
  }

  Map<String, dynamic> _extractOrder(dynamic responseData) {
    if (responseData is! Map || responseData['data'] is! Map) {
      throw const ApiException(message: 'Format detail pesanan tidak sesuai.');
    }

    return Map<String, dynamic>.from(responseData['data'] as Map);
  }
}
