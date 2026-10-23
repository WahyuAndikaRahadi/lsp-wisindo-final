#!/usr/bin/env python3
"""
Generator FR.IA.01 (Ceklis Observasi Aktivitas Praktik) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 9 halaman sesuai master dokumen BNSP.
"""

import os
import sys
import json
import io
import base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
ASSETS = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets")
CELLS_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "ia01_master_cells.json")
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_IA_01_generated.pdf")

def find_font(filename, fallback="LiberationSans-Regular.ttf"):
    candidates = [
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/liberation",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/dejavu",
    ]
    for d in candidates:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            return p
    for root, _, files in os.walk("/usr/share/fonts"):
        if filename in files:
            return os.path.join(root, filename)
    if fallback and fallback != filename:
        return find_font(fallback, fallback=None)
    return filename

font_map = {
    "Lib": ("LiberationSans-Regular.ttf", "LiberationSans-Regular.ttf"),
    "Lib-B": ("LiberationSans-Bold.ttf", "LiberationSans-Bold.ttf"),
    "Lib-I": ("LiberationSans-Italic.ttf", "LiberationSans-Italic.ttf"),
    "Cal": ("Carlito-Regular.ttf", "LiberationSans-Regular.ttf"),
    "Cal-B": ("Carlito-Bold.ttf", "LiberationSans-Bold.ttf"),
    "DJ": ("DejaVuSans.ttf", "DejaVuSans.ttf"),
}
for name, (ffile, fallback) in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile, fallback)))

PAGE_W, PAGE_H = 595.5, 842.5
Y = lambda top: PAGE_H - top
GRAY_FILL = HexColor("#D9D9D9")

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "tanggal_ttd": "19 Oktober 2026",
    "is_kompeten": True,
    "signature_asesi": None,
    "signature_asesor": None,
}

TABLE_COLS = {
    (1, 0): [72.6, 169.2, 556.6],
    (1, 1): [72.6, 200.3, 250.0, 264.2, 556.6],
    (1, 2): [72.6, 556.6],
    (1, 3): [72.6, 150.6, 172.3, 278.9, 556.6],
    (1, 4): [72.6, 194.6, 259.1, 273.3, 556.6],
    (1, 5): [72.6, 101.2, 194.6, 343.8, 414.7, 452.0, 497.1, 556.6],

    (2, 0): [72.6, 169.2, 556.4],
    (2, 1): [72.6, 101.2, 195.1, 343.9, 414.8, 452.1, 497.1, 556.4],
    (2, 2): [72.6, 195.1, 259.1, 273.3, 556.4],
    (2, 3): [72.6, 101.2, 195.1, 343.9, 414.8, 452.1, 497.1, 556.4],
    (2, 4): [72.6, 195.1, 259.1, 273.3, 556.4],
    (2, 5): [72.6, 101.2, 195.1, 343.9, 414.8, 452.1, 497.1, 556.4],

    (3, 0): [72.8, 169.2, 556.5],
    (3, 1): [72.8, 195.6, 259.1, 273.3, 556.5],
    (3, 2): [72.8, 101.6, 195.6, 344.3, 415.2, 452.4, 497.6, 556.5],
    (3, 3): [72.8, 195.6, 259.1, 273.3, 556.5],
    (3, 4): [72.8, 101.6, 195.6, 344.3, 415.2, 452.4, 497.6, 556.5],

    (4, 0): [72.6, 169.2, 555.1],
    (4, 1): [72.6, 150.8, 194.9, 328.0, 555.1],
    (4, 2): [72.6, 194.9, 259.1, 273.4, 555.1],
    (4, 3): [72.6, 101.2, 194.9, 343.8, 414.7, 456.4, 497.1, 555.1],

    (5, 0): [72.8, 169.2, 554.8],
    (5, 1): [72.8, 102.2, 193.9, 343.8, 413.0, 456.4, 497.0, 554.8],
    (5, 2): [72.8, 150.7, 193.9, 293.1, 554.8],
    (5, 3): [72.8, 193.9, 259.1, 273.3, 554.8],
    (5, 4): [72.8, 102.2, 183.0, 330.4, 413.0, 451.9, 497.0, 554.8],

    (6, 0): [73.9, 169.2, 556.3],
    (6, 1): [73.9, 103.3, 183.0, 330.4, 411.4, 451.9, 496.9, 556.3],

    (7, 0): [73.0, 169.2, 556.0],
    (7, 1): [73.0, 196.2, 259.1, 273.3, 556.0],
    (7, 2): [73.0, 101.8, 196.2, 344.5, 415.4, 452.6, 497.8, 556.0],

    (8, 0): [73.0, 169.2, 556.7],
    (8, 1): [73.0, 196.1, 259.1, 273.3, 556.7],
    (8, 2): [73.0, 101.8, 196.1, 344.4, 415.3, 452.6, 497.7, 556.7],

    (9, 0): [74.0, 169.2, 556.4],
    (9, 1): [74.0, 101.9, 197.1, 344.5, 415.4, 452.7, 497.8, 556.4],
    (9, 2): [74.0, 266.1, 400.8, 420.9, 556.4],
}

