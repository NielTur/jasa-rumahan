import 'package:flutter/material.dart';
import 'package:core/core.dart';

import 'booking_page.dart';
import '../service/catalog_service.dart';
import '../theme/app_colors.dart';

class ServiceDetailPage extends StatefulWidget {
  final ServiceItem service;
  final CatalogService? catalogService;

  const ServiceDetailPage({
    super.key,
    required this.service,
    this.catalogService,
  });

  @override
  State<ServiceDetailPage> createState() => _ServiceDetailPageState();
}

class _ServiceDetailPageState extends State<ServiceDetailPage> {
  late final CatalogService _catalogService;
  late Future<ServiceDetail> _detailFuture;

  @override
  void initState() {
    super.initState();
    _catalogService = widget.catalogService ?? CatalogService();
    _loadDetail();
  }

  void _loadDetail() {
    _detailFuture = _catalogService.getServiceDetail(widget.service.id);
  }

  void _retry() {
    setState(_loadDetail);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAF8),
      appBar: AppBar(
        title: const Text('Detail Jasa'),
        backgroundColor: Colors.white,
        foregroundColor: AppColors.forestDeep,
        elevation: 0,
      ),
      body: FutureBuilder<ServiceDetail>(
        future: _detailFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            final message = snapshot.error is ApiException
                ? (snapshot.error as ApiException).message
                : 'Terjadi kesalahan saat memuat detail jasa.';

            return _MessageState(
              icon: Icons.wifi_off_outlined,
              message: message,
              actionLabel: 'Coba lagi',
              onAction: _retry,
            );
          }

          final detail = snapshot.data;

          if (detail == null) {
            return const _MessageState(
              icon: Icons.info_outline,
              message: 'Detail jasa belum tersedia.',
            );
          }

