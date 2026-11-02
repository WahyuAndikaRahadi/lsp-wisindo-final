#!/usr/bin/env python3
"""
Generator FR.APL.01 (Permohonan Sertifikasi Kompetensi) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; semua isi dinamis datang dari dict DATA.
Mendukung skema standar (9 unit) dan skema besar (hingga 18+ unit) dengan paginasi dinamis.
"""
import os, sys, re, io, base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.colors import Color, black, white
from reportlab.lib.utils import ImageReader

BASE = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(BASE, ".."))
OUT = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/FR_APL_01_generated.pdf")
ASSETS = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/assets")

# Dimensi A4 pada 100 dpi: 827 x 1169 px -> Point: 595.56 x 842.52 pt
W, H = 595.56, 842.52
S = 0.72  # 1 px @100dpi = 0.72 pt
X = lambda px: px * S
Y = lambda py: H - py * S

# Register fonts dengan fallback deteksi lokasi OS (Arch/Debian/Ubuntu)
def find_font(filename):
    win_fonts = os.path.join(os.environ.get('WINDIR', 'C:\\Windows'), 'Fonts')
    win_map = {
        'Carlito-Regular.ttf': ['calibri.ttf', 'arial.ttf'],
        'Carlito-Bold.ttf': ['calibrib.ttf', 'arialbd.ttf'],
        'Carlito-Italic.ttf': ['calibrii.ttf', 'ariali.ttf'],
        'LiberationSans-Regular.ttf': ['arial.ttf', 'calibri.ttf'],
        'LiberationSans-Bold.ttf': ['arialbd.ttf', 'calibrib.ttf'],
        'DejaVuSans.ttf': ['segoeui.ttf', 'arial.ttf'],
        'DejaVuSansCondensed.ttf': ['arial.ttf'],
        'DejaVuSansCondensed-Bold.ttf': ['arialbd.ttf'],
    }
    if os.path.isdir(win_fonts):
        if filename in win_map:
            for cand in win_map[filename]:
                p = os.path.join(win_fonts, cand)
                if os.path.isfile(p):
                    return p
        p = os.path.join(win_fonts, filename)
        if os.path.isfile(p):
            return p

    candidates = [
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/liberation",
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

    fallbacks = {
        "Carlito-Regular.ttf": "LiberationSans-Regular.ttf",
        "Carlito-Bold.ttf": "LiberationSans-Bold.ttf",
        "Carlito-Italic.ttf": "LiberationSans-Italic.ttf",
    }
    if filename in fallbacks:
        return find_font(fallbacks[filename])
    return filename

font_map = {
    "Cal": "Carlito-Regular.ttf",
    "Cal-B": "Carlito-Bold.ttf",
    "Cal-I": "Carlito-Italic.ttf",
    "Lib": "LiberationSans-Regular.ttf",
    "Lib-B": "LiberationSans-Bold.ttf",
    "DJ": "DejaVuSans.ttf",
    "DJC": "DejaVuSansCondensed.ttf",
    "DJC-B": "DejaVuSansCondensed-Bold.ttf",
}
for name, ffile in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile)))

sw = pdfmetrics.stringWidth
BLUE = Color(0.27, 0.45, 0.77)

def fit_size(s, font, width_px, ref=10.0):
    return ref * (width_px * S) / sw(s, font, ref)

BODY = fit_size("Pada bagian ini, cantumlan data pribadi, data pendidikan formal serta data pekerjaan anda pada saat", "Cal", 618)
UNIT = fit_size("G.45OTO01.001.2", "Lib", 121)

# DATA dummy representatif (fiktif, sesuai konteks Klaster 2)
DATA = {
    "skema_judul": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "standar": ["SKKNI Nomor 97", "Tahun 2018"],
    "units": [
        ("G.45OTO01.001.2", "Melaksanakan Keselamatan dan Kesehatan Kerja"),
        ("G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja"),
        ("G.45OTO01.007.2", "Membaca Gambar Teknik"),
        ("G.45OTO01.008.2", "Menggunakan Alat Ukur"),
        ("G.45OTO01.009.2", "Melaksanakan Diagnosis"),
        ("G.45OTO01.010.2", "Melakukan Perawatan Berkala Engine"),
        ("G.45OTO01.065.2", "Memelihara Baterai"),
        ("G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan"),
        ("G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine"),
    ],
    # Data Pribadi Asesi
    "nama_lengkap": "Ahmad Fauzi Ramadhan",
    "nik": "3275010203050004",
    "ttl": "Bekasi, 02 Maret 2005",
    "jenis_kelamin": "L",
    "kebangsaan": "Indonesia",
    "alamat_rumah": "Jl. Melati Raya No. 12, RT 03/RW 05, Kel. Kayuringin Jaya, Kec. Bekasi Selatan, Kota Bekasi",
    "kode_pos": "17144",
    "telp_rumah": "-",
    "telp_kantor_pribadi": "021-5550123",
    "hp": "0812-3456-7890",
    "email": "fauzi.r@example.com",
    "pendidikan": "SMK Teknik Kendaraan Ringan Otomotif",
    "instansi": "CV Maju Jaya Motor (dummy)",
    "jabatan": "Mekanik Junior",
    "alamat_kantor": "Jl. Raya Contoh No. 45, Kel. Contoh Baru, Kota Bekasi",
    "kode_pos_kantor": "17145",
    "telp_kantor": "021-5550199",
    "fax_kantor": "021-5550188",
    "kontak_kantor": "021-5550199 / 021-5550188 / hrd@majujayamotor.example",
    "tujuan": {"sertifikasi": True, "pkt": False, "rpl": False, "lainnya": False},
    "syarat_dasar": ["memenuhi", "tidak_ada", "tidak_ada", "tidak_ada"],
    "syarat_admin": ["memenuhi", "memenuhi"],
    "diterima": True,
    "catatan": "Berkas lengkap dan memenuhi persyaratan dasar.",
    "tgl_pemohon": "20 Oktober 2026",
    "nama_admin": "Rina Kartika",
    "tgl_admin": "20 Oktober 2026",
}

# --- Primitif Gambar ---
def hline(c, x1, x2, y, w=0.7):
    c.setLineWidth(w); c.line(X(x1), Y(y), X(x2), Y(y))

def vline(c, x, y1, y2, w=0.7):
    c.setLineWidth(w); c.line(X(x), Y(y1), X(x), Y(y2))

