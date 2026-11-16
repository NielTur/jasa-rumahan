import 'package:flutter/material.dart';
import 'package:core/core.dart';

import 'theme/app_theme.dart';
import 'ui/main_navigation_page.dart';
import 'service/catalog_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppFormatters.initialize();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  final CatalogService? catalogService;

  const MyApp({super.key, this.catalogService});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'JasaRumahan',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: MainNavigationPage(catalogService: catalogService),
    );
  }
}
