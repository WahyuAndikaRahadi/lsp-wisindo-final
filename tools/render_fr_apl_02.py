#!/usr/bin/env python3
"""
Generator FR.APL.02 (Asesmen Mandiri) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 6 halaman sesuai master document BNSP.
"""

import os
import sys
import json
import io
import base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor, Color
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

BASE = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(BASE, ".."))
OUT = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/FR_APL_02_generated.pdf")
ASSETS = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/assets")
CELLS_FILE = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/apl02_master_cells_exact.json")

# Dimensions A4: 595.28 x 841.89 pt
PAGE_W, PAGE_H = 595.28, 841.89
Y = lambda top: PAGE_H - top

try:
    from font_helper import register_reportlab_fonts
except ImportError:
    sys.path.insert(0, BASE)
    from font_helper import register_reportlab_fonts

register_reportlab_fonts(["Lib", "Lib-B", "Lib-I", "Cal", "Cal-B", "DJ", "Times"])

BLUE_NUM = Color(0.267, 0.447, 0.769)

DATA = {
    "skema_judul": "Skema Sertifikasi Klaster Maintenance Gasoline Engine Injection System",
    "skema_nomor": "Skema 02/LSP PrO Indo/XI/2025",
    "asesi_nama": "Wahyu Andika Rahadi",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_reg": "MET.000.001234 2022",
    "tanggal": "19/10/2026",
    "is_kompeten": True,
}

def draw_header(c):
    x0, x1 = 50.4, 536.2
    top, bot = 35.4, 99.3
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    
    # Logo divider line
    x_div = 147.7
    c.line(x_div, Y(top), x_div, Y(bot))
    
    # Logo image
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, 67.0, Y(97.2), 69.4, 61.4, mask="auto")
        
    # Header text with exact initial capitals in bold
    words = [
        ("L", "Lib-B", 174.2),
        ("embaga ", "Lib", 181.1),
        ("S", "Lib-B", 227.2),
        ("ertifikasi ", "Lib", 234.8),
        ("P", "Lib-B", 282.9),
        ("rofesi ", "Lib", 290.8),
        ("P", "Lib-B", 323.6),
        ("raktisi ", "Lib", 331.4),
        ("O", "Lib-B", 366.6),
        ("tomotif  ", "Lib", 375.9),
        ("LSP-PrO Indo", "Lib-B", 421.1),
    ]
    for text_val, f_name, pos_x in words:
        c.setFont(f_name, 12.0)
        c.setFillColor(black)
        c.drawString(pos_x, 762.6, text_val)

def draw_cb(c, cx, cy, checked=False):
    sz = 8.6
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz, fill=0, stroke=1)
    if checked:
        c.setLineWidth(1.4)
        c.setStrokeColor(black)
        p = c.beginPath()
        p.moveTo(cx - 2.6, cy - 0.2)
        p.lineTo(cx - 0.6, cy - 2.6)
        p.lineTo(cx + 2.8, cy + 2.8)
        c.drawPath(p, stroke=1, fill=0)

def draw_page_num(c, pnum):
    c.setFont("Times", 12.0)
    c.setFillColor(BLUE_NUM)
    c.drawCentredString(PAGE_W / 2, 42.0, str(pnum))

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

        # Sample corner colors to detect background
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

        # Auto-crop to bounding box of actual non-zero strokes
        bbox = im.getbbox()
        if bbox:
            pad = 2
            crop_box = (
                max(0, bbox[0] - pad),
                max(0, bbox[1] - pad),
                min(w, bbox[2] + pad),
                min(h, bbox[3] + pad)
            )
            im = im.crop(crop_box)

        # Boost ink opacity & contrast: make non-transparent strokes dark solid slate/black
        for y in range(im.height):
            for x in range(im.width):
                px = im.getpixel((x, y))
                if px[3] > 25:
                    im.putpixel((x, y), (15, 23, 42, 255))

        out_io = io.BytesIO()
        im.save(out_io, format="PNG")
        out_io.seek(0)
        return out_io
    except Exception:
        return None

