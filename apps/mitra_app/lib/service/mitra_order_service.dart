import 'package:core/core.dart';

class MitraOrderService {
  MitraOrderService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;
  static const activeStatuses =
      'waiting_payment,confirmed,on_the_way,arrived,in_progress,mitra_completed';

  Future<List<MitraOrder>> getOrders() async {
    final response = await _apiClient.get(
      '/mitra/orders',
      queryParameters: {'status': activeStatuses, 'page': 1, 'per_page': 50},
    );
    final data = _unwrapData(response.data);
    if (data is! List) {
      throw const ApiException(message: 'Format daftar pesanan tidak sesuai.');
    }
    return data
        .whereType<Map>()
        .map((item) => MitraOrder.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<MitraOrder> getOrderDetail(String orderCode) async {
    final response = await _apiClient.get('/mitra/orders/$orderCode');
    return MitraOrder.fromJson(_asObject(_unwrapData(response.data)));
  }

  Future<MitraOrder> advanceStatus(String orderCode, String action) async {
    const allowedActions = {'depart', 'arrive', 'start', 'finish'};
    if (!allowedActions.contains(action)) {
      throw ArgumentError.value(action, 'action', 'Aksi status tidak dikenal.');
    }
    final response = await _apiClient.post('/mitra/orders/$orderCode/$action');
    return MitraOrder.fromJson(_asObject(_unwrapData(response.data)));
  }

  dynamic _unwrapData(dynamic body) {
    if (body is Map && body.containsKey('data')) return body['data'];
    return body;
  }

  Map<String, dynamic> _asObject(dynamic value) {
    if (value is! Map) {
      throw const ApiException(message: 'Format detail pesanan tidak sesuai.');
    }
    return Map<String, dynamic>.from(value);
  }
}
