import 'package:core/core.dart';

class BookingService {
  final ApiClient _apiClient = ApiClient.instance;

  // Mengambil profil customer yang sedang login.
  // Guest akan mendapatkan nilai null.
  Future<CustomerProfile?> getCustomerProfile() async {
    final token = await UserInfo.getToken();

    if (token == null || token.isEmpty) {
      return null;
    }

    final response = await _apiClient.get('/auth/me');
    final responseData = response.data;

    final profileData = responseData is Map && responseData['data'] is Map
        ? responseData['data']
        : responseData;

    if (profileData is! Map) {
      throw const ApiException(
        message: 'Format profil customer dari server tidak sesuai.',
      );
    }

    try {
      return CustomerProfile.fromJson(Map<String, dynamic>.from(profileData));
    } on TypeError {
      throw const ApiException(
        message: 'Data profil customer tidak lengkap atau tidak sesuai.',
      );
    }
  }

  // Mengambil daftar slot berdasarkan jasa dan tanggal.
  Future<List<BookingSlot>> getSlots({
    required int serviceId,
    required DateTime date,
  }) async {
    final formattedDate =
        '${date.year.toString().padLeft(4, '0')}-'
        '${date.month.toString().padLeft(2, '0')}-'
        '${date.day.toString().padLeft(2, '0')}';

    final response = await _apiClient.get(
      '/slots',
      queryParameters: {'service_id': serviceId, 'date': formattedDate},
    );

    final responseData = response.data;

    // Kontrak mendukung array langsung maupun array dalam data.
    final slotData = responseData is Map ? responseData['data'] : responseData;

    if (slotData is! List) {
      throw const ApiException(
        message: 'Format jadwal dari server tidak sesuai.',
      );
    }

    try {
      return slotData
          .map(
            (item) =>
                BookingSlot.fromJson(Map<String, dynamic>.from(item as Map)),
          )
          .toList();
    } on TypeError {
      throw const ApiException(
        message: 'Data jadwal dari server tidak lengkap atau tidak sesuai.',
      );
    }
  }

  // Meminta server menghitung rincian harga pesanan.
  Future<OrderQuote> getQuote({
    required int serviceId,
    required int propertySizeId,
    Map<int, int> addonQuantities = const {},
  }) async {
    final addons = addonQuantities.entries
        .where((entry) => entry.value > 0)
        .map(
          (entry) => {'service_addon_id': entry.key, 'quantity': entry.value},
        )
        .toList();

    final response = await _apiClient.post(
      '/orders/quote',
      data: {
        'service_id': serviceId,
        'property_size_id': propertySizeId,
        'addons': addons,
      },
    );

    final responseData = response.data;

    final quoteData = responseData is Map && responseData['data'] is Map
        ? responseData['data']
        : responseData;

    if (quoteData is! Map) {
      throw const ApiException(
        message: 'Format rincian harga dari server tidak sesuai.',
      );
    }

    try {
      return OrderQuote.fromJson(Map<String, dynamic>.from(quoteData));
    } on TypeError {
      throw const ApiException(
        message: 'Rincian harga dari server tidak lengkap atau tidak sesuai.',
      );
    }
  }

  // Membuat pesanan baru.
  Future<OrderResult> createOrder({
    required int serviceId,
    required int propertySizeId,
    Map<int, int> addonQuantities = const {},
    required DateTime scheduledAt,
    required String customerName,
    required String customerPhone,
    required String serviceAddress,
    String? addressNotes,
    String? customerNote,
  }) async {
    final addons = addonQuantities.entries
        .where((entry) => entry.value > 0)
        .map(
          (entry) => {'service_addon_id': entry.key, 'quantity': entry.value},
        )
        .toList();

    final scheduledTime = _formatWib(scheduledAt);

    final response = await _apiClient.post(
      '/orders',
      data: {
        'service_id': serviceId,
        'property_size_id': propertySizeId,
        'addons': addons,
        'scheduled_at': scheduledTime,
        'customer_name': customerName.trim(),
        'customer_phone': customerPhone.trim(),
        'service_address': serviceAddress.trim(),
        'address_notes': _nullableText(addressNotes),
        'customer_note': _nullableText(customerNote),
        'customer_address_id': null,
      },
    );

    if (response.data is! Map) {
      throw const ApiException(
        message: 'Format hasil pemesanan dari server tidak sesuai.',
      );
    }

    try {
      final result = OrderResult.fromJson(
        Map<String, dynamic>.from(response.data),
      );

      // Identitas guest disimpan untuk kebutuhan tracking pesanan.
      final token = await UserInfo.getToken();

      if (token == null || token.isEmpty) {
        await UserInfo.saveGuestOrder(
          orderCode: result.orderCode,
          phone: customerPhone.trim(),
        );
      }

      return result;
    } on TypeError {
      throw const ApiException(
        message: 'Data hasil pemesanan dari server tidak lengkap.',
      );
    }
  }

  String _formatWib(DateTime dateTime) {
    final wib = dateTime.toUtc().add(const Duration(hours: 7));

    final year = wib.year.toString().padLeft(4, '0');
    final month = wib.month.toString().padLeft(2, '0');
    final day = wib.day.toString().padLeft(2, '0');
    final hour = wib.hour.toString().padLeft(2, '0');
    final minute = wib.minute.toString().padLeft(2, '0');
    final second = wib.second.toString().padLeft(2, '0');

    return '$year-$month-${day}T$hour:$minute:$second+07:00';
  }

  String? _nullableText(String? value) {
    final text = value?.trim();
    return text == null || text.isEmpty ? null : text;
  }
}
