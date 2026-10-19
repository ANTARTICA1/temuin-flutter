import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../core/constants/api_constants.dart';
import '../core/constants/app_colors.dart';
import '../core/network/api_client.dart';

class BoostPriorityBottomSheet extends StatefulWidget {
  final String itemId;
  final String itemName;
  final bool isPremium;
  final int currentRewardAmount;
  final String currentRewardDescription;
  final String currentLastSeenLocation;
  final String currentContactPhone;

  const BoostPriorityBottomSheet({
    super.key,
    required this.itemId,
    required this.itemName,
    required this.isPremium,
    this.currentRewardAmount = 0,
    this.currentRewardDescription = '',
    this.currentLastSeenLocation = '',
    this.currentContactPhone = '',
  });

  static Future<bool?> show(
    BuildContext context, {
    required String itemId,
    required String itemName,
    required bool isPremium,
    int currentRewardAmount = 0,
    String currentRewardDescription = '',
    String currentLastSeenLocation = '',
    String currentContactPhone = '',
  }) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => BoostPriorityBottomSheet(
        itemId: itemId,
        itemName: itemName,
        isPremium: isPremium,
        currentRewardAmount: currentRewardAmount,
        currentRewardDescription: currentRewardDescription,
        currentLastSeenLocation: currentLastSeenLocation,
        currentContactPhone: currentContactPhone,
      ),
    );
  }

  @override
  State<BoostPriorityBottomSheet> createState() => _BoostPriorityBottomSheetState();
}

class _BoostPriorityBottomSheetState extends State<BoostPriorityBottomSheet> {
  late TextEditingController _rewardAmountController;
  late TextEditingController _rewardDescController;
  late TextEditingController _locationController;
  late TextEditingController _phoneController;

  bool _isProcessing = false;
  bool _isCheckingStatus = false;
  bool _isAwaitingPayment = false;
  bool _isSuccess = false;
  String? _orderId;
  String? _paymentUrl;

  @override
  void initState() {
    super.initState();
    _rewardAmountController = TextEditingController(
      text: widget.currentRewardAmount > 0 ? widget.currentRewardAmount.toString() : '',
    );
    _rewardDescController = TextEditingController(text: widget.currentRewardDescription);
    _locationController = TextEditingController(text: widget.currentLastSeenLocation);
    _phoneController = TextEditingController(text: widget.currentContactPhone);
  }

  @override
  void dispose() {
    _rewardAmountController.dispose();
    _rewardDescController.dispose();
    _locationController.dispose();
    _phoneController.dispose();
    super.dispose();
  }


