import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class TrackingCurrentStatus extends StatelessWidget {
  final String orderCode;
  final String status;

  const TrackingCurrentStatus({
    super.key,
    required this.orderCode,
    required this.status,
  });

  @override
  Widget build(BuildContext context) => Card(
    color: Colors.white,
    elevation: 0,
    child: Padding(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Status Pesanan Saat Ini',
            style: TextStyle(color: AppColors.mutedStone),
          ),
          const SizedBox(height: 8),
          Text(
            statusLabel(status),
            style: const TextStyle(
              color: AppColors.forestDeep,
              fontSize: 20,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Kode: $orderCode',
            style: const TextStyle(color: AppColors.mutedStone),
          ),
        ],
      ),
    ),
  );
}

class TrackingProgress extends StatelessWidget {
  final String status;

  const TrackingProgress({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final stage = switch (status) {
      'confirmed' => 0,
      'on_the_way' || 'arrived' || 'in_progress' => 1,
      'mitra_completed' || 'completed' => 2,
      _ => -1,
    };
    final cancelled = status == 'cancelled';

    const labels = ['Dikonfirmasi', 'Pengerjaan', 'Selesai'];
    const icons = [
      Icons.check_circle_outline,
      Icons.home_repair_service_outlined,
      Icons.task_alt,
    ];

    return Card(
      color: Colors.white,
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 8),
        child: Row(
          children: List.generate(3, (index) {
            final active = !cancelled && stage >= index;
            final color = active
                ? AppColors.emeraldActive
                : AppColors.mutedStone;

            return Expanded(
              child: Column(
                children: [
                  Icon(icons[index], color: color, size: 28),
                  const SizedBox(height: 8),
                  Text(
                    labels[index],
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: color,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            );
          }),
        ),
      ),
    );
  }
}

class TrackingHistory extends StatelessWidget {
  final List<Map<String, dynamic>> items;

  const TrackingHistory({super.key, required this.items});

  @override
  Widget build(BuildContext context) => Card(
    color: Colors.white,
    elevation: 0,
    child: Padding(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Riwayat Status',
            style: TextStyle(
              color: AppColors.forestDeep,
              fontSize: 16,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 18),
          if (items.isEmpty)
            const Text(
              'Belum ada riwayat perubahan status.',
              style: TextStyle(color: AppColors.mutedStone),
            )
          else
            ...items.map(_historyItem),
        ],
      ),
    ),
  );

  Widget _historyItem(Map<String, dynamic> item) {
    final status = item['new_status']?.toString() ?? '-';
    final note = item['note']?.toString() ?? '';
    final date = DateTime.tryParse(
      item['created_at']?.toString() ?? '',
    )?.toLocal();

    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(
            Icons.check_circle,
            color: AppColors.emeraldActive,
            size: 20,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  statusLabel(status),
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: AppColors.charcoalSlate,
                  ),
                ),
                if (note.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    note,
                    style: const TextStyle(color: AppColors.mutedStone),
                  ),
                ],
                if (date != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    '${date.day.toString().padLeft(2, '0')}/'
                    '${date.month.toString().padLeft(2, '0')}/'
                    '${date.year} ${date.hour.toString().padLeft(2, '0')}:'
                    '${date.minute.toString().padLeft(2, '0')}',
                    style: const TextStyle(
                      color: AppColors.mutedStone,
                      fontSize: 11,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

String statusLabel(String status) {
  switch (status) {
    case 'pending':
    case 'searching_mitra':
      return 'Mencari Mitra';
    case 'waiting_payment':
      return 'Menunggu Pembayaran';
    case 'confirmed':
      return 'Pesanan Dikonfirmasi';
    case 'on_the_way':
      return 'Mitra Dalam Perjalanan';
    case 'arrived':
      return 'Mitra Telah Tiba';
    case 'in_progress':
      return 'Pesanan Sedang Dikerjakan';
    case 'mitra_completed':
      return 'Menunggu Konfirmasi Customer';
    case 'completed':
      return 'Pesanan Selesai';
    case 'cancelled':
      return 'Pesanan Dibatalkan';
    default:
      return status.replaceAll('_', ' ');
  }
}
