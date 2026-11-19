import 'package:core/core.dart';

class CustomerOrderActionService {
  final ApiClient _apiClient = ApiClient.instance;

  Future<Map<String, dynamic>> cancelOrder({
    required String orderCode,
    String? reason,
  }) async {
    final data = await _guestIdentity(orderCode);

    if (reason != null && reason.trim().isNotEmpty) {
      data['reason'] = reason.trim();
    }

    return _sendAction('/orders/$orderCode/cancel', data);
  }

  Future<Map<String, dynamic>> confirmCompletion({
    required String orderCode,
  }) async {
    final data = await _guestIdentity(orderCode);

    return _sendAction('/orders/$orderCode/confirm-completion', data);
  }

  /// Guest mencari nomor HP berdasarkan kode pesanan yang dipilih.
  Future<Map<String, dynamic>> _guestIdentity(String orderCode) async {
    final token = await UserInfo.getToken();

    if (token != null && token.isNotEmpty) {
      return {};
    }

    final guestOrders = await UserInfo.getGuestOrders();

    for (final entry in guestOrders) {
      final code = entry['order_code'] ?? '';

      if (code.toUpperCase() == orderCode.toUpperCase()) {
        final phone = entry['phone'] ?? '';

        if (phone.isNotEmpty) {
          return {'phone': phone};
        }
      }
    }

    throw const ApiException(
      message: 'Identitas pesanan guest tidak ditemukan.',
    );
  }

  Future<Map<String, dynamic>> _sendAction(
    String path,
    Map<String, dynamic> data,
  ) async {
    final response = await _apiClient.post(path, data: data);

    final responseData = response.data;

    if (responseData is! Map || responseData['data'] is! Map) {
      throw const ApiException(
        message: 'Format respons tindakan pesanan tidak sesuai.',
      );
    }

    return Map<String, dynamic>.from(responseData['data'] as Map);
  }
}