def clean_no(val):
    val = val.strip()
    digits = "".join([ch for ch in val if ch.isdigit()])
    if digits:
        if digits == "33":
            return "3"
        return digits[0]
    return ""

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

        corners = []
        for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)):
            px = im.getpixel(pt)
            if isinstance(px, tuple) and len(px) >= 3:
                corners.append((px[0], px[1], px[2]))
            elif isinstance(px, (int, float)):
                corners.append((int(px), int(px), int(px)))

        if not corners:
            corners = [(255, 255, 255)]

        bg_r = sum(c[0] for c in corners) / len(corners)
        bg_g = sum(c[1] for c in corners) / len(corners)
        bg_b = sum(c[2] for c in corners) / len(corners)

        pixels = im.load()
        if pixels is None:
            return None

        for y in range(h):
            for x in range(w):
                px = pixels[x, y]
                if not isinstance(px, tuple) or len(px) < 4:
                    continue
                r, g, b, a = int(px[0]), int(px[1]), int(px[2]), int(px[3])
                if a == 0:
                    continue
                is_green = (g > 80 and g > r * 1.15 and g > b * 1.15)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5
                is_bg = dist_bg < 45
                is_white = (r > 235 and g > 235 and b > 235)

                if is_green or is_bg or is_white:
                    pixels[x, y] = (0, 0, 0, 0)
                elif dist_bg < 75 or (g > r and g > b and g > 70):
                    factor = min(1.0, max(0.0, (dist_bg - 45) / 30.0))
                    pixels[x, y] = (r, g, b, int(a * factor))

        out_io = io.BytesIO()
        im.save(out_io, format="PNG")
        out_io.seek(0)
        return out_io
    except Exception:
        return None

def draw_signature(c, sig_data, x, y_bot, w=65.0, h=26.0):
    if not sig_data:
        return
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
            cleaned = clean_signature_image(raw)
            img = ImageReader(cleaned if cleaned else io.BytesIO(raw))
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            cleaned = clean_signature_image(sig_data)
            img = ImageReader(cleaned if cleaned else sig_data)
        else:
            return
        c.drawImage(img, x, Y(y_bot), w, h, mask="auto")
    except Exception:
        pass

def draw_header_box(c, pnum):
    top, bot = 21.5, 67.0
    cols = TABLE_COLS.get((pnum, 0), [72.6, 169.2, 556.6])
    x0, x_div, x1 = cols[0], cols[1], cols[2]
    
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    c.line(x_div, Y(top), x_div, Y(bot))
    
    logo_file = os.path.join(ASSETS, "logo.png")
    if not os.path.exists(logo_file):
        logo_file = os.path.join(PROJECT_ROOT, "public", "images", "logo-lsp-proindo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 15.0, Y(bot) + 5.0, 44.0, 39.0, mask="auto")
        
    c.setFont("Lib-B", 11.0)
    c.setFillColor(black)
    c.drawString(x_div + 38.0, Y(47.0), "Lembaga Sertifikasi Profesi Praktisi Otomotif")
    c.drawString(x_div + 130.0, Y(60.0), "LSP-PrO Indo")

def draw_cb(c, cx, cy, checked):
    sz = 8.5
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz)
    if checked:
        c.setFont("DJ", 8.5)
        c.setFillColor(black)
        c.drawCentredString(cx, cy - sz / 2 + 1.0, "✓")

def wrap_text(c, text, font, size, max_width):
    lines = []
    for par in text.split("\n"):
        words = par.split(" ")
        if not words:
            lines.append("")
            continue
        cur = ""
        for w in words:
            test = (cur + " " + w).strip()
            if c.stringWidth(test, font, size) <= max_width:
                cur = test
            else:
                if cur:
                    lines.append(cur)
                cur = w
        if cur:
            lines.append(cur)
    return lines

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    with open(CELLS_FILE) as f:
        master_pages = json.load(f)
        
    for p_data in master_pages:
        pnum = p_data["page"]
        
        # 1. Header Box on all pages
        draw_header_box(c, pnum)
        
        # 2. Page Number Footer
        c.setFont("Cal", 9.5)
        c.setFillColor(black)
        c.drawCentredString(307.8, Y(780.8), str(pnum))
        
        # 3. Page specific rendering
        if pnum == 1:
            render_page_1(c, p_data)
        elif pnum == 2:
            render_page_2(c, p_data)
        elif pnum == 3:
            render_page_3(c, p_data)
        elif pnum == 4:
            render_page_4(c, p_data)
        elif pnum == 5:
            render_page_5(c, p_data)
        elif pnum == 6:
            render_page_6(c, p_data)
        elif pnum == 7:
            render_page_7(c, p_data)
        elif pnum == 8:
            render_page_8(c, p_data)
        elif pnum == 9:
            render_page_9(c, p_data)
            
        c.showPage()
        
    c.save()
    print(f"OK -> Sukses generate FR.IA.01 (9 Halaman): {dest_path}")

