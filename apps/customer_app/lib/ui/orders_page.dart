import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../theme/app_colors.dart';

class OrdersPage extends StatelessWidget {
  final OrderResult? orderResult;

  const OrdersPage({super.key, this.orderResult});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Pesanan',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                color: AppColors.forestDeep,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Pantau pesanan jasa rumahanmu di sini.',
              style: TextStyle(color: AppColors.mutedStone),
            ),
            const SizedBox(height: 20),
            Expanded(
              child: orderResult == null
                  ? _buildEmptyState()
                  : _buildOrderCard(orderResult!),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
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

  Widget _buildOrderCard(OrderResult order) {
    return SingleChildScrollView(
      child: Card(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: Color(0xFFE7ECE8)),
        ),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(
                    Icons.check_circle_outline,
                    color: AppColors.forestDeep,
                  ),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'Pesanan berhasil dibuat',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 15,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              _buildInfoRow('Kode Pesanan', order.orderCode),
              const Divider(height: 24),
              _buildInfoRow('Status', _statusLabel(order.orderStatus)),
              if (order.scheduledAt != null) ...[
                const Divider(height: 24),
                _buildInfoRow('Jadwal', _formatDate(order.scheduledAt!)),
              ],
              if (order.totalPrice != null) ...[
                const Divider(height: 24),
                _buildInfoRow('Total Harga', _formatRupiah(order.totalPrice!)),
              ],
              if (order.trackingStage != null) ...[
                const Divider(height: 24),
                _buildInfoRow(
                  'Tahap Pelacakan',
                  _statusLabel(order.trackingStage!),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          flex: 2,
          child: Text(
            label,
            style: const TextStyle(color: AppColors.mutedStone),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          flex: 3,
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
      case 'searching':
      case 'searching_mitra':
        return 'Mencari Mitra';
      case 'waiting':
      case 'waiting_payment':
        return 'Menunggu Pembayaran';
      case 'confirmed':
        return 'Pesanan Dikonfirmasi';
      case 'working':
      case 'on_the_way':
      case 'arrived':
      case 'in_progress':
        return 'Sedang Diproses';
      case 'awaiting_confirmation':
      case 'mitra_completed':
        return 'Menunggu Konfirmasi';
      case 'done':
      case 'completed':
        return 'Selesai';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status.replaceAll('_', ' ');
    }
  }

  String _formatDate(DateTime date) {
    final localDate = date.toLocal();

    final day = localDate.day.toString().padLeft(2, '0');
    final month = localDate.month.toString().padLeft(2, '0');
    final year = localDate.year;
    final hour = localDate.hour.toString().padLeft(2, '0');
    final minute = localDate.minute.toString().padLeft(2, '0');

    return '$day/$month/$year, $hour:$minute';
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