def text(c, s, x, yc, font="Cal", size=None, color=black, strike=False):
    size = size or BODY
    base = Y(yc) - size * 0.32
    c.setFillColor(color); c.setFont(font, size); c.drawString(X(x), base, s)
    wdt = sw(s, font, size)
    if strike:
        c.setStrokeColor(color); c.setLineWidth(0.6)
        c.line(X(x), base + size * 0.3, X(x) + wdt, base + size * 0.3)
    return X(x) + wdt

def ctext(c, s, xc, yc, font="Cal", size=None, color=black):
    size = size or BODY
    c.setFillColor(color); c.setFont(font, size)
    c.drawString(X(xc) - sw(s, font, size) / 2, Y(yc) - size * 0.32, s)

def heading(c, s, x, yc, width_px, font="Cal-B"):
    text(c, s, x, yc, font, fit_size(s, font, width_px))

def runs(c, parts, x, yc, size=None):
    size = size or BODY
    xp = X(x)
    for s, font, strike in parts:
        base = Y(yc) - size * 0.32
        c.setFont(font, size); c.setFillColor(black); c.drawString(xp, base, s)
        wdt = sw(s, font, size)
        if strike:
            c.setLineWidth(0.6); c.line(xp, base + size * 0.3, xp + wdt, base + size * 0.3)
        xp += wdt
    return xp

def justified(c, line, x, yc, width_px):
    words = line.split(); size = BODY
    gaps = len(words) - 1
    total = sum(sw(w, "Cal", size) for w in words)
    gap = (X(width_px) - total) / gaps if gaps > 0 else 0
    xp = X(x); base = Y(yc) - size * 0.32
    c.setFont("Cal", size); c.setFillColor(black)
    for w in words:
        c.drawString(xp, base, w); xp += sw(w, "Cal", size) + gap

def checkbox(c, checked, xc, yc, size=13):
    c.saveState()
    half = (size * S) / 2.0
    x0 = X(xc) - half
    y0 = Y(yc) - half
    w = size * S
    c.setLineWidth(1.0)
    c.setStrokeColor(black)
    c.setFillColor(white)
    c.rect(x0, y0, w, w, fill=1, stroke=1)
    if checked:
        c.setStrokeColor(black)
        c.setLineWidth(1.8)
        c.setLineCap(1)
        c.setLineJoin(1)
        p = c.beginPath()
        p.moveTo(x0 + 0.20 * w, y0 + 0.48 * w)
        p.lineTo(x0 + 0.42 * w, y0 + 0.22 * w)
        p.lineTo(x0 + 0.82 * w, y0 + 0.80 * w)
        c.drawPath(p, stroke=1, fill=0)
    c.restoreState()

def wrap_plain(s, font, size, maxw):
    lines, cur = [], ""
    for w in s.split():
        t = (cur + " " + w).strip()
        if sw(t, font, size) <= maxw: cur = t
        else: lines.append(cur); cur = w
    if cur: lines.append(cur)
    return lines

STYLE_FONT = {"n": "Cal", "i": "Cal-I", "a": "Lib"}
def rich_lines(template, maxw_px, judul):
    toks = []
    for part in re.split(r"(\{judul\}|\{atau\})", template):
        if part == "{judul}":
            toks += [(w, "i") for w in judul.split()]
        elif part == "{atau}":
            toks += [("atau", "a")]
        else:
            toks += [(w, "n") for w in part.split()]
    maxw, lines, cur, curw = X(maxw_px), [], [], 0
    space = sw(" ", "Cal", BODY)
    for w, st in toks:
        ww = sw(w, STYLE_FONT[st], BODY)
        add = ww if not cur else space + ww
        if cur and curw + add > maxw:
            lines.append(cur); cur, curw = [(w, st)], ww
        else:
            cur.append((w, st)); curw += add
    if cur: lines.append(cur)
    return lines

def draw_rich(c, lines, x, yc_first, step_px):
    for i, ln in enumerate(lines):
        xp = X(x); yc = yc_first + i * step_px; base = Y(yc) - BODY * 0.32
        for j, (w, st) in enumerate(ln):
            if j: xp += sw(" ", "Cal", BODY)
            c.setFont(STYLE_FONT[st], BODY); c.setFillColor(black); c.drawString(xp, base, w)
            xp += sw(w, STYLE_FONT[st], BODY)

def header(c, dy):
    top, bot = 18 + dy, 108 + dy
    c.setLineWidth(0.7); c.setStrokeColor(black)
    c.rect(X(126), Y(bot), X(762 - 126), (bot - top) * S)
    vline(c, 254, top, bot)
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, X(148.5), Y(20 + dy + 85.6), X(96.7), X(85.6), mask="auto")
    segs = [("L", 1), ("embaga ", 0), ("S", 1), ("ertifikasi ", 0), ("P", 1), ("rofesi ", 0),
            ("P", 1), ("raktisi ", 0), ("O", 1), ("tomotif  ", 0), ("LSP-PrO Indo", 1)]
    f = lambda b: "DJC-B" if b else "DJC"
    w10 = sum(sw(s, f(b), 10) for s, b in segs)
    size = 10 * (417 * S) / w10
    xp = X(293)
    for s, b in segs:
        c.setFont(f(b), size); c.setFillColor(black)
        c.drawString(xp, Y(70 + dy) - size * 0.32, s); xp += sw(s, f(b), size)

def watermark(c):
    # Dihapus sesuai permintaan user: tidak menggunakan watermark LSP
    pass

def pageno(c, n):
    ctext(c, str(n), 452, 1093, "Cal", 10, BLUE)

def colon(c, x, yc):
    text(c, ":", x - 2, yc, "Cal", BODY)

def table_header(c, top, yc, no_x, bukti_x, bukti_label):
    ctext(c, "No.", no_x, yc, "Cal-B"); ctext(c, bukti_label, bukti_x, yc, "Cal-B")
    ctext(c, "Ada", 575, top + 14, "Cal-B")
    ctext(c, "Memenuhi", 525, yc - 2, "Cal-B"); ctext(c, "Syarat", 525, yc + 18, "Cal-B")
    ctext(c, "Tidak", 618, yc - 9, "Cal-B"); ctext(c, "Memenuhi", 618, yc + 10, "Cal-B"); ctext(c, "Syarat", 618, yc + 29, "Cal-B")
    text(c, "Tidak Ada", 672, yc, "Cal-B")

