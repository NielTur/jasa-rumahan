import 'package:core/core.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ApiException', () {
    test('mengubah status 401 menjadi pesan sesi berakhir', () {
      final exception = ApiException.fromDioException(
        DioException(
          requestOptions: RequestOptions(path: '/auth/me'),
          response: Response(
            requestOptions: RequestOptions(path: '/auth/me'),
            statusCode: 401,
          ),
        ),
      );

      expect(exception.statusCode, 401);
      expect(exception.message, 'Sesi berakhir. Silakan login kembali.');
    });

    test('mengubah status 422 menjadi pesan validasi', () {
      final exception = ApiException.fromDioException(
        DioException(
          requestOptions: RequestOptions(path: '/orders'),
          response: Response(
            requestOptions: RequestOptions(path: '/orders'),
            statusCode: 422,
            data: {
              'message': 'Validation failed',
              'errors': {
                'full_name': ['Nama wajib diisi.'],
                'address.label': ['Label alamat tidak valid.'],
              },
            },
          ),
        ),
      );

      expect(exception.statusCode, 422);
      expect(exception.message, 'Data yang dikirim tidak valid.');
      expect(exception.errors['full_name'], ['Nama wajib diisi.']);
      expect(exception.errors['address.label'], ['Label alamat tidak valid.']);
    });

    test('mengubah status 500 menjadi pesan gangguan server', () {
      final exception = ApiException.fromDioException(
        DioException(
          requestOptions: RequestOptions(path: '/orders'),
          response: Response(
            requestOptions: RequestOptions(path: '/orders'),
            statusCode: 500,
          ),
        ),
      );

      expect(exception.statusCode, 500);
      expect(exception.message, 'Server sedang mengalami gangguan.');
    });

    test('mengubah connectionError menjadi pesan koneksi', () {
      final exception = ApiException.fromDioException(
        DioException(
          requestOptions: RequestOptions(path: '/orders'),
          type: DioExceptionType.connectionError,
        ),
      );

      expect(exception.message, 'Tidak dapat terhubung ke server.');
    });

    test('mengubah timeout menjadi pesan timeout', () {
      final exception = ApiException.fromDioException(
        DioException(
          requestOptions: RequestOptions(path: '/orders'),
          type: DioExceptionType.connectionTimeout,
        ),
      );

      expect(
        exception.message,
        'Koneksi timeout. Periksa jaringan internet kamu.',
      );
    });

    test('toString mengembalikan pesan exception', () {
      const exception = ApiException(message: 'Terjadi kesalahan.');

      expect(exception.toString(), 'Terjadi kesalahan.');
    });
  });
}
