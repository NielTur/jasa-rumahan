import 'package:dio/dio.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;

  const ApiException({
    required this.message,
    this.statusCode,
    this.errors = const {},
  });

  factory ApiException.fromDioException(DioException exception) {
    final statusCode = exception.response?.statusCode;
    final responseData = exception.response?.data;

    String message = 'Terjadi kesalahan. Silakan coba lagi.';
    Map<String, List<String>> errors = {};

    if (responseData is Map) {
      final responseMessage = responseData['message'];

      if (responseMessage is String && responseMessage.isNotEmpty) {
        message = responseMessage;
      }

      // Format errors API: Map<String, List<String>>.
      final responseErrors = responseData['errors'];

      if (responseErrors is Map) {
        errors = responseErrors.map((key, value) {
          if (value is List) {
            return MapEntry(
              key.toString(),
              value.map((item) => item.toString()).toList(),
            );
          }

          return MapEntry(key.toString(), [value.toString()]);
        });
      }
    }

    switch (statusCode) {
      case 401:
        message = 'Sesi berakhir. Silakan login kembali.';
        break;
      case 403:
        message = 'Kamu tidak memiliki akses untuk tindakan ini.';
        break;
      case 404:
        message = 'Data yang dicari tidak ditemukan.';
        break;
      case 409:
        message = 'Permintaan tidak dapat diproses karena konflik data.';
        break;
      case 422:
        message = 'Data yang dikirim tidak valid.';
        break;
      case 429:
        message = 'Terlalu banyak permintaan. Silakan tunggu.';
        break;
    }

    if (statusCode != null && statusCode >= 500) {
      message = 'Server sedang mengalami gangguan.';
    }

    if (exception.type == DioExceptionType.connectionTimeout ||
        exception.type == DioExceptionType.receiveTimeout ||
        exception.type == DioExceptionType.sendTimeout) {
      message = 'Koneksi timeout. Periksa jaringan internet kamu.';
    } else if (exception.type == DioExceptionType.connectionError) {
      message = 'Tidak dapat terhubung ke server.';
    }

    return ApiException(
      message: message,
      statusCode: statusCode,
      errors: errors,
    );
  }

  @override
  String toString() => message;
}