def draw_signature(c, sig_data, x, y_bot, w=75.0, h=26.0):
    if not sig_data:
        return
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            with open(sig_data, "rb") as f:
                raw = f.read()
        else:
            return

        cleaned_io = clean_signature_image(raw)
        if not cleaned_io:
            return

        im = Image.open(cleaned_io)
        im_w, im_h = im.size
        if im_w == 0 or im_h == 0:
            return

        aspect = float(im_w) / float(im_h)
        if aspect > (w / h):
            draw_w = w
            draw_h = w / aspect
        else:
            draw_h = h
            draw_w = h * aspect

        y_bot_native = Y(y_bot)
        cleaned_io.seek(0)
        img = ImageReader(cleaned_io)
        c.drawImage(img, x, y_bot_native, draw_w, draw_h, mask="auto")
    except Exception:
        pass

def draw_unit_header(c, num, code, title, y_top, y_bot, L=53.4, R=541.2):
    c1, c2, c3 = 160.2, 227.5, 242.9
    y_mid = (y_top + y_bot) / 2
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(L, Y(y_bot), R - L, y_bot - y_top)
    c.line(c1, Y(y_mid), R, Y(y_mid))
    
    c.line(c1, Y(y_bot), c1, Y(y_top))
    c.line(c2, Y(y_bot), c2, Y(y_top))
    c.line(c3, Y(y_bot), c3, Y(y_top))
    
    c.setFont("Lib-B", 10.0)
    c.setFillColor(black)
    c.drawString(L + 7.5, Y(y_mid + 3.0), f"Unit Kompetensi {num}")
    
    c.setFont("Lib-B", 10.0)
    c.drawString(c1 + 6.0, Y(y_top + 14.5), "Kode Unit")
    c.drawString(c2 + 5.0, Y(y_top + 14.5), ":")
    c.drawString(c3 + 6.0, Y(y_top + 14.5), code)
    
    c.drawString(c1 + 6.0, Y(y_mid + 14.5), "Judul Unit")
    c.drawString(c2 + 5.0, Y(y_mid + 14.5), ":")
    c.drawString(c3 + 6.0, Y(y_mid + 14.5), title)

def parse_element_lines(cell_text):
    result = []
    lines = cell_text.split('\n')
    current_mode = "ele"
    for line in lines:
        line_s = line.strip()
        if not line_s:
            continue
        if 'Elemen:' in line_s or line_s.startswith("1. Elemen") or line_s.startswith("2. Elemen") or line_s.startswith("3. Elemen"):
            result.append((line_s, 'ele', 23.8))
            current_mode = "ele"
        elif '•' in line_s or 'Kriteria Unjuk Kerja:' in line_s:
            result.append((line_s, 'hdr_kuk', 59.8))
            current_mode = "hdr"
        elif any(line_s.startswith(f'{i}.{j} ') or line_s.startswith(f'{i}.{j}.') for i in range(1, 10) for j in range(1, 20)):
            result.append((line_s, 'kuk_num', 59.8))
            current_mode = "kuk"
        else:
            if current_mode == "ele":
                result.append((line_s, 'ele_cont', 41.8))
            elif current_mode == "kuk":
                result.append((line_s, 'kuk_cont', 77.8))
            else:
                result.append((line_s, 'kuk_cont', 59.8))
    return result

def wrap_text_width(text, font_name, font_size, max_w):
    words = text.split()
    if not words:
        return []
    lines = []
    cur_line = []
    for w in words:
        test_line = " ".join(cur_line + [w])
        if pdfmetrics.stringWidth(test_line, font_name, font_size) <= max_w:
            cur_line.append(w)
        else:
            if cur_line:
                lines.append(" ".join(cur_line))
                cur_line = [w]
            else:
                lines.append(w)
                cur_line = []
    if cur_line:
        lines.append(" ".join(cur_line))
    return lines

