import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../core/constants/api_constants.dart';
import '../core/constants/app_colors.dart';
import '../core/network/api_client.dart';

class BuySlotBottomSheet extends StatefulWidget {
  final Map<String, dynamic>? initialSlots;

  const BuySlotBottomSheet({super.key, this.initialSlots});

  static Future<bool?> show(BuildContext context, {Map<String, dynamic>? initialSlots}) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => BuySlotBottomSheet(initialSlots: initialSlots),
    );
  }

  @override
  State<BuySlotBottomSheet> createState() => _BuySlotBottomSheetState();
}

class _BuySlotBottomSheetState extends State<BuySlotBottomSheet> {
  static const int _pricePerSlot = 9000;
  int _selectedSlots = 1;
  bool _isLoadingSlots = false;
  bool _isProcessing = false;
  bool _isCheckingStatus = false;
  bool _isAwaitingPayment = false;
  bool _isPaymentSuccess = false;

  String? _orderId;
  String? _paymentUrl;
  Map<String, dynamic>? _slotData;

  @override
  void initState() {
    super.initState();
    _slotData = widget.initialSlots;
    if (_slotData == null) {
      _fetchSlotData();
    }
  }

  Future<void> _fetchSlotData() async {
    setState(() => _isLoadingSlots = true);
    try {
      final response = await ApiClient.getRequest(ApiConstants.itemSlots);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && mounted) {
          setState(() {
            _slotData = data['data'];
          });
        }
      }
    } catch (_) {
      // Ignored, will fallback gracefully
    } finally {
      if (mounted) setState(() => _isLoadingSlots = false);
    }
  }

  String _formatRupiah(int amount) {
    return amount.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]}.',
    );
  }

  Future<void> _createTransaction() async {
    setState(() => _isProcessing = true);
    try {
      final response = await ApiClient.postRequest(
        ApiConstants.paymentCharge,
        {
          'type': 'slot',
          'slots': _selectedSlots,
        },
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
            _isPaymentSuccess = true;
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
            content: Text('Gagal mengecek status pembayaran. Silakan coba lagi.'),
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
    final maxSlots = _slotData?['max_slots'] ?? 5;
    final totalPrice = _selectedSlots * _pricePerSlot;

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
          topLeft: Radius.circular(28),
          topRight: Radius.circular(28),
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
                  color: Colors.grey.withOpacity(0.3),
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
            const SizedBox(height: 20),

            if (_isPaymentSuccess) ...[
              _buildSuccessState(),
            ] else ...[
              // Header
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      gradient: AppColors.primaryGradient,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primary.withOpacity(0.25),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.add_shopping_cart_rounded, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Beli Slot Tambahan',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Rp ${_formatRupiah(_pricePerSlot)} / slot barang',
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppColors.primary,
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
              const SizedBox(height: 22),

              if (!_isAwaitingPayment) ...[
                // Quantity Selector Title
                const Text(
                  'Pilih Jumlah Slot yang Ingin Dibeli:',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),

                // Counter Box
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.primary.withOpacity(0.25), width: 1.5),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      IconButton(
                        onPressed: _selectedSlots > 1
                            ? () => setState(() => _selectedSlots--)
                            : null,
                        icon: Icon(
                          Icons.remove_circle_outline_rounded,
                          color: _selectedSlots > 1 ? AppColors.primary : Colors.grey.withOpacity(0.4),
                          size: 28,
                        ),
                      ),
                      Column(
                        children: [
                          Text(
                            '$_selectedSlots Slot',
                            style: const TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w800,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          Text(
                            '+$_selectedSlots slot baru (Total nanti: ${maxSlots + _selectedSlots} slot)',
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                      IconButton(
                        onPressed: () => setState(() => _selectedSlots++),
                        icon: const Icon(
                          Icons.add_circle_outline_rounded,
                          color: AppColors.primary,
                          size: 28,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),

                // Quick choice chips
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [1, 2, 3, 5].map((count) {
                    final isSelected = _selectedSlots == count;
                    final price = count * _pricePerSlot;
                    return GestureDetector(
                      onTap: () => setState(() => _selectedSlots = count),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.primary : AppColors.background,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isSelected ? AppColors.primary : Colors.black.withOpacity(0.08),
                          ),
                        ),
                        child: Text(
                          '+$count Slot (Rp ${_formatRupiah(price)})',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: isSelected ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 20),

                // Total Summary Card
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        AppColors.primary.withOpacity(0.08),
                        AppColors.secondary.withOpacity(0.08),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Total Bayar:',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        'Rp ${_formatRupiah(totalPrice)}',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w900,
                          color: AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // Pay Button
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: _isProcessing ? null : _createTransaction,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      elevation: 2,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: _isProcessing
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                          )
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.payment_rounded, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                'Bayar - Rp ${_formatRupiah(totalPrice)}',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                  fontSize: 15,
                                ),
                              ),
                            ],
                          ),
                  ),
                ),
              ] else ...[
                // Awaiting payment state
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.amber.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.amber.withOpacity(0.4)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.access_time_rounded, color: Colors.orange, size: 28),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Menunggu Pembayaran',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                color: Colors.brown,
                                fontSize: 14,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Halaman pembayaran telah dibuka di browser. Selesaikan pembayaran sebesar Rp ${_formatRupiah(totalPrice)}.',
                              style: const TextStyle(fontSize: 12, color: Colors.brown),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

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
                const SizedBox(height: 10),

                if (_paymentUrl != null)
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
        ),
      ),
    );
  }

  Widget _buildSuccessState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 20),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.success.withOpacity(0.12),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.check_circle_rounded,
                color: AppColors.success,
                size: 64,
              ),
            ),
            const SizedBox(height: 18),
            const Text(
              'Pembayaran Sukses!',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Selamat! Slot tambahan sebanyak $_selectedSlots slot berhasil ditambahkan ke akun Anda.',
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                color: AppColors.textSecondary,
                height: 1.4,
              ),
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
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: const Text(
                  'Selesai',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
