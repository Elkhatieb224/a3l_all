/// ثوابت API - API Constants
/// غيّر [baseUrl] حسب البيئة (تطوير / إنتاج)
class ApiConstants {
  ApiConstants._();

  /// رابط الـ API الأساسي - Base API URL
  /// للنشر: https://aalenha.com/api/v1 (القيمة الافتراضية)
  /// للتطوير: flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://aalenha.com/api/v1',
  );

  /// أصل النطاق (لطلبات مسارات الويب مثل بحث الفئات عند 401 من الـ API)
  static String get webOrigin => Uri.parse(baseUrl).origin;

  /// روابط الـ Endpoints
  static const String login = '/login';
  static const String register = '/register';
  static const String logout = '/logout';
  static const String passwordForgot = '/password/forgot';
  static const String passwordReset = '/password/reset';
  static const String me = '/me';
  static const String user = '/user';
  static const String categories = '/categories';
  static const String subcategories = '/subcategories';
  static const String ads = '/ads';
  /// GET /regions/{SY|TR}
  static const String regions = '/regions';

  /// GET /geo-tree/{SY|TR} — شجرة كاملة بثلاث لغات (محافظة → مدينة → حي)
  static String geoTree(String country) =>
      '/geo-tree/${country.toUpperCase()}';

  /// GET /states?country=TR|SY — محافظات مع id (تركيا: id = رقم اللوحة مثل 34)
  static const String geoStates = '/states';

  /// GET /districts/{parentId} — مقاطعات تحت محافظة
  static String geoDistricts(int parentId) => '/districts/$parentId';

  /// GET /neighborhoods/{parentId} — أحياء تحت مقاطعة
  static String geoNeighborhoods(int parentId) => '/neighborhoods/$parentId';

  /// GET /syria-geojson/manifest — روابط ملفات GeoJSON (حدود إدارية) من Syria-GeoJson-Maps
  static const String syriaGeoJsonManifest = '/syria-geojson/manifest';

  /// POST /regions/discover-map — حل موقع الخريطة أو إنشاء مدينة/محافظة ديناميكية
  static const String regionsDiscoverMap = '/regions/discover-map';

  /// GET /reverse-geocode — وكيل Nominatim (للويب بدون CORS)
  static const String reverseGeocode = '/reverse-geocode';

  /// GET /geo-coords?country=SY&state_code=...&city_code=...&district_code=...
  /// إحداثيات من جدول geo_divisions حسب الأكواد المختارة (بدون Google).
  static const String geoCoords = '/geo-coords';
  static const String sellers = '/sellers';
  static const String favorites = '/favorites';
  static const String favoriteSellers = '/favorite-sellers';
  static const String messages = '/messages';
  static const String negotiations = '/negotiations';
  static const String reports = '/reports';
  static const String packages = '/packages';
  static const String packageRequests = '/package-requests';
  static const String help = '/help';
  static const String blockedUsers = '/blocked-users';
  static const String verification = '/verification';
  static const String appInfo = '/app-info';
  static const String legalPrivacy = '/legal/privacy';
  static const String legalTerms = '/legal/terms';
  static const String notifications = '/notifications';
  static const String savedSearches = '/saved-searches';
  static const String fcmToken = '/fcm-token';
  static const String wallet = '/wallet';
  static const String walletTransactions = '/wallet/transactions';
  static const String hawalaTransfers = '/hawala-transfers';

  /// مهلة الطلب (بالثواني) - استقبال أبطأ من الخادم أو شبكة ضعيفة تحتاج مهلة أطول
  static const int connectTimeout = 60;
  static const int receiveTimeout = 90;
}