def draw_elements_table_generic(c, L, R, y_hdr_top, y_hdr_bot, rows_data, is_first_page_of_table=True):
    c1, c2, c3 = 321.4, 348.2, 375.2
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    
    top_y = y_hdr_top if is_first_page_of_table else rows_data[0]["y_top"]
    bot_y = rows_data[-1]["y_bot"]
    c.rect(L, Y(bot_y), R - L, bot_y - top_y)
    
    # Vertical lines
    c.line(c1, Y(bot_y), c1, Y(top_y))
    c.line(c2, Y(bot_y), c2, Y(top_y))
    c.line(c3, Y(bot_y), c3, Y(top_y))
    
    if is_first_page_of_table:
        # Header horizontal line
        c.line(L, Y(y_hdr_bot), R, Y(y_hdr_bot))
        c.setFont("Lib-B", 10.0)
        c.setFillColor(black)
        c.drawString(L + 6.0, Y(y_hdr_top + 9.5), "Dapatkah Saya ................?")
        c.drawCentredString((c1 + c2) / 2, Y(y_hdr_top + 9.5), "K")
        c.drawCentredString((c2 + c3) / 2, Y(y_hdr_top + 9.5), "BK")
        c.drawString(c3 + 8.0, Y(y_hdr_top + 9.5), "Bukti yang relevan")
    
    # Rows
    for r_idx, r in enumerate(rows_data):
        yt = r["y_top"]
        yb = r["y_bot"]
        if r_idx < len(rows_data) - 1:
            c.line(L, Y(yb), R, Y(yb))
            
        # Draw text lines in Col 0
        parsed = parse_element_lines(r["cell_text"])
        cur_y = yt + 12.0
        for text_str, style, indent in parsed:
            if style == "ele" or style == "ele_cont":
                c.setFont("Lib-B", 10.0)
            elif style == "hdr_kuk":
                c.setFont("Lib-B", 10.0)
            else:
                c.setFont("Lib", 10.0)
            c.setFillColor(black)
            c.drawString(L + indent, Y(cur_y), text_str)
            cur_y += 11.5
            
        # Draw Checkboxes
        if r.get("has_checkbox", True):
            cb_y = (Y(yt) + Y(yb)) / 2
            is_k = r.get("k", True)
            if "row_index" in r and "answers" in DATA and isinstance(DATA["answers"], list):
                idx = r["row_index"]
                if idx < len(DATA["answers"]):
                    val = DATA["answers"][idx]
                    if isinstance(val, bool):
                        is_k = val
                    elif isinstance(val, str):
                        is_k = (val.upper() == "K")
            draw_cb(c, (c1 + c2) / 2, cb_y, is_k)
            draw_cb(c, (c2 + c3) / 2, cb_y, not is_k)

        # Draw Bukti yang relevan in Col 3 (c3 to R)
        proof_text = ""
        idx = r.get("row_index")
        if idx is not None and "proofs" in DATA and isinstance(DATA["proofs"], list):
            if idx < len(DATA["proofs"]):
                proof_text = str(DATA["proofs"][idx] or "")
        if not proof_text:
            proof_text = str(DATA.get("default_proof") or DATA.get("bukti_relevan") or "Ijazah SMK Otomotif")

        if proof_text:
            c.setFillColor(black)
            font_name = "Lib"
            font_size = 8.5
            c.setFont(font_name, font_size)
            max_text_w = (R - 6.0) - (c3 + 8.0)
            wrapped_lines = wrap_text_width(proof_text, font_name, font_size, max_text_w)
            
            line_h = 10.5
            total_h = len(wrapped_lines) * line_h
            y_mid = (yt + yb) / 2
            start_y = y_mid - (total_h / 2) + 8.0
            
            for line_idx, line_str in enumerate(wrapped_lines):
                c.drawString(c3 + 8.0, Y(start_y + line_idx * line_h), line_str)

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    with open(CELLS_FILE) as f:
        master_pages = json.load(f)
        
    for p_data in master_pages:
        pnum = p_data["page"]
        
        # 1. Header Box on every page
        draw_header(c)
        
        # 2. Render Page Content
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
            
        # 3. Page Number
        draw_page_num(c, pnum)
        c.showPage()
        
    c.save()
    print(f"OK -> Sukses generate APL 02 (6 Halaman): {dest_path}")

