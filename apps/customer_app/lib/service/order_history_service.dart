import 'package:core/core.dart';

class OrderHistoryService {
  final ApiClient _apiClient = ApiClient.instance;

  // Customer login mengambil riwayat dari endpoint khusus customer.
  // Guest hanya dapat mengambil pesanan terakhir yang tersimpan di perangkat.
  Future<List<Map<String, dynamic>>> getOrders() async {
    final token = await UserInfo.getToken();

    if (token != null && token.isNotEmpty) {
      final response = await _apiClient.get(
        '/customer/orders',
        queryParameters: {'per_page': 15},
      );

      final data = response.data;
      if (data is! Map || data['data'] is! List) {
        throw const ApiException(
          message: 'Format riwayat pesanan dari server tidak sesuai.',
        );
      }

      return (data['data'] as List)
          .map((item) => Map<String, dynamic>.from(item as Map))
          .toList();
    }

    final orderCode = await UserInfo.getGuestOrderCode();
    final phone = await UserInfo.getGuestPhone();

    if (orderCode == null ||
        orderCode.isEmpty ||
        phone == null ||
        phone.isEmpty) {
      return [];
    }

    final response = await _apiClient.post(
      '/orders/track',
      data: {'order_code': orderCode, 'phone': phone},
    );

    final data = response.data;
    if (data is! Map || data['data'] is! Map) {
      throw const ApiException(
        message: 'Format pelacakan pesanan dari server tidak sesuai.',
      );
    }

    return [Map<String, dynamic>.from(data['data'] as Map)];
  }
}
