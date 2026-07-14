import 'package:app_settings/app_settings.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/utils/platform_utils.dart';
import 'package:a3lnha/core/notifications/fcm_service.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/storage/preferences_storage.dart';
import 'package:a3lnha/helpers/cache_helper.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';


class PermissionsPage extends StatefulWidget {
  const PermissionsPage({super.key});

  @override
  State<PermissionsPage> createState() => _PermissionsPageState();
}

class _PermissionsPageState extends State<PermissionsPage>
    with WidgetsBindingObserver {
  bool _notificationEnabled = false;
  bool _readReceiptEnabled = false;
  bool _loading = true;
  bool _notificationToggling = false;
  bool _readReceiptToggling = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadStatus();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _loadStatus();
    }
  }

  Future<void> _loadStatus() async {
    setState(() => _loading = true);
    bool notificationOn = false;
    bool readReceiptOn = false;
    if (isMobilePlatform) {
      notificationOn = await FcmService.isNotificationPermissionGranted();
    }
    final stored = CacheHelper.getData(key: PreferencesStorage.readReceiptStorageKey);
    readReceiptOn = stored == true;
    if (mounted) {
      setState(() {
        _notificationEnabled = notificationOn;
        _readReceiptEnabled = readReceiptOn;
        _loading = false;
      });
    }
  }

  Future<void> _onNotificationChanged(bool value) async {
    if (_notificationToggling) return;
    setState(() => _notificationToggling = true);
    if (value) {
      final granted = await FcmService.requestNotificationPermission();
      if (mounted) {
        setState(() {
          _notificationEnabled = granted;
          _notificationToggling = false;
        });
        showToast(
            message: granted
                ? AppLocale.tr('notification_permission_granted')
                : AppLocale.tr('notification_permission_denied'));
      }
    } else {
      await AppSettings.openAppSettings();
      if (mounted) {
        setState(() => _notificationToggling = false);
        showToast(message: AppLocale.tr('open_settings_to_disable_notifications'));
      }
    }
  }

  Future<void> _onReadReceiptChanged(bool value) async {
    if (_readReceiptToggling) return;
    setState(() => _readReceiptToggling = true);
    await CacheHelper.putBoolean(key: PreferencesStorage.readReceiptStorageKey, value: value);
    if (mounted) {
      setState(() {
        _readReceiptEnabled = value;
        _readReceiptToggling = false;
      });
      showToast(
          message: value
              ? AppLocale.tr('read_receipt_enabled')
              : AppLocale.tr('read_receipt_disabled'));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('permissions')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: EdgeInsets.all(20.w),
              child: Column(
                children: [
                  PermissionsWidget(
                    title: AppLocale.tr('mobile_notification_permission'),
                    subTitle: AppLocale.tr('notification_permission_subtitle'),
                    value: _notificationEnabled,
                    onChanged: _onNotificationChanged,
                    enabled: !_notificationToggling,
                  ),
                  Divider(thickness: 2, height: 40.h, color: Colors.grey[300]),
                  PermissionsWidget(
                    title: AppLocale.tr('read_message_info'),
                    subTitle: AppLocale.tr('read_message_info_full'),
                    value: _readReceiptEnabled,
                    onChanged: _onReadReceiptChanged,
                    enabled: !_readReceiptToggling,
                  ),
                ],
              ),
            ),
    );
  }
}

class PermissionsWidget extends StatelessWidget {
  final String title;
  final String subTitle;
  final bool value;
  final Future<void> Function(bool) onChanged;
  final bool enabled;

  const PermissionsWidget({
    super.key,
    required this.title,
    required this.subTitle,
    required this.value,
    required this.onChanged,
    this.enabled = true,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 16.sp,
                  fontWeight: FontWeight.w400,
                ),
              ),
              SizedBox(height: 5.h),
              Text(
                subTitle,
                style: TextStyle(
                  height: 1.5,
                  fontSize: 10.sp,
                  fontWeight: FontWeight.w500,
                  color: Colors.grey[500],
                ),
              ),
            ],
          ),
        ),
        Switch(
          activeTrackColor: AppColors.darkBlue,
          inactiveTrackColor: Colors.grey,
          value: value,
          onChanged: enabled
              ? (v) {
                  onChanged(v);
                }
              : null,
        ),
      ],
    );
  }
}