# ================= PAGE 1 =================
def render_page_1(c, p_data):
    L, R = 53.4, 541.6
    
    # Title
    c.setFont("Lib-B", 10.0)
    c.setFillColor(black)
    c.drawString(72.03, 718.37, "FR.APL.02. ASESMEN MANDIRI")
    
    # Table 1: Skema Table (top=132.4 to 180.4)
    c1, c2, c3 = 167.2, 220.5, 234.7
    r_top, r_mid, r_bot = 132.4, 159.3, 180.4
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(L, Y(r_bot), R - L, r_bot - r_top)
    c.line(c1, Y(r_mid), R, Y(r_mid))
    
    c.line(c1, Y(r_bot), c1, Y(r_top))
    c.line(c2, Y(r_bot), c2, Y(r_top))
    c.line(c3, Y(r_bot), c3, Y(r_top))
    
    c.setFont("Lib-B", 10.0)
    c.drawString(L + 5.5, Y(149.5), "Skema Sertifikasi")
    c.setFont("Lib", 8.0)
    c.drawString(L + 5.5, Y(161.0), "(KKNI/Okupasi/Klaster)")
    
    c.setFont("Lib-B", 9.5)
    c.drawString(c1 + 5.5, Y(149.5), "Judul")
    c.drawString(c2 + 5.0, Y(149.5), ":")
    skema_judul = str(DATA.get("skema_judul") or "Skema Sertifikasi Klaster Maintenance Gasoline Engine Injection System")
    if len(skema_judul) > 50 and " " in skema_judul:
        words = skema_judul.split()
        mid = len(words) // 2
        line1 = " ".join(words[:mid])
        line2 = " ".join(words[mid:])
        c.drawString(c3 + 5.5, Y(146.0), line1)
        c.drawString(c3 + 5.5, Y(159.0), line2)
    else:
        c.drawString(c3 + 5.5, Y(149.5), skema_judul)
    
    c.drawString(c1 + 5.5, Y(174.5), "Nomor")
    c.drawString(c2 + 5.0, Y(174.5), ":")
    c.drawString(c3 + 5.5, Y(174.5), str(DATA.get("skema_nomor") or "Skema 02/LSP PrO Indo/XI/2025"))
    
    # Table 2: Panduan Asesmen Mandiri (top=193.0 to 294.2)
    p_top, p_hdr_bot, p_bot = 193.0, 207.6, 294.2
    c.rect(L, Y(p_bot), R - L, p_bot - p_top)
    c.line(L, Y(p_hdr_bot), R, Y(p_hdr_bot))
    
    c.setFont("Lib-B", 10.0)
    c.drawString(L + 6.0, Y(204.5), "PANDUAN ASESMEN MANDIRI")
    
    c.setFont("Lib", 10.0)
    c.drawString(L + 6.0, Y(224.5), "Instruksi:")
    c.drawString(L + 6.0, Y(238.5), "•  Baca setiap pertanyaan di kolom sebelah kiri")
    c.drawString(L + 6.0, Y(253.5), "•  Beri tanda centang (☑) pada kotak jika Anda yakin dapat melakukan tugas yang dijelaskan.")
    c.drawString(L + 6.0, Y(269.0), "•  Isi kolom di sebelah kanan dengan menuliskan bukti yang relevan anda miliki untuk menunjukkan")
    c.drawString(L + 24.0, Y(284.0), "bahwa anda melakukan pekerjaan")
    
    # Table 3: Unit 1 Header (306.2 to 346.9)
    draw_unit_header(c, 1, "G.45OTO01.001.2", "Melaksanakan Keselamatan dan Kesehatan Kerja", 306.2, 346.9, L, R)
    
    # Table 4: Unit 1 Elements (356.6 to 532.0)
    t4_data = p_data["tables"][4]["data"]
    draw_elements_table_generic(c, L, R, 356.6, 368.6, [
        {"y_top": 368.6, "y_bot": 438.8, "cell_text": t4_data[1][0], "has_checkbox": True, "k": True, "row_index": 0},
        {"y_top": 438.8, "y_bot": 532.0, "cell_text": t4_data[2][0], "has_checkbox": True, "k": True, "row_index": 1},
    ])
    
    # Table 5: Unit 2 Header (541.7 to 582.3)
    draw_unit_header(c, 2, "G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja", 541.7, 582.3, L, R)
    
    # Table 6: Unit 2 Elements (594.4 to 769.8)
    t6_data = p_data["tables"][6]["data"]
    draw_elements_table_generic(c, L, R, 594.4, 606.4, [
        {"y_top": 606.4, "y_bot": 688.0, "cell_text": t6_data[1][0], "has_checkbox": True, "k": True, "row_index": 2},
        {"y_top": 688.0, "y_bot": 769.8, "cell_text": t6_data[2][0], "has_checkbox": True, "k": True, "row_index": 3},
    ])