          return Column(
            children: [
              Expanded(child: _buildDetail(detail)),
              _buildBookingButton(detail),
            ],
          );
        },
      ),
    );
  }

  Widget _buildBookingButton(ServiceDetail detail) {
    final canBook = detail.prices.any((group) => group.sizes.isNotEmpty);

    return SafeArea(
      top: false,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        color: Colors.white,
        child: SizedBox(
          height: 52,
          child: FilledButton(
            onPressed: canBook
                ? () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => BookingPage(detail: detail),
                      ),
                    );
                  }
                : null,
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.forestDeep,
              foregroundColor: Colors.white,
              disabledBackgroundColor: Colors.grey.shade300,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            child: Text(
              canBook ? 'Pesan Sekarang' : 'Jasa Belum Tersedia',
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDetail(ServiceDetail detail) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 32),
      children: [
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: AppColors.forestDeep,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(
                Icons.home_repair_service_outlined,
                color: AppColors.sageFresh,
                size: 34,
              ),
              const SizedBox(height: 16),
              Text(
                detail.serviceName,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 23,
                  fontWeight: FontWeight.w800,
                ),
              ),
              if (detail.durationMinutes > 0) ...[
                const SizedBox(height: 10),
                Row(
                  children: [
                    const Icon(
                      Icons.schedule_outlined,
                      color: Colors.white70,
                      size: 18,
                    ),
                    const SizedBox(width: 7),
                    Text(
                      'Estimasi ${detail.durationMinutes} menit',
                      style: const TextStyle(color: Colors.white70),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 24),
        _sectionTitle('Deskripsi layanan'),
        const SizedBox(height: 10),
        Text(
          detail.description?.trim().isNotEmpty == true
              ? detail.description!.trim()
              : 'Belum ada deskripsi untuk layanan ini.',
          style: const TextStyle(color: AppColors.mutedStone, height: 1.55),
        ),
        const SizedBox(height: 26),
        _sectionTitle('Daftar harga'),
        const SizedBox(height: 10),
        if (detail.prices.isEmpty)
          const _InlineNotice(message: 'Harga untuk jasa ini belum tersedia.'),
        ...detail.prices.map(_buildPriceGroup),
        const SizedBox(height: 24),
        _sectionTitle('Layanan tambahan'),
        const SizedBox(height: 10),
        if (detail.addons.isEmpty)
          const _InlineNotice(
            message: 'Belum ada layanan tambahan untuk jasa ini.',
          ),
        ...detail.addons.map(_buildAddon),
        const SizedBox(height: 18),
        const Text(
          'Harga akhir akan dihitung oleh sistem berdasarkan ukuran '
          'properti dan layanan tambahan yang dipilih.',
          style: TextStyle(
            color: AppColors.mutedStone,
            fontSize: 12,
            height: 1.5,
          ),
        ),
      ],
    );
  }

  Widget _sectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        color: AppColors.charcoalSlate,
        fontSize: 17,
        fontWeight: FontWeight.w800,
      ),
    );
  }

  Widget _buildPriceGroup(ServicePriceGroup group) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: const BorderSide(color: Color(0xFFE7ECE8)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(15),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              group.typeName,
              style: const TextStyle(
                color: AppColors.forestDeep,
                fontWeight: FontWeight.w800,
              ),
            ),
            if (group.sizes.isEmpty)
              const Padding(
                padding: EdgeInsets.only(top: 8),
                child: Text(
                  'Ukuran properti belum tersedia.',
                  style: TextStyle(color: AppColors.mutedStone),
                ),
              ),
            ...group.sizes.map(
              (size) => Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            size.sizeName,
                            style: const TextStyle(
                              color: AppColors.charcoalSlate,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          if (size.minArea != null || size.maxArea != null)
                            Text(
                              _areaLabel(size.minArea, size.maxArea),
                              style: const TextStyle(
                                color: AppColors.mutedStone,
                                fontSize: 12,
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      _formatRupiah(size.price),
                      style: const TextStyle(
                        color: AppColors.forestDeep,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAddon(ServiceAddon addon) {
    return Card(
      margin: const EdgeInsets.only(bottom: 9),
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: const BorderSide(color: Color(0xFFE7ECE8)),
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 15, vertical: 5),
        leading: const CircleAvatar(
          backgroundColor: AppColors.mintTint,
          child: Icon(Icons.add, color: AppColors.forestDeep),
        ),
        title: Text(
          addon.addonName,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: addon.description?.trim().isNotEmpty == true
            ? Text(addon.description!.trim())
            : null,
        trailing: Text(
          _formatRupiah(addon.price),
          style: const TextStyle(
            color: AppColors.forestDeep,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
  }

  String _areaLabel(num? minArea, num? maxArea) {
    if (minArea != null && maxArea != null) {
      return '${_areaNumber(minArea)}–${_areaNumber(maxArea)} m²';
    }

    if (minArea != null) {
      return 'Mulai ${_areaNumber(minArea)} m²';
    }

    return 'Maksimal ${_areaNumber(maxArea!)} m²';
  }

  String _areaNumber(num value) {
    return value == value.roundToDouble()
        ? value.toInt().toString()
        : value.toString();
  }

  String _formatRupiah(int amount) {
    final digits = amount.toString();
    final result = StringBuffer();

    for (var index = 0; index < digits.length; index++) {
      if (index > 0 && (digits.length - index) % 3 == 0) {
        result.write('.');
      }

      result.write(digits[index]);
    }

    return 'Rp $result';
  }
}

class _InlineNotice extends StatelessWidget {
  final String message;

  const _InlineNotice({required this.message});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.mintTint,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(message, style: const TextStyle(color: AppColors.mutedStone)),
    );
  }
}

class _MessageState extends StatelessWidget {
  final IconData icon;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  const _MessageState({
    required this.icon,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 42, color: AppColors.mutedStone),
            const SizedBox(height: 14),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.mutedStone),
            ),
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 14),
              OutlinedButton(onPressed: onAction, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}
