import os

WIN_FONTS = os.path.join(os.environ.get('WINDIR', 'C:\\Windows'), 'Fonts')

WIN_MAP = {
    'Carlito-Regular.ttf': ['calibri.ttf', 'arial.ttf'],
    'Carlito-Bold.ttf': ['calibrib.ttf', 'arialbd.ttf'],
    'Carlito-Italic.ttf': ['calibrii.ttf', 'ariali.ttf'],
    'LiberationSans-Regular.ttf': ['arial.ttf', 'calibri.ttf'],
    'LiberationSans-Bold.ttf': ['arialbd.ttf', 'calibrib.ttf'],
    'LiberationSans-Italic.ttf': ['ariali.ttf', 'calibrii.ttf'],
    'LiberationSerif-Regular.ttf': ['times.ttf', 'arial.ttf'],
    'DejaVuSans.ttf': ['segoeui.ttf', 'arial.ttf'],
    'DejaVuSansCondensed.ttf': ['arial.ttf'],
    'DejaVuSansCondensed-Bold.ttf': ['arialbd.ttf'],
}

def find_font(filename, fallback="LiberationSans-Regular.ttf"):
    if os.path.isdir(WIN_FONTS):
        if filename in WIN_MAP:
            for cand in WIN_MAP[filename]:
                p = os.path.join(WIN_FONTS, cand)
                if os.path.isfile(p):
                    return p
        p = os.path.join(WIN_FONTS, filename)
        if os.path.isfile(p):
            return p

    candidates = [
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/liberation",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/dejavu",
        "/Library/Fonts",
        "/System/Library/Fonts",
    ]
    for d in candidates:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            return p
    for d in candidates:
        if os.path.isdir(d):
            for root, _, files in os.walk(d):
                if filename in files:
                    return os.path.join(root, filename)

    if fallback and fallback != filename:
        return find_font(fallback, fallback=None)
    return filename
