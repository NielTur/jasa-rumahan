class MitraOrder {
  final String orderCode;
  final String orderStatus;
  final String serviceName;
  final String propertySizeName;
  final DateTime? scheduledAt;
  final int durationMinutes;
  final String customerName;
  final String customerPhone;
  final String serviceAddress;
  final String? addressNotes;
  final String? customerNote;
  final int totalPrice;
  final String? paymentStatus;

  const MitraOrder({
    required this.orderCode,
    required this.orderStatus,
    required this.serviceName,
    required this.propertySizeName,
    required this.scheduledAt,
    required this.durationMinutes,
    required this.customerName,
    required this.customerPhone,
    required this.serviceAddress,
    required this.addressNotes,
    required this.customerNote,
    required this.totalPrice,
    required this.paymentStatus,
  });

  factory MitraOrder.fromJson(Map<String, dynamic> json) {
    final service = _orderMap(json['service']);
    final property = _orderMap(json['property_size']);
    final customer = _orderMap(json['customer']);
    final pricing = _orderMap(json['pricing']);
    final payment = _orderMap(json['payment']);

    return MitraOrder(
      orderCode: json['order_code']?.toString() ?? '-',
      orderStatus: json['order_status']?.toString() ?? '',
      serviceName: service['service_name']?.toString() ?? 'Nama jasa',
      propertySizeName: property['size_name']?.toString() ?? '-',
      scheduledAt: DateTime.tryParse(json['scheduled_at']?.toString() ?? ''),
      durationMinutes: _orderInt(json['duration_minutes']),
      customerName: customer['name']?.toString() ?? '-',
      customerPhone: customer['phone']?.toString() ?? '-',
      serviceAddress: json['service_address']?.toString() ?? '-',
      addressNotes: json['address_notes']?.toString(),
      customerNote: json['customer_note']?.toString(),
      totalPrice: _orderInt(pricing['total_price']),
      paymentStatus: payment['payment_status']?.toString(),
    );
  }
}

Map<String, dynamic> _orderMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

int _orderInt(dynamic value) =>
    value is num ? value.toInt() : int.tryParse('$value') ?? 0;
