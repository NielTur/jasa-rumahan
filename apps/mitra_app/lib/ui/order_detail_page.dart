import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/mitra_order_service.dart';
import '../theme/app_colors.dart';

class OrderDetailPage extends StatefulWidget {
  final String orderCode;
  const OrderDetailPage({super.key, required this.orderCode});
  @override
  State<OrderDetailPage> createState() => _OrderDetailPageState();
}

class _OrderDetailPageState extends State<OrderDetailPage> {
  final _service = MitraOrderService();
  MitraOrder? _order;
  bool _loading = true;
  bool _processing = false;
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
      setState(() => _order = result);
    } catch (error) {
      if (!mounted) return;
      setState(
        () => _error = error is ApiException
            ? error.message
            : 'Gagal memuat detail pesanan.',
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String? get _nextAction {
    switch (_order?.orderStatus) {
      case 'confirmed':
        return _order?.paymentStatus == 'paid' ? 'depart' : null;
      case 'on_the_way':
        return 'arrive';
      case 'arrived':
        return 'start';
      case 'in_progress':
        return 'finish';
      default:
        return null;
    }
  }

  String get _actionLabel =>
      const {
        'depart': 'Mulai perjalanan',
        'arrive': 'Saya sudah tiba',
        'start': 'Mulai pengerjaan',
        'finish': 'Selesaikan pekerjaan',
      }[_nextAction] ??
      '';

  String _statusLabel(String value) =>
      const {
        'waiting_payment': 'Menunggu pembayaran',
        'confirmed': 'Dikonfirmasi',
        'on_the_way': 'Dalam perjalanan',
        'arrived': 'Sudah tiba',
        'in_progress': 'Sedang dikerjakan',
        'mitra_completed': 'Menunggu konfirmasi customer',
      }[value] ??
      value;

  String _money(int value) =>
      'Rp${value.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => '.')}';

  String _date(DateTime? value) {
    if (value == null) return '-';
    final d = value.toLocal();
    return '${d.day}/${d.month}/${d.year} • ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';
  }

  Future<void> _advance() async {
    final action = _nextAction;
    if (action == null || _order == null) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(_actionLabel),
        content: const Text(
          'Pastikan kamu sudah siap melanjutkan ke tahap berikutnya.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Lanjutkan'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    setState(() => _processing = true);
    try {
      final updated = await _service.advanceStatus(_order!.orderCode, action);
      if (!mounted) return;
      setState(() => _order = updated);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Status pesanan berhasil diperbarui.')),
      );
    } catch (error) {
      if (!mounted) return;
      final message = error is ApiException
          ? error.message
          : 'Status pesanan gagal diperbarui.';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
      await _loadDetail();
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final order = _order;
    return Scaffold(
      appBar: AppBar(title: const Text('Detail Pesanan')),
      body: _loading && order == null
          ? const Center(child: CircularProgressIndicator())
          : _error != null && order == null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(_error!, textAlign: TextAlign.center),
                    TextButton(
                      onPressed: _loadDetail,
                      child: const Text('Coba lagi'),
                    ),
                  ],
                ),
              ),
            )
          : order == null
          ? const SizedBox.shrink()
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          order.serviceName,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 6),
                        Text(
                          order.orderCode,
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          _statusLabel(order.orderStatus),
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                _DetailSection(
                  title: 'Jadwal',
                  rows: {
                    'Tanggal dan waktu': _date(order.scheduledAt),
                    'Durasi': '${order.durationMinutes} menit',
                    'Ukuran properti': order.propertySizeName,
                  },
                ),
                _DetailSection(
                  title: 'Customer',
                  rows: {
                    'Nama': order.customerName,
                    'Nomor HP': order.customerPhone,
                  },
                ),
                _DetailSection(
                  title: 'Lokasi pengerjaan',
                  rows: {
                    'Alamat': order.serviceAddress,
                    'Catatan alamat': order.addressNotes ?? '-',
                    'Catatan customer': order.customerNote ?? '-',
                  },
                ),
                _DetailSection(
                  title: 'Pembayaran',
                  rows: {
                    'Status pembayaran':
                        order.paymentStatus ?? 'Belum ada transaksi',
                    'Total': _money(order.totalPrice),
                  },
                ),
                if (order.orderStatus == 'confirmed' &&
                    order.paymentStatus != 'paid')
                  const Card(
                    child: Padding(
                      padding: EdgeInsets.all(14),
                      child: Text(
                        'Tombol perjalanan aktif setelah pembayaran customer terverifikasi.',
                      ),
                    ),
                  ),
                if (_nextAction != null)
                  FilledButton.icon(
                    onPressed: _processing ? null : _advance,
                    icon: _processing
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.arrow_forward_rounded),
                    label: Text(_processing ? 'Memproses...' : _actionLabel),
                  ),
                if (order.orderStatus == 'mitra_completed')
                  const Card(
                    child: Padding(
                      padding: EdgeInsets.all(14),
                      child: Text(
                        'Pekerjaan selesai. Menunggu customer mengonfirmasi penyelesaian.',
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _DetailSection extends StatelessWidget {
  final String title;
  final Map<String, String> rows;
  const _DetailSection({required this.title, required this.rows});
  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.only(top: 12),
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 10),
          ...rows.entries.map(
            (row) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    row.key,
                    style: const TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(row.value),
                ],
              ),
            ),
          ),
        ],
      ),
    ),
  );
}