def mark_cells(c, status, yc):
    st = str(status).lower().strip()
    is_memenuhi = (st in ("memenuhi", "memenuhi_syarat", "1", "true", "ada"))
    is_tidak_memenuhi = (st in ("tidak_memenuhi", "tidak_memenuhi_syarat"))
    is_tidak_ada = not is_memenuhi and not is_tidak_memenuhi
    checkbox(c, is_memenuhi, 525, yc, 12)
    checkbox(c, is_tidak_memenuhi, 618, yc, 12)
    checkbox(c, is_tidak_ada, 717, yc, 12)

def clean_signature_image(raw_bytes_or_path):
    try:
        if isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path))
        else:
            im = Image.open(raw_bytes_or_path)
        im = im.convert("RGBA")
        w, h = im.size
        if w == 0 or h == 0:
            return None

        # Sample corner colors to detect solid background if opaque
        corners = []
        for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)):
            px = im.getpixel(pt)
            if px[3] > 200:
                corners.append((px[0], px[1], px[2]))

        has_solid_bg = len(corners) >= 3
        bg_r = sum(c[0] for c in corners) / len(corners) if has_solid_bg else 255
        bg_g = sum(c[1] for c in corners) / len(corners) if has_solid_bg else 255
        bg_b = sum(c[2] for c in corners) / len(corners) if has_solid_bg else 255

        out = Image.new("RGBA", (w, h), (0, 0, 0, 0))
        for y in range(h):
            for x in range(w):
                r, g, b, a = im.getpixel((x, y))
                if a < 15:
                    continue
                # Background checks
                is_white = (r > 220 and g > 220 and b > 220)
                is_green = (g > 80 and g > r * 1.15 and g > b * 1.15)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5 if has_solid_bg else 999
                is_bg = (has_solid_bg and dist_bg < 45)

                if is_white or is_green or is_bg:
                    continue

                # Ink stroke: sharpen and boost opacity
                new_a = min(255, int(a * 1.8))
                out.putpixel((x, y), (15, 23, 42, new_a))

        bbox = out.getbbox()
        if bbox:
            pad = 2
            x1 = max(0, bbox[0] - pad)
            y1 = max(0, bbox[1] - pad)
            x2 = min(w, bbox[2] + pad)
            y2 = min(h, bbox[3] + pad)
            out = out.crop((x1, y1, x2, y2))
        else:
            return None

        buf = io.BytesIO()
        out.save(buf, format="PNG")
        buf.seek(0)
        return buf
    except Exception:
        return None

def draw_signature(c, sig_data, x_px, y_bottom_px, max_w_px=140, max_h_px=38):
    if not sig_data:
        return
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
            cleaned = clean_signature_image(raw)
            if cleaned:
                im_pil = Image.open(cleaned)
                img = ImageReader(cleaned)
            else:
                im_pil = Image.open(io.BytesIO(raw))
                img = ImageReader(io.BytesIO(raw))
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            cleaned = clean_signature_image(sig_data)
            if cleaned:
                im_pil = Image.open(cleaned)
                img = ImageReader(cleaned)
            else:
                im_pil = Image.open(sig_data)
                img = ImageReader(sig_data)
        else:
            return

        orig_w, orig_h = im_pil.size
        if orig_w <= 0 or orig_h <= 0:
            return

        scale = min(max_w_px / orig_w, max_h_px / orig_h)
        draw_w = orig_w * scale
        draw_h = orig_h * scale

        draw_x = x_px + (max_w_px - draw_w) / 2.0
        c.drawImage(img, X(draw_x), Y(y_bottom_px), X(draw_w), X(draw_h), mask="auto")
    except Exception:
        pass

# ================= HALAMAN 1 =================
def page1(c, d, pnum=1):
    watermark(c); header(c, 0)
    heading(c, "FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI", 127, 139, 363)
    heading(c, "Bagian 1 :  Rincian Data Pemohon Sertifikasi", 127, 180, 303)
    text(c, "Pada bagian ini, cantumlan data pribadi, data pendidikan formal serta data pekerjaan anda pada saat", 127, 207)
    text(c, "ini.", 127, 227)
    heading(c, "a.  Data Pribadi", 127, 252, 98)

    # --- a. Data Pribadi ---
    L, R, C1, C2 = 136, 776, 311, 350
    rows = [274, 303, 340, 378, 416, 453, 524, 556, 594, 629]
    hline(c, L, R, rows[0]); hline(c, L, R, rows[-1])
    for y in (303, 340, 378, 416, 453, 524, 594): hline(c, L, R, y)
    hline(c, C1, R, 556)
    for x in (L, C1, C2, R): vline(c, x, rows[0], rows[-1])

    lab = [("Nama lengkap", 290), ("No. KTP/NIK/Paspor", 326), ("Tempat / tgl. Lahir", 364),
           ("Jenis kelamin", 401), ("Kebangsaan", 438), ("Alamat rumah", 489),
           ("No. Telepon/E-mail", 547), ("Kualifikasi Pendidikan", 617)]
    for s, yc in lab: text(c, s, 149, yc)
    for yc in (284, 321, 357, 395, 432, 536, 573, 611): colon(c, 331, yc)

    text(c, str(d.get("nama_lengkap") or "-"), 356, 290)
    text(c, str(d.get("nik") or "-"), 356, 326)
    text(c, str(d.get("ttl") or "-"), 356, 364)
    jk = str(d.get("jenis_kelamin") or "L").upper()
    runs(c, [("Laki-laki", "Cal", jk != "L"), (" / ", "Cal", False), ("Wanita", "Cal", jk != "P"), (" *)", "Cal", False)], 356, 401)
    text(c, str(d.get("kebangsaan") or "Indonesia"), 356, 438)
    for i, ln in enumerate(wrap_plain(str(d.get("alamat_rumah") or "-"), "Cal", BODY, X(410))):
        text(c, ln, 356, 475 + i * 17)
    xe = text(c, "Kode pos :", 549, 514); text(c, " " + str(d.get("kode_pos") or "-"), 549 + (xe - X(549)) / S, 514)
    for lbl, val, x, yc in (("Rumah :", str(d.get("telp_rumah") or "-"), 351, 539), ("Kantor :", str(d.get("telp_kantor_pribadi") or "-"), 549, 539),
                            ("HP :", str(d.get("hp") or "-"), 351, 574), ("E-mail :", str(d.get("email") or "-"), 555, 574)):
        xe = text(c, lbl, x, yc); text(c, " " + val, x + (xe - X(x)) / S, yc)
    text(c, str(d.get("pendidikan") or "-"), 356, 617)
    text(c, "*Coret yang tidak perlu", 138, 647)

    # --- b. Data Pekerjaan ---
    heading(c, "b.  Data Pekerjaan Sekarang", 127, 684, 178)
    L, R, C1, C2 = 136, 776, 304, 330
    top, bottom = 711, 926
    hline(c, L, R, top); hline(c, L, R, bottom)
    for y in (746, 781, 857, 900): hline(c, L, R, y)
    for x in (L, C1, C2, R): vline(c, x, top, bottom)
    text(c, "Nama Institusi /", 151, 721); text(c, "Perusahaan", 151, 736)
    text(c, "Jabatan", 151, 761); text(c, "Alamat Kantor", 151, 797); text(c, "No. Telp/Fax/E-mail", 151, 913)
    for yc in (720, 761, 797, 913): colon(c, 317, yc)
    text(c, str(d.get("instansi") or "-"), 336, 728)
    text(c, str(d.get("jabatan") or "-"), 336, 761)
    for i, ln in enumerate(wrap_plain(str(d.get("alamat_kantor") or "-"), "Cal", BODY, X(430))):
        text(c, ln, 336, 797 + i * 17)
    xe = text(c, "Kode pos :", 561, 847); text(c, " " + str(d.get("kode_pos_kantor") or "-"), 561 + (xe - X(561)) / S, 847)
    xe = text(c, "Telp   :", 563, 869); text(c, " " + str(d.get("telp_kantor") or "-"), 563 + (xe - X(563)) / S, 869)
    xe = text(c, "Fax    :", 569, 889); text(c, " " + str(d.get("fax_kantor") or "-"), 569 + (xe - X(569)) / S, 889)
    text(c, str(d.get("kontak_kantor") or "-"), 336, 913)
    pageno(c, pnum)

