# دليل استخدام cmdline-tools لبناء APK

## المتطلبات الأساسية

1. **Java JDK 17** (مطلوب لـ sdkmanager و Flutter)
   ```bash
   brew install openjdk@17
   ```
   ثم أضف للسطر في `~/.zshrc`:
   ```bash
   export PATH="/opt/homebrew/opt/openjdk@17/bin:$PATH"
   ```

2. **Flutter** (مثبت بالفعل)

---

## خطوات الإعداد

### 1. تثبيت Java (مطلوب)

```bash
brew install openjdk@17
echo 'export PATH="/opt/homebrew/opt/openjdk@17/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

### 2. إنشاء مجلد SDK وتثبيت المكونات

```bash
cd /Volumes/info/a3l_all

# إنشاء مجلد SDK
mkdir -p android-sdk

# تثبيت المكونات (استخدام cmdline-tools الموجود)
# قبول التراخيص (اضغط y ثم Enter لكل ترخيص):
./cmdline-tools/bin/sdkmanager --sdk_root="$(pwd)/android-sdk" --licenses

# تثبيت المكونات:
./cmdline-tools/bin/sdkmanager --sdk_root="$(pwd)/android-sdk" \
  "platform-tools" \
  "platforms;android-34" \
  "build-tools;34.0.0"
```

### 3. ربط Flutter مع Android SDK

```bash
flutter config --android-sdk /Volumes/info/a3l_all/android-sdk
flutter doctor -v
```
يُفترض أن تظهر ✓ أمام Android toolchain.

### 4. بناء APK

```bash
cd /Volumes/info/a3l_all/flutter
flutter build apk --release
```

الملف الناتج: `build/app/outputs/flutter-apk/aalenha_test.apk`

---

## سكربت سريع (بعد تثبيت Java)

```bash
cd /Volumes/info/a3l_all
chmod +x setup_and_build.sh
./setup_and_build.sh
```

---

## ملاحظة

- إذا كان `cmdline-tools` قد نُقل مسبقاً، استخدم المسار الفعلي في السكربت.
- أضف `ANDROID_HOME` في `~/.zshrc` للاستخدام الدائم:
  ```bash
  export ANDROID_HOME=/Volumes/info/a3l_all/android-sdk
  export PATH="$ANDROID_HOME/platform-tools:$PATH"
  ```
