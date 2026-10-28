#!/usr/bin/env bash
# ساخت دارایی‌های لوگو از فایل اصلی.
# منبع: assets/img/logo-source.png  (لوگوی ۶۴۰×۶۴۰ روی پس‌زمینهٔ سفید)
# خروجی: نسخه‌های knockout با پس‌زمینهٔ شفاف، آماده برای هر رنگ پس‌زمینه.
#
# روش: کانال آلفا از «تیرگی» ساخته می‌شود (alpha = 255 - luminance)، بعد رنگ
# یکدست روی آن می‌نشیند. آستانهٔ ۷٪ تضمین می‌کند پس‌زمینهٔ سفیدِ اسکن کاملاً
# شفاف شود (بدون مستطیل محو) و لبه‌های نرم و ضدپله دست‌نخورده بمانند.

set -euo pipefail
cd "$(dirname "$0")/.."

SRC="assets/img/logo-source.png"
OUT="assets/img"
NAVY="#14234B"
CREAM="#f7f4ee"

# محدوده‌های اندازه‌گیری‌شده در فایل اصلی (۶۴۰×۶۴۰)
MARK="395x228+146+147"      # مونوگرام B + دونده
LOCKUP="522x339+62+147"     # مونوگرام + وردمارک + تگ‌لاین

knockout () { # $1=crop  $2=color  $3=out
  convert "$SRC" -crop "$1" +repage \
    \( +clone -colorspace Gray -negate -level 7%,88% \) \
    -alpha off -compose CopyOpacity -composite \
    -fill "$2" -colorize 100% \
    -strip "$3"
}

# مونوگرام (برای هدر، آواتار، فاوآیکون)
knockout "$MARK"   "$NAVY"  "$OUT/logo-mark.png"
knockout "$MARK"   "$CREAM" "$OUT/logo-mark-light.png"

# لاک‌آپ کامل (برای فوتر و کارت ویزیت دیجیتال)
knockout "$LOCKUP" "$NAVY"  "$OUT/logo-full.png"
knockout "$LOCKUP" "$CREAM" "$OUT/logo-full-light.png"

# فاوآیکون: مونوگرام کرم روی مربع سرمه‌ای گرد
for size in 64 180 512; do
  convert -size "${size}x${size}" xc:none \
    -fill "$NAVY" -draw "roundrectangle 0,0,$((size-1)),$((size-1)),$((size*22/100)),$((size*22/100))" \
    \( "$OUT/logo-mark-light.png" -resize $((size*66/100))x$((size*66/100)) \) \
    -gravity center -compose over -composite \
    -strip "$OUT/favicon-$size.png"
done

mv "$OUT/favicon-64.png"  "$OUT/favicon.png"
mv "$OUT/favicon-180.png" "$OUT/apple-touch-icon.png"
mv "$OUT/favicon-512.png" "$OUT/icon-512.png"

echo "ساخته شد:"
ls -1sh "$OUT"/logo-*.png "$OUT"/favicon.png "$OUT"/apple-touch-icon.png "$OUT"/icon-512.png