# ================= HALAMAN 2 (SKEMA STANDAR <= 9 UNIT) =================
def page2(c, d, pnum=2):
    watermark(c); header(c, 21)
    heading(c, "Bagian  2 :  Data Sertifikasi", 128, 160, 181)
    justified(c, "Tuliskan Judul dan Nomor Skema Sertifikasi yang anda ajukan berikut Daftar Unit Kompetensi sesuai", 128, 188, 622)
    justified(c, "kemasan pada skema sertifikasi untuk mendapatkan pengakuan sesuai dengan latar belakang", 128, 207, 622)
    text(c, "pendidikan, pelatihan serta pengalaman kerja yang anda miliki.", 128, 226)

    # --- tabel skema ---
    L, R = 128, 768; THICK = 1.5
    hline(c, L, R, 236, THICK); hline(c, L, R, 428, THICK)
    vline(c, L, 236, 428, THICK); vline(c, R, 236, 428, THICK)
    hline(c, 313, R, 264); hline(c, L, R, 292)
    vline(c, 313, 236, 292); vline(c, 383, 236, 428); vline(c, 411, 236, 428)
    for y in (325, 359, 392): hline(c, 383, R, y)
    text(c, "Skema Sertifikasi", 151, 254)
    runs(c, [("(", "Cal", False), ("KKNI/Okupasi", "Cal", True), ("/Klaster)", "Cal", False)], 143, 275)
    text(c, "Judul", 327, 247); text(c, "Nomor", 327, 278)
    colon(c, 396, 247); colon(c, 396, 278)
    text(c, str(d.get("skema_judul") or "-"), 418, 247, "Cal-I"); text(c, str(d.get("skema_nomor") or "-"), 418, 278)
    text(c, "Tujuan Asesmen", 151, 303); colon(c, 397, 297)
    t = d.get("tujuan") or {"sertifikasi": True, "pkt": False, "rpl": False, "lainnya": False}
    for key, lbl, bx, yc in (("sertifikasi", "Sertifikasi", 424, 312),
                             ("pkt", "Pengakuan Kompetensi Terkini (PKT)", 426, 345),
                             ("rpl", "Rekognisi Pembelajaran Lampau (RPL)", 426, 376),
                             ("lainnya", "Lainnya", 426, 411)):
        checkbox(c, bool(t.get(key, False)), bx, yc, 14); text(c, lbl, 446, yc + 1)

    # --- tabel unit ---
    heading(c, "Daftar Unit Kompetensi sesuai kemasan:", 128, 453, 254)
    L, R = 128, 771
    hline(c, L, R, 471, THICK); hline(c, L, R, 523)
    ctext(c, "No.", 152, 497, "Cal-B"); ctext(c, "Kode Unit", 245, 497, "Cal-B"); ctext(c, "Judul Unit", 477, 497, "Cal-B")
    ctext(c, "Standar", 706, 481, "Cal-B"); ctext(c, "Kompetensi Kerja", 706, 499, "Cal-B")
    y = 523
    maxw = X(316)
    for i, unit_entry in enumerate(d.get("units", []), 1):
        if isinstance(unit_entry, (list, tuple)):
            kode = str(unit_entry[0])
            judul = str(unit_entry[1]) if len(unit_entry) > 1 else ""
        elif isinstance(unit_entry, dict):
            kode = str(unit_entry.get("code", ""))
            judul = str(unit_entry.get("title", ""))
        else:
            kode = str(unit_entry)
            judul = ""

        ln = wrap_plain(judul, "Lib", UNIT, maxw)
        h = 20.6 if len(ln) <= 1 else 37.5
        yc = y + h / 2
        hline(c, L, 640, y + h, 0.7)
        ctext(c, f"{i}.", 152, yc)
        ctext(c, kode, 245, yc, "Lib", UNIT)
        for k, l in enumerate(ln):
            text(c, l, 318, yc + (k - (len(ln) - 1) / 2) * 17, "Lib", UNIT)
        y += h

    hline(c, L, R, y, THICK)
    vline(c, L, 471, y, THICK); vline(c, R, 471, y, THICK)
    for x in (176, 313, 640): vline(c, x, 471, y)
    mid = (523 + y) / 2
    standar = d.get("standar") or ["SKKNI Nomor 97", "Tahun 2018"]
    s0 = standar[0] if len(standar) > 0 else ""
    s1 = standar[1] if len(standar) > 1 else ""
    ctext(c, str(s0), 705, mid - 9); ctext(c, str(s1), 705, mid + 9)

    # --- 3.1 Bukti Persyaratan Dasar (Item 1 & 2 di bawah tabel unit agar halaman full) ---
    y_b3 = y + 20
    heading(c, "Bagian  3 :  Bukti Kelengkapan Pemohon", 128, y_b3, 279)
    heading(c, "3.1 Bukti Persyaratan Dasar Pemohon", 128, y_b3 + 20, 260)
    top1 = y_b3 + 42
    L_b, R_b = 130, 767
    hline(c, L_b, R_b, top1, THICK)
    table_header(c, top1, top1 + 48, 158, 334, "Bukti Persyaratan Dasar")
    hline(c, 482, 668, top1 + 27)
    hline(c, L_b, R_b, top1 + 86, 1.2)

    # Row 1 (Item 1)
    y_row1 = top1 + 86
    h_row1 = 36
    hline(c, L_b, R_b, y_row1 + h_row1, 0.9)
    ctext(c, "1.", 158, y_row1 + h_row1 / 2)
    xe = text(c, "Fotocopy Ijazah SMK otomotif;", 176, y_row1 + h_row1 / 2 - 1)
    text(c, " atau", 176 + (xe - X(176)) / S, y_row1 + h_row1 / 2 - 1, "Lib", UNIT)
    sd = d.get("syarat_dasar") or ["tidak_ada", "tidak_ada", "tidak_ada", "tidak_ada"]
    while len(sd) < 4:
        sd.append("tidak_ada")
    mark_cells(c, sd[0], y_row1 + h_row1 / 2)

    # Row 2 (Item 2)
    y_row2 = y_row1 + h_row1
    tmpl2 = "Surat Keterangan Peserta didik SMK Jurusan Otomotif yang telah menyelesaikan mata pelajaran bidang {judul} sesuai unit kompetensi skema dengan dibuktikan nilai raport semester 1-4 dan fotocopy Surat Keterangan/Sertifikat mengikuti prakerin; {atau}"
    ln2 = rich_lines(tmpl2, 298, str(d.get("skema_judul") or ""))
    h_row2 = max(55, len(ln2) * 18.6 + 6)
    bot_row2 = y_row2 + h_row2
    hline(c, L_b, R_b, bot_row2, THICK)
    ctext(c, "2.", 158, y_row2 + h_row2 / 2)
    draw_rich(c, ln2, 176, y_row2 + 13, 18.6)
    mark_cells(c, sd[1], y_row2 + h_row2 / 2)

    # Garis vertikal pembatas kolom tabel 3.1 di Halaman 2
    for x in (L_b, R_b): vline(c, x, top1, bot_row2, THICK)
    for x in (171, 482, 668): vline(c, x, top1, bot_row2)
    vline(c, 569, top1 + 27, bot_row2)

    pageno(c, pnum)

