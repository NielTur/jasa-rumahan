import 'package:flutter/material.dart';

import '../service/order_history_service.dart';
import '../service/customer_order_action_service.dart';
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
  final CustomerOrderActionService _actionService =
      CustomerOrderActionService();

  Map<String, dynamic>? _order;
  bool _loading = true;
  bool _actionLoading = false;
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

  // Status yang diizinkan backend untuk pembatalan customer.
  bool get _canCancel {
    const allowedStatuses = {
      'searching_mitra',
      'waiting_payment',
      'confirmed',
      'on_the_way',
      'arrived',
    };

    return allowedStatuses.contains(_order?['order_status']?.toString());
  }

  // Konfirmasi hanya tersedia setelah mitra menyelesaikan pekerjaan.
  bool get _canConfirmCompletion {
    return _order?['order_status'] == 'mitra_completed';
  }

  Future<void> _cancelOrder() async {
    final controller = TextEditingController();
    final payment = _map(_order?['payment']);
    final isPaid = payment['payment_status'] == 'paid';

    final reason = await showDialog<String>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Batalkan Pesanan'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                isPaid
                    ? 'Pesanan akan dibatalkan. Pembayaran yang sudah '
                          'berhasil akan ditandai menunggu refund dan '
                          'diproses admin secara manual.'
                    : 'Apakah kamu yakin ingin membatalkan pesanan ini?',
              ),
              const SizedBox(height: 16),
              TextField(
                controller: controller,
                maxLength: 255,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Alasan pembatalan (opsional)',
                  hintText: 'Tuliskan alasan pembatalan...',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Kembali'),
            ),
            FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.coralDanger,
              ),
              onPressed: () {
                Navigator.pop(dialogContext, controller.text.trim());
              },
              child: const Text('Batalkan Pesanan'),
            ),
          ],
        );
      },
    );

    controller.dispose();

    if (reason == null || !mounted) return;

    await _runOrderAction(
      action: () => _actionService.cancelOrder(
        orderCode: widget.orderCode,
        reason: reason,
      ),
      successMessage: 'Pesanan berhasil dibatalkan.',
    );
  }

  Future<void> _confirmCompletion() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Konfirmasi Penyelesaian'),
          content: const Text(
            'Apakah kamu yakin pekerjaan sudah selesai '
            'dan hasilnya sesuai? Tindakan ini akan '
            'menyelesaikan pesanan.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('Periksa Lagi'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, true),
              child: const Text('Ya, Selesai'),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) return;

    await _runOrderAction(
      action: () =>
          _actionService.confirmCompletion(orderCode: widget.orderCode),
      successMessage: 'Pesanan berhasil dikonfirmasi selesai.',
    );
  }

  Future<void> _runOrderAction({
    required Future<Map<String, dynamic>> Function() action,
    required String successMessage,
  }) async {
    if (_actionLoading) return;

    setState(() => _actionLoading = true);

    try {
      final updatedOrder = await action();

      if (!mounted) return;

      setState(() => _order = updatedOrder);

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(successMessage)));

      await _loadDetail();
    } catch (error) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString()),
          backgroundColor: AppColors.coralDanger,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _actionLoading = false);
      }
    }
  }

  Widget _buildOrderActions() {
    if (!_canCancel && !_canConfirmCompletion) {
      return const SizedBox.shrink();
    }

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
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_canConfirmCompletion) ...[
              const Text(
                'Pekerjaan telah ditandai selesai oleh mitra. '
                'Periksa hasil pekerjaan sebelum melakukan konfirmasi.',
                style: TextStyle(color: AppColors.mutedStone),
              ),
              const SizedBox(height: 14),
              FilledButton.icon(
                onPressed: _actionLoading ? null : _confirmCompletion,
                icon: const Icon(Icons.check_circle_outline),
                label: const Text('Konfirmasi Selesai'),
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.forestDeep,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 15),
                ),
              ),
            ] else if (_canCancel) ...[
              const Text(
                'Kamu masih dapat membatalkan pesanan ini.',
                style: TextStyle(color: AppColors.mutedStone),
              ),
              const SizedBox(height: 14),
              OutlinedButton.icon(
                onPressed: _actionLoading ? null : _cancelOrder,
                icon: const Icon(Icons.cancel_outlined),
                label: const Text('Batalkan Pesanan'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.coralDanger,
                  side: const BorderSide(color: AppColors.coralDanger),
                  padding: const EdgeInsets.symmetric(vertical: 15),
                ),
              ),
            ],
            if (_actionLoading) ...[
              const SizedBox(height: 12),
              const Center(child: CircularProgressIndicator()),
            ],
          ],
        ),
      ),
    );
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
          const SizedBox(height: 12),
          _buildOrderActions(),
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
