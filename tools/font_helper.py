import os

TOOLS_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(TOOLS_DIR, ".."))
BUNDLED_FONTS_DIR = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets", "fonts")
WIN_FONTS = os.path.join(os.environ.get("WINDIR", "C:\\Windows"), "Fonts")

WIN_MAP = {
    "Carlito-Regular.ttf": ["calibri.ttf", "arial.ttf"],
    "Carlito-Bold.ttf": ["calibrib.ttf", "arialbd.ttf"],
    "Carlito-Italic.ttf": ["calibrii.ttf", "ariali.ttf"],
    "Carlito-BoldItalic.ttf": ["calibriz.ttf", "arialbi.ttf"],
    "LiberationSans-Regular.ttf": ["arial.ttf", "calibri.ttf"],
    "LiberationSans-Bold.ttf": ["arialbd.ttf", "calibrib.ttf"],
    "LiberationSans-Italic.ttf": ["ariali.ttf", "calibrii.ttf"],
    "LiberationSerif-Regular.ttf": ["times.ttf", "georgia.ttf", "arial.ttf"],
    "Tahoma.ttf": ["tahoma.ttf", "arial.ttf"],
    "Tahoma-Bold.ttf": ["tahomabd.ttf", "arialbd.ttf"],
    "DejaVuSans.ttf": ["DejaVuSans.ttf", "segoeui.ttf", "arial.ttf"],
    "DejaVuSans-Bold.ttf": ["DejaVuSans-Bold.ttf", "segoeuib.ttf", "arialbd.ttf"],
    "DejaVuSansCondensed.ttf": ["DejaVuSansCondensed.ttf", "arial.ttf"],
    "DejaVuSansCondensed-Bold.ttf": ["DejaVuSansCondensed-Bold.ttf", "arialbd.ttf"],
}

BUNDLED_MAP = {
    "Carlito-Regular.ttf": "Carlito-Regular.ttf",
    "Carlito-Bold.ttf": "Carlito-Bold.ttf",
    "Carlito-Italic.ttf": "Carlito-Italic.ttf",
    "Carlito-BoldItalic.ttf": "Carlito-BoldItalic.ttf",
    "LiberationSans-Regular.ttf": "Carlito-Regular.ttf",
    "LiberationSans-Bold.ttf": "Carlito-Bold.ttf",
    "LiberationSans-Italic.ttf": "Carlito-Italic.ttf",
    "LiberationSerif-Regular.ttf": "Carlito-Regular.ttf",
    "Tahoma.ttf": "Carlito-Regular.ttf",
    "Tahoma-Bold.ttf": "Carlito-Bold.ttf",
    "DejaVuSans.ttf": "Carlito-Regular.ttf",
    "DejaVuSans-Bold.ttf": "Carlito-Bold.ttf",
    "DejaVuSansCondensed.ttf": "Carlito-Regular.ttf",
    "DejaVuSansCondensed-Bold.ttf": "Carlito-Bold.ttf",
}

REPORTLAB_FALLBACK_MAP = {
    "Carlito-Bold.ttf": "VeraBd.ttf",
    "LiberationSans-Bold.ttf": "VeraBd.ttf",
    "Tahoma-Bold.ttf": "VeraBd.ttf",
    "DejaVuSans-Bold.ttf": "VeraBd.ttf",
    "DejaVuSansCondensed-Bold.ttf": "VeraBd.ttf",
    "Carlito-Italic.ttf": "VeraIt.ttf",
    "LiberationSans-Italic.ttf": "VeraIt.ttf",
    "Carlito-BoldItalic.ttf": "VeraBI.ttf",
}

ALIAS_MAP = {
    "Cal": ("Carlito-Regular.ttf", "LiberationSans-Regular.ttf"),
    "Cal-B": ("Carlito-Bold.ttf", "LiberationSans-Bold.ttf"),
    "Cal-I": ("Carlito-Italic.ttf", "LiberationSans-Italic.ttf"),
    "Cal-BI": ("Carlito-BoldItalic.ttf", "Carlito-Bold.ttf"),
    "Lib": ("LiberationSans-Regular.ttf", "Carlito-Regular.ttf"),
    "Lib-B": ("LiberationSans-Bold.ttf", "Carlito-Bold.ttf"),
    "Lib-I": ("LiberationSans-Italic.ttf", "Carlito-Italic.ttf"),
    "Tah": ("Tahoma.ttf", "Carlito-Regular.ttf"),
    "Tah-B": ("Tahoma-Bold.ttf", "Carlito-Bold.ttf"),
    "DJ": ("DejaVuSans.ttf", "Carlito-Regular.ttf"),
    "DJC": ("DejaVuSansCondensed.ttf", "Carlito-Regular.ttf"),
    "DJC-B": ("DejaVuSansCondensed-Bold.ttf", "Carlito-Bold.ttf"),
    "Times": ("LiberationSerif-Regular.ttf", "Carlito-Regular.ttf"),
}