# ================= HALAMAN 3 (SKEMA STANDAR <= 9 UNIT) =================
def page3(c, d, pnum=3):
    watermark(c); header(c, 21)
    THICK = 1.5; L, R = 130, 767

    # Lanjutan 3.1 Bukti Persyaratan Dasar Pemohon (Item 3 & 4) dengan header tabel lengkap
    heading(c, "3.1 Bukti Persyaratan Dasar Pemohon (Lanjutan)", 128, 142, 280)
    top3 = 162
    hline(c, L, R, top3, THICK)
    table_header(c, top3, top3 + 48, 158, 334, "Bukti Persyaratan Dasar")
    hline(c, 482, 668, top3 + 27)
    hline(c, L, R, top3 + 86, 1.2)

    sd = d.get("syarat_dasar") or ["tidak_ada", "tidak_ada", "tidak_ada", "tidak_ada"]
    while len(sd) < 4:
        sd.append("tidak_ada")

    # Row 3 (Item 3)
    y_row3 = top3 + 86
    t3 = "Fotocopy Ijazah SMA sederajat dan fotocopy sertifikat pelatihan berbasis kompetensi {judul} dari lembaga pelatihan kerja yang Terakreditasi/Kredibel; {atau}"
    ln3 = rich_lines(t3, 298, str(d.get("skema_judul") or ""))
    h_row3 = max(55, len(ln3) * 18.2 + 6)
    hline(c, L, R, y_row3 + h_row3, 0.9)
    ctext(c, "3.", 152, y_row3 + h_row3 / 2)
    draw_rich(c, ln3, 176, y_row3 + 13, 18.2)
    mark_cells(c, sd[2], y_row3 + h_row3 / 2)

    # Row 4 (Item 4)
    y_row4 = y_row3 + h_row3
    t4 = "Surat keterangan pengalaman kerja sesuai bidang {judul} minimal 1 tahun"
    ln4 = rich_lines(t4, 298, str(d.get("skema_judul") or ""))
    h_row4 = max(38, len(ln4) * 18.4 + 6)
    bot_row4 = y_row4 + h_row4
    hline(c, L, R, bot_row4, THICK)
    ctext(c, "4.", 152, y_row4 + h_row4 / 2)
    draw_rich(c, ln4, 176, y_row4 + 13, 18.4)
    mark_cells(c, sd[3], y_row4 + h_row4 / 2)

    # Garis vertikal pembatas kolom tabel 3.1 (Lanjutan)
    for x in (L, R): vline(c, x, top3, bot_row4, THICK)
    for x in (171, 482, 668): vline(c, x, top3, bot_row4)
    vline(c, 569, top3 + 27, bot_row4)

    # 3.2 Bukti Administratif
    y_32_head = bot_row4 + 20
    heading(c, "3.2 Bukti Administratif", 128, y_32_head, 148)
    top_32 = y_32_head + 18
    hline(c, L, R, top_32, THICK)
    table_header(c, top_32, top_32 + 48, 151, 327, "Bukti Administratif")
    hline(c, 482, 668, top_32 + 27)
    hline(c, L, R, top_32 + 86, 1.2)

    # Row 1 (KTP)
    y_adm1 = top_32 + 86
    h_adm1 = 40
    hline(c, L, R, y_adm1 + h_adm1, 0.9)
    ctext(c, "1.", 151, y_adm1 + h_adm1 / 2)
    text(c, "Fotocopy Kartu Tanda Penduduk (KTP),", 180, y_adm1 + h_adm1 / 2, "Lib", UNIT + 0.5)
    sa = d.get("syarat_admin") or ["tidak_ada", "tidak_ada"]
    while len(sa) < 2:
        sa.append("tidak_ada")
    mark_cells(c, sa[0], y_adm1 + h_adm1 / 2)

    # Row 2 (Pas Foto)
    y_adm2 = y_adm1 + h_adm1
    h_adm2 = 46
    bot_adm = y_adm2 + h_adm2
    hline(c, L, R, bot_adm, THICK)
    ctext(c, "2.", 151, y_adm2 + h_adm2 / 2)
    text(c, "Pas photo ukuran 3x4 sebanyak 2 lembar", 180, y_adm2 + 16, "Lib", UNIT + 0.5)
    text(c, "dengan background warna merah.", 180, y_adm2 + 33, "Lib", UNIT + 0.5)
    mark_cells(c, sa[1], y_adm2 + h_adm2 / 2)

    # Vertical lines for 3.2
    for x in (L, R): vline(c, x, top_32, bot_adm, THICK)
    for x in (172, 482, 668): vline(c, x, top_32, bot_adm)
    vline(c, 569, top_32 + 27, bot_adm)

    # Rekomendasi (diisi oleh LSP)
    L_rek, R_rek = 126, 767
    top_rek = bot_adm + 18
    bot_rek = top_rek + 258
    mid_rek = top_rek + 130
    hline(c, L_rek, R_rek, top_rek, THICK); hline(c, L_rek, R_rek, bot_rek, THICK)
    vline(c, L_rek, top_rek, bot_rek, THICK); vline(c, R_rek, top_rek, bot_rek, THICK); vline(c, 475, top_rek, bot_rek, THICK)
    hline(c, L_rek, R_rek, mid_rek, THICK)
    for y_div in (top_rek + 20, top_rek + 56, mid_rek + 17, mid_rek + 55):
        hline(c, 475, R_rek, y_div)
    vline(c, 593, top_rek + 20, mid_rek); vline(c, 593, mid_rek + 17, bot_rek)

    heading(c, "Rekomendasi (diisi oleh LSP):", 136, top_rek + 11, 199)
    text(c, "Berdasarkan ketentuan persyaratan dasar,", 136, top_rek + 31)
    text(c, "maka pemohon:", 136, top_rek + 50)
    ok = bool(d.get("diterima", True))
    runs(c, [("Diterima", "Cal-B", not ok), ("/ ", "Cal-B", False), ("Tidak diterima", "Cal-B", ok),
             (" *)", "Cal", False), (" sebagai peserta sertifikasi", "Cal", False)], 136, top_rek + 70)
    text(c, "* coret yang tidak sesuai", 136, top_rek + 120)

    text(c, "Catatan :", 136, mid_rek + 10, "Cal-B")
    for i, l in enumerate(wrap_plain(str(d.get("catatan") or ""), "Cal", BODY, X(325))):
        text(c, l, 136, mid_rek + 34 + i * 18)

    text(c, "Pemohon/ Kandidat :", 484, top_rek + 11, "Cal-B")
    text(c, "Nama", 484, top_rek + 36); text(c, str(d.get("nama_lengkap") or "-"), 600, top_rek + 36, "Cal", BODY - 0.5)
    text(c, "Tanda tangan/", 484, top_rek + 78); text(c, "Tanggal", 484, top_rek + 96); text(c, str(d.get("tgl_pemohon") or "-"), 600, top_rek + 96)
    draw_signature(c, d.get("signature_asesi") or d.get("asesi_signature"), 600, top_rek + 93, 140, 38)

    text(c, "Admin LSP  :", 484, mid_rek + 10, "Cal-B")
    text(c, "Nama :", 484, mid_rek + 36); text(c, str(d.get("nama_admin") or "Admin LSP"), 600, mid_rek + 36)
    text(c, "Tanda tangan/", 484, mid_rek + 74); text(c, "Tanggal", 484, mid_rek + 92); text(c, str(d.get("tgl_admin") or "-"), 600, mid_rek + 92)
    draw_signature(c, d.get("signature_admin") or d.get("admin_signature"), 600, mid_rek + 89, 140, 38)
    pageno(c, pnum)

