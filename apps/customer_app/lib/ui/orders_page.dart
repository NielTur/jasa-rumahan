import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

class OrdersPage extends StatelessWidget {
  const OrdersPage({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Pesanan',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: AppColors.forestDeep,
                      fontWeight: FontWeight.w800,
                    )),
            const SizedBox(height: 8),
            const Text('Pantau pesanan jasa rumahanmu di sini.',
                style: TextStyle(color: AppColors.mutedStone)),
            const Expanded(
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.receipt_long_outlined,
                        size: 58, color: AppColors.sageFresh),
                    SizedBox(height: 14),
                    Text('Belum ada pesanan',
                        style: TextStyle(
                            fontSize: 17, fontWeight: FontWeight.w700)),
                    SizedBox(height: 5),
                    Text('Pesanan yang kamu buat akan muncul di sini.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.mutedStone)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
