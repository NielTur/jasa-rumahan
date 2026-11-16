class ServiceItem {
  final int id;
  final int categoryId;
  final String serviceName;
  final String? description;
  final int durationMinutes;

  const ServiceItem({
    required this.id,
    required this.categoryId,
    required this.serviceName,
    this.description,
    required this.durationMinutes,
  });

  factory ServiceItem.fromJson(Map<String, dynamic> json) {
    return ServiceItem(
      id: json['id'] as int,
      categoryId: json['category_id'] as int,
      serviceName: json['service_name'] as String,
      description: json['description'] as String?,
      durationMinutes: json['duration_minutes'] as int,
    );
  }
}