# ================= HALAMAN 2 & 3 UNTUK SKEMA BESAR (> 9 UNIT) =================
def page2_large(c, d, pnum=2):
    watermark(c); header(c, 21)
    heading(c, "Bagian  2 :  Data Sertifikasi", 128, 160, 181)
    justified(c, "Tuliskan Judul dan Nomor Skema Sertifikasi yang anda ajukan berikut Daftar Unit Kompetensi sesuai", 128, 188, 622)
    justified(c, "kemasan pada skema sertifikasi untuk mendapatkan pengakuan sesuai dengan latar belakang", 128, 207, 622)
    text(c, "pendidikan, pelatihan serta pengalaman kerja yang anda miliki.", 128, 226)

    # --- tabel skema ---
    L, R = 128, 768; THICK = 1.5
    hline(c, L, R, 236, THICK); hline(c, L, R, 428, THICK)
    vline(c, L, 236, 428, THICK); vline(c, R, 236, 428, THICK)
    hline(c, 313, R, 264); hline(c, L, R, 292)
    vline(c, 313, 236, 292); vline(c, 383, 236, 428); vline(c, 411, 236, 428)
    for y in (325, 359, 392): hline(c, 383, R, y)
    text(c, "Skema Sertifikasi", 151, 254)
    runs(c, [("(", "Cal", False), ("KKNI/Okupasi", "Cal", True), ("/Klaster)", "Cal", False)], 143, 275)
    text(c, "Judul", 327, 247); text(c, "Nomor", 327, 278)
    colon(c, 396, 247); colon(c, 396, 278)
    text(c, str(d.get("skema_judul") or "-"), 418, 247, "Cal-I"); text(c, str(d.get("skema_nomor") or "-"), 418, 278)
    text(c, "Tujuan Asesmen", 151, 303); colon(c, 397, 297)
    t = d.get("tujuan") or {"sertifikasi": True, "pkt": False, "rpl": False, "lainnya": False}
    for key, lbl, bx, yc in (("sertifikasi", "Sertifikasi", 424, 312),
                             ("pkt", "Pengakuan Kompetensi Terkini (PKT)", 426, 345),
                             ("rpl", "Rekognisi Pembelajaran Lampau (RPL)", 426, 376),
                             ("lainnya", "Lainnya", 426, 411)):
        checkbox(c, bool(t.get(key, False)), bx, yc, 14); text(c, lbl, 446, yc + 1)

    # --- tabel unit ---
    heading(c, "Daftar Unit Kompetensi sesuai kemasan:", 128, 453, 254)
    L, R = 128, 771
    hline(c, L, R, 471, THICK); hline(c, L, R, 523)
    ctext(c, "No.", 152, 497, "Cal-B"); ctext(c, "Kode Unit", 245, 497, "Cal-B"); ctext(c, "Judul Unit", 477, 497, "Cal-B")
    ctext(c, "Standar", 706, 481, "Cal-B"); ctext(c, "Kompetensi Kerja", 706, 499, "Cal-B")
    y = 523
    maxw = X(316)
    for i, unit_entry in enumerate(d.get("units", []), 1):
        if isinstance(unit_entry, (list, tuple)):
            kode = str(unit_entry[0])
            judul = str(unit_entry[1]) if len(unit_entry) > 1 else ""
        elif isinstance(unit_entry, dict):
            kode = str(unit_entry.get("code", ""))
            judul = str(unit_entry.get("title", ""))
        else:
            kode = str(unit_entry)
            judul = ""

        ln = wrap_plain(judul, "Lib", UNIT, maxw)
        h = 20.6 if len(ln) <= 1 else 37.5
        yc = y + h / 2
        hline(c, L, 640, y + h, 0.7)
        ctext(c, f"{i}.", 152, yc)
        ctext(c, kode, 245, yc, "Lib", UNIT)
        for k, l in enumerate(ln):
            text(c, l, 318, yc + (k - (len(ln) - 1) / 2) * 17, "Lib", UNIT)
        y += h

    hline(c, L, R, y, THICK)
    vline(c, L, 471, y, THICK); vline(c, R, 471, y, THICK)
    for x in (176, 313, 640): vline(c, x, 471, y)
    mid = (523 + y) / 2
    standar = d.get("standar") or ["SKKNI Nomor 97", "Tahun 2018"]
    s0 = standar[0] if len(standar) > 0 else ""
    s1 = standar[1] if len(standar) > 1 else ""
    ctext(c, str(s0), 705, mid - 9); ctext(c, str(s1), 705, mid + 9)

    # Bottom heading only
    if y + 35 < 1050:
        heading(c, "Bagian  3 :  Bukti Kelengkapan Pemohon", 128, y + 25, 279)
    pageno(c, pnum)

