#!/bin/bash
# بناء APK باسم aalenha_test باستخدام https://aalenha.com/api/v1
# التشغيل: ./build_aalenha_test.sh
# أو: bash build_aalenha_test.sh

cd "$(dirname "$0")"

echo "بناء APK: aalenha_test.apk"
echo "Base URL: https://aalenha.com/api/v1"
echo ""

flutter build apk --release

if [ $? -eq 0 ]; then
  APK_PATH="build/app/outputs/flutter-apk/aalenha_test.apk"
  if [ -f "$APK_PATH" ]; then
    echo ""
    echo "✓ تم البناء بنجاح!"
    echo "المسار: $(pwd)/$APK_PATH"
  else
    APK_ALT="build/app/outputs/flutter-apk/app-release.apk"
    if [ -f "$APK_ALT" ]; then
      cp "$APK_ALT" "build/app/outputs/flutter-apk/aalenha_test.apk"
      echo ""
      echo "✓ تم البناء والنسخ باسم aalenha_test.apk"
    fi
  fi
else
  echo "فشل البناء. تأكد من:"
  echo "  1. تثبيت Flutter و Android SDK"
  echo "  2. تشغيل: flutter doctor"
fi