def render_table_generic(c, pnum, tidx, tbl, font_size=7.5):
    cols = TABLE_COLS.get((pnum, tidx))
    if not cols:
        return
    rows = tbl["rows"]
    if not rows:
        return
    
    x0, x1 = cols[0], cols[-1]
    table_top = tbl["bbox"][1]
    table_bot = tbl["bbox"][3]
    
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    
    has_header = len(rows) > 1 and ("Elemen" in str(rows[0].get("cells", [])) or "Pencapaian" in str(rows[0].get("cells", [])))
    
    start_data_idx = 0
    hdr_bot = table_top
    if has_header:
        start_data_idx = 2
        hdr_bot = rows[0]["bbox"][3]
        ya_top = rows[1]["bbox"][1]
        
        c.line(x0, Y(hdr_bot), x1, Y(hdr_bot))
        c.line(cols[4], Y(ya_top), cols[6], Y(ya_top))
        c.line(cols[5], Y(ya_top), cols[5], Y(hdr_bot))
        
        c.setFont("Lib-B", font_size)
        c.drawCentredString((cols[0] + cols[1]) / 2, Y((table_top + hdr_bot) / 2 + 2.5), "No.")
        c.drawCentredString((cols[1] + cols[2]) / 2, Y((table_top + hdr_bot) / 2 + 2.5), "Elemen")
        c.drawCentredString((cols[2] + cols[3]) / 2, Y((table_top + hdr_bot) / 2 + 2.5), "Kriteria Unjuk Kerja")
        
        c.setFont("Lib-B", font_size - 0.5)
        lines = wrap_text(c, "Standar Industri atau Tempat Kerja", "Lib-B", font_size - 0.5, (cols[4] - cols[3]) - 6.0)
        lh = font_size + 1.0
        sy = Y((table_top + hdr_bot) / 2 - (len(lines) * lh) / 2 + 3.0)
        for li, ln in enumerate(lines):
            c.drawCentredString((cols[3] + cols[4]) / 2, sy - li * lh, ln)
            
        c.setFont("Lib-B", font_size)
        c.drawCentredString((cols[4] + cols[6]) / 2, Y((table_top + ya_top) / 2 + 2.5), "Pencapaian")
        c.drawCentredString((cols[4] + cols[5]) / 2, Y((ya_top + hdr_bot) / 2 + 2.5), "Ya")
        c.drawCentredString((cols[5] + cols[6]) / 2, Y((ya_top + hdr_bot) / 2 + 2.5), "Tidak")
        
        lines_pl = wrap_text(c, "Penilaian Lanjut", "Lib-B", font_size - 0.5, (cols[7] - cols[6]) - 6.0)
        sy_pl = Y((table_top + hdr_bot) / 2 - (len(lines_pl) * lh) / 2 + 3.0)
        for li, ln in enumerate(lines_pl):
            c.drawCentredString((cols[6] + cols[7]) / 2, sy_pl - li * lh, ln)
            
    c.line(cols[1], Y(table_bot), cols[1], Y(hdr_bot))
    c.line(cols[2], Y(table_bot), cols[2], Y(table_top))
    c.line(cols[3], Y(table_bot), cols[3], Y(table_top))
    c.line(cols[4], Y(table_bot), cols[4], Y(table_top))
    c.line(cols[5], Y(table_bot), cols[5], Y(rows[1]["bbox"][1] if has_header else table_top))
    c.line(cols[6], Y(table_bot), cols[6], Y(table_top))
    
    data_rows = rows[start_data_idx:]
    is_k = bool(DATA.get("is_kompeten", True))
    
    for idx, r in enumerate(data_rows):
        abs_idx = start_data_idx + idx
        r_top = r["bbox"][1]
        r_bot = rows[abs_idx + 1]["bbox"][1] if abs_idx + 1 < len(rows) else table_bot
        r_h = r_bot - r_top
        
        c0 = r["cells"][0] if len(r["cells"]) > 0 else ""
        c1 = r["cells"][1] if len(r["cells"]) > 1 else ""
        c2 = r["cells"][2] if len(r["cells"]) > 2 else ""
        c3 = r["cells"][3] if len(r["cells"]) > 3 else ""
        c4 = r["cells"][4] if len(r["cells"]) > 4 else ""
        c5 = r["cells"][5] if len(r["cells"]) > 5 else ""
        c6 = r["cells"][6] if len(r["cells"]) > 6 else ""
        
        num_clean = clean_no(c0)
        is_new_element = bool(num_clean)
        
        if idx > 0:
            if is_new_element:
                c.line(x0, Y(r_top), x1, Y(r_top))
            else:
                c.line(cols[2], Y(r_top), x1, Y(r_top))
                
        if is_new_element or (idx == 0 and c1.strip()):
            elem_bot = table_bot
            for next_r in data_rows[idx + 1:]:
                next_c0 = next_r["cells"][0] if len(next_r["cells"]) > 0 else ""
                if clean_no(next_c0):
                    elem_bot = next_r["bbox"][1]
                    break
            
            if num_clean:
                c.setFont("Lib-B", font_size)
                c.drawCentredString((cols[0] + cols[1]) / 2, Y((r_top + elem_bot) / 2 + 2.5), num_clean)
                
            if c1.strip():
                c.setFont("Lib", font_size)
                lines = wrap_text(c, c1.strip(), "Lib", font_size, (cols[2] - cols[1]) - 8.0)
                line_h = font_size + 2.0
                tot_h = len(lines) * line_h
                sy = Y((r_top + elem_bot) / 2 - tot_h / 2 + font_size * 0.8)
                for li, ln in enumerate(lines):
                    c.drawString(cols[1] + 4.0, sy - li * line_h, ln)
                    
        if c2.strip():
            clean_c2 = c2.strip()
            if clean_c2.startswith("r ") and len(clean_c2) > 2 and clean_c2[2].isdigit():
                clean_c2 = clean_c2[2:]
            c.setFont("Lib", font_size)
            lines = wrap_text(c, clean_c2, "Lib", font_size, (cols[3] - cols[2]) - 8.0)
            line_h = font_size + 2.0
            tot_h = len(lines) * line_h
            sy = Y(r_top + (r_h - tot_h) / 2 + font_size * 0.8) if r_h > tot_h else Y(r_top + 2.0 + font_size * 0.8)
            for li, ln in enumerate(lines):
                c.drawString(cols[2] + 4.0, sy - li * line_h, ln)
                
        if c3.strip():
            c.setFont("Lib", font_size)
            lines = wrap_text(c, c3.strip(), "Lib", font_size, (cols[4] - cols[3]) - 8.0)
            line_h = font_size + 2.0
            tot_h = len(lines) * line_h
            sy = Y(r_top + (r_h - tot_h) / 2 + font_size * 0.8) if r_h > tot_h else Y(r_top + 2.0 + font_size * 0.8)
            for li, ln in enumerate(lines):
                c.drawString(cols[3] + 4.0, sy - li * line_h, ln)
                
        if "☐" in c4 or "✓" in c4 or "☐" in c5 or "✓" in c5:
            cy = Y((r_top + r_bot) / 2)
            cx_ya = (cols[4] + cols[5]) / 2
            cx_tdk = (cols[5] + cols[6]) / 2
            draw_cb(c, cx_ya, cy, checked=is_k)
            draw_cb(c, cx_tdk, cy, checked=not is_k)
            
        if c6.strip():
            c.setFont("Lib", font_size)
            lines = wrap_text(c, c6.strip(), "Lib", font_size, (cols[7] - cols[6]) - 6.0)
            line_h = font_size + 2.0
            sy = Y(r_top + 2.0 + font_size * 0.8)
            for li, ln in enumerate(lines):
                c.drawString(cols[6] + 3.0, sy - li * line_h, ln)
                
    c.rect(x0, Y(table_bot), x1 - x0, table_bot - table_top)

