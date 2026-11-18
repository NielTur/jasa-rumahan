import 'package:core/core.dart';

class AuthService {
  AuthService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;

  /// Login menggunakan nomor HP dan password.
  Future<AuthUser> login({
    required String phone,
    required String password,
  }) async {
    final response = await _apiClient.post(
      '/auth/login',
      data: {'phone': phone, 'password': password},
    );

    return _saveAuthenticatedUser(response.data);
  }

  /// Registrasi Mitra sekaligus menyimpan token hasil registrasi.
  Future<AuthUser> register({
    required String fullName,
    required String phone,
    required String password,
    required String passwordConfirmation,
    String? bio,
    String? serviceArea,
  }) async {
    final response = await _apiClient.post(
      '/auth/register/mitra',
      data: {
        'full_name': fullName,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (bio != null && bio.trim().isNotEmpty) 'bio': bio,
        if (serviceArea != null && serviceArea.trim().isNotEmpty)
          'service_area': serviceArea,
      },
    );

    return _saveAuthenticatedUser(response.data);
  }

  /// Mengambil profil dan memvalidasi sesi dari token tersimpan.
  Future<AuthUser> getMe() async {
    try {
      final response = await _apiClient.get('/auth/me');
      final responseData = response.data;

      if (responseData is! Map || responseData['data'] is! Map) {
        throw const ApiException(
          message: 'Format data profil dari server tidak sesuai.',
        );
      }

      final user = AuthUser.fromJson(
        Map<String, dynamic>.from(responseData['data'] as Map),
      );

      if (user.role != 'mitra') {
        await UserInfo.clearToken();
        throw const ApiException(
          message: 'Akun ini bukan akun Mitra.',
          statusCode: 403,
        );
      }

      return user;
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        await UserInfo.clearToken();
      }
      rethrow;
    }
  }

  /// Logout di server, lalu hapus token lokal.
  Future<void> logout() async {
    try {
      await _apiClient.post('/auth/logout');
    } finally {
      await UserInfo.clearToken();
    }
  }

  /// Memvalidasi role dan menyimpan token dari respons login/register.
  Future<AuthUser> _saveAuthenticatedUser(dynamic responseBody) async {
    if (responseBody is! Map || responseBody['data'] is! Map) {
      throw const ApiException(
        message: 'Format respons autentikasi dari server tidak sesuai.',
      );
    }

    final data = Map<String, dynamic>.from(responseBody['data'] as Map);

    final token = data['token'];
    final user = data['user'];

    if (token is! String || token.isEmpty || user is! Map) {
      throw const ApiException(
        message: 'Token atau data pengguna tidak ditemukan.',
      );
    }

    final authUser = AuthUser.fromJson(Map<String, dynamic>.from(user));

    if (authUser.role != 'mitra') {
      throw const ApiException(
        message: 'Akun ini bukan akun Mitra.',
        statusCode: 403,
      );
    }

    await UserInfo.saveToken(token);

    return authUser;
  }
}
