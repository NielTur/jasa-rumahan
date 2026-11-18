import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/mitra_order_service.dart';
import '../theme/app_colors.dart';
import 'order_detail_page.dart';

class OrdersPage extends StatefulWidget {
  const OrdersPage({super.key});
  @override
  State<OrdersPage> createState() => _OrdersPageState();
}

class _OrdersPageState extends State<OrdersPage> {
  final _service = MitraOrderService();
  List<MitraOrder> _orders = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await _service.getOrders();
      if (!mounted) return;
      setState(() => _orders = result);
    } catch (error) {
      if (!mounted) return;
      setState(
        () => _error = error is ApiException
            ? error.message
            : 'Gagal memuat pesanan.',
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _status(String value) =>
      const {
        'waiting_payment': 'Menunggu pembayaran',
        'confirmed': 'Dikonfirmasi',
        'on_the_way': 'Dalam perjalanan',
        'arrived': 'Sudah tiba',
        'in_progress': 'Sedang dikerjakan',
        'mitra_completed': 'Menunggu konfirmasi customer',
      }[value] ??
      value;

  String _date(DateTime? value) {
    if (value == null) return 'Jadwal tidak tersedia';
    final d = value.toLocal();
    return '${d.day}/${d.month}/${d.year} • ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) => RefreshIndicator(
    onRefresh: _loadOrders,
    child: ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        Text('Pesanan saya', style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 6),
        const Text('Kelola pekerjaan yang sudah kamu terima.'),
        const SizedBox(height: 16),
        if (_loading && _orders.isEmpty)
          const Center(
            child: Padding(
              padding: EdgeInsets.all(36),
              child: CircularProgressIndicator(),
            ),
          ),
        if (_error != null && _orders.isEmpty)
          _StateCard(message: _error!, onRetry: _loadOrders),
        if (!_loading && _error == null && _orders.isEmpty)
          const _StateCard(message: 'Belum ada pesanan aktif.'),
        ..._orders.map(
          (order) => Card(
            margin: const EdgeInsets.only(bottom: 10),
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 10,
              ),
              title: Text(
                order.serviceName,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text(
                  '${order.orderCode}\n${_date(order.scheduledAt)}\n${_status(order.orderStatus)}',
                ),
              ),
              isThreeLine: true,
              trailing: const Icon(Icons.chevron_right_rounded),
              onTap: () async {
                await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => OrderDetailPage(orderCode: order.orderCode),
                  ),
                );
                if (mounted) _loadOrders();
              },
            ),
          ),
        ),
        if (_error != null && _orders.isNotEmpty)
          Text(_error!, style: const TextStyle(color: AppColors.error)),
      ],
    ),
  );
}

class _StateCard extends StatelessWidget {
  final String message;
  final VoidCallback? onRetry;
  const _StateCard({required this.message, this.onRetry});
  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          Text(message, textAlign: TextAlign.center),
          if (onRetry != null)
            TextButton(onPressed: onRetry, child: const Text('Coba lagi')),
        ],
      ),
    ),
  );
}
