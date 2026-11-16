import 'package:core/core.dart';

class OrderHistoryService {
  final ApiClient _apiClient = ApiClient.instance;

  /// Mengambil daftar pesanan.
  /// - Customer login: dari API `/customer/orders`.
  /// - Guest: dari daftar lokal (order_code + phone), di-track satu per satu.
  Future<List<Map<String, dynamic>>> getOrders() async {
    final token = await UserInfo.getToken();

    if (token != null && token.isNotEmpty) {
      // Customer sudah login → ambil dari API.
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

    // Guest → ambil dari penyimpanan lokal, tracking satu per satu.
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
        // Pesanan mungkin sudah tidak ada di server; lewati saja.
      }
    }

    return results;
  }

  /// Mengambil detail pesanan berdasarkan order_code.
  Future<Map<String, dynamic>> getOrderDetail(String orderCode) async {
    final token = await UserInfo.getToken();

    if (token != null && token.isNotEmpty) {
      final response = await _apiClient.get('/orders/$orderCode');
      return _extractOrder(response.data);
    }

    // Guest: cari phone yang cocok dari daftar lokal.
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

  /// Mengambil riwayat status pesanan.
  Future<List<Map<String, dynamic>>> getStatusHistory(
    String orderCode,
  ) async {
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

  /// Mencari nomor HP guest yang sesuai dengan order_code dari penyimpanan lokal.
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
