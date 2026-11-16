class ServiceDetail {
  final int id;
  final int categoryId;
  final String serviceName;
  final String? description;
  final int durationMinutes;
  final List<ServiceAddon> addons;
  final List<ServicePriceGroup> prices;

  const ServiceDetail({
    required this.id,
    required this.categoryId,
    required this.serviceName,
    this.description,
    required this.durationMinutes,
    required this.addons,
    required this.prices,
  });

  factory ServiceDetail.fromJson(Map<String, dynamic> json) {
    return ServiceDetail(
      id: (json['id'] as num).toInt(),
      categoryId: (json['category_id'] as num).toInt(),
      serviceName: json['service_name'] as String,
      description: json['description'] as String?,
      durationMinutes: (json['duration_minutes'] as num).toInt(),
      addons: (json['addons'] as List? ?? [])
          .map(
            (item) =>
                ServiceAddon.fromJson(Map<String, dynamic>.from(item as Map)),
          )
          .toList(),
      prices: (json['prices'] as List? ?? [])
          .map(
            (item) => ServicePriceGroup.fromJson(
              Map<String, dynamic>.from(item as Map),
            ),
          )
          .toList(),
    );
  }
}

class ServiceAddon {
  final int id;
  final String addonName;
  final String? description;
  final int price;

  const ServiceAddon({
    required this.id,
    required this.addonName,
    this.description,
    required this.price,
  });

  factory ServiceAddon.fromJson(Map<String, dynamic> json) {
    return ServiceAddon(
      id: (json['id'] as num).toInt(),
      addonName: json['addon_name'] as String,
      description: json['description'] as String?,
      price: (json['price'] as num).toInt(),
    );
  }
}

class ServicePriceGroup {
  final int propertyTypeId;
  final String typeName;
  final List<ServicePriceSize> sizes;

  const ServicePriceGroup({
    required this.propertyTypeId,
    required this.typeName,
    required this.sizes,
  });

  factory ServicePriceGroup.fromJson(Map<String, dynamic> json) {
    return ServicePriceGroup(
      propertyTypeId: (json['property_type_id'] as num).toInt(),
      typeName: json['type_name'] as String,
      sizes: (json['sizes'] as List? ?? [])
          .map(
            (item) => ServicePriceSize.fromJson(
              Map<String, dynamic>.from(item as Map),
            ),
          )
          .toList(),
    );
  }
}

class ServicePriceSize {
  final int propertySizeId;
  final String sizeName;
  final num? minArea;
  final num? maxArea;
  final int price;

  const ServicePriceSize({
    required this.propertySizeId,
    required this.sizeName,
    this.minArea,
    this.maxArea,
    required this.price,
  });

  factory ServicePriceSize.fromJson(Map<String, dynamic> json) {
    return ServicePriceSize(
      propertySizeId: (json['property_size_id'] as num).toInt(),
      sizeName: json['size_name'] as String,
      minArea: json['min_area'] as num?,
      maxArea: json['max_area'] as num?,
      price: (json['price'] as num).toInt(),
    );
  }
}