# ================= PAGE 1 =================
def render_page_1(c, p_data):
    x0, x1 = 72.6, 556.6
    
    # Title
    c.setFont("Lib-B", 10.5)
    c.setFillColor(black)
    c.drawString(75.0, Y(82.1), "FR.IA.01. CL - CEKLIS OBSERVASI AKTIVITAS DI TEMPAT KERJA ATAU")
    c.drawString(127.9, Y(97.5), "TEMPAT KERJA SIMULASI")
    c.setFont("Lib-I", 8.0)
    c.drawString(75.0, Y(112.0), "*Coret yang tidak perlu")
    
    # Table 1: Metadata (top=123.8, bot=229.5)
    cols1 = TABLE_COLS[(1, 1)]
    r_meta = [123.8, 141.8, 159.3, 176.8, 194.3, 211.6, 229.5]
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(r_meta[-1]), x1 - x0, r_meta[-1] - r_meta[0])
    for y in r_meta[1:-1]:
        c.line(x0, Y(y), x1, Y(y))
    c.line(cols1[1], Y(r_meta[2]), cols1[1], Y(r_meta[0]))
    c.line(cols1[2], Y(r_meta[-1]), cols1[2], Y(r_meta[0]))
    c.line(cols1[3], Y(r_meta[-1]), cols1[3], Y(r_meta[0]))
    
    skema_nama = DATA.get("skema_nama") or DATA.get("skema_judul") or "Maintenance Gasoline Engine Injection System"
    skema_nomor = DATA.get("skema_nomor") or "02/LSP PrO Indo/I/2026"
    tuk_tipe = DATA.get("tuk_tipe") or DATA.get("tuk_nama") or "Sewaktu/Tempat Kerja/Mandiri*"
    asesor_nama = DATA.get("asesor_nama") or "Drs. Budi Santoso, M.T."
    asesi_nama = DATA.get("asesi_nama") or "Wahyu Andika Rahadi"
    tanggal = DATA.get("tanggal") or "19 Oktober 2026"

    c.setFont("Lib", 8.5)
    c.drawString(x0 + 4.0, Y(136.0), "Skema Sertifikasi (KKNI/Okupasi/Klaster)")
    c.drawString(cols1[1] + 6.0, Y(136.0), "Judul")
    c.drawString(cols1[2] + 4.0, Y(136.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(cols1[3] + 6.0, Y(136.0), skema_nama)
    
    c.setFont("Lib", 8.5)
    c.drawString(cols1[1] + 6.0, Y(152.0), "Nomor")
    c.drawString(cols1[2] + 4.0, Y(152.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(cols1[3] + 6.0, Y(152.0), skema_nomor)
    
    c.setFont("Lib", 8.5)
    c.drawString(x0 + 4.0, Y(170.0), "TUK")
    c.drawString(cols1[2] + 4.0, Y(170.0), ":")
    c.drawString(cols1[3] + 6.0, Y(170.0), tuk_tipe)
    
    c.drawString(x0 + 4.0, Y(187.0), "Nama Asesor")
    c.drawString(cols1[2] + 4.0, Y(187.0), ":")
    c.drawString(cols1[3] + 6.0, Y(187.0), asesor_nama)
    
    c.drawString(x0 + 4.0, Y(204.0), "Nama Asesi")
    c.drawString(cols1[2] + 4.0, Y(204.0), ":")
    c.drawString(cols1[3] + 6.0, Y(204.0), asesi_nama)
    
    c.drawString(x0 + 4.0, Y(221.0), "Tanggal")
    c.drawString(cols1[2] + 4.0, Y(221.0), ":")
    c.drawString(cols1[3] + 6.0, Y(221.0), tanggal)
    
    # Table 2: Panduan Bagi Asesor (top=258.4, bot=386.3)
    c.setFillColor(GRAY_FILL)
    c.rect(x0, Y(286.8), x1 - x0, 286.8 - 258.4, fill=1, stroke=0)
    c.rect(x0, Y(386.3), x1 - x0, 386.3 - 258.4, fill=0, stroke=1)
    c.line(x0, Y(286.8), x1, Y(286.8))
    
    c.setFont("Lib-B", 9.0)
    c.setFillColor(black)
    c.drawString(x0 + 6.0, Y(275.0), "PANDUAN BAGI ASESOR")
    
    c.setFont("Lib", 8.0)
    guide_lines = [
        "• Lengkapi nama unit kompetensi, elemen, dan kriteria unjuk kerja sesuai kolom dalam tabel.",
        "• Isilah standar industri atau tempat kerja",
        "• Beri tanda centang (✓) pada kolom “YA” jika Anda yakin asesi dapat melakukan/ mendemonstrasikan tugas sesuai KUK,",
        "  atau centang (✓) pada kolom “Tidak” bila sebaliknya.",
        "• Penilaian Lanjut diisi bila hasil belum dapat disimpulkan, untuk itu gunakan metode lain sehingga keputusan dapat dibuat.",
        "• Isilah kolom KUK sesuai dengan Unit Kompetensi/ SKKNI"
    ]
    for idx, gl in enumerate(guide_lines):
        c.drawString(x0 + 8.0, Y(303.0 + idx * 14.0), gl)
        
    # Table 3: Kelompok Pekerjaan 1 (top=401.5, bot=570.9)
    cols3 = TABLE_COLS[(1, 3)]
    c.rect(x0, Y(570.9), x1 - x0, 570.9 - 401.5)
    c.setFillColor(GRAY_FILL)
    c.rect(cols3[1], Y(440.0), x1 - cols3[1], 440.0 - 401.5, fill=1, stroke=0)
    c.rect(x0, Y(570.9), x1 - x0, 570.9 - 401.5, fill=0, stroke=1)
    
    c.line(cols3[1], Y(401.5), cols3[1], Y(570.9))
    c.line(cols3[2], Y(401.5), cols3[2], Y(570.9))
    c.line(cols3[3], Y(401.5), cols3[3], Y(570.9))
    
    r_k1 = [401.5, 440.0, 466.6, 492.5, 518.4, 544.3, 570.9]
    for y in r_k1[1:-1]:
        c.line(cols3[1], Y(y), x1, Y(y))
        
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(x0 + 6.0, Y(470.0), "Kelompok")
    c.drawString(x0 + 6.0, Y(483.0), "Pekerjaan 1")
    c.drawString(x0 + 6.0, Y(496.0), "General Service")
    
    c.drawCentredString((cols3[1] + cols3[2]) / 2, Y(424.0), "No")
    c.drawCentredString((cols3[2] + cols3[3]) / 2, Y(424.0), "Kode Unit")
    c.drawString(cols3[3] + 12.0, Y(424.0), "Judul Unit")
    
    units_k1 = [
        ("1.", "G.45OTO01.001.2", "Melaksanakan Keselamatan dan Kesehatan Kerja"),
        ("2.", "G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja"),
        ("3.", "G.45OTO01.007.2", "Membaca Gambar Teknik"),
        ("4.", "G.45OTO01.008.2", "Menggunakan Alat Ukur"),
        ("5.", "G.45OTO01.009.2", "Melaksanakan Diagnosis"),
    ]
    for idx, (no_u, code_u, title_u) in enumerate(units_k1):
        ymid = (r_k1[idx + 1] + r_k1[idx + 2]) / 2
        c.setFont("Lib", 8.0)
        c.drawCentredString((cols3[1] + cols3[2]) / 2, Y(ymid + 3.0), no_u)
        c.drawCentredString((cols3[2] + cols3[3]) / 2, Y(ymid + 3.0), code_u)
        c.drawString(cols3[3] + 6.0, Y(ymid + 3.0), title_u)
        
    # Table 4: Unit Kompetensi 1 Header (top=586.1, bot=633.2)
    cols4 = TABLE_COLS[(1, 4)]
    c.rect(x0, Y(633.2), x1 - x0, 633.2 - 586.1)
    c.line(cols4[1], Y(586.1), cols4[1], Y(633.2))
    c.line(cols4[2], Y(586.1), cols4[2], Y(633.2))
    c.line(cols4[3], Y(586.1), cols4[3], Y(633.2))
    c.line(cols4[1], Y(609.5), x1, Y(609.5))
    
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 6.0, Y(612.0), "Unit Kompetensi 1")
    c.drawString(cols4[1] + 6.0, Y(600.0), "Kode Unit")
    c.drawString(cols4[2] + 4.0, Y(600.0), ":")
    c.drawString(cols4[3] + 6.0, Y(600.0), "G.45OTO01.001.2")
    c.drawString(cols4[1] + 6.0, Y(623.0), "Judul Unit")
    c.drawString(cols4[2] + 4.0, Y(623.0), ":")
    c.drawString(cols4[3] + 6.0, Y(623.0), "Melaksanakan Keselamatan dan Kesehatan Kerja")
    
    # Table 5: KUK Table Page 1 (top=647.8, bot=772.1)
    render_table_generic(c, 1, 5, p_data["tables"][5], font_size=7.5)

# ================= PAGE 2 =================
def render_page_2(c, p_data):
    # Tbl 1: Continuation of Unit 1
    render_table_generic(c, 2, 1, p_data["tables"][1], font_size=7.5)
    
    # Tbl 2: Unit Kompetensi 2
    cols2 = TABLE_COLS[(2, 2)]
    render_unit_box(c, cols2, 2, "G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja", 219.0, 266.2)
    
    # Tbl 3: KUK Unit 2
    render_table_generic(c, 2, 3, p_data["tables"][3], font_size=7.5)
    
    # Tbl 4: Unit Kompetensi 3
    cols4 = TABLE_COLS[(2, 4)]
    render_unit_box(c, cols4, 3, "G.45OTO01.007.2", "Membaca Gambar Teknik", 491.0, 538.2)
    
    # Tbl 5: KUK Unit 3
    render_table_generic(c, 2, 5, p_data["tables"][5], font_size=7.5)

# ================= PAGE 3 =================
def render_page_3(c, p_data):
    # Tbl 1: Unit Kompetensi 4
    cols1 = TABLE_COLS[(3, 1)]
    render_unit_box(c, cols1, 4, "G.45OTO01.008.2", "Menggunakan Alat Ukur", 94.9, 142.1)
    
    # Tbl 2: KUK Unit 4
    render_table_generic(c, 3, 2, p_data["tables"][2], font_size=7.5)
    
    # Tbl 3: Unit Kompetensi 5
    cols3 = TABLE_COLS[(3, 3)]
    render_unit_box(c, cols3, 5, "G.45OTO01.009.2", "Melaksanakan Diagnosis", 390.5, 437.5)
    
    # Tbl 4: KUK Unit 5
    render_table_generic(c, 3, 4, p_data["tables"][4], font_size=7.5)

# ================= PAGE 4 =================
def render_page_4(c, p_data):
    x0, x1 = 72.6, 555.1
    # Tbl 1: Kelompok Pekerjaan 2
    cols1 = TABLE_COLS[(4, 1)]
    c.setLineWidth(0.6)
    c.rect(x0, Y(146.5), x1 - x0, 146.5 - 81.4)
    c.setFillColor(GRAY_FILL)
    c.rect(cols1[1], Y(120.2), x1 - cols1[1], 120.2 - 81.4, fill=1, stroke=0)
    c.rect(x0, Y(146.5), x1 - x0, 146.5 - 81.4, fill=0, stroke=1)
    
    c.line(cols1[1], Y(81.4), cols1[1], Y(146.5))
    c.line(cols1[2], Y(81.4), cols1[2], Y(146.5))
    c.line(cols1[3], Y(81.4), cols1[3], Y(146.5))
    c.line(cols1[1], Y(120.2), x1, Y(120.2))
    
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(x0 + 6.0, Y(98.0), "Kelompok")
    c.drawString(x0 + 6.0, Y(111.0), "Pekerjaan 2")
    c.drawString(x0 + 6.0, Y(124.0), "Engine")
    
    c.drawCentredString((cols1[1] + cols1[2]) / 2, Y(104.0), "No.")
    c.drawCentredString((cols1[2] + cols1[3]) / 2, Y(104.0), "Kode Unit")
    c.drawString(cols1[3] + 12.0, Y(104.0), "Judul Unit")
    
    c.setFont("Lib", 8.0)
    c.drawCentredString((cols1[1] + cols1[2]) / 2, Y(135.0), "1.")
    c.drawCentredString((cols1[2] + cols1[3]) / 2, Y(135.0), "G.45OTO01.010.2")
    c.drawString(cols1[3] + 6.0, Y(135.0), "Melakukan Perawatan Berkala Engine")
    
    # Tbl 2: Unit Kompetensi 1
    cols2 = TABLE_COLS[(4, 2)]
    render_unit_box(c, cols2, 1, "G.45OTO01.010.2", "Melakukan Perawatan Berkala Engine", 161.7, 208.9)
    
    # Tbl 3: KUK Unit 1 Part 1
    render_table_generic(c, 4, 3, p_data["tables"][3], font_size=7.5)

# ================= PAGE 5 =================
def render_page_5(c, p_data):
    x0, x1 = 72.8, 554.8
    # Tbl 1: Continuation of Unit 1
    render_table_generic(c, 5, 1, p_data["tables"][1], font_size=7.5)
    
    # Tbl 2: Kelompok Pekerjaan 3
    cols2 = TABLE_COLS[(5, 2)]
    c.setLineWidth(0.6)
    c.rect(x0, Y(379.0), x1 - x0, 379.0 - 258.9)
    c.setFillColor(GRAY_FILL)
    c.rect(cols2[1], Y(297.6), x1 - cols2[1], 297.6 - 258.9, fill=1, stroke=0)
    c.rect(x0, Y(379.0), x1 - x0, 379.0 - 258.9, fill=0, stroke=1)
    
    c.line(cols2[1], Y(258.9), cols2[1], Y(379.0))
    c.line(cols2[2], Y(258.9), cols2[2], Y(379.0))
    c.line(cols2[3], Y(258.9), cols2[3], Y(379.0))
    
    r_k3 = [258.9, 297.6, 324.2, 352.5, 379.0]
    for y in r_k3[1:-1]:
        c.line(cols2[1], Y(y), x1, Y(y))
        
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(x0 + 6.0, Y(315.0), "Kelompok")
    c.drawString(x0 + 6.0, Y(328.0), "Pekerjaan 3")
    
    c.drawCentredString((cols2[1] + cols2[2]) / 2, Y(281.0), "No.")
    c.drawCentredString((cols2[2] + cols2[3]) / 2, Y(281.0), "Kode Unit")
    c.drawString(cols2[3] + 12.0, Y(281.0), "Judul Unit")
    
    units_k3 = [
        ("1.", "G.45OTO01.065.2", "Memelihara Baterai"),
        ("2.", "G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan"),
        ("3.", "G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine"),
    ]
    for idx, (no_u, code_u, title_u) in enumerate(units_k3):
        ymid = (r_k3[idx + 1] + r_k3[idx + 2]) / 2
        c.setFont("Lib", 8.0)
        c.drawCentredString((cols2[1] + cols2[2]) / 2, Y(ymid + 3.0), no_u)
        c.drawCentredString((cols2[2] + cols2[3]) / 2, Y(ymid + 3.0), code_u)
        c.drawString(cols2[3] + 6.0, Y(ymid + 3.0), title_u)
        
    # Tbl 3: Unit Kompetensi 1
    cols3 = TABLE_COLS[(5, 3)]
    render_unit_box(c, cols3, 1, "G.45OTO01.065.2", "Memelihara Baterai", 394.2, 441.3)
    
    # Tbl 4: KUK Unit 1 Part 1
    render_table_generic(c, 5, 4, p_data["tables"][4], font_size=7.5)

# ================= PAGE 6 =================
def render_page_6(c, p_data):
    # Tbl 1: Continuation of Unit 1
    render_table_generic(c, 6, 1, p_data["tables"][1], font_size=7.5)

# ================= PAGE 7 =================
def render_page_7(c, p_data):
    # Tbl 1: Unit Kompetensi 2
    cols1 = TABLE_COLS[(7, 1)]
    render_unit_box(c, cols1, 2, "G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan", 81.4, 128.7)
    
    # Tbl 2: KUK Unit 2
    render_table_generic(c, 7, 2, p_data["tables"][2], font_size=7.5)

# ================= PAGE 8 =================
def render_page_8(c, p_data):
    # Tbl 1: Unit Kompetensi 3
    cols1 = TABLE_COLS[(8, 1)]
    render_unit_box(c, cols1, 3, "G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine", 81.4, 128.6)
    
    # Tbl 2: KUK Unit 3 Part 1
    render_table_generic(c, 8, 2, p_data["tables"][2], font_size=7.5)

# ================= PAGE 9 =================
def render_page_9(c, p_data):
    x0, x1 = 74.0, 556.4
    # Tbl 1: Continuation of Unit 3
    render_table_generic(c, 9, 1, p_data["tables"][1], font_size=7.5)
    
    # Feedback Text
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(78.6, Y(238.4), "Umpan Balik untuk asesi:")
    
    # Table 2: Rekomendasi & Tanda Tangan (top=312.4, bot=513.5)
    cols2 = TABLE_COLS[(9, 2)]
    r_sig = [312.4, 334.2, 363.3, 406.4, 428.3, 450.9, 473.1, 513.5]
    c.setLineWidth(0.6)
    c.rect(x0, Y(513.5), x1 - x0, 513.5 - 312.4)
    
    # Split column Left (Rekomendasi) & Right (Signatures)
    c.line(cols2[1], Y(312.4), cols2[1], Y(513.5))
    
    # Right column lines
    c.line(cols2[2], Y(334.2), cols2[2], Y(406.4))
    c.line(cols2[3], Y(334.2), cols2[3], Y(406.4))
    
    c.line(cols2[2], Y(428.3), cols2[2], Y(513.5))
    c.line(cols2[3], Y(428.3), cols2[3], Y(513.5))
    
    for y in r_sig[1:]:
        c.line(cols2[1], Y(y), x1, Y(y))
        
    is_k = bool(DATA.get("is_kompeten", True))

    # Left Column: Rekomendasi
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 6.0, Y(326.0), "Rekomendasi:")
    
    draw_cb(c, x0 + 12.0, Y(338.0), is_k)
    c.setFont("Lib", 7.5)
    c.drawString(x0 + 22.0, Y(336.0), "Asesi telah memenuhi pencapaian")
    c.drawString(x0 + 22.0, Y(347.0), "seluruh kriteria unjuk kerja,")
    c.setFont("Lib-B", 7.5)
    c.drawString(x0 + 22.0, Y(358.0), "direkomendasikan KOMPETEN")
    
    draw_cb(c, x0 + 12.0, Y(375.0), not is_k)
    c.setFont("Lib", 7.5)
    c.drawString(x0 + 22.0, Y(373.0), "Asesi belum memenuhi pencapaian")
    c.drawString(x0 + 22.0, Y(384.0), "seluruh kriteria unjuk kerja,")
    c.drawString(x0 + 22.0, Y(395.0), "direkomendasikan BELUM KOMPETEN")
    c.drawString(x0 + 22.0, Y(408.0), "pada:")
    c.drawString(x0 + 22.0, Y(419.0), "Kelompok Pekerjaan: …")
    c.drawString(x0 + 22.0, Y(430.0), "Unit: …..")
    c.drawString(x0 + 22.0, Y(441.0), "Elemen: …..")
    c.drawString(x0 + 22.0, Y(452.0), "KUK: …….")
    
    asesi_nama = DATA.get("asesi_nama") or ""
    asesor_nama = DATA.get("asesor_nama") or ""
    asesor_reg = DATA.get("asesor_no_reg") or DATA.get("asesor_reg") or ""
    tanggal_ttd = DATA.get("tanggal_ttd") or DATA.get("tanggal") or ""

    # Right Column: Asesi
    c.setFont("Lib-B", 8.5)
    c.drawString(cols2[1] + 6.0, Y(326.0), "Asesi")
    c.setFont("Lib", 8.0)
    c.drawString(cols2[1] + 6.0, Y(350.0), "Nama")
    c.drawString(cols2[2] + 4.0, Y(350.0), ":")
    c.drawString(cols2[3] + 6.0, Y(350.0), asesi_nama)
    
    c.drawString(cols2[1] + 6.0, Y(386.0), "Tanda tangan/ Tanggal")
    c.drawString(cols2[2] + 4.0, Y(386.0), ":")
    sig_asesi = DATA.get("signature_asesi") or DATA.get("asesi_signature")
    if sig_asesi:
        draw_signature(c, sig_asesi, cols2[3] + 6.0, 402.0, w=55.0, h=25.0)
        c.drawString(cols2[3] + 66.0, Y(386.0), tanggal_ttd)
    else:
        c.drawString(cols2[3] + 6.0, Y(386.0), f"[Ttd Digital] - {tanggal_ttd}")
    
    # Right Column: Asesor
    c.setFont("Lib-B", 8.5)
    c.drawString(cols2[1] + 6.0, Y(419.0), "Asesor")
    c.setFont("Lib", 8.0)
    c.drawString(cols2[1] + 6.0, Y(441.0), "Nama")
    c.drawString(cols2[2] + 4.0, Y(441.0), ":")
    c.drawString(cols2[3] + 6.0, Y(441.0), asesor_nama)
    
    c.drawString(cols2[1] + 6.0, Y(463.0), "No. Reg")
    c.drawString(cols2[2] + 4.0, Y(463.0), ":")
    c.drawString(cols2[3] + 6.0, Y(463.0), asesor_reg)
    
    c.drawString(cols2[1] + 6.0, Y(495.0), "Tanda tangan/ Tanggal")
    c.drawString(cols2[2] + 4.0, Y(495.0), ":")
    sig_asesor = DATA.get("signature_asesor") or DATA.get("assessor_signature")
    if sig_asesor:
        draw_signature(c, sig_asesor, cols2[3] + 6.0, 510.0, w=55.0, h=25.0)
        c.drawString(cols2[3] + 66.0, Y(495.0), tanggal_ttd)
    else:
        c.drawString(cols2[3] + 6.0, Y(495.0), f"[Ttd Digital] - {tanggal_ttd}")

def render_unit_box(c, cols, unit_num, unit_code, unit_title, top, bot):
    x0, x1 = cols[0], cols[-1]
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    c.line(cols[1], Y(top), cols[1], Y(bot))
    c.line(cols[2], Y(top), cols[2], Y(bot))
    c.line(cols[3], Y(top), cols[3], Y(bot))
    mid_y = (top + bot) / 2
    c.line(cols[1], Y(mid_y), x1, Y(mid_y))
    
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(x0 + 6.0, Y(mid_y + 3.0), f"Unit Kompetensi {unit_num}")
    
    c.drawString(cols[1] + 6.0, Y((top + mid_y) / 2 + 3.0), "Kode Unit")
    c.drawString(cols[2] + 4.0, Y((top + mid_y) / 2 + 3.0), ":")
    c.drawString(cols[3] + 6.0, Y((top + mid_y) / 2 + 3.0), unit_code)
    
    c.drawString(cols[1] + 6.0, Y((mid_y + bot) / 2 + 3.0), "Judul Unit")
    c.drawString(cols[2] + 4.0, Y((mid_y + bot) / 2 + 3.0), ":")
    c.drawString(cols[3] + 6.0, Y((mid_y + bot) / 2 + 3.0), unit_title)

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = OUT_FILE
    if len(sys.argv) > 1:
        arg1 = sys.argv[1]
        if arg1.endswith(".json") and os.path.isfile(arg1):
            with open(arg1, "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        elif len(sys.argv) > 2 and sys.argv[2].endswith(".json") and os.path.isfile(sys.argv[2]):
            out_file = arg1
            with open(sys.argv[2], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
        else:
            out_file = arg1
    render(out_file)
