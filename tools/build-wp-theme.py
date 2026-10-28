#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
سازندهٔ قالب وردپرس «بهنیا فیت» از فایل‌های ایستای سایت.

هدف: تضمین ۱۰۰٪ یکسان بودن ظاهر. به‌جای بازنویسی دستی HTML، ساختار موجود
مکانیکی به قالب تبدیل می‌شود تا هیچ تگ یا کلاسی جا نیفتد و هیچ چیز اضافه نشود.

اجرا:  python3 tools/build-wp-theme.py
"""
import io
import os
import re
import shutil
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, "wordpress", "wp-content", "themes", "behnia-fit")

ASSET = '<?php echo esc_url( BF_THEME_URI ); ?>'


def read(name):
    with io.open(os.path.join(ROOT, name), encoding="utf-8") as f:
        return f.read()


def write(rel, text):
    path = os.path.join(THEME, rel)
    parent = os.path.dirname(path)
    if parent:
        os.makedirs(parent, exist_ok=True)
    with io.open(path, "w", encoding="utf-8") as f:
        f.write(text)
    return path


def rewrite_assets(html):
    """assets/... → آدرس مطلق پوشهٔ قالب"""
    return re.sub(r'(["\'(])assets/', lambda m: m.group(1) + ASSET + "/assets/", html)


def copy_assets():
    for sub in ("css", "js", "img", "fonts"):
        src = os.path.join(ROOT, "assets", sub)
        dst = os.path.join(THEME, "assets", sub)
        if os.path.isdir(dst):
            shutil.rmtree(dst)
        shutil.copytree(src, dst)
    patch_main_js()


def patch_main_js():
    """تنها تغییر در دارایی‌ها — یک نگهبان کوچک در main.js.

    هایلایت لینک فعال منو، مقدار href هر لینک را بی‌واسطه به querySelector
    می‌دهد؛ در نسخهٔ ایستا همهٔ لینک‌ها #anchor هستند و مشکلی نیست، ولی در
    وردپرس لینک «حساب من» یک نشانی کامل است و این کار خطای SyntaxError
    می‌دهد (و بقیهٔ init را متوقف می‌کند). اینجا فقط لینک‌های #دار بررسی
    می‌شوند. فایل اصلی در ریشهٔ مخزن دست‌نخورده می‌ماند.
    """
    path = os.path.join(THEME, "assets", "js", "main.js")
    with open(path, encoding="utf-8") as f:
        js = f.read()

    original = (
        '      var id = link.getAttribute("href");\n'
        '      return id && id.length > 1 ? document.querySelector(id) : null;'
    )
    patched = (
        '      var id = link.getAttribute("href");\n'
        '      // فقط لنگرهای همین صفحه؛ نشانی کامل یک selector معتبر نیست.\n'
        '      if (!id || id.charAt(0) !== "#" || id.length < 2) return null;\n'
        '      return document.querySelector(id);'
    )
    if patched in js:
        return
    if original not in js:
        raise SystemExit("الگوی هایلایت منو در main.js پیدا نشد — patch_main_js را به‌روز کنید")
    with open(path, "w", encoding="utf-8") as f:
        f.write(js.replace(original, patched, 1))


def strip_asset_links(html):
    """CSS و JS از طریق enqueue بارگذاری می‌شوند، نه تگ دستی."""
    html = re.sub(r'[ \t]*<link rel="stylesheet" href="assets/css/style\.css\?v=\d+" */>\n?', "", html)
    html = re.sub(r'[ \t]*<script src="assets/js/main\.js\?v=\d+" *defer></script>\n?', "", html)
    return html



# --------------------------------------------------------------- متاتگ‌ها

FRONT_TITLE = "بهنیا فیت | مربی شخصی و کوچینگ آنلاین · کارشناس ارشد فیزیولوژی کاربردی"
FRONT_DESC = (
    "کارشناس ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون و قهرمان فیتنس با ۵ سال سابقهٔ حرفه‌ای. "
    "تمرین حضوری و کوچینگ آنلاین با برنامهٔ اختصاصی، اصلاح فرم و پیگیری هفتگی."
)
FRONT_OG_TITLE = "بهنیا فیت | مربی شخصی، کارشناس ارشد فیزیولوژی کاربردی"
FRONT_OG_DESC = (
    "قوی‌تر شو، بدون حدس و گمان. کارشناس ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون، "
    "قهرمان فیتنس و ۵ سال سابقهٔ حرفه‌ای. حضوری و آنلاین."
)


def localize_head(html):
    """عنوان و متاتگ‌ها از ثابت به تابع پویا تبدیل می‌شوند.

    مقادیر پیش‌فرض عیناً همان متن‌های نسخهٔ ایستا هستند تا صفحهٔ اصلی
    هیچ تفاوتی نداشته باشد؛ صفحه‌های دیگر عنوان خودشان را می‌گیرند.
    """
    html = re.sub(
        r"<title>.*?</title>",
        "<title><?php echo esc_html( bf_doc_title() ); ?></title>",
        html,
        count=1,
        flags=re.S,
    )
    html = re.sub(
        r'<meta name="description" content=".*?" />',
        '<meta name="description" content="<?php echo esc_attr( bf_doc_description() ); ?>" />',
        html,
        count=1,
        flags=re.S,
    )
    # صفحهٔ اصلی در نسخهٔ ایستا متای robots ندارد؛ صفحه‌های دیگر noindex,follow
    html = html.replace(
        '<link rel="canonical" href=',
        "<?php bf_robots_meta(); ?><link rel=\"canonical\" href=",
        1,
    )
    html = re.sub(
        r'<link rel="canonical" href="[^"]*" />',
        '<link rel="canonical" href="<?php echo esc_url( bf_doc_canonical() ); ?>" />',
        html,
        count=1,
    )
    html = re.sub(
        r'<meta property="og:title" content=".*?" />',
        '<meta property="og:title" content="<?php echo esc_attr( bf_doc_og_title() ); ?>" />',
        html,
        count=1,
    )
    html = re.sub(
        r'<meta property="og:description" content=".*?" />',
        '<meta property="og:description" content="<?php echo esc_attr( bf_doc_og_description() ); ?>" />',
        html,
        count=1,
    )
    html = re.sub(
        r'<meta property="og:url" content="[^"]*" />',
        '<meta property="og:url" content="<?php echo esc_url( bf_doc_canonical() ); ?>" />',
        html,
        count=1,
    )
    html = re.sub(
        r'<meta property="og:image" content="[^"]*" />',
        "<meta property=\"og:image\" content=\"<?php echo esc_url( BF_THEME_URI . '/assets/img/og-image.png' ); ?>\" />",
        html,
        count=1,
    )
    # دادهٔ ساختاریافته: نشانی باید دامنهٔ واقعی باشد
    html = html.replace(
        '"url": "https://soheilsadri278.github.io/Behnia-fit/",',
        '"url": "<?php echo esc_url( home_url( \'/\' ) ); ?>",',
    )
    return html


# ------------------------------------------------------------------ index

def build_from_index():
    lines = read("index.html").split("\n")

    def find(pred, start=0):
        for i in range(start, len(lines)):
            if pred(lines[i]):
                return i
        raise SystemExit("مرز در index.html پیدا نشد")

    i_head_end = find(lambda l: l.strip() == "</head>")
    i_body = find(lambda l: l.strip().startswith("<body"))
    i_header_end = find(lambda l: l.strip() == "</header>")
    i_main = find(lambda l: l.strip().startswith("<main"))
    i_main_end = find(lambda l: l.strip() == "</main>", i_main)
    i_footer = find(lambda l: l.strip().startswith("<footer"))
    i_script = find(lambda l: "assets/js/main.js" in l)

    head_html = rewrite_assets(strip_asset_links("\n".join(lines[:i_head_end])))
    # تا شروع <main>: این بازه منوی موبایل (#mobileMenu) را هم دربر می‌گیرد.
    # اگر به </header> ختم شود، دکمهٔ همبرگری چیزی برای باز کردن ندارد.
    inner_head = rewrite_assets("\n".join(lines[i_body + 1 : i_main]))
    main_html = rewrite_assets("\n".join(lines[i_main : i_main_end + 1]))
    tail_html = rewrite_assets(strip_asset_links("\n".join(lines[i_footer:i_script])))

    header_php = localize_head(
        head_html
        + "\n  <?php wp_head(); ?>\n</head>\n"
        + '<?php bf_body_tag(); ?><?php wp_body_open(); ?>\n\n'
        + ""
        + inner_head
        + "\n"
    )
    write("header.php", header_php)

    write(
        "front-page.php",
        "<?php\n/**\n * صفحهٔ اصلی — ساختار عیناً از index.html گرفته شده است.\n *\n * @package behnia-fit\n */\n\n"
        "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\nget_header();\n?>\n\n"
        + main_html
        + "\n\n<?php\nget_footer();\n",
    )

    write(
        "footer.php",
        "<?php\n/**\n * فوتر — ساختار عیناً از index.html گرفته شده است.\n *\n * @package behnia-fit\n */\n\n"
        "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n?>\n\n"
        + tail_html
        + "\n\n  <?php wp_footer(); ?>\n</body>\n</html>\n",
    )

    return len(main_html.split("\n"))


# ---------------------------------------------------------------- privacy

def build_privacy():
    src = read("privacy.html")
    m = re.search(r"<main[^>]*>(.*)</main>", src, re.S)
    inner = m.group(1).strip() if m else ""

    # دو بلوک استایل در privacy.html هست:
    #   ۱) نسخهٔ کوچک‌شدهٔ استایل حیاتی — در قالب، بلوک کاملش در header.php
    #      هست و تکرارش اینجا آبشار را برمی‌گرداند (body از ۱۶px به ۱۷px می‌رفت).
    #   ۲) قواعد مخصوص صفحهٔ حقوقی — در فایل اصلی دقیقاً بعد از style.css
    #      می‌آمد، پس تنها همین بلوک به inc/legal.css می‌رود.
    styles = re.findall(r"<style>(.*?)</style>", src, re.S)
    legal_css = "\n".join(s.strip() for s in styles[1:])
    write("inc/legal.css", legal_css)

    write("template-parts/content-privacy.html", inner)

    # فوتر سبک صفحه‌های حقوقی — در privacy.html فوتر کامل نیست و عمداً
    # فقط یک نوار باریک پایین دارد. بدون این، صفحه از نسخهٔ ایستا بلندتر می‌شد.
    fm = re.search(r"<footer.*?</footer>", src, re.S)
    footer = fm.group(0) if fm else ""
    footer = footer.replace(
        'href="privacy.html"',
        'href="<?php echo esc_url( bf_privacy_url() ); ?>"',
    ).replace(
        'href="index.html"',
        'href="<?php echo esc_url( home_url( \'/\' ) ); ?>"',
    )
    ym = re.search(r"<script>\s*\(function \(\) \{.*?</script>", src, re.S)
    year_script = ym.group(0) if ym else ""

    write(
        "footer-legal.php",
        "<?php\n/**\n * فوتر برگه‌های حقوقی — ساختار عیناً از privacy.html.\n *\n * @package behnia-fit\n */\n\n"
        "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n?>\n\n"
        + footer
        + "\n\n  "
        + year_script
        + "\n\n  <?php wp_footer(); ?>\n</body>\n</html>\n",
    )

    return styles, inner



# --------------------------------------------------------------------- زیپ

ZIP_PATH = os.path.join(ROOT, "wordpress", "behnia-fit-theme.zip")


def build_zip():
    """زیپ آمادهٔ نصب — پوشهٔ behnia-fit در ریشه، دقیقاً همان چیزی که
    «بارگذاری پوسته» وردپرس انتظار دارد.

    هر بار قالب بازسازی می‌شود این فایل هم از نو ساخته می‌شود تا هیچ‌وقت
    از سورس عقب نماند.
    """
    if os.path.exists(ZIP_PATH):
        os.remove(ZIP_PATH)

    with zipfile.ZipFile(ZIP_PATH, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for base, _dirs, files in os.walk(THEME):
            for name in sorted(files):
                full = os.path.join(base, name)
                rel = os.path.relpath(full, os.path.dirname(THEME))
                z.write(full, rel.replace(os.sep, "/"))

    return ZIP_PATH

# -------------------------------------------------------------------- 404

def build_page_php():
    """برگه‌های عادی — همان ساختار ‎<main class="legal">‎ که privacy.html دارد."""
    write(
        "page.php",
        "<?php\n/**\n * برگه‌های عادی (حریم خصوصی و مانند آن).\n *\n * @package behnia-fit\n */\n\n"
        "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\nget_header();\n?>\n\n"
        '<main class="legal">\n'
        "  <?php\n"
        "  while ( have_posts() ) :\n"
        "    the_post();\n"
        "    // محتوای اصلی صفحه‌های حقوقی به‌صورت فایل در قالب است، نه در دیتابیس:\n"
        "    // ذخیره در دیتابیس برای کاربرِ بدون دسترسی unfiltered_html باعث\n"
        "    // حذف تگ‌های svg توسط KSES و شکستن آیکون‌ها می‌شود.\n"
        "    // برای ویرایش متن، همین فایل را عوض کنید؛ اگر فایل نباشد،\n"
        "    // محتوای برگهٔ وردپرس نمایش داده می‌شود.\n"
        "    $bf_slug = get_post_field( 'post_name', get_the_ID() );\n"
        "    $bf_file = get_theme_file_path( 'template-parts/content-' . sanitize_file_name( $bf_slug ) . '.html' );\n"
        "    $bf_content = get_the_content();\n"
        "    if ( file_exists( $bf_file ) ) {\n"
        "        include $bf_file;\n"
        "    } elseif ( false === strpos( $bf_content, '<!-- wp:' ) ) {\n"
        "        // HTML خام؛ wpautop با افزودن <p> خالی چیدمان را جابه‌جا می‌کند.\n"
        "        echo $bf_content; // phpcs:ignore WordPress.Security.EscapeOutput\n"
        "    } else {\n"
        "        the_content();\n"
        "    }\n"
        "  endwhile;\n"
        "  ?>\n"
        "</main>\n\n"
        "<?php\nget_footer( 'legal' );\n",
    )


def localize_404(src):
    """مسیرهای مطلق /Behnia-fit/ (ویژهٔ GitHub Pages) به آدرس قالب و خانه
    تبدیل می‌شوند تا ۴۰۴ روی هر دامنه و هر زیرمسیری درست کار کند."""
    src = src.replace('href="/Behnia-fit/assets/', 'href="<?php echo esc_url( BF_THEME_URI ); ?>/assets/')
    src = src.replace('src="/Behnia-fit/assets/', 'src="<?php echo esc_url( BF_THEME_URI ); ?>/assets/')
    src = src.replace('href="/Behnia-fit/"', 'href="<?php echo esc_url( home_url( \'/\' ) ); ?>"')
    return src


def build_404():
    src = localize_404(rewrite_assets(strip_asset_links(read("404.html"))))
    src = src.replace("</head>", "  <?php wp_head(); ?>\n</head>", 1)
    src = src.replace("<body>", "<?php bf_body_tag(); ?>", 1)
    src = src.replace("</body>", "  <?php wp_footer(); ?>\n</body>", 1)
    write("404.php", src)


def main():
    os.makedirs(THEME, exist_ok=True)
    copy_assets()
    n = build_from_index()
    styles, inner = build_privacy()
    build_page_php()
    build_404()
    print("قالب ساخته شد:", THEME)
    print("  خطوط <main> منتقل‌شده:", n)
    print("  استایل درون‌خطی privacy:", len(styles), "بلوک")
    print("  محتوای privacy:", len(inner), "کاراکتر")
    zp = build_zip()
    size = os.path.getsize(zp) / 1024.0
    print("  زیپ آمادهٔ نصب: %s (%.0f کیلوبایت)" % (zp, size))


if __name__ == "__main__":
    main()
