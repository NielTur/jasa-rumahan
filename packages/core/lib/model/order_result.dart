class OrderResult {
  final String orderCode;
  final String orderStatus;
  final String? trackingStage;
  final DateTime? scheduledAt;
  final int? totalPrice;
  final String message;

  const OrderResult({
    required this.orderCode,
    required this.orderStatus,
    required this.message,
    this.trackingStage,
    this.scheduledAt,
    this.totalPrice,
  });

  factory OrderResult.fromJson(Map<String, dynamic> json) {
    final data = Map<String, dynamic>.from(json['data'] as Map);
    final pricing = data['pricing'] == null
        ? null
        : Map<String, dynamic>.from(data['pricing'] as Map);

    return OrderResult(
      orderCode: data['order_code'] as String,
      orderStatus: data['order_status'] as String,
      trackingStage: data['tracking_stage'] as String?,
      scheduledAt: data['scheduled_at'] == null
          ? null
          : DateTime.parse(data['scheduled_at'] as String),
      totalPrice: pricing?['total_price'] as int?,
      message: json['message'] as String? ?? 'Pesanan berhasil dibuat.',
    );
  }
}
