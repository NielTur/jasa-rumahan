import 'package:flutter/material.dart';

import '../service/order_history_service.dart';
import '../theme/app_colors.dart';
import 'order_tracking_page.dart';

class OrderDetailPage extends StatefulWidget {
  final String orderCode;

  const OrderDetailPage({super.key, required this.orderCode});

  @override
  State<OrderDetailPage> createState() => _OrderDetailPageState();
}

class _OrderDetailPageState extends State<OrderDetailPage> {
  final OrderHistoryService _service = OrderHistoryService();

  Map<String, dynamic>? _order;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDetail();
  }

  Future<void> _loadDetail() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final result = await _service.getOrderDetail(widget.orderCode);
      if (!mounted) return;

      setState(() {
        _order = result;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvasCream,
      appBar: AppBar(
        title: const Text('Detail Pesanan'),
        backgroundColor: AppColors.canvasCream,
        foregroundColor: AppColors.forestDeep,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? _buildError()
          : _buildDetail(),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 48),
            const SizedBox(height: 12),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _loadDetail,
              child: const Text('Coba Lagi'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetail() {
    final order = _order!;
    final customer = _map(order['customer']);
    final service = _map(order['service']);
    final property = _map(order['property_size']);
    final pricing = _map(order['pricing']);
    final mitra = _map(order['mitra']);
    final payment = _map(order['payment']);
    final addons = order['addons'] is List ? order['addons'] as List : [];

    return RefreshIndicator(
      onRefresh: _loadDetail,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          _section('Informasi Pesanan', [
            _row('Kode Pesanan', _text(order['order_code'])),
            _row('Status', _status(order['order_status'])),
            _row('Layanan', _text(service['service_name'])),
            _row('Tipe Properti', _text(property['size_name'])),
            _row('Jadwal', _date(order['scheduled_at'])),
            _row('Durasi', '${order['duration_minutes'] ?? '-'} menit'),
          ]),
          _section('Informasi Customer', [
            _row('Nama', _text(customer['name'])),
            _row('Nomor HP', _text(customer['phone'])),
            _row('Alamat', _text(order['service_address'])),
            _row('Catatan Alamat', _text(order['address_notes'])),
            _row('Catatan Pesanan', _text(order['customer_note'])),
          ]),
          _section('Layanan Tambahan', [
            if (addons.isEmpty)
              const Text('Tidak ada layanan tambahan.')
            else
              ...addons.map((item) {
                final addon = _map(item);
                return _row(
                  '${_text(addon['name'])} x${addon['quantity'] ?? 1}',
                  _rupiah(addon['subtotal']),
                );
              }),
          ]),
          _section('Rincian Harga', [
            _row('Harga Layanan', _rupiah(pricing['service_price'])),
            _row('Total Tambahan', _rupiah(pricing['addons_total'])),
            const Divider(),
            _row(
              'Total Pembayaran',
              _rupiah(pricing['total_price']),
              bold: true,
            ),
          ]),
          _section('Informasi Mitra', [
            _row('Nama Mitra', _text(mitra['full_name'])),
            _row('Nomor HP', _text(mitra['phone'])),
          ]),
          _section('Informasi Pembayaran', [
            _row('Status', _text(payment['payment_status'])),
            _row('Metode', _text(payment['payment_method'])),
            _row('Jumlah', _rupiah(payment['amount'])),
            _row('Batas Pembayaran', _date(payment['expired_at'])),
          ]),
          const SizedBox(height: 4),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) =>
                        OrderTrackingPage(orderCode: widget.orderCode),
                  ),
                );
              },
              icon: const Icon(Icons.location_searching),
              label: const Text('Lacak Pesanan'),
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.forestDeep,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _section(String title, List<Widget> children) {
    return Card(
      color: Colors.white,
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 14),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: const BorderSide(color: AppColors.borderHairline),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 15,
                color: AppColors.forestDeep,
              ),
            ),
            const SizedBox(height: 14),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(
              label,
              style: const TextStyle(color: AppColors.mutedStone),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
                color: AppColors.charcoalSlate,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map) return Map<String, dynamic>.from(value);
    return {};
  }

  String _text(dynamic value) {
    if (value == null || value.toString().isEmpty) return '-';
    return value.toString();
  }

  String _date(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return '-';

    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');

    return '$day/$month/${date.year} $hour:$minute';
  }

  String _rupiah(dynamic value) {
    final amount = value is num
        ? value.toInt()
        : int.tryParse(value?.toString() ?? '') ?? 0;

    final digits = amount.toString();
    final result = StringBuffer();

    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && (digits.length - i) % 3 == 0) {
        result.write('.');
      }
      result.write(digits[i]);
    }

    return 'Rp $result';
  }

  String _status(dynamic value) {
    switch (value?.toString()) {
      case 'pending':
      case 'searching_mitra':
        return 'Mencari Mitra';
      case 'waiting_payment':
        return 'Menunggu Pembayaran';
      case 'confirmed':
        return 'Dikonfirmasi';
      case 'on_the_way':
        return 'Mitra Dalam Perjalanan';
      case 'arrived':
        return 'Mitra Telah Tiba';
      case 'in_progress':
        return 'Sedang Dikerjakan';
      case 'mitra_completed':
        return 'Menunggu Konfirmasi Customer';
      case 'completed':
        return 'Selesai';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return _text(value);
    }
  }
}
