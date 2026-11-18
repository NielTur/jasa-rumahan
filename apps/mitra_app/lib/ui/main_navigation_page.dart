import 'package:core/core.dart';
import 'package:flutter/material.dart';

import 'dashboard_page.dart';
import 'offers_page.dart';
import 'orders_page.dart';
import 'profile_page.dart';
import 'services_page.dart';

class MainNavigationPage extends StatefulWidget {
  final AuthUser user;
  final Future<void> Function() onLogout;
  const MainNavigationPage({
    super.key,
    required this.user,
    required this.onLogout,
  });

  @override
  State<MainNavigationPage> createState() => _MainNavigationPageState();
}

class _MainNavigationPageState extends State<MainNavigationPage> {
  int _selectedIndex = 0;
  static const _titles = [
    'Beranda',
    'Penawaran',
    'Pesanan',
    'Layanan',
    'Profil',
  ];

  @override
  Widget build(BuildContext context) {
    final pages = [
      DashboardPage(user: widget.user),
      const OffersPage(),
      const OrdersPage(),
      const ServicesPage(),
      const ProfilePage(),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[_selectedIndex]),
        actions: [
          IconButton(
            tooltip: 'Keluar',
            onPressed: () => widget.onLogout(),
            icon: const Icon(Icons.logout_rounded),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: IndexedStack(index: _selectedIndex, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (index) =>
            setState(() => _selectedIndex = index),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_work_outlined),
            selectedIcon: Icon(Icons.home_work_rounded),
            label: 'Beranda',
          ),
          NavigationDestination(
            icon: Icon(Icons.notifications_active_outlined),
            selectedIcon: Icon(Icons.notifications_active_rounded),
            label: 'Tawaran',
          ),
          NavigationDestination(
            icon: Icon(Icons.assignment_outlined),
            selectedIcon: Icon(Icons.assignment_rounded),
            label: 'Pesanan',
          ),
          NavigationDestination(
            icon: Icon(Icons.handyman_outlined),
            selectedIcon: Icon(Icons.handyman_rounded),
            label: 'Layanan',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
