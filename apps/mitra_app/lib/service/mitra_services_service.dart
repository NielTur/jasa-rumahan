import 'package:core/core.dart';

class MitraServiceItem {
  final int id;
  final int categoryId;
  final String serviceName;
  final String? description;
  final int durationMinutes;
  final bool selected;

  const MitraServiceItem({
    required this.id,
    required this.categoryId,
    required this.serviceName,
    this.description,
    required this.durationMinutes,
    required this.selected,
  });

  factory MitraServiceItem.fromJson(Map<String, dynamic> json) {
    return MitraServiceItem(
      id: json['id'] as int,
      categoryId: json['category_id'] as int,
      serviceName: json['service_name'] as String,
      description: json['description'] as String?,
      durationMinutes: json['duration_minutes'] as int,
      selected: json['selected'] as bool? ?? false,
    );
  }
}

class MitraServicesService {
  MitraServicesService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;

  Future<List<MitraServiceItem>> getServices() async {
    final response = await _apiClient.get('/mitra/services');
    return _parseServices(response.data);
  }

  Future<List<MitraServiceItem>> updateServices(List<int> serviceIds) async {
    final response = await _apiClient.put(
      '/mitra/services',
      data: {'service_ids': serviceIds},
    );

    return _parseServices(response.data);
  }

  List<MitraServiceItem> _parseServices(dynamic body) {
    if (body is! Map || body['data'] is! List) {
      throw const ApiException(
        message: 'Format katalog jasa dari server tidak sesuai.',
      );
    }

    return (body['data'] as List)
        .whereType<Map>()
        .map(
          (item) => MitraServiceItem.fromJson(Map<String, dynamic>.from(item)),
        )
        .toList();
  }
}