def page3_large(c, d, pnum=3):
    watermark(c); header(c, 21)
    THICK = 1.5; L, R = 130, 767

    # 3.1 Bukti Persyaratan Dasar Pemohon
    heading(c, "Bagian  3 :  Bukti Kelengkapan Pemohon", 128, 140, 279)
    heading(c, "3.1 Bukti Persyaratan Dasar Pemohon", 128, 160, 260)
    top1 = 180
    hline(c, L, R, top1, THICK)
    table_header(c, top1, top1 + 48, 158, 334, "Bukti Persyaratan Dasar")
    hline(c, 482, 668, top1 + 27)
    hline(c, L, R, top1 + 86, 1.2)

    # Row 1 (Item 1)
    y_row1 = top1 + 86
    h_row1 = 36
    hline(c, L, R, y_row1 + h_row1, 0.9)
    ctext(c, "1.", 158, y_row1 + h_row1 / 2)
    xe = text(c, "Fotocopy Ijazah SMK otomotif;", 176, y_row1 + h_row1 / 2 - 1)
    text(c, " atau", 176 + (xe - X(176)) / S, y_row1 + h_row1 / 2 - 1, "Lib", UNIT)
    sd = d.get("syarat_dasar") or ["tidak_ada", "tidak_ada", "tidak_ada", "tidak_ada"]
    while len(sd) < 4:
        sd.append("tidak_ada")
    mark_cells(c, sd[0], y_row1 + h_row1 / 2)

    # Row 2 (Item 2)
    y_row2 = y_row1 + h_row1
    tmpl2 = "Surat Keterangan Peserta didik SMK Jurusan Otomotif yang telah menyelesaikan mata pelajaran bidang {judul} sesuai unit kompetensi skema dengan dibuktikan nilai raport semester 1-4 dan fotocopy Surat Keterangan/Sertifikat mengikuti prakerin; {atau}"
    ln2 = rich_lines(tmpl2, 298, str(d.get("skema_judul") or ""))
    h_row2 = max(60, len(ln2) * 18.6 + 6)
    hline(c, L, R, y_row2 + h_row2, 0.9)
    ctext(c, "2.", 158, y_row2 + h_row2 / 2)
    draw_rich(c, ln2, 176, y_row2 + 13, 18.6)
    mark_cells(c, sd[1], y_row2 + h_row2 / 2)

    # Row 3 (Item 3)
    y_row3 = y_row2 + h_row2
    t3 = "Fotocopy Ijazah SMA sederajat dan fotocopy sertifikat pelatihan berbasis kompetensi {judul} dari lembaga pelatihan kerja yang Terakreditasi/Kredibel; {atau}"
    ln3 = rich_lines(t3, 298, str(d.get("skema_judul") or ""))
    h_row3 = max(55, len(ln3) * 18.2 + 6)
    hline(c, L, R, y_row3 + h_row3, 0.9)
    ctext(c, "3.", 152, y_row3 + h_row3 / 2)
    draw_rich(c, ln3, 176, y_row3 + 13, 18.2)
    mark_cells(c, sd[2], y_row3 + h_row3 / 2)

    # Row 4 (Item 4)
    y_row4 = y_row3 + h_row3
    t4 = "Surat keterangan pengalaman kerja sesuai bidang {judul} minimal 1 tahun"
    ln4 = rich_lines(t4, 298, str(d.get("skema_judul") or ""))
    h_row4 = max(38, len(ln4) * 18.4 + 6)
    bot_row4 = y_row4 + h_row4
    hline(c, L, R, bot_row4, THICK)
    ctext(c, "4.", 152, y_row4 + h_row4 / 2)
    draw_rich(c, ln4, 176, y_row4 + 13, 18.4)
    mark_cells(c, sd[3], y_row4 + h_row4 / 2)

    # Vertical lines for 3.1
    for x in (L, R): vline(c, x, top1, bot_row4, THICK)
    for x in (171, 482, 668): vline(c, x, top1, bot_row4)
    vline(c, 569, top1 + 27, bot_row4)

    # 3.2 Bukti Administratif
    y_32_head = bot_row4 + 20
    heading(c, "3.2 Bukti Administratif", 128, y_32_head, 148)
    top_32 = y_32_head + 18
    hline(c, L, R, top_32, THICK)
    table_header(c, top_32, top_32 + 48, 151, 327, "Bukti Administratif")
    hline(c, 482, 668, top_32 + 27)
    hline(c, L, R, top_32 + 86, 1.2)

    # Row 1 (KTP)
    y_adm1 = top_32 + 86
    h_adm1 = 40
    hline(c, L, R, y_adm1 + h_adm1, 0.9)
    ctext(c, "1.", 151, y_adm1 + h_adm1 / 2)
    text(c, "Fotocopy Kartu Tanda Penduduk (KTP),", 180, y_adm1 + h_adm1 / 2, "Lib", UNIT + 0.5)
    sa = d.get("syarat_admin") or ["tidak_ada", "tidak_ada"]
    while len(sa) < 2:
        sa.append("tidak_ada")
    mark_cells(c, sa[0], y_adm1 + h_adm1 / 2)

    # Row 2 (Pas Foto)
    y_adm2 = y_adm1 + h_adm1
    h_adm2 = 46
    bot_adm = y_adm2 + h_adm2
    hline(c, L, R, bot_adm, THICK)
    ctext(c, "2.", 151, y_adm2 + h_adm2 / 2)
    text(c, "Pas photo ukuran 3x4 sebanyak 2 lembar", 180, y_adm2 + 16, "Lib", UNIT + 0.5)
    text(c, "dengan background warna merah.", 180, y_adm2 + 33, "Lib", UNIT + 0.5)
    mark_cells(c, sa[1], y_adm2 + h_adm2 / 2)

    # Vertical lines for 3.2
    for x in (L, R): vline(c, x, top_32, bot_adm, THICK)
    for x in (172, 482, 668): vline(c, x, top_32, bot_adm)
    vline(c, 569, top_32 + 27, bot_adm)

    # Rekomendasi (diisi oleh LSP)
    L_rek, R_rek = 126, 767
    top_rek = bot_adm + 18
    bot_rek = top_rek + 258
    mid_rek = top_rek + 130
    hline(c, L_rek, R_rek, top_rek, THICK); hline(c, L_rek, R_rek, bot_rek, THICK)
    vline(c, L_rek, top_rek, bot_rek, THICK); vline(c, R_rek, top_rek, bot_rek, THICK); vline(c, 475, top_rek, bot_rek, THICK)
    hline(c, L_rek, R_rek, mid_rek, THICK)
    for y_div in (top_rek + 20, top_rek + 56, mid_rek + 17, mid_rek + 55):
        hline(c, 475, R_rek, y_div)
    vline(c, 593, top_rek + 20, mid_rek); vline(c, 593, mid_rek + 17, bot_rek)

    heading(c, "Rekomendasi (diisi oleh LSP):", 136, top_rek + 11, 199)
    text(c, "Berdasarkan ketentuan persyaratan dasar,", 136, top_rek + 31)
    text(c, "maka pemohon:", 136, top_rek + 50)
    ok = bool(d.get("diterima", True))
    runs(c, [("Diterima", "Cal-B", not ok), ("/ ", "Cal-B", False), ("Tidak diterima", "Cal-B", ok),
             (" *)", "Cal", False), (" sebagai peserta sertifikasi", "Cal", False)], 136, top_rek + 70)
    text(c, "* coret yang tidak sesuai", 136, top_rek + 120)

    text(c, "Catatan :", 136, mid_rek + 10, "Cal-B")
    for i, l in enumerate(wrap_plain(str(d.get("catatan") or ""), "Cal", BODY, X(325))):
        text(c, l, 136, mid_rek + 34 + i * 18)

    text(c, "Pemohon/ Kandidat :", 484, top_rek + 11, "Cal-B")
    text(c, "Nama", 484, top_rek + 36); text(c, str(d.get("nama_lengkap") or "-"), 600, top_rek + 36, "Cal", BODY - 0.5)
    text(c, "Tanda tangan/", 484, top_rek + 78); text(c, "Tanggal", 484, top_rek + 96); text(c, str(d.get("tgl_pemohon") or "-"), 600, top_rek + 96)
    draw_signature(c, d.get("signature_asesi") or d.get("asesi_signature"), 600, top_rek + 93, 140, 38)

    text(c, "Admin LSP  :", 484, mid_rek + 10, "Cal-B")
    text(c, "Nama :", 484, mid_rek + 36); text(c, str(d.get("nama_admin") or "Admin LSP"), 600, mid_rek + 36)
    text(c, "Tanda tangan/", 484, mid_rek + 74); text(c, "Tanggal", 484, mid_rek + 92); text(c, str(d.get("tgl_admin") or "-"), 600, mid_rek + 92)
    draw_signature(c, d.get("signature_admin") or d.get("admin_signature"), 600, mid_rek + 89, 140, 38)
    pageno(c, pnum)

