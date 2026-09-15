import 'package:flutter/material.dart';

class AppColors {
  static const Color primary = Color(0xFF6C5CE7);      
  static const Color primaryLight = Color(0xFF8B7FF5);  
  static const Color primaryDark = Color(0xFF5A4BD1);   
  static const Color secondary = Color(0xFF00B894);     
  static const Color secondaryLight = Color(0xFF55EFC4);
  static const Color accent = Color(0xFFFF7675);
  static const Color background = Color(0xFFF0F0F5);   
  static const Color surface = Colors.white;
  static const Color cardColor = Color(0xFFFAFAFC);
  static const Color darkBackground = Color(0xFF1A1A2E);
  static const Color success = Color(0xFF00B894);
  static const Color error = Color(0xFFE17055);
  static const Color warning = Color(0xFFFDCB6E);
  static const Color info = Color(0xFF74B9FF);
  static const Color textPrimary = Color(0xFF2D3436);
  static const Color textSecondary = Color(0xFF949CA8);
  static const Color textLight = Colors.white;
  static const Color textHint = Color(0xFFB2BEC3);

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF6C5CE7), Color(0xFF8B7FF5)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient heroGradient = LinearGradient(
    colors: [Color(0xFF6C5CE7), Color(0xFF4834D4)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  static const LinearGradient accentGradient = LinearGradient(
    colors: [Color(0xFF00B894), Color(0xFF55EFC4)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient warmGradient = LinearGradient(
    colors: [Color(0xFFE17055), Color(0xFFFDCB6E)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static Color shimmer = Colors.white.withOpacity(0.08);
  static Color glass = Colors.white.withOpacity(0.15);
}
