#!/bin/bash
# بناء APK - شغّل من مجلد flutter
# المرة الأولى قد تستغرق 15-20 دقيقة بسبب تحميل NDK

set -e
cd "$(dirname "$0")"

export ANDROID_HOME="${ANDROID_HOME:-$HOME/Library/Android/sdk}"
echo "ANDROID_HOME=$ANDROID_HOME"

# إزالة quarantine إن لزم (للنسخ من قرص خارجي)
xattr -cr . 2>/dev/null || true

echo "جاري البناء... (قد يستغرق عدة دقائق)"
set +e
flutter build apk --release
FLUTTER_EXIT=$?
set -e

GRADLE_APK="android/app/build/outputs/flutter-apk/app-release.apk"
OUT_DIR="build/app/outputs/flutter-apk"
OUT_APK="$OUT_DIR/app-release.apk"

if [ ! -f "$OUT_APK" ] && [ -f "$GRADLE_APK" ]; then
    echo "نسخ الـ APK من مخرجات Gradle (مسار Flutter المتوقع غير مُنشأ)..."
    mkdir -p "$OUT_DIR"
    cp "$GRADLE_APK" "$OUT_APK"
    FLUTTER_EXIT=0
fi

if [ "$FLUTTER_EXIT" -ne 0 ]; then
    echo "فشل البناء (رمز الخروج: $FLUTTER_EXIT)"
    exit "$FLUTTER_EXIT"
fi

echo ""
echo "✓ تم البناء بنجاح!"
echo "الملف: $(pwd)/$OUT_APK"