# ================= PAGE 2 =================
def render_page_2(c, p_data):
    L, R = 53.6, 541.2
    
    # Unit 3 Header (113.4 to 154.1)
    draw_unit_header(c, 3, "G.45OTO01.007.2", "Membaca Gambar Teknik", 113.4, 154.1, L, R)
    
    # Unit 3 Elements (166.1 to 307.0)
    t2_data = p_data["tables"][2]["data"]
    draw_elements_table_generic(c, L, R, 166.1, 178.1, [
        {"y_top": 178.1, "y_bot": 236.8, "cell_text": t2_data[1][0], "has_checkbox": True, "k": True, "row_index": 4},
        {"y_top": 236.8, "y_bot": 307.0, "cell_text": t2_data[2][0], "has_checkbox": True, "k": True, "row_index": 5},
    ])
    
    # Unit 4 Header (319.0 to 359.6)
    draw_unit_header(c, 4, "G.45OTO01.008.2", "Menggunakan Alat Ukur", 319.0, 359.6, L, R)
    
    # Unit 4 Elements (371.7 to 547.0)
    t4_data = p_data["tables"][4]["data"]
    draw_elements_table_generic(c, L, R, 371.7, 383.7, [
        {"y_top": 383.7, "y_bot": 476.8, "cell_text": t4_data[1][0], "has_checkbox": True, "k": True, "row_index": 6},
        {"y_top": 476.8, "y_bot": 547.0, "cell_text": t4_data[2][0], "has_checkbox": True, "k": True, "row_index": 7},
    ])
    
    # Unit 5 Header (559.0 to 599.7)
    draw_unit_header(c, 5, "G.45OTO01.009.2", "Melaksanakan Diagnosis", 559.0, 599.7, L, R)
    
    # Unit 5 Elements Part 1 (611.7 to 775.5)
    t6_data = p_data["tables"][6]["data"]
    draw_elements_table_generic(c, L, R, 611.7, 623.7, [
        {"y_top": 623.7, "y_bot": 693.9, "cell_text": t6_data[1][0], "has_checkbox": True, "k": True, "row_index": 8},
        {"y_top": 693.9, "y_bot": 775.5, "cell_text": t6_data[2][0], "has_checkbox": True, "k": True, "row_index": 9},
    ])

# ================= PAGE 3 =================
def render_page_3(c, p_data):
    L, R = 53.4, 541.2
    
    # Table 1: Continuation of Unit 5 Elemen 2 (113.4 to 148.4)
    t1_data = p_data["tables"][1]["data"]
    draw_elements_table_generic(c, L, R, 113.4, 113.4, [
        {"y_top": 113.4, "y_bot": 148.4, "cell_text": t1_data[0][0], "has_checkbox": True, "k": True, "row_index": 10},
    ], is_first_page_of_table=False)
    
    # Table 2: Unit 6 Header (160.4 to 201.1)
    draw_unit_header(c, 6, "G.45OTO01.010.2", "Melakukan Perawatan Berkala Engine", 160.4, 201.1, L, R)
    
    # Table 3: Unit 6 Elements (213.1 to 733.5)
    t3_data = p_data["tables"][3]["data"]
    draw_elements_table_generic(c, L, R, 213.1, 225.1, [
        {"y_top": 225.1, "y_bot": 444.8, "cell_text": t3_data[1][0], "has_checkbox": True, "k": True, "row_index": 11},
        {"y_top": 444.8, "y_bot": 733.5, "cell_text": t3_data[2][0], "has_checkbox": True, "k": True, "row_index": 12},
    ])

