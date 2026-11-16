import 'package:flutter/material.dart';
import 'package:core/core.dart';

import '../service/catalog_service.dart';
import '../theme/app_colors.dart';
import 'widgets/catalog_widgets.dart';

class HomePage extends StatefulWidget {
  final CatalogService? catalogService;
  final ValueChanged<OrderResult>? onOrderCreated;

  const HomePage({super.key, this.catalogService, this.onOrderCreated});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  late final CatalogService _catalogService;

  List<ServiceCategory> _categories = [];
  List<ServiceItem> _services = [];
  int? _selectedCategoryId;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _catalogService = widget.catalogService ?? CatalogService();
    _loadCatalog();
  }

  Future<void> _loadCatalog() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final categories = await _catalogService.getCategories();
      final services = await _catalogService.getServices();

      if (!mounted) return;

      setState(() {
        _categories = categories;
        _services = services;
        _selectedCategoryId = null;
        _isLoading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _errorMessage = error.message;
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _errorMessage = 'Terjadi kesalahan saat memuat katalog.';
        _isLoading = false;
      });
    }
  }

  List<ServiceItem> get _visibleServices {
    if (_selectedCategoryId == null) return _services;

    return _services
        .where((item) => item.categoryId == _selectedCategoryId)
        .toList();
  }

  void _selectCategory(int? id) {
    setState(() => _selectedCategoryId = id);
  }

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
                    Text(
                      'Halo, selamat datang',
                      style: TextStyle(color: AppColors.mutedStone),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Rumah lebih nyaman,\nmulai dari sini.',
                      style: TextStyle(
                        color: AppColors.forestDeep,
                        fontSize: 25,
                        height: 1.2,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
              const CircleAvatar(
                radius: 23,
                backgroundColor: AppColors.mintTint,
                child: Icon(
                  Icons.home_work_outlined,
                  color: AppColors.forestDeep,
                  size: 25,
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          const WelcomeBanner(),
          const SizedBox(height: 26),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Pilih layanan',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: AppColors.charcoalSlate,
                ),
              ),
              TextButton(
                onPressed: () => _selectCategory(null),
                child: const Text('Lihat semua'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          _buildCategories(),
          const SizedBox(height: 18),
          _buildServices(),
          const SizedBox(height: 24),
          const Text(
            'Butuh bantuan dengan pesanan?',
            style: TextStyle(
              color: AppColors.charcoalSlate,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Kamu bisa memantau proses pesanan melalui menu Pesanan.',
            style: TextStyle(color: AppColors.mutedStone, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _buildCategories() {
    if (_isLoading && _categories.isEmpty) {
      return const SizedBox(
        height: 42,
        child: Center(child: LinearProgressIndicator()),
      );
    }

    if (_categories.isEmpty) {
      return const Text(
        'Kategori belum tersedia.',
        style: TextStyle(color: AppColors.mutedStone),
      );
    }

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          FilterChip(
            label: const Text('Semua'),
            selected: _selectedCategoryId == null,
            onSelected: (_) => _selectCategory(null),
          ),
          const SizedBox(width: 8),
          ..._categories.map(
            (category) => Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilterChip(
                label: Text(category.categoryName),
                selected: _selectedCategoryId == category.id,
                onSelected: (_) => _selectCategory(category.id),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildServices() {
    if (_isLoading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 36),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (_errorMessage != null) {
      return CatalogMessage(
        icon: Icons.wifi_off_outlined,
        message: _errorMessage!,
        actionLabel: 'Coba lagi',
        onAction: _loadCatalog,
      );
    }

    if (_visibleServices.isEmpty) {
      return const CatalogMessage(
        icon: Icons.search_off_outlined,
        message: 'Belum ada layanan untuk kategori ini.',
      );
    }

    return Column(
      children: _visibleServices
          .map(
            (service) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: ServiceTile(
                service: service,
                onOrderCreated: widget.onOrderCreated,
              ),
            ),
          )
          .toList(),
    );
  }
}
