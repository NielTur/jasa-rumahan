class ServiceCategory {
  final int id;
  final String categoryName;
  final String? description;

  const ServiceCategory({
    required this.id,
    required this.categoryName,
    this.description,
  });

  factory ServiceCategory.fromJson(Map<String, dynamic> json) {
    return ServiceCategory(
      id: json['id'] as int,
      categoryName: json['category_name'] as String,
      description: json['description'] as String?,
    );
  }
}
