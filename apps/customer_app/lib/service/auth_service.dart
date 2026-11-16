import 'package:core/core.dart';

class AuthService {
  AuthService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;

  /// Login menggunakan nomor HP dan password.
  Future<CustomerProfile> login({
    required String phone,
    required String password,
  }) async {
    final response = await _apiClient.post(
      '/auth/login',
      data: {'phone': phone, 'password': password},
    );

    return _saveAuthenticatedUser(response.data);
  }

  /// Registrasi Customer sekaligus menyimpan token hasil registrasi.
  Future<CustomerProfile> register({
    required String fullName,
    required String phone,
    required String password,
    required String passwordConfirmation,
    required String addressLabel,
    required String fullAddress,
    String? city,
    String? province,
    String? postalCode,
  }) async {
    final response = await _apiClient.post(
      '/auth/register/customer',
      data: {
        'full_name': fullName,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'address': {
          'label': addressLabel,
          'full_address': fullAddress,
          if (city != null && city.trim().isNotEmpty) 'city': city,
          if (province != null && province.trim().isNotEmpty)
            'province': province,
          if (postalCode != null && postalCode.trim().isNotEmpty)
            'postal_code': postalCode,
        },
      },
    );

    return _saveAuthenticatedUser(response.data);
  }

  /// Mengambil profil dari token yang tersimpan.
  Future<CustomerProfile> getProfile() async {
    final response = await _apiClient.get('/auth/me');
    final responseData = response.data;

    if (responseData is! Map || responseData['data'] is! Map) {
      throw const ApiException(
        message: 'Format data profil dari server tidak sesuai.',
      );
    }

    final profile = CustomerProfile.fromJson(
      Map<String, dynamic>.from(responseData['data'] as Map),
    );

    if (profile.role != 'customer') {
      await UserInfo.clearToken();
      throw const ApiException(
        message: 'Akun ini bukan akun Customer.',
        statusCode: 403,
      );
    }

    return profile;
  }

  /// Logout di server, lalu hapus token lokal.
  Future<void> logout() async {
    try {
      await _apiClient.post('/auth/logout');
    } finally {
      await UserInfo.clearToken();
    }
  }

  /// Memvalidasi role dan menyimpan token dari response login/register.
  Future<CustomerProfile> _saveAuthenticatedUser(dynamic responseBody) async {
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

    final profile = CustomerProfile.fromJson(Map<String, dynamic>.from(user));

    // Pastikan aplikasi Customer tidak menerima akun Mitra/Admin.
    if (profile.role != 'customer') {
      throw const ApiException(
        message: 'Akun ini bukan akun Customer.',
        statusCode: 403,
      );
    }

    await UserInfo.saveToken(token);
    return profile;
  }
}
