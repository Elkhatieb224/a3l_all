import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/wallet_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/payement/hewala_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:intl/intl.dart';

class MyWalletPage extends StatefulWidget {
  const MyWalletPage({super.key});

  @override
  State<MyWalletPage> createState() => _MyWalletPageState();
}

class _MyWalletPageState extends State<MyWalletPage> {
  WalletResponse? _wallet;
  List<WalletTransactionModel> _transactions = [];
  bool _loading = true;
  bool _balanceVisible = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final wallet = await WalletService.getWallet();
    final transactions = await WalletService.getTransactions();
    if (mounted) {
      setState(() {
        _wallet = wallet;
        _transactions = transactions;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isRtl = AppLocale.isRtl;
    return Directionality(
      textDirection: AppLocale.textDirection,
      child: Scaffold(
      backgroundColor: Colors.grey.shade100,
      appBar: AppBar(
        backgroundColor: AppColors.darkBlue,
        elevation: 0,
        leading: IconButton(
          onPressed: () => context.pop(),
          icon: Icon(
            isRtl ? Icons.arrow_forward_ios : Icons.arrow_back_ios,
            color: Colors.white,
            size: 20.sp,
          ),
        ),
        title: Text(
          AppLocale.tr('wallet'),
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white,
            fontSize: 18.sp,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Column(
                  children: [
                    _buildBalanceCard(),
                    _buildRechargeButton(),
                    Padding(
                      padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 16.h),
                      child: Column(
                        crossAxisAlignment:
                            AppLocale.isRtl ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppLocale.tr('recent_transactions'),
                            style: TextStyle(
                              color: Colors.grey.shade700,
                              fontWeight: FontWeight.bold,
                              fontSize: 14.sp,
                            ),
                          ),
                          SizedBox(height: 10.h),
                          if (_transactions.isEmpty)
                            Padding(
                              padding: EdgeInsets.symmetric(vertical: 24.h),
                              child: Center(
                                child: Directionality(
                                  textDirection: AppLocale.textDirection,
                                  child: Text(
                                    AppLocale.tr('no_transactions'),
                                    textAlign: AppLocale.isRtl
                                        ? TextAlign.right
                                        : TextAlign.left,
                                    style: TextStyle(
                                      color: Colors.grey.shade600,
                                      fontSize: 14.sp,
                                    ),
                                  ),
                                ),
                              ),
                            )
                          else
                            ListView.separated(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: _transactions.length,
                              separatorBuilder: (_, __) => Divider(thickness: 1.5, height: 1.h),
                              itemBuilder: (context, index) {
                                final t = _transactions[index];
                                return _TransactionRow(transaction: t);
                              },
                            ),
                          SizedBox(height: 30.h),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
    ),
    );
  }

  Widget _buildBalanceCard() {
    final balances = _wallet?.balances ?? [];
    final isRtl = AppLocale.isRtl;
    return Stack(
      children: [
        Container(
          width: double.infinity,
          padding: EdgeInsets.fromLTRB(20.w, 16.h, 20.w, 20.h),
          color: AppColors.darkBlue,
          child: Container(
            padding: EdgeInsets.all(20.w),
            decoration: BoxDecoration(
              color: AppColors.darkBlue,
              border: Border.all(color: Colors.white.withOpacity(0.6)),
              borderRadius: BorderRadius.circular(20.r),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.15),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment:
                  isRtl ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Row(
                  children: isRtl
                      ? [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  AppLocale.tr('wallet'),
                                  textAlign: TextAlign.right,
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 20.sp,
                                  ),
                                ),
                                SizedBox(height: 4.h),
                                Text(
                                  AppLocale.tr('available_balance'),
                                  textAlign: TextAlign.right,
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 14.sp,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          SizedBox(width: 16.w),
                          Image.asset(
                            'assets/images/wallet.png',
                            height: 40.h,
                            fit: BoxFit.contain,
                          ),
                        ]
                      : [
                          Image.asset(
                            'assets/images/wallet.png',
                            height: 40.h,
                            fit: BoxFit.contain,
                          ),
                          SizedBox(width: 16.w),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  AppLocale.tr('wallet'),
                                  textAlign: TextAlign.left,
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 20.sp,
                                  ),
                                ),
                                SizedBox(height: 4.h),
                                Text(
                                  AppLocale.tr('available_balance'),
                                  textAlign: TextAlign.left,
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 14.sp,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                ),
                SizedBox(height: 16.h),
                if (balances.isEmpty)
                  _buildBalanceRow(
                    visible: _balanceVisible,
                    amount: 0,
                    currency: 'SYP',
                    onToggle: () => setState(() => _balanceVisible = !_balanceVisible),
                  )
                else
                  ...balances.asMap().entries.map((entry) {
                    final b = entry.value;
                    return Padding(
                      padding: EdgeInsets.only(bottom: 8.h),
                      child: _buildBalanceRow(
                        visible: _balanceVisible,
                        amount: b.amount,
                        currency: b.currency,
                        onToggle: () => setState(() => _balanceVisible = !_balanceVisible),
                      ),
                    );
                  }),
              ],
            ),
          ),
        ),
      ],
    );
  }

  /// زر عائم لإعادة الشحن تحت الكرت، كما في التصميم
  Widget _buildRechargeButton() {
    final isRtl = AppLocale.isRtl;
    return Padding(
      padding: EdgeInsets.only(top: 8.h),
      child: Align(
        alignment: isRtl ? Alignment.centerRight : Alignment.centerLeft,
        child: Padding(
          padding: EdgeInsets.only(right: isRtl ? 24.w : 0, left: isRtl ? 0 : 24.w),
          child: GestureDetector(
            onTap: () async {
              await context.push(const HewalaPage());
              _load();
            },
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment:
                  isRtl ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Container(
                  width: 56.w,
                  height: 56.w,
                  decoration: BoxDecoration(
                    color: AppColors.darkBlue,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.18),
                        blurRadius: 8,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Center(
                    child: Image.asset(
                      'assets/images/wallet.png',
                      width: 28.w,
                      height: 28.w,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                SizedBox(height: 4.h),
                Text(
                  AppLocale.tr('charge_balance'),
                  textAlign: isRtl ? TextAlign.right : TextAlign.left,
                  style: TextStyle(
                    color: Colors.grey.shade800,
                    fontSize: 12.sp,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// شريط الرصيد الأصفر: أيقونة إخفاء/إظهار + نوع العملة + المبلغ (مطابق للصورة)
  Widget _buildBalanceRow({
    required bool visible,
    required num amount,
    required String currency,
    required VoidCallback onToggle,
  }) {
    final symbol = CurrencyHelper.symbol(currency);
    final amountStr = CurrencyHelper.formatNumber(amount);
    final isRtl = AppLocale.isRtl;
    final displayText = visible ? '$symbol $amountStr' : '••••••';
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 12.h),
      width: double.infinity,
      decoration: BoxDecoration(
        color: AppColors.yellow,
        borderRadius: BorderRadius.circular(8.r),
      ),
      child: Row(
        mainAxisAlignment:
            isRtl ? MainAxisAlignment.end : MainAxisAlignment.start,
        children: isRtl
            ? [
                Expanded(
                  child: Text(
                    displayText,
                    textAlign: TextAlign.right,
                    style: TextStyle(
                      fontSize: 20.sp,
                      color: Colors.black87,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                SizedBox(width: 12.w),
                GestureDetector(
                  onTap: onToggle,
                  child: Icon(
                    visible ? Icons.visibility_off : Icons.visibility,
                    color: Colors.black87,
                    size: 24.sp,
                  ),
                ),
              ]
            : [
                GestureDetector(
                  onTap: onToggle,
                  child: Icon(
                    visible ? Icons.visibility_off : Icons.visibility,
                    color: Colors.black87,
                    size: 24.sp,
                  ),
                ),
                SizedBox(width: 12.w),
                Expanded(
                  child: Text(
                    displayText,
                    textAlign: TextAlign.left,
                    style: TextStyle(
                      fontSize: 20.sp,
                      color: Colors.black87,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
      ),
    );
  }
}

class _TransactionRow extends StatelessWidget {
  final WalletTransactionModel transaction;

  const _TransactionRow({required this.transaction});

  @override
  Widget build(BuildContext context) {
    final isRtl = AppLocale.isRtl;
    final isCredit = transaction.isCredit;
    final dateStr = _formatDate(transaction.createdAt);
    final desc = transaction.description ?? (isCredit ? AppLocale.tr('transfer_balance_hevala') : AppLocale.tr('package_purchase'));

    final amountText = '${isCredit ? '+' : '-'} ${CurrencyHelper.symbol(transaction.currency)} ${CurrencyHelper.formatNumber(transaction.amount.abs())}';
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 12.h),
      child: Row(
        children: [
          CircleAvatar(
            radius: 22.r,
            backgroundColor: AppColors.darkBlue,
            child: Icon(
              Icons.swap_horiz,
              color: Colors.white,
              size: 22.sp,
            ),
          ),
          SizedBox(width: 12.w),
          Expanded(
            child: Column(
              crossAxisAlignment:
                  isRtl ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Text(
                  desc,
                  textAlign: isRtl ? TextAlign.right : TextAlign.left,
                  style: TextStyle(
                    height: 1.4,
                    fontSize: 14.sp,
                    color: Colors.grey.shade800,
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                SizedBox(height: 2.h),
                Text(
                  dateStr,
                  textAlign: isRtl ? TextAlign.right : TextAlign.left,
                  style: TextStyle(
                    fontSize: 12.sp,
                    height: 1.3,
                    color: Colors.grey.shade600,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          SizedBox(width: 8.w),
          Text(
            amountText,
            textAlign: isRtl ? TextAlign.left : TextAlign.right,
            style: TextStyle(
              fontSize: 15.sp,
              color: isCredit ? Colors.green : Colors.red,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(String iso) {
    if (iso.isEmpty) return '';
    try {
      final dt = DateTime.tryParse(iso);
      if (dt != null) {
        return DateFormat('dd/MM/yyyy - HH:mm').format(dt);
      }
    } catch (_) {}
    return iso;
  }
}