# ================= PAGE 4 =================
def render_page_4(c, p_data):
    L, R = 53.6, 541.2
    
    # Table 1: Unit 7 Header (113.4 to 154.1)
    draw_unit_header(c, 7, "G.45OTO01.065.2", "Memelihara Baterai", 113.4, 154.1, L, R)
    
    # Table 2: Unit 7 Elements (166.1 to 676.1)
    t2_data = p_data["tables"][2]["data"]
    draw_elements_table_generic(c, L, R, 166.1, 178.1, [
        {"y_top": 178.1, "y_bot": 397.8, "cell_text": t2_data[1][0], "has_checkbox": True, "k": True, "row_index": 13},
        {"y_top": 397.8, "y_bot": 594.5, "cell_text": t2_data[2][0], "has_checkbox": True, "k": True, "row_index": 14},
        {"y_top": 594.5, "y_bot": 676.1, "cell_text": t2_data[3][0], "has_checkbox": True, "k": True, "row_index": 15},
    ])
    
    # Table 3: Unit 8 Header (688.1 to 728.9)
    draw_unit_header(c, 8, "G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan", 688.1, 728.9, L, R)
    
    # Table 4: Unit 8 Elements Part 1 (740.9 to 776.4)
    t4_data = p_data["tables"][4]["data"]
    draw_elements_table_generic(c, L, R, 740.9, 752.9, [
        {"y_top": 752.9, "y_bot": 776.4, "cell_text": t4_data[1][0], "has_checkbox": True, "k": True, "row_index": 16},
    ])

# ================= PAGE 5 =================
def render_page_5(c, p_data):
    L, R = 53.5, 541.2
    
    # Table 1: Unit 8 Continuation Table (113.4 to 530.9)
    t1_data = p_data["tables"][1]["data"]
    draw_elements_table_generic(c, L, R, 113.4, 113.4, [
        {"y_top": 113.4, "y_bot": 264.1, "cell_text": t1_data[0][0], "has_checkbox": True, "k": True, "row_index": 17},
        {"y_top": 264.1, "y_bot": 391.8, "cell_text": t1_data[1][0], "has_checkbox": True, "k": True, "row_index": 18},
        {"y_top": 391.8, "y_bot": 530.9, "cell_text": t1_data[2][0], "has_checkbox": True, "k": True, "row_index": 19},
    ], is_first_page_of_table=False)
    
    # Table 2: Unit 9 Header (542.9 to 583.6)
    draw_unit_header(c, 9, "G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine", 542.9, 583.6, L, R)
    
    # Table 3: Unit 9 Elements Part 1 (595.6 to 781.4)
    t3_data = p_data["tables"][3]["data"]
    draw_elements_table_generic(c, L, R, 595.6, 607.6, [
        {"y_top": 607.6, "y_bot": 781.4, "cell_text": t3_data[1][0], "has_checkbox": True, "k": True, "row_index": 20},
    ])

