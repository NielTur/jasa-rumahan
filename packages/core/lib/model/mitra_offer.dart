class MitraOfferAddon {
  final String name;
  final int quantity;

  const MitraOfferAddon({required this.name, required this.quantity});

  factory MitraOfferAddon.fromJson(Map<String, dynamic> json) {
    return MitraOfferAddon(
      name: json['name']?.toString() ?? '-',
      quantity: _asInt(json['quantity']),
    );
  }
}

class MitraOffer {
  final int attemptId;
  final int attemptNumber;
  final String serviceName;
  final String propertySizeName;
  final DateTime? scheduledAt;
  final DateTime? expiresAt;
  final int durationMinutes;
  final int totalPrice;
  final List<MitraOfferAddon> addons;

  const MitraOffer({
    required this.attemptId,
    required this.attemptNumber,
    required this.serviceName,
    required this.propertySizeName,
    required this.scheduledAt,
    required this.expiresAt,
    required this.durationMinutes,
    required this.totalPrice,
    required this.addons,
  });

  factory MitraOffer.fromJson(Map<String, dynamic> json) {
    final service = _asMap(json['service']);
    final property = _asMap(json['property_size']);
    final pricing = _asMap(json['pricing']);
    final addons = json['addons'] is List ? json['addons'] as List : const [];

    return MitraOffer(
      attemptId: _asInt(json['attempt_id']),
      attemptNumber: _asInt(json['attempt_number']),
      serviceName: service['service_name']?.toString() ?? 'Nama jasa',
      propertySizeName: property['size_name']?.toString() ?? '-',
      scheduledAt: DateTime.tryParse(json['scheduled_at']?.toString() ?? ''),
      expiresAt: DateTime.tryParse(json['expires_at']?.toString() ?? ''),
      durationMinutes: _asInt(json['duration_minutes']),
      totalPrice: _asInt(pricing['total_price']),
      addons: addons
          .whereType<Map>()
          .map((item) => MitraOfferAddon.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

Map<String, dynamic> _asMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

int _asInt(dynamic value) => value is num ? value.toInt() : int.tryParse('$value') ?? 0;