  Future<void> _savePrioritySettingsOnly() async {
    setState(() => _isProcessing = true);
    try {
      final reward = int.tryParse(_rewardAmountController.text.replaceAll('.', '').trim()) ?? 0;
      final response = await ApiClient.postRequest(
        ApiConstants.itemUpdatePriority,
        {
          'item_id': widget.itemId,
          'reward_amount': reward,
          'reward_description': _rewardDescController.text.trim(),
          'last_seen_location': _locationController.text.trim(),
          'contact_phone': _phoneController.text.trim(),
        },
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['status'] == 'success') {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Pengaturan imbalan dan info lokasi berhasil disimpan!'),
              backgroundColor: AppColors.success,
            ),
          );
          Navigator.pop(context, true);
        }
      } else {
        throw Exception(data['message'] ?? 'Gagal menyimpan pengaturan.');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceAll('Exception: ', '')),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isProcessing = false);
    }
  }

  Future<void> _processBoostPayment() async {
    setState(() => _isProcessing = true);
    try {
      // 1. Simpan dulu info imbalan & lokasi jika diisi
      final reward = int.tryParse(_rewardAmountController.text.replaceAll('.', '').trim()) ?? 0;
      await ApiClient.postRequest(
        ApiConstants.itemUpdatePriority,
        {
          'item_id': widget.itemId,
          'reward_amount': reward,
          'reward_description': _rewardDescController.text.trim(),
          'last_seen_location': _locationController.text.trim(),
          'contact_phone': _phoneController.text.trim(),
        },
      );

      // 2. Buat transaksi boost Rp 15.000 via charge.php
      final response = await ApiClient.postRequest(
        ApiConstants.paymentCharge,
        {'item_id': widget.itemId},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['status'] == 'success') {
        final redirectUrl = data['redirect_url'];
        final orderId = data['order_id'];

        if (redirectUrl != null) {
          _orderId = orderId;
          _paymentUrl = redirectUrl;

          final launched = await launchUrl(
            Uri.parse(redirectUrl),
            mode: LaunchMode.externalApplication,
          );

          if (!launched) {
            throw Exception('Tidak dapat membuka browser pembayaran');
          }

          if (mounted) {
            setState(() {
              _isAwaitingPayment = true;
            });
          }
        } else {
          throw Exception('URL pembayaran tidak ditemukan');
        }
      } else {
        throw Exception(data['message'] ?? 'Gagal membuat tagihan pembayaran.');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceAll('Exception: ', '')),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isProcessing = false);
    }
  }

  Future<void> _checkPaymentStatus() async {
    if (_orderId == null) return;

    setState(() => _isCheckingStatus = true);
    try {
      final response = await ApiClient.postRequest(
        ApiConstants.paymentCheckStatus,
        {'order_id': _orderId},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['status'] == 'success' && data['is_paid'] == true) {
        if (mounted) {
          setState(() {
            _isSuccess = true;
            _isAwaitingPayment = false;
          });
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['message'] ?? 'Pembayaran belum terkonfirmasi.'),
              backgroundColor: AppColors.warning,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Gagal mengecek status pembayaran.'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isCheckingStatus = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 28,
      ),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(30),
          topRight: Radius.circular(30),
        ),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Drag indicator
            Center(
              child: Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
            const SizedBox(height: 18),

            if (_isSuccess) ...[
              _buildSuccessView(),
            ] else ...[
              // Header
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF00C6FF), Color(0xFF0072FF)],
                      ),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF0072FF).withValues(alpha: 0.3),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.rocket_launch_rounded, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.isPremium
                              ? 'Pengaturan Sayembara & Lokasi'
                              : 'Boost Prioritas VIP',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          widget.isPremium
                              ? 'Status Prioritas Aktif'
                              : 'Rp 15.000 • Maksimalkan Peluang Ditemukan',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: widget.isPremium ? AppColors.secondary : const Color(0xFF0072FF),
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context, false),
                    icon: const Icon(Icons.close_rounded, color: AppColors.textSecondary),
                  ),
                ],
              ),
              const SizedBox(height: 18),

              // Benefits Cards
              if (!widget.isPremium) ...[
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF1E1357), Color(0xFF2C1A7D)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.stars_rounded, color: Color(0xFF00C6FF), size: 20),
                          SizedBox(width: 8),
                          Text(
                            'Keuntungan Fitur Prioritas Rp 15.000:',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w800,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _buildBenefitItem(Icons.radar_rounded, 'Masuk ke Halaman Khusus Radar Barang Hilang Prioritas'),
                      _buildBenefitItem(Icons.trending_up_rounded, 'Tampil di baris terdepan Beranda aplikasi'),
                      _buildBenefitItem(Icons.payments_rounded, 'Bisa memasang Sayembara Imbalan uang tunai untuk penemu'),
                      _buildBenefitItem(Icons.location_on_rounded, 'Menampilkan lokasi terakhir barang terlihat'),
                      _buildBenefitItem(Icons.chat_rounded, 'Tombol WhatsApp instan agar penemu langsung menghubungi Anda'),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
              ],

              // Form: Sayembara & Lokasi
              const Text(
                'Sayembara Imbalan (Opsional):',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _rewardAmountController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  hintText: 'Misal: 100000 (Nominal Rupiah)',
                  prefixIcon: const Icon(Icons.attach_money_rounded, color: Color(0xFFF39C12)),
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _rewardDescController,
                decoration: InputDecoration(
                  hintText: 'Keterangan Imbalan (Misal: Uang tunai + Ucapan Terima Kasih)',
                  prefixIcon: const Icon(Icons.card_giftcard_rounded, color: AppColors.primary),
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
              const SizedBox(height: 18),

              const Text(
                'Lokasi Terakhir Terlihat (Opsional):',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _locationController,
                decoration: InputDecoration(
                  hintText: 'Misal: Perpustakaan Kampus Lt. 2 / Stasiun Manggarai',
                  prefixIcon: const Icon(Icons.location_on_outlined, color: AppColors.error),
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
              const SizedBox(height: 18),

              const Text(
                'Nomor WhatsApp Kontak Pemilik (Opsional):',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(
                  hintText: 'Misal: 081234567890 (Agar penemu bisa langsung chat)',
                  prefixIcon: const Icon(Icons.chat_bubble_outline_rounded, color: Color(0xFF25D366)),
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
              const SizedBox(height: 24),

              // Action Buttons
              if (!_isAwaitingPayment) ...[
                if (widget.isPremium) ...[
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: ElevatedButton(
                      onPressed: _isProcessing ? null : _savePrioritySettingsOnly,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      child: _isProcessing
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                            )
                          : const Text(
                              'Simpan Perubahan',
                              style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                            ),
                    ),
                  ),
                ] else ...[
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: ElevatedButton(
                      onPressed: _isProcessing ? null : _processBoostPayment,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0072FF),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      child: _isProcessing
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                            )
                          : const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.rocket_launch_rounded, size: 20),
                                SizedBox(width: 8),
                                Text(
                                  'Aktifkan Prioritas - Rp 15.000',
                                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                                ),
                              ],
                            ),
                    ),
                  ),
                ],
              ] else ...[
                // Awaiting payment in Midtrans
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.amber.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.amber.withValues(alpha: 0.4)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.access_time_rounded, color: Colors.orange, size: 28),
                      SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Menunggu Pembayaran',
                              style: TextStyle(fontWeight: FontWeight.w700, color: Colors.brown, fontSize: 14),
                            ),
                            SizedBox(height: 2),
                            Text(
                              'Silakan selesaikan pembayaran Rp 15.000 pada tab browser yang telah dibuka.',
                              style: TextStyle(fontSize: 12, color: Colors.brown),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton.icon(
                    onPressed: _isCheckingStatus ? null : _checkPaymentStatus,
                    icon: _isCheckingStatus
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : const Icon(Icons.sync_rounded),
                    label: const Text(
                      'Cek Status Pembayaran',
                      style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.secondary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                  ),
                ),
                if (_paymentUrl != null) ...[
                  const SizedBox(height: 8),
                  Center(
                    child: TextButton.icon(
                      onPressed: () => launchUrl(
                        Uri.parse(_paymentUrl!),
                        mode: LaunchMode.externalApplication,
                      ),
                      icon: const Icon(Icons.open_in_browser_rounded, size: 16),
                      label: const Text('Buka Ulang Halaman Pembayaran'),
                    ),
                  ),
                ],
              ],
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildBenefitItem(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 15, color: const Color(0xFF00C6FF)),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSuccessView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 24),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.success.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.verified_rounded, color: AppColors.success, size: 64),
            ),
            const SizedBox(height: 18),
            const Text(
              'Status Prioritas Aktif!',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.textPrimary),
            ),
            const SizedBox(height: 8),
            const Text(
              'Barang Anda kini ditampilkan di Radar Prioritas & Baris Teratas Beranda dengan info sayembara imbalan.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: AppColors.textSecondary, height: 1.4),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: const Text('Selesai', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