# ================= PAGE 6 =================
def render_page_6(c, p_data):
    L, R = 55.6, 538.9
    
    # Table 0: Unit 9 Continuation Table (113.4 to 507.3)
    t0_data = p_data["tables"][0]["data"]
    draw_elements_table_generic(c, L, R, 113.4, 113.4, [
        {"y_top": 113.4, "y_bot": 148.4, "cell_text": t0_data[0][0], "has_checkbox": True, "k": True, "row_index": 21},
        {"y_top": 148.4, "y_bot": 333.6, "cell_text": t0_data[1][0], "has_checkbox": True, "k": True, "row_index": 22},
        {"y_top": 333.6, "y_bot": 507.3, "cell_text": t0_data[2][0], "has_checkbox": True, "k": True, "row_index": 23},
    ], is_first_page_of_table=False)
    
    # Table 1: Rekomendasi & Signature Box (519.9 to 648.8)
    r_top, r_bot = 519.9, 648.8
    c_mid = 283.5
    c_lbl = 375.6
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(L, Y(r_bot), R - L, r_bot - r_top)
    c.line(c_mid, Y(r_bot), c_mid, Y(r_top))
    
    # Horizontal lines in right column
    r_lines = [537.1, 567.9, 581.6, 599.7, 617.7]
    for y in r_lines:
        c.line(c_mid, Y(y), R, Y(y))
        
    # Vertical line inside right column for labels
    c.line(c_lbl, Y(537.1), c_lbl, Y(519.9))
    c.line(c_lbl, Y(567.9), c_lbl, Y(537.1))
    c.line(c_lbl, Y(599.7), c_lbl, Y(581.6))
    c.line(c_lbl, Y(617.7), c_lbl, Y(599.7))
    c.line(c_lbl, Y(648.8), c_lbl, Y(617.7))
    
    # Left Section: Rekomendasi
    c.setFont("Lib", 10.0)
    c.setFillColor(black)
    ok = bool(DATA.get("is_kompeten", True))
    w1 = pdfmetrics.stringWidth("Asesmen ", "Lib", 10.0)
    w2 = pdfmetrics.stringWidth("dapat", "Lib", 10.0)
    w3 = pdfmetrics.stringWidth(" / ", "Lib", 10.0)
    w4 = pdfmetrics.stringWidth("tidak dapat", "Lib", 10.0)
    c.drawString(L + 7.0, Y(534.0), "Asesmen ")
    c.drawString(L + 7.0 + w1, Y(534.0), "dapat")
    c.drawString(L + 7.0 + w1 + w2, Y(534.0), " / ")
    c.drawString(L + 7.0 + w1 + w2 + w3, Y(534.0), "tidak dapat")
    c.drawString(L + 7.0 + w1 + w2 + w3 + w4, Y(534.0), " dilanjutkan")
    c.setLineWidth(0.6)
    if ok:
        c.line(L + 7.0 + w1 + w2 + w3, Y(534.0 - 3.0), L + 7.0 + w1 + w2 + w3 + w4, Y(534.0 - 3.0))
    else:
        c.line(L + 7.0 + w1, Y(534.0 - 3.0), L + 7.0 + w1 + w2, Y(534.0 - 3.0))
    
    # Right Section: Asesi
    c.setFont("Lib", 10.0)
    c.drawString(c_mid + 6.0, Y(531.0), "Nama")
    c.drawString(c_lbl + 6.0, Y(531.0), str(DATA.get('asesi_nama', '')))
    
    c.drawString(c_mid + 6.0, Y(548.0), "Tanda tangan/")
    c.drawString(c_mid + 6.0, Y(560.0), "Tanggal")
    x_cell_ctr = (c_lbl + R) / 2
    draw_signature(c, DATA.get("signature_asesi") or DATA.get("asesi_signature"), x_cell_ctr - 35.0, 555.5, w=70.0, h=18.0)
    c.setFont("Lib", 8.5)
    c.drawCentredString(x_cell_ctr, Y(564.5), str(DATA.get('tanggal', '')))
    
    # Right Section: Asesor Header
    c.setFont("Lib-B", 10.0)
    c.drawString(c_mid + 6.0, Y(576.5), "Ditinjau Oleh Asesor :")
    
    # Right Section: Asesor Details
    c.setFont("Lib", 10.0)
    c.drawString(c_mid + 6.0, Y(592.0), "Nama :")
    c.drawString(c_lbl + 6.0, Y(592.0), str(DATA.get('asesor_nama', '')))
    
    c.drawString(c_mid + 6.0, Y(610.0), "No. Reg:")
    c.drawString(c_lbl + 6.0, Y(610.0), str(DATA.get('asesor_reg', '')))
    
    c.drawString(c_mid + 6.0, Y(628.0), "Tanda tangan/")
    c.drawString(c_mid + 6.0, Y(640.0), "Tanggal")
    draw_signature(c, DATA.get("signature_asesor") or DATA.get("assessor_signature"), x_cell_ctr - 35.0, 636.5, w=70.0, h=18.0)
    c.setFont("Lib", 8.5)
    c.drawCentredString(x_cell_ctr, Y(645.5), str(DATA.get('tanggal', '')))

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = OUT
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
