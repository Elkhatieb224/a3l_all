import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/services/negotiation_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/chat_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class MyProductsDealsPage extends StatefulWidget {
  final int initialTabIndex;

  const MyProductsDealsPage({super.key, this.initialTabIndex = 0});

  @override
  State<MyProductsDealsPage> createState() => _MyProductsDealsPageState();
}

class _MyProductsDealsPageState extends State<MyProductsDealsPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<NegotiationModel> _sent = [];
  List<NegotiationModel> _received = [];
  bool _loadingSent = true;
  bool _loadingReceived = true;

  @override
  void initState() {
    super.initState();
    final safeInitialIndex = widget.initialTabIndex.clamp(0, 1).toInt();
    _tabController = TabController(
      length: 2,
      vsync: this,
      initialIndex: safeInitialIndex,
    );
    _load();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loadingSent = true;
      _loadingReceived = true;
    });
    final sentList = await NegotiationService.getAllSent();
    final receivedList = await NegotiationService.getAllReceived();
    if (mounted) {
      setState(() {
        _sent = sentList;
        _received = receivedList;
        _loadingSent = false;
        _loadingReceived = false;
      });
    }
  }

  Future<void> _accept(NegotiationModel n) async {
    final ok = await NegotiationService.accept(n.id);
    if (mounted) {
      showToast(message: ok ? AppLocale.tr('accepted_success') : AppLocale.tr('failed'));
      if (ok) _load();
    }
  }

  Future<void> _reject(NegotiationModel n) async {
    final ok = await NegotiationService.reject(n.id);
    if (mounted) {
      showToast(message: ok ? AppLocale.tr('rejected_success') : AppLocale.tr('failed'));
      if (ok) _load();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('sent_received_offers')),
      body: Column(
        children: [
          Material(
            color: Colors.white,
            child: TabBar(
              controller: _tabController,
              labelColor: AppColors.darkBlue,
              unselectedLabelColor: Colors.grey,
              indicatorColor: AppColors.darkBlue,
              tabs: [
                Tab(text: AppLocale.tr('sent_offers')),
                Tab(text: AppLocale.tr('received_offers')),
              ],
            ),
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _NegotiationsList(
                  negotiations: _sent,
                  loading: _loadingSent,
                  isSent: true,
                  onRefresh: _load,
                  onAccept: _accept,
                  onReject: _reject,
                ),
                _NegotiationsList(
                  negotiations: _received,
                  loading: _loadingReceived,
                  isSent: false,
                  onRefresh: _load,
                  onAccept: _accept,
                  onReject: _reject,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _NegotiationsList extends StatelessWidget {
  final List<NegotiationModel> negotiations;
  final bool loading;
  final bool isSent;
  final VoidCallback onRefresh;
  final void Function(NegotiationModel) onAccept;
  final void Function(NegotiationModel) onReject;

  const _NegotiationsList({
    required this.negotiations,
    required this.loading,
    required this.isSent,
    required this.onRefresh,
    required this.onAccept,
    required this.onReject,
  });

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return Center(child: CircularProgressIndicator(color: AppColors.darkBlue));
    }
    if (negotiations.isEmpty) {
      return Center(
        child: Text(
          isSent ? AppLocale.tr('no_sent_offers') : AppLocale.tr('no_received_offers'),
          style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () async => onRefresh(),
      child: ListView.builder(
        padding: EdgeInsets.symmetric(horizontal: 15.w, vertical: 10.h),
        itemCount: negotiations.length,
        itemBuilder: (context, index) {
          final n = negotiations[index];
          return _NegotiationCard(
            negotiation: n,
            isSent: isSent,
            onAccept: onAccept,
            onReject: onReject,
          );
        },
      ),
    );
  }
}

class _NegotiationCard extends StatelessWidget {
  final NegotiationModel negotiation;
  final bool isSent;
  final void Function(NegotiationModel) onAccept;
  final void Function(NegotiationModel) onReject;

  const _NegotiationCard({
    required this.negotiation,
    required this.isSent,
    required this.onAccept,
    required this.onReject,
  });

  @override
  Widget build(BuildContext context) {
    final n = negotiation;
    final ad = n.ad;
    final adUid = ad?['uid'] as String?;
    final adTitle = ad?['title'] as String? ?? '—';
    final adImage = ad?['first_image'] as String? ??
        ad?['image'] as String? ??
        ((ad?['images'] is List && (ad!['images'] as List).isNotEmpty)
            ? (ad['images'] as List).first?.toString()
            : null);
    final adPrice = ad?['price'];
    final adCurrency = ad?['currency'] as String? ?? '';
    final otherName = isSent
        ? (n.seller?['name'] as String? ?? '—')
        : (n.buyer?['name'] as String? ?? '—');

    return Container(
      padding: EdgeInsets.all(12.w),
      margin: EdgeInsets.only(bottom: 12.h),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8.r),
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (adUid != null)
            GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: () => context.push(AdDetailsPage(adUid: adUid)),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8.r),
                    child: SizedBox(
                      width: 56.w,
                      height: 56.w,
                      child: AppNetworkImage(
                        imageUrl: adImage,
                        fit: BoxFit.cover,
                        placeholder: (_) => Container(
                          color: Colors.grey[200],
                          child: Icon(Icons.image, color: Colors.grey[500], size: 22.sp),
                        ),
                      ),
                    ),
                  ),
                  SizedBox(width: 12.w),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          adTitle,
                          style: TextStyle(
                            fontSize: 14.sp,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        SizedBox(height: 4.h),
                        Text(
                          '${AppLocale.tr('offer_label')}: ${CurrencyHelper.formatPrice(n.offeredPrice, n.currency)}',
                          style: TextStyle(
                            fontSize: 12.sp,
                            color: AppColors.darkBlue,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        if (!isSent && adPrice != null)
                          Padding(
                            padding: EdgeInsets.only(top: 2.h),
                            child: Text(
                              '${AppLocale.tr('original_price')}: ${CurrencyHelper.formatPrice(adPrice is num ? adPrice : num.tryParse(adPrice.toString()), adCurrency.isNotEmpty ? adCurrency : null)}',
                              style: TextStyle(
                                fontSize: 11.sp,
                                color: Colors.grey[600],
                              ),
                            ),
                          ),
                        if (n.message != null && n.message!.isNotEmpty)
                          Padding(
                            padding: EdgeInsets.only(top: 4.h),
                            child: Text(
                              n.message!,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(fontSize: 11.sp, color: Colors.grey[700]),
                            ),
                          ),
                        SizedBox(height: 4.h),
                        Text(
                          isSent ? '${AppLocale.tr('sent_to')}: $otherName' : '${AppLocale.tr('offer_from')}: $otherName',
                          style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
                        ),
                        if (n.createdAt != null)
                          Text(
                            n.createdAt!.substring(0, n.createdAt!.length > 10 ? 10 : n.createdAt!.length),
                            style: TextStyle(fontSize: 10.sp, color: Colors.grey[500]),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          SizedBox(height: 10.h),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _StatusChip(status: n.status),
              if (n.status == 'accepted' && n.conversationId != null)
                GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTap: () {
                    if (!TokenStorage.hasToken()) {
                      context.push(LoginPage());
                      return;
                    }
                    context.push(ChatPage(
                      conversationId: n.conversationId,
                      sellerName: otherName,
                    ));
                  },
                  child: Container(
                    padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
                    decoration: BoxDecoration(
                      color: AppColors.darkBlue,
                      borderRadius: BorderRadius.circular(6.r),
                    ),
                    child: Text(
                      AppLocale.tr('conversation_offer'),
                      style: TextStyle(color: Colors.white, fontSize: 11.sp),
                    ),
                  ),
                )
              else if (n.status == 'pending' && !isSent)
                Row(
                  children: [
                    GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () => onAccept(n),
                      child: Container(
                        padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(4.r),
                          color: HexColor("208F20"),
                        ),
                        child: Text(AppLocale.tr('accept'), style: TextStyle(color: Colors.white, fontSize: 10.sp)),
                      ),
                    ),
                    SizedBox(width: 8.w),
                    GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () => onReject(n),
                      child: Container(
                        padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(4.r),
                          color: HexColor("FF0000"),
                        ),
                        child: Text(AppLocale.tr('reject'), style: TextStyle(color: Colors.white, fontSize: 10.sp)),
                      ),
                    ),
                  ],
                )
              else if (n.status == 'rejected' && n.rejectionReason != null && n.rejectionReason!.isNotEmpty)
                Expanded(
                  child: Padding(
                    padding: EdgeInsets.only(right: 8.w),
                    child: Text(
                      '${AppLocale.tr('rejection_reason')}: ${n.rejectionReason}',
                      style: TextStyle(fontSize: 10.sp, color: Colors.red[700]),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String status;

  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    String label;
    Color color;
    if (status == 'pending') {
      label = AppLocale.tr('status_pending_label');
      color = Colors.orange;
    } else if (status == 'accepted') {
      label = AppLocale.tr('status_accepted');
      color = Colors.green;
    } else if (status == 'rejected') {
      label = AppLocale.tr('status_rejected_label');
      color = Colors.red;
    } else {
      label = status;
      color = Colors.grey;
    }
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 4.h),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(6.r),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 11.sp, color: color, fontWeight: FontWeight.w500),
      ),
    );
  }
}