# --- Generator Utama dengan Flow Kontrol ---
def build(d=DATA, out=OUT):
    os.makedirs(os.path.dirname(out), exist_ok=True)
    c = canvas.Canvas(out, pagesize=(W, H))
    c.setTitle("FR.APL.01 Permohonan Sertifikasi Kompetensi")
    c.setAuthor("LSP PrO Indo - ReportLab Generator")

    page1(c, d, pnum=1)
    c.showPage()
    
    units = d.get("units", [])
    if len(units) > 9:
        page2_large(c, d, pnum=2)
        c.showPage()
        page3_large(c, d, pnum=3)
        c.showPage()
    else:
        page2(c, d, pnum=2)
        c.showPage()
        page3(c, d, pnum=3)
        c.showPage()

    c.save()
    print(f"OK -> Sukses generate: {out} | BODY={BODY:.2f}pt UNIT={UNIT:.2f}pt")

if __name__ == "__main__":
    import json
    out_file = OUT
    d = dict(DATA)
    if len(sys.argv) > 1:
        arg1 = sys.argv[1]
        if arg1.endswith(".json") and os.path.isfile(arg1):
            with open(arg1, "r", encoding="utf-8") as f:
                d.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        elif len(sys.argv) > 2 and sys.argv[2].endswith(".json") and os.path.isfile(sys.argv[2]):
            out_file = arg1
            with open(sys.argv[2], "r", encoding="utf-8") as f:
                d.update(json.load(f))
        else:
            out_file = arg1

    # Normalize units
    if "units" in d and isinstance(d["units"], list):
        norm_units = []
        for u in d["units"]:
            if isinstance(u, (list, tuple)) and len(u) >= 2:
                norm_units.append((str(u[0]), str(u[1])))
            elif isinstance(u, dict):
                norm_units.append((str(u.get("code", "")), str(u.get("title", ""))))
            else:
                norm_units.append((str(u), ""))
        d["units"] = norm_units

    # Normalize standar
    if "standar" in d:
        if isinstance(d["standar"], str):
            d["standar"] = [d["standar"], ""]
        elif isinstance(d["standar"], list) and len(d["standar"]) == 1:
            d["standar"].append("")

    build(d, out_file)
