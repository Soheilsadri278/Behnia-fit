#!/usr/bin/env python3
"""
سرور محلی پیش‌نمایش برای سایت بهنیا فیت.

چرا از `python3 -m http.server` ساده بهتر است؟
  ۱. لیست‌شدن پوشه‌ها غیرفعال است (ساختار فایل‌ها لو نمی‌رود).
  ۲. با HTTP/1.1 و keep-alive کار می‌کند؛ بارگذاری صفحه سریع‌تر است.
  ۳. برای فایل‌های استاتیک هدر کش می‌دهد و برای HTML همیشه نسخهٔ تازه می‌دهد.
  ۴. آدرس‌های بدون پسوند (/impressum) را مثل هاست واقعی به .html نگاشت می‌کند.
  ۵. پورت را از آرگومان یا متغیر محیطی PORT می‌گیرد (پیش‌فرض ۸۰۰۰).

اجرا:
    python3 server.py            # روی 0.0.0.0:8000
    PORT=3000 python3 server.py  # روی پورت دیگر

این فایل فقط برای توسعه است و در سایت نهایی لازم نیست.
"""

import os
import sys
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer

ROOT = os.path.dirname(os.path.abspath(__file__))
DEFAULT_PORT = int(os.environ.get("PORT", "8000"))
HOST = "0.0.0.0"

# تصویر و فونت: یک روز کش. CSS/JS پایین‌تر استثنا شده‌اند.
CACHEABLE = {
    ".css", ".js", ".woff2", ".woff", ".jpg", ".jpeg", ".png",
    ".webp", ".avif", ".svg", ".ico",
}
ONE_DAY = 60 * 60 * 24


class SiteHandler(SimpleHTTPRequestHandler):
    # HTTP/1.1 برای keep-alive؛ به همین دلیل باید Content-Length همیشه ست شود
    protocol_version = "HTTP/1.1"
    server_version = "BehniaFitDev/1.0"

    def end_headers(self):
        path = self.path.split("?")[0].split("#")[0].lower()
        ext = os.path.splitext(path)[1]

        # CSS و JS هرگز کش نشوند: اشتباهی که یک‌بار صفحه را بی‌استایل کرد
        if ext in (".css", ".js"):
            self.send_header("Cache-Control", "no-store, must-revalidate")
        elif ext in CACHEABLE:
            self.send_header("Cache-Control", "public, max-age=%d" % ONE_DAY)
        else:
            # HTML و بقیه: همیشه تازه بماند تا تغییرات بلافاصله دیده شود
            self.send_header("Cache-Control", "no-cache")

        self.send_header("X-Content-Type-Options", "nosniff")
        self.send_header("Referrer-Policy", "strict-origin-when-cross-origin")
        super().end_headers()

    def list_directory(self, path):
        """بدون لیست پوشه: 404 برگردان."""
        self.send_error(404, "Not Found")
        return None

    def translate_path(self, path):
        """نگاشت آدرس‌های تمیز: /impressum → impressum.html"""
        local = super().translate_path(path)
        if not os.path.exists(local) and not os.path.splitext(local)[1]:
            candidate = local.rstrip("/") + ".html"
            if os.path.isfile(candidate):
                return candidate
        return local

    def log_message(self, fmt, *args):
        sys.stderr.write("%s - %s\n" % (self.address_string(), fmt % args))


def main():
    port = DEFAULT_PORT
    if len(sys.argv) > 1:
        try:
            port = int(sys.argv[1])
        except ValueError:
            print("پورت نامعتبر است: %r" % sys.argv[1])
            return 2

    handler = partial(SiteHandler, directory=ROOT)
    try:
        httpd = ThreadingHTTPServer((HOST, port), handler)
    except OSError as exc:
        print("پورت %d آزاد نیست: %s" % (port, exc))
        print("یا سرور قبلی را ببندید، یا پورت دیگری بدهید: python3 server.py 8001")
        return 1

    print("Behnia Fit — پیش‌نمایش روی http://%s:%d  (ریشه: %s)" % (HOST, port, ROOT))
    print("برای توقف: Ctrl+C")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nسرور متوقف شد.")
    finally:
        httpd.server_close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
