import 'package:core/core.dart';

class MitraOfferService {
  MitraOfferService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;

  Future<List<MitraOffer>> getOffers() async {
    final response = await _apiClient.get('/mitra/offers');
    final data = _unwrapData(response.data);
    if (data is! List) {
      throw const ApiException(
        message: 'Format penawaran dari server tidak sesuai.',
      );
    }
    return data
        .whereType<Map>()
        .map((item) => MitraOffer.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<MitraOrder> acceptOffer(int attemptId) async {
    final response = await _apiClient.post('/mitra/offers/$attemptId/accept');
    return MitraOrder.fromJson(_asObject(_unwrapData(response.data)));
  }

  Future<void> rejectOffer(int attemptId) async {
    await _apiClient.post('/mitra/offers/$attemptId/reject');
  }

  dynamic _unwrapData(dynamic body) {
    if (body is Map && body.containsKey('data')) return body['data'];
    return body;
  }

  Map<String, dynamic> _asObject(dynamic value) {
    if (value is! Map) {
      throw const ApiException(message: 'Format respons pesanan tidak sesuai.');
    }
    return Map<String, dynamic>.from(value);
  }
}
