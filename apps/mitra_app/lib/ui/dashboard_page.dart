import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/dashboard_service.dart';
import '../theme/app_colors.dart';

class DashboardPage extends StatefulWidget {
  final AuthUser user;

  const DashboardPage({super.key, required this.user});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  final DashboardService _service = DashboardService();

  DashboardData? _data;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await _service.getDashboardData();
      if (!mounted) return;
      setState(() => _data = result);
    } catch (error) {
      if (!mounted) return;
      setState(
        () => _error = error is ApiException
            ? error.message
            : 'Gagal mengambil data dashboard.',
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Map<String, dynamic> _map(dynamic value) =>
      value is Map ? Map<String, dynamic>.from(value) : {};

  String _rupiah(dynamic value) {
    final number = value is num ? value.toInt() : 0;
    final digits = number.toString().replaceAllMapped(
      RegExp(r'\B(?=(\d{3})+(?!\d))'),
      (_) => '.',
    );
    return 'Rp$digits';
  }

  String _date(dynamic value) {
    final date = DateTime.tryParse(value?.toString() ?? '')?.toLocal();
    if (date == null) return 'Jadwal belum tersedia';

    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Agu',
      'Sep',
      'Okt',
      'Nov',
      'Des',
    ];

    return '${date.day} ${months[date.month - 1]} ${date.year}, '
        '${date.hour.toString().padLeft(2, '0')}:'
        '${date.minute.toString().padLeft(2, '0')}';
  }

  String _statusLabel(String status) {
    const labels = {
      'waiting_payment': 'Menunggu pembayaran',
      'confirmed': 'Dikonfirmasi',
      'on_the_way': 'Dalam perjalanan',
      'arrived': 'Sudah tiba',
      'in_progress': 'Sedang dikerjakan',
      'mitra_completed': 'Menunggu konfirmasi selesai',
    };
    return labels[status] ?? status;
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _loadDashboard,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            'Halo, ${widget.user.fullName}',
            style: Theme.of(context).textTheme.headlineMedium,
          ),
          const SizedBox(height: 6),
          Text(
            'Berikut ringkasan aktivitas Mitra kamu.',
            style: Theme.of(
              context,
            ).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 24),
          if (_isLoading && _data == null)
            const Padding(
              padding: EdgeInsets.all(48),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (_error != null && _data == null)
            _ErrorView(message: _error!, onRetry: _loadDashboard)
          else if (_data != null) ...[
            if (_error != null)
              Text(
                'Pembaruan gagal: $_error',
                style: const TextStyle(color: AppColors.error),
              ),
            Row(
              children: [
                Expanded(
                  child: _SummaryCard(
                    title: 'Penawaran masuk',
                    count: _data!.offers.length,
                    icon: Icons.notifications_active_outlined,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _SummaryCard(
                    title: 'Pesanan aktif',
                    count: _data!.orders.length,
                    icon: Icons.assignment_outlined,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 28),
            _sectionTitle('Penawaran Masuk'),
            if (_data!.offers.isEmpty)
              const _EmptyView(message: 'Belum ada penawaran baru.')
            else
              ..._data!.offers.take(3).map(_offerCard),
            const SizedBox(height: 24),
            _sectionTitle('Pesanan Aktif'),
            if (_data!.orders.isEmpty)
              const _EmptyView(message: 'Belum ada pesanan aktif.')
            else
              ..._data!.orders.take(3).map(_orderCard),
          ],
        ],
      ),
    );
  }

  Widget _sectionTitle(String title) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: Text(title, style: Theme.of(context).textTheme.titleMedium),
  );

  Widget _offerCard(Map<String, dynamic> offer) {
    final service = _map(offer['service']);
    final property = _map(offer['property_size']);
    final pricing = _map(offer['pricing']);

    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.all(14),
        title: Text(service['service_name']?.toString() ?? 'Nama jasa'),
        subtitle: Text(
          '${property['size_name'] ?? '-'}\n'
          '${_date(offer['scheduled_at'])}\n'
          'Durasi: ${offer['duration_minutes'] ?? '-'} menit',
        ),
        isThreeLine: true,
        trailing: Text(
          _rupiah(pricing['total_price']),
          style: Theme.of(context).textTheme.labelLarge,
        ),
      ),
    );
  }

  Widget _orderCard(Map<String, dynamic> order) {
    final service = _map(order['service']);

    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.all(14),
        title: Text(service['service_name']?.toString() ?? 'Nama jasa'),
        subtitle: Text(
          '${order['order_code'] ?? '-'}\n'
          '${_date(order['scheduled_at'])}\n'
          '${_statusLabel(order['order_status']?.toString() ?? '')}',
        ),
        isThreeLine: true,
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final String title;
  final int count;
  final IconData icon;

  const _SummaryCard({
    required this.title,
    required this.count,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: AppColors.primary, size: 26),
            const SizedBox(height: 12),
            Text(
              count.toString(),
              style: Theme.of(context).textTheme.headlineMedium,
            ),
            Text(title, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final String message;

  const _EmptyView({required this.message});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Center(child: Text(message)),
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            const Icon(Icons.cloud_off_outlined, size: 40),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton(onPressed: onRetry, child: const Text('Coba Lagi')),
          ],
        ),
      ),
    );
  }
}
