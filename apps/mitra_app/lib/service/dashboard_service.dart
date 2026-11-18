import 'package:core/core.dart';

class DashboardData {
  final List<Map<String, dynamic>> offers;
  final List<Map<String, dynamic>> orders;

  const DashboardData({required this.offers, required this.orders});
}

class DashboardService {
  DashboardService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;

  Future<DashboardData> getDashboardData() async {
    final responses = await Future.wait([
      _apiClient.get('/mitra/offers'),
      _apiClient.get(
        '/mitra/orders',
        queryParameters: {
          'status':
              'waiting_payment,confirmed,on_the_way,arrived,in_progress,mitra_completed',
          'page': 1,
          'per_page': 10,
        },
      ),
    ]);

    final offers = _extractList(responses[0].data);
    final orders = _extractList(responses[1].data);

    return DashboardData(offers: offers, orders: orders);
  }

  List<Map<String, dynamic>> _extractList(dynamic responseBody) {
    if (responseBody is! Map || responseBody['data'] is! List) {
      throw const ApiException(
        message: 'Format data dashboard dari server tidak sesuai.',
      );
    }

    return (responseBody['data'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }
}
