import 'package:flutter/material.dart';

class AppColors {
  AppColors._();

  // Primary palette
  static const Color forestDeep = Color(0xFF1B4332);
  static const Color emeraldActive = Color(0xFF2D6A4F);
  static const Color sageFresh = Color(0xFF52B788);
  static const Color mintTint = Color(0xFFD8F3DC);

  // Background and surface
  static const Color canvasCream = Color(0xFFFAF8F5);
  static const Color surfaceSand = Color(0xFFF2EDE4);
  static const Color borderHairline = Color(0xFFE9E3D8);

  // Text
  static const Color charcoalSlate = Color(0xFF1A2E26);
  static const Color mutedStone = Color(0xFF607268);

  // Status and payment
  static const Color amberWarning = Color(0xFFD97706);
  static const Color coralDanger = Color(0xFFDC2626);
  static const Color blueMidtrans = Color(0xFF002855);

  // Compatibility alias untuk halaman autentikasi yang sudah ada.
  static const Color primary = forestDeep;
  static const Color primaryDark = forestDeep;
  static const Color secondary = emeraldActive;
  static const Color background = canvasCream;
  static const Color surface = Colors.white;
  static const Color textPrimary = charcoalSlate;
  static const Color textSecondary = mutedStone;
  static const Color success = emeraldActive;
  static const Color warning = amberWarning;
  static const Color error = coralDanger;
  static const Color border = borderHairline;
}
