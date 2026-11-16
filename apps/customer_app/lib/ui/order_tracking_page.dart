import 'dart:async';
import 'package:flutter/material.dart';
import '../service/order_history_service.dart';
import '../theme/app_colors.dart';
import 'order_tracking_sections.dart';

class OrderTrackingPage extends StatefulWidget {
  final String orderCode;

  const OrderTrackingPage({super.key, required this.orderCode});

  @override
  State<OrderTrackingPage> createState() => _OrderTrackingPageState();
}

class _OrderTrackingPageState extends State<OrderTrackingPage> {
  final _service = OrderHistoryService();
  Map<String, dynamic>? _order;
  List<Map<String, dynamic>> _history = [];
  Timer? _timer;
  bool _loading = true;
  bool _refreshing = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _refreshTracking(showLoading: true);
    _timer = Timer.periodic(
      const Duration(seconds: 10),
      (_) => _refreshTracking(),
    );
  }

  Future<void> _refreshTracking({bool showLoading = false}) async {
    if (_refreshing) return;
    _refreshing = true;

    if (showLoading && mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }

    try {
      final results = await Future.wait([
        _service.getOrderDetail(widget.orderCode),
        _service.getStatusHistory(widget.orderCode),
      ]);

      if (!mounted) return;

      final order = results[0] as Map<String, dynamic>;
      final history = results[1] as List<Map<String, dynamic>>;

      setState(() {
        _order = order;
        _history = history;
        _loading = false;
        _error = null;
      });

      final status = order['order_status']?.toString();
      if (status == 'completed' || status == 'cancelled') {
        _timer?.cancel();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    } finally {
      _refreshing = false;
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvasCream,
      appBar: AppBar(
        title: const Text('Lacak Pesanan'),
        backgroundColor: AppColors.canvasCream,
        foregroundColor: AppColors.forestDeep,
        actions: [
          IconButton(
            onPressed: _refreshing ? null : () => _refreshTracking(),
            icon: const Icon(Icons.refresh),
            tooltip: 'Perbarui status',
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _order == null
          ? _buildError()
          : RefreshIndicator(
              onRefresh: () => _refreshTracking(),
              child: ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  TrackingCurrentStatus(
                    orderCode: widget.orderCode,
                    status: _order!['order_status']?.toString() ?? '-',
                  ),
                  const SizedBox(height: 16),
                  TrackingProgress(
                    status: _order!['order_status']?.toString() ?? '-',
                  ),
                  const SizedBox(height: 20),
                  TrackingHistory(items: _history),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      'Pembaruan gagal. Data mungkin belum terbaru.',
                      style: TextStyle(color: AppColors.coralDanger),
                    ),
                  ],
                  const SizedBox(height: 12),
                  const Center(
                    child: Text(
                      'Status diperbarui otomatis setiap 10 detik.',
                      style: TextStyle(
                        color: AppColors.mutedStone,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildError() => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.wifi_off_outlined, size: 48),
          const SizedBox(height: 12),
          Text(_error ?? 'Gagal memuat pesanan.', textAlign: TextAlign.center),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () => _refreshTracking(showLoading: true),
            child: const Text('Coba Lagi'),
          ),
        ],
      ),
    ),
  );
}
