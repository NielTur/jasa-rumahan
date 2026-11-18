import 'package:core/core.dart';
import 'package:flutter/material.dart';

import '../service/mitra_offer_service.dart';
import '../theme/app_colors.dart';

class OffersPage extends StatefulWidget {
  const OffersPage({super.key});

  @override
  State<OffersPage> createState() => _OffersPageState();
}

class _OffersPageState extends State<OffersPage> {
  final _service = MitraOfferService();
  List<MitraOffer> _offers = [];
  bool _loading = true;
  final Set<int> _processing = {};
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadOffers();
  }

  Future<void> _loadOffers() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await _service.getOffers();
      if (!mounted) return;
      setState(() => _offers = result);
    } catch (error) {
      if (!mounted) return;
      setState(
        () => _error = error is ApiException
            ? error.message
            : 'Gagal memuat penawaran.',
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _handleOffer(MitraOffer offer, {required bool accept}) async {
    final action = accept ? 'menerima' : 'menolak';
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('${accept ? 'Terima' : 'Tolak'} penawaran?'),
        content: Text(
          'Yakin ingin $action penawaran jasa ${offer.serviceName}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(accept ? 'Terima' : 'Tolak'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _processing.add(offer.attemptId));
    try {
      if (accept) {
        final order = await _service.acceptOffer(offer.attemptId);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Penawaran diterima. Pesanan ${order.orderCode} masuk ke daftar pesanan.',
            ),
          ),
        );
      } else {
        await _service.rejectOffer(offer.attemptId);
        if (!mounted) return;
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Penawaran ditolak.')));
      }
      await _loadOffers();
    } catch (error) {
      if (!mounted) return;
      final message = error is ApiException
          ? error.message
          : 'Tindakan gagal diproses.';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) setState(() => _processing.remove(offer.attemptId));
    }
  }

  String _date(DateTime? value) {
    if (value == null) return 'Jadwal tidak tersedia';
    final date = value.toLocal();
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
    return '${date.day} ${months[date.month - 1]} ${date.year}, ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
  }

  String _money(int value) =>
      'Rp${value.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => '.')}';

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _loadOffers,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            'Penawaran untuk kamu',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 6),
          const Text('Terima atau tolak tawaran pekerjaan yang masuk.'),
          const SizedBox(height: 16),
          if (_loading && _offers.isEmpty)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(36),
                child: CircularProgressIndicator(),
              ),
            ),
          if (_error != null && _offers.isEmpty)
            _MessageCard(message: _error!, action: _loadOffers),
          if (!_loading && _error == null && _offers.isEmpty)
            const _MessageCard(message: 'Belum ada penawaran baru.'),
          ..._offers.map(
            (offer) => Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            offer.serviceName,
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                        ),
                        Text(
                          _money(offer.totalPrice),
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text('Ukuran: ${offer.propertySizeName}'),
                    Text('Jadwal: ${_date(offer.scheduledAt)}'),
                    Text('Durasi: ${offer.durationMinutes} menit'),
                    Text(
                      'Batas respons: ${_date(offer.expiresAt)}',
                      style: const TextStyle(color: AppColors.warning),
                    ),
                    if (offer.addons.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      const Text(
                        'Layanan tambahan',
                        style: TextStyle(fontWeight: FontWeight.w600),
                      ),
                      ...offer.addons.map(
                        (addon) => Text('• ${addon.name} x${addon.quantity}'),
                      ),
                    ],
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: _processing.contains(offer.attemptId)
                                ? null
                                : () => _handleOffer(offer, accept: false),
                            child: const Text('Tolak'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: FilledButton(
                            onPressed: _processing.contains(offer.attemptId)
                                ? null
                                : () => _handleOffer(offer, accept: true),
                            child: _processing.contains(offer.attemptId)
                                ? const SizedBox(
                                    width: 18,
                                    height: 18,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Text('Terima'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
          if (_error != null && _offers.isNotEmpty)
            Text(
              'Pembaruan gagal: $_error',
              style: const TextStyle(color: AppColors.error),
            ),
        ],
      ),
    );
  }
}

class _MessageCard extends StatelessWidget {
  final String message;
  final VoidCallback? action;
  const _MessageCard({required this.message, this.action});
  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          Text(message, textAlign: TextAlign.center),
          if (action != null)
            TextButton(onPressed: action, child: const Text('Coba lagi')),
        ],
      ),
    ),
  );
}
