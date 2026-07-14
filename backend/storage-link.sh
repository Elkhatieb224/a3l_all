#!/bin/bash
# إزالة public/storage (مجلد أو رابط) ثم إنشاء الرابط الرمزي الصحيح

cd "$(dirname "$0")"

if [ -d "public/storage" ] && [ ! -L "public/storage" ]; then
  echo "جاري نقل محتويات public/storage إلى public_storage (إن وُجدت)..."
  mkdir -p public_storage
  cp -an public/storage/. public_storage/ 2>/dev/null || true
  echo "جاري إزالة المجلد public/storage..."
  rm -rf public/storage
fi

if [ -L "public/storage" ]; then
  rm -f public/storage
fi

php artisan storage:link
echo "تم. جرّب فتح رابط صورة إعلان."
