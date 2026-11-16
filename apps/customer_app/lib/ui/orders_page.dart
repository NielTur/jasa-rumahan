import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../service/order_history_service.dart';
import '../theme/app_colors.dart';

class OrdersPage extends StatefulWidget {
  final int refreshKey;

  const OrdersPage({super.key, this.refreshKey = 0});

  @override
  State<OrdersPage> createState() => _OrdersPageState();
}

class _OrdersPageState extends State<OrdersPage> {
  final OrderHistoryService _service = OrderHistoryService();

  List<Map<String, dynamic>> _orders = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  @override
  void didUpdateWidget(covariant OrdersPage oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.refreshKey != widget.refreshKey) {
      _loadOrders();
    }
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final orders = await _service.getOrders();
      if (!mounted) return;

      setState(() {
        _orders = orders;
        _isLoading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _errorMessage = error.message;
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _errorMessage = 'Terjadi kesalahan saat memuat pesanan.';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Pesanan',
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: AppColors.forestDeep,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                IconButton(
                  onPressed: _isLoading ? null : _loadOrders,
                  icon: const Icon(Icons.refresh),
                  tooltip: 'Muat ulang',
                ),
              ],
            ),
            const SizedBox(height: 6),
            const Text(
              'Pantau pesanan jasa rumahanmu di sini.',
              style: TextStyle(color: AppColors.mutedStone),
            ),
            const SizedBox(height: 18),
            Expanded(child: _buildContent()),
          ],
        ),
      ),
    );
  }

  Widget _buildContent() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_outlined, size: 48),
            const SizedBox(height: 12),
            Text(_errorMessage!, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: _loadOrders,
              child: const Text('Coba lagi'),
            ),
          ],
        ),
      );
    }

    if (_orders.isEmpty) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.receipt_long_outlined,
              size: 58,
              color: AppColors.sageFresh,
            ),
            SizedBox(height: 14),
            Text(
              'Belum ada pesanan',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
            ),
            SizedBox(height: 5),
            Text(
              'Pesanan yang kamu buat akan muncul di sini.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.mutedStone),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadOrders,
      child: ListView.separated(
        itemCount: _orders.length,
        separatorBuilder: (_, _) => const SizedBox(height: 12),
        itemBuilder: (context, index) => _buildOrderCard(_orders[index]),
      ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order) {
    final service = order['service'] is Map
        ? Map<String, dynamic>.from(order['service'] as Map)
        : <String, dynamic>{};
    final pricing = order['pricing'] is Map
        ? Map<String, dynamic>.from(order['pricing'] as Map)
        : <String, dynamic>{};

    final serviceName = service['service_name']?.toString() ?? 'Jasa Rumahan';
    final orderCode = order['order_code']?.toString() ?? '-';
    final status = order['order_status']?.toString() ?? '-';
    final total = _toInt(pricing['total_price']);
    final scheduledAt = order['scheduled_at']?.toString();

    return Card(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFE7ECE8)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(
                  Icons.home_repair_service_outlined,
                  color: AppColors.forestDeep,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    serviceName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                    ),
                  ),
                ),
                _statusBadge(status),
              ],
            ),
            const Divider(height: 24),
            _infoRow('Kode Pesanan', orderCode),
            const SizedBox(height: 10),
            _infoRow(
              'Jadwal',
              scheduledAt == null ? '-' : _formatDate(scheduledAt),
            ),
            const SizedBox(height: 10),
            _infoRow('Total Harga', _formatRupiah(total)),
          ],
        ),
      ),
    );
  }

  Widget _statusBadge(String status) {
    final isCancelled = status == 'cancelled';
    final isDone = status == 'completed';

    final color = isCancelled
        ? AppColors.coralDanger
        : isDone
        ? AppColors.emeraldActive
        : AppColors.amberWarning;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        _statusLabel(status),
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Row(
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
            style: const TextStyle(
              color: AppColors.charcoalSlate,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'pending':
      case 'searching_mitra':
        return 'Mencari Mitra';
      case 'waiting_payment':
        return 'Menunggu Bayar';
      case 'confirmed':
        return 'Dikonfirmasi';
      case 'on_the_way':
        return 'Dalam Perjalanan';
      case 'arrived':
        return 'Mitra Tiba';
      case 'in_progress':
        return 'Dikerjakan';
      case 'mitra_completed':
        return 'Menunggu Konfirmasi';
      case 'completed':
        return 'Selesai';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status.replaceAll('_', ' ');
    }
  }

  String _formatDate(String value) {
    final date = DateTime.tryParse(value)?.toLocal();
    if (date == null) return '-';

    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');

    return '$day/$month/${date.year}, $hour:$minute';
  }

  int _toInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _formatRupiah(int amount) {
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
}
