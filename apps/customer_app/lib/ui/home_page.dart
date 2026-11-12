import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
        children: [
          Row(
            children: [
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Halo, selamat datang',
                        style: TextStyle(color: AppColors.mutedStone)),
                    SizedBox(height: 4),
                    Text('Rumah lebih nyaman,\nmulai dari sini.',
                        style: TextStyle(
                          color: AppColors.forestDeep,
                          fontSize: 25,
                          height: 1.2,
                          fontWeight: FontWeight.w800,
                        )),
                  ],
                ),
              ),
              CircleAvatar(
                radius: 23,
                backgroundColor: AppColors.mintTint,
                child: Icon(Icons.home_work_outlined,
                    color: AppColors.forestDeep, size: 25),
              ),
            ],
          ),
          const SizedBox(height: 22),
          const _WelcomeBanner(),
          const SizedBox(height: 26),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Pilih layanan',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                        color: AppColors.charcoalSlate,
                      )),
              const Text('Lihat semua',
                  style: TextStyle(
                      color: AppColors.emeraldActive,
                      fontWeight: FontWeight.w700)),
            ],
          ),
          const SizedBox(height: 14),
          const _ServiceTile(
            icon: Icons.cleaning_services_outlined,
            title: 'Bersih-bersih rumah',
            subtitle: 'Bantu rumah tetap bersih dan rapi',
          ),
          const SizedBox(height: 10),
          const _ServiceTile(
            icon: Icons.grass_outlined,
            title: 'Perawatan taman',
            subtitle: 'Rawat rumput dan area halaman',
          ),
          const SizedBox(height: 10),
          const _ServiceTile(
            icon: Icons.park_outlined,
            title: 'Pemangkasan pohon',
            subtitle: 'Bantu rapikan pohon di sekitar rumah',
          ),
          const SizedBox(height: 24),
          const Text('Butuh bantuan dengan pesanan?',
              style: TextStyle(
                  color: AppColors.charcoalSlate,
                  fontWeight: FontWeight.w700)),
          const SizedBox(height: 6),
          const Text(
            'Kamu bisa memantau proses pesanan melalui menu Pesanan.',
            style: TextStyle(color: AppColors.mutedStone, height: 1.4),
          ),
        ],
      ),
    );
  }
}

class _WelcomeBanner extends StatelessWidget {
  const _WelcomeBanner();

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
                Text('Urusan rumah jadi lebih ringan',
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w700)),
                SizedBox(height: 7),
                Text('Temukan bantuan jasa rumahan sesuai kebutuhanmu.',
                    style: TextStyle(color: Colors.white70, height: 1.4)),
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

class _ServiceTile extends StatelessWidget {
  const _ServiceTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        leading: CircleAvatar(
          backgroundColor: AppColors.mintTint,
          child: Icon(icon, color: AppColors.forestDeep),
        ),
        title: Text(title,
            style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_right_rounded),
        onTap: () {},
      ),
    );
  }
}
