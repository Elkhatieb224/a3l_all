import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/helpers/cache_helper.dart';

/// تهيئة التطبيق عند البدء
class AppInit {
  AppInit._();

  /// استدعاء مرة واحدة في main()
  static Future<void> init() async {
    await CacheHelper.init();
    ApiClient.init();
  }
}