_REGISTERED_PATHS = {}


def _reportlab_font_path(filename):
    try:
        import reportlab
        fonts_dir = os.path.join(os.path.dirname(reportlab.__file__), "fonts")
        vera_name = REPORTLAB_FALLBACK_MAP.get(filename, "Vera.ttf")
        for cand in (vera_name, "Vera.ttf"):
            p = os.path.join(fonts_dir, cand)
            if os.path.isfile(p):
                return p
    except Exception:
        pass
    return None


def _find_bundled_font(filename):
    bundled_name = BUNDLED_MAP.get(filename)
    if bundled_name:
        p = os.path.join(BUNDLED_FONTS_DIR, bundled_name)
        if os.path.isfile(p):
            return p
    p = os.path.join(BUNDLED_FONTS_DIR, filename)
    if os.path.isfile(p):
        return p
    return None


def find_font(filename, fallback="Carlito-Regular.ttf"):
    """Find an existing TrueType font path on Windows, Linux/macOS, or bundled assets."""
    if os.path.isdir(WIN_FONTS):
        if filename in WIN_MAP:
            for cand in WIN_MAP[filename]:
                p = os.path.join(WIN_FONTS, cand)
                if os.path.isfile(p):
                    return p
        p = os.path.join(WIN_FONTS, filename)
        if os.path.isfile(p):
            return p

    if filename.startswith("Carlito-"):
        bundled = os.path.join(BUNDLED_FONTS_DIR, filename)
        if os.path.isfile(bundled):
            return bundled

    fast_dirs = [
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/liberation-sans",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/dejavu",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/liberation",
        "/usr/share/fonts/truetype/dejavu",
        "/Library/Fonts",
        "/System/Library/Fonts",
    ]
    for d in fast_dirs:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            return p

    search_roots = [
        "/usr/share/fonts",
        "/usr/local/share/fonts",
        os.path.expanduser("~/.local/share/fonts"),
        os.path.expanduser("~/.fonts"),
    ]
    for root_dir in search_roots:
        if os.path.isdir(root_dir):
            for root, _, files in os.walk(root_dir):
                if filename in files:
                    return os.path.join(root, filename)

    bundled = _find_bundled_font(filename)
    if bundled:
        return bundled

    if fallback and fallback != filename:
        return find_font(fallback, fallback=None)

    rl_font = _reportlab_font_path(filename)
    if rl_font:
        return rl_font

    return filename


def register_reportlab_fonts(aliases=None):
    """Register requested ReportLab font aliases safely with guaranteed TTF fallbacks."""
    from reportlab.pdfbase import pdfmetrics
    from reportlab.pdfbase.ttfonts import TTFont

    target_aliases = aliases if aliases is not None else list(ALIAS_MAP.keys())

    for alias in target_aliases:
        primary, fallback = ALIAS_MAP.get(alias, ("Carlito-Regular.ttf", "Vera.ttf"))
        candidates = [
            find_font(primary, fallback=fallback),
            _find_bundled_font(primary),
            _reportlab_font_path(primary),
        ]
        registered = False
        for path in candidates:
            if path and os.path.isfile(path):
                try:
                    pdfmetrics.registerFont(TTFont(alias, path))
                    _REGISTERED_PATHS[alias] = path
                    registered = True
                    break
                except Exception:
                    continue
        if not registered:
            raise FileNotFoundError(f"Unable to register any TTF font for alias '{alias}'.")

    return dict(_REGISTERED_PATHS)


def registered_font_path(alias):
    return _REGISTERED_PATHS.get(alias, "")


def draw_checkmark(canvas_obj, x_center, y_bottom, size=7.0, font_name="DJ"):
    """Draw a checkmark using DejaVu/Segoe if available, else a crisp vector path."""
    font_path = os.path.basename(_REGISTERED_PATHS.get(font_name, "")).lower()
    if any(k in font_path for k in ("dejavu", "segoe")):
        canvas_obj.setFont(font_name, size)
        canvas_obj.drawCentredString(x_center, y_bottom, "\u2713")
        return

    canvas_obj.saveState()
    canvas_obj.setLineWidth(max(0.8, size * 0.14))
    p = canvas_obj.beginPath()
    p.moveTo(x_center - size * 0.34, y_bottom + size * 0.34)
    p.lineTo(x_center - size * 0.08, y_bottom + size * 0.08)
    p.lineTo(x_center + size * 0.36, y_bottom + size * 0.64)
    canvas_obj.drawPath(p, stroke=1, fill=0)
    canvas_obj.restoreState()
