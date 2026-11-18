import 'package:core/core.dart';

class MitraProfile {
  final int userId;
  final int mitraId;
  final String fullName;
  final String phone;
  final String? bio;
  final String? serviceArea;
  final String? verificationStatus;
  final bool isAvailable;

  const MitraProfile({
    required this.userId,
    required this.mitraId,
    required this.fullName,
    required this.phone,
    this.bio,
    this.serviceArea,
    this.verificationStatus,
    required this.isAvailable,
  });

  factory MitraProfile.fromJson(Map<String, dynamic> json) {
    return MitraProfile(
      userId: json['user_id'] as int,
      mitraId: json['mitra_id'] as int,
      fullName: json['full_name'] as String,
      phone: json['phone'] as String,
      bio: json['bio'] as String?,
      serviceArea: json['service_area'] as String?,
      verificationStatus: json['verification_status'] as String?,
      isAvailable: json['is_available'] as bool? ?? false,
    );
  }
}

class MitraAvailability {
  final int? id;
  final int dayOfWeek;
  final String startTime;
  final String endTime;
  final bool isActive;

  const MitraAvailability({
    this.id,
    required this.dayOfWeek,
    required this.startTime,
    required this.endTime,
    required this.isActive,
  });

  factory MitraAvailability.fromJson(Map<String, dynamic> json) {
    return MitraAvailability(
      id: json['id'] as int?,
      dayOfWeek: json['day_of_week'] as int,
      startTime: json['start_time'] as String,
      endTime: json['end_time'] as String,
      isActive: json['is_active'] as bool? ?? true,
    );
  }

  Map<String, dynamic> toJson() => {
    'day_of_week': dayOfWeek,
    'start_time': startTime,
    'end_time': endTime,
    'is_active': isActive,
  };
}

class MitraProfileService {
  MitraProfileService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient.instance;

  final ApiClient _apiClient;

  Future<MitraProfile> getProfile() async {
    final response = await _apiClient.get('/mitra/profile');

    if (response.data is! Map) {
      throw const ApiException(message: 'Format profil Mitra tidak sesuai.');
    }

    return MitraProfile.fromJson(
      Map<String, dynamic>.from(response.data as Map),
    );
  }

  Future<MitraProfile> updateProfile({
    required String bio,
    required String serviceArea,
    required bool isAvailable,
  }) async {
    final response = await _apiClient.put(
      '/mitra/profile',
      data: {
        'bio': bio,
        'service_area': serviceArea,
        'is_available': isAvailable,
      },
    );

    if (response.data is! Map) {
      throw const ApiException(
        message: 'Format respons profil Mitra tidak sesuai.',
      );
    }

    return MitraProfile.fromJson(
      Map<String, dynamic>.from(response.data as Map),
    );
  }

  Future<List<MitraAvailability>> getAvailabilities() async {
    final response = await _apiClient.get('/mitra/availabilities');
    return _parseAvailabilities(response.data);
  }

  Future<List<MitraAvailability>> updateAvailabilities(
    List<MitraAvailability> items,
  ) async {
    final response = await _apiClient.put(
      '/mitra/availabilities',
      data: {'availabilities': items.map((item) => item.toJson()).toList()},
    );

    return _parseAvailabilities(response.data);
  }

  List<MitraAvailability> _parseAvailabilities(dynamic body) {
    if (body is! Map || body['data'] is! List) {
      throw const ApiException(
        message: 'Format jadwal operasional tidak sesuai.',
      );
    }

    return (body['data'] as List)
        .whereType<Map>()
        .map(
          (item) => MitraAvailability.fromJson(Map<String, dynamic>.from(item)),
        )
        .toList();
  }
}
