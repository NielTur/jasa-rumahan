import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../../service/catalog_service.dart';
import '../service_detail_page.dart';
import '../../theme/app_colors.dart';

class WelcomeBanner extends StatelessWidget {
  const WelcomeBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.forestDeep,
        borderRadius: BorderRadius.circular(20),
      ),
      child: const Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Urusan rumah jadi lebih ringan',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                SizedBox(height: 7),
                Text(
                  'Temukan bantuan jasa rumahan sesuai kebutuhanmu.',
                  style: TextStyle(color: Colors.white70, height: 1.4),
                ),
              ],
            ),
          ),
          SizedBox(width: 10),
          Icon(Icons.eco_outlined, color: AppColors.sageFresh, size: 42),
        ],
      ),
    );
  }
}

class ServiceTile extends StatelessWidget {
  const ServiceTile({super.key, required this.service, this.onOrderCreated});

  final ServiceItem service;
  final ValueChanged<OrderResult>? onOrderCreated;

  Future<void> _openDetail(BuildContext context) async {
    final result = await Navigator.push<OrderResult>(
      context,
      MaterialPageRoute(
        builder: (_) => ServiceDetailPage(
          service: service,
          catalogService: CatalogService(),
        ),
      ),
    );

    if (result != null) {
      onOrderCreated?.call(result);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        onTap: () => _openDetail(context),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        leading: const CircleAvatar(
          backgroundColor: AppColors.mintTint,
          child: Icon(
            Icons.miscellaneous_services_outlined,
            color: AppColors.forestDeep,
          ),
        ),
        title: Text(
          service.serviceName,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (service.description?.isNotEmpty == true)
              Text(service.description!),
            if (service.durationMinutes > 0)
              Text('Estimasi durasi: ${service.durationMinutes} menit'),
            const SizedBox(height: 4),
            const Text(
              'Lihat detail jasa',
              style: TextStyle(
                color: AppColors.forestDeep,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right, color: AppColors.mutedStone),
      ),
    );
  }
}

class CatalogMessage extends StatelessWidget {
  const CatalogMessage({
    super.key,
    required this.icon,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 28),
      child: Center(
        child: Column(
          children: [
            Icon(icon, size: 38, color: AppColors.mutedStone),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.mutedStone),
            ),
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 12),
              OutlinedButton(onPressed: onAction, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}
