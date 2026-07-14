#!/bin/bash
# إعداد Android SDK وبناء APK باستخدام cmdline-tools
# التشغيل: ./setup_and_build.sh

set -e
cd /Volumes/info/a3l_all

echo "=== التحقق من Java ==="
if ! java -version 2>/dev/null; then
  echo "خطأ: يجب تثبيت Java أولاً:"
  echo "  brew install openjdk@17"
  echo "  export PATH=\"/opt/homebrew/opt/openjdk@17/bin:\$PATH\""
  exit 1
fi

echo ""
echo "=== إعداد Android SDK ==="
mkdir -p android-sdk
ANDROID_HOME="$(pwd)/android-sdk"
SDKMANAGER="./cmdline-tools/bin/sdkmanager"

if [ ! -f "$SDKMANAGER" ]; then
  echo "خطأ: لم يتم العثور على cmdline-tools في $(pwd)/cmdline-tools"
  exit 1
fi

echo "تثبيت platform-tools و platforms و build-tools..."
yes | $SDKMANAGER --sdk_root="$ANDROID_HOME" --licenses 2>/dev/null || true
$SDKMANAGER --sdk_root="$ANDROID_HOME" \
  "platform-tools" \
  "platforms;android-34" \
  "build-tools;34.0.0"

echo ""
echo "=== ربط Flutter ==="
flutter config --android-sdk "$ANDROID_HOME"

echo ""
echo "=== بناء APK ==="
cd flutter
flutter build apk --release

echo ""
echo "✓ تم البناء بنجاح!"
echo "الملف: $(pwd)/build/app/outputs/flutter-apk/aalenha_test.apk"
