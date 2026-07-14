import 'package:a3lnha/core/cache/app_image_cache.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/user_model.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/blocked_user_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/seller_profile_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class BlockedUsersPage extends StatefulWidget {
  const BlockedUsersPage({super.key});

  @override
  State<BlockedUsersPage> createState() => _BlockedUsersPageState();
}

class _BlockedUsersPageState extends State<BlockedUsersPage> {
  List<UserModel> _users = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final list = await BlockedUserService.getBlockedUsers();
    if (mounted) {
      setState(() {
        _users = list;
        _loading = false;
      });
    }
  }

  Future<void> _unblock(UserModel user) async {
    final ok = await BlockedUserService.unblockUser(user.id);
    if (mounted) {
      showToast(message: ok['message'] as String? ?? '');
      if (ok['success'] == true) _load();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('blocked_users')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _users.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.block, size: 64.sp, color: Colors.grey),
                      SizedBox(height: 16.h),
                      Text(
                        AppLocale.tr('no_blocked_users'),
                        style: TextStyle(
                          fontSize: 16.sp,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                    itemCount: _users.length,
                    itemBuilder: (context, index) {
                      final user = _users[index];
                      return _BlockedUserCard(
                        user: user,
                        onUnblock: () => _unblock(user),
                        onTap: () {
                          if (user.slug != null) {
                            context.push(
                              SellerProfilePage(sellerSlug: user.slug!),
                            );
                          }
                        },
                      );
                    },
                  ),
                ),
    );
  }
}

class _BlockedUserCard extends StatelessWidget {
  final UserModel user;
  final VoidCallback onUnblock;
  final VoidCallback onTap;

  const _BlockedUserCard({
    required this.user,
    required this.onUnblock,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.only(bottom: 12.h),
      child: ListTile(
        onTap: onTap,
        leading: CircleAvatar(
          radius: 24.r,
          backgroundColor: AppColors.darkBlue.withValues(alpha: 0.2),
          backgroundImage:
              user.avatar != null && user.avatar!.startsWith('http')
                  ? CachedNetworkImageProvider(
                      user.avatar!,
                      cacheManager: AppImageCache.instance,
                    )
                  : null,
          child: user.avatar == null || !user.avatar!.startsWith('http')
              ? Text(
                  (user.name.isNotEmpty ? user.name[0] : '?').toUpperCase(),
                  style: TextStyle(
                    color: AppColors.darkBlue,
                    fontWeight: FontWeight.bold,
                  ),
                )
              : null,
        ),
        title: Text(user.name, style: TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(user.email),
        trailing: TextButton(
          onPressed: onUnblock,
          child: Text(AppLocale.tr('unblock_user'), style: TextStyle(color: Colors.green)),
        ),
      ),
    );
  }
}
