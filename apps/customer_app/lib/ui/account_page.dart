import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

class AccountPage extends StatelessWidget {
  const AccountPage({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text('Akun',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    color: AppColors.forestDeep,
                    fontWeight: FontWeight.w800,
                  )),
          const SizedBox(height: 20),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Row(
                children: [
                  const CircleAvatar(
                    radius: 27,
                    backgroundColor: AppColors.mintTint,
                    child: Icon(Icons.person_outline,
                        color: AppColors.forestDeep, size: 29),
                  ),
                  const SizedBox(width: 14),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Pengguna tamu',
                            style: TextStyle(
                                fontWeight: FontWeight.w700, fontSize: 16)),
                        SizedBox(height: 4),
                        Text('Masuk untuk menyimpan data profilmu.',
                            style: TextStyle(color: AppColors.mutedStone)),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right_rounded),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
          const _AccountMenu(
              icon: Icons.person_outline, title: 'Informasi profil'),
          const _AccountMenu(
              icon: Icons.location_on_outlined, title: 'Alamat tersimpan'),
          const _AccountMenu(
              icon: Icons.help_outline_rounded, title: 'Pusat bantuan'),
          const _AccountMenu(
              icon: Icons.info_outline_rounded, title: 'Tentang aplikasi'),
        ],
      ),
    );
  }
}

class _AccountMenu extends StatelessWidget {
  const _AccountMenu({required this.icon, required this.title});

  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: AppColors.emeraldActive),
      title: Text(title),
      trailing: const Icon(Icons.chevron_right_rounded),
      onTap: () {},
    );
  }
}
