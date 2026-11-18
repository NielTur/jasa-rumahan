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

    String? serverMessage;
    Map<String, List<String>> errors = {};

    if (responseData is Map) {
      final responseMessage = responseData['message'];

      if (responseMessage is String && responseMessage.isNotEmpty) {
        serverMessage = responseMessage;
      }

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

    // Pesan backend diprioritaskan agar detail kesalahan tidak hilang.
    String message = serverMessage ?? _fallbackMessage(statusCode);

    if (statusCode != null && statusCode >= 500) {
      message = serverMessage ?? 'Server sedang mengalami gangguan.';
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

  static String _fallbackMessage(int? statusCode) {
    switch (statusCode) {
      case 401:
        return 'Sesi berakhir. Silakan login kembali.';
      case 403:
        return 'Kamu tidak memiliki akses untuk tindakan ini.';
      case 404:
        return 'Data yang dicari tidak ditemukan.';
      case 409:
        return 'Permintaan tidak dapat diproses karena konflik data.';
      case 422:
        return 'Data yang dikirim tidak valid.';
      case 429:
        return 'Terlalu banyak permintaan. Silakan tunggu.';
      default:
        return 'Terjadi kesalahan. Silakan coba lagi.';
    }
  }

  @override
  String toString() => message;
}
