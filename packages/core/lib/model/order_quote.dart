class OrderQuote {
  final int serviceId;
  final String serviceName;
  final int propertySizeId;
  final String sizeName;
  final int durationMinutes;
  final List<QuoteAddon> addons;
  final QuotePricing pricing;

  const OrderQuote({
    required this.serviceId,
    required this.serviceName,
    required this.propertySizeId,
    required this.sizeName,
    required this.durationMinutes,
    required this.addons,
    required this.pricing,
  });

  factory OrderQuote.fromJson(Map<String, dynamic> json) {
    final addonList = json['addons'] as List<dynamic>? ?? [];

    return OrderQuote(
      serviceId: json['service_id'] as int,
      serviceName: json['service_name'] as String,
      propertySizeId: json['property_size_id'] as int,
      sizeName: json['size_name'] as String,
      durationMinutes: json['duration_minutes'] as int,
      addons: addonList
          .map(
            (item) =>
                QuoteAddon.fromJson(Map<String, dynamic>.from(item as Map)),
          )
          .toList(),
      pricing: QuotePricing.fromJson(
        Map<String, dynamic>.from(json['pricing'] as Map),
      ),
    );
  }
}

class QuoteAddon {
  final int serviceAddonId;
  final String name;
  final int unitPrice;
  final int quantity;
  final int subtotal;

  const QuoteAddon({
    required this.serviceAddonId,
    required this.name,
    required this.unitPrice,
    required this.quantity,
    required this.subtotal,
  });

  factory QuoteAddon.fromJson(Map<String, dynamic> json) {
    return QuoteAddon(
      serviceAddonId: json['service_addon_id'] as int,
      name: json['name'] as String,
      unitPrice: json['unit_price'] as int,
      quantity: json['quantity'] as int,
      subtotal: json['subtotal'] as int,
    );
  }
}

class QuotePricing {
  final int servicePrice;
  final int addonsTotal;
  final int totalPrice;

  const QuotePricing({
    required this.servicePrice,
    required this.addonsTotal,
    required this.totalPrice,
  });

  factory QuotePricing.fromJson(Map<String, dynamic> json) {
    return QuotePricing(
      servicePrice: json['service_price'] as int,
      addonsTotal: json['addons_total'] as int,
      totalPrice: json['total_price'] as int,
    );
  }
}
