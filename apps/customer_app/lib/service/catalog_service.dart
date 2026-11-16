import 'package:core/core.dart';

class CatalogService {
  final ApiClient _apiClient = ApiClient.instance;

  Future<List<ServiceCategory>> getCategories() async {
    final response = await _apiClient.get('/service-categories');
    final responseData = response.data;

    if (responseData is! Map || responseData['data'] is! List) {
      throw const ApiException(
        message: 'Format data kategori dari server tidak sesuai.',
      );
    }

    final items = responseData['data'] as List;
    return items
        .map(
          (item) =>
              ServiceCategory.fromJson(Map<String, dynamic>.from(item as Map)),
        )
        .toList();
  }

  Future<List<ServiceItem>> getServices({int? categoryId}) async {
    final response = await _apiClient.get(
      '/services',
      queryParameters: categoryId == null ? null : {'category_id': categoryId},
    );
    final responseData = response.data;

    if (responseData is! Map || responseData['data'] is! List) {
      throw const ApiException(
        message: 'Format data jasa dari server tidak sesuai.',
      );
    }

    final items = responseData['data'] as List;
    return items
        .map(
          (item) =>
              ServiceItem.fromJson(Map<String, dynamic>.from(item as Map)),
        )
        .toList();
  }

  Future<ServiceDetail> getServiceDetail(int serviceId) async {
    final response = await _apiClient.get('/services/$serviceId');
    final responseData = response.data;

    // Kontrak detail mengembalikan objek jasa secara langsung.
    // Bentuk berbungkus {data: {...}} juga ditangani agar kompatibel
    // jika API Resource backend membungkus respons tersebut.
    final detailData = responseData is Map && responseData['data'] is Map
        ? responseData['data']
        : responseData;

    if (detailData is! Map) {
      throw const ApiException(
        message: 'Format detail jasa dari server tidak sesuai.',
      );
    }

    try {
      return ServiceDetail.fromJson(Map<String, dynamic>.from(detailData));
    } on TypeError {
      throw const ApiException(
        message:
            'Data detail jasa dari server tidak lengkap atau tidak sesuai.',
      );
    }
  }
}
