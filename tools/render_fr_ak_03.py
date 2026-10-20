#!/usr/bin/env python3
"""
Generator FR.AK.03 (Umpan Balik dan Catatan Asesmen) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor, Color
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

def find_font(name):
    paths = [
        f"/usr/share/fonts/{name}",
        f"/usr/share/fonts/truetype/{name}",
        f"/usr/share/fonts/TTF/{name}",
        f"/usr/share/fonts/carlito/{name}",
        f"/usr/share/fonts/liberation/{name}",
    ]
    for p in paths:
        if os.path.exists(p):
            return p
    return None

carlito_reg = find_font("Carlito-Regular.ttf") or "/usr/share/fonts/carlito/Carlito-Regular.ttf"
carlito_bold = find_font("Carlito-Bold.ttf") or "/usr/share/fonts/carlito/Carlito-Bold.ttf"
carlito_italic = find_font("Carlito-Italic.ttf") or "/usr/share/fonts/carlito/Carlito-Italic.ttf"
dejavu_reg = find_font("DejaVuSans.ttf") or "/usr/share/fonts/TTF/DejaVuSans.ttf"

if os.path.exists(carlito_reg):
    pdfmetrics.registerFont(TTFont("Cal", carlito_reg))
if os.path.exists(carlito_bold):
    pdfmetrics.registerFont(TTFont("Cal-B", carlito_bold))
if os.path.exists(carlito_italic):
    pdfmetrics.registerFont(TTFont("Cal-I", carlito_italic))
if os.path.exists(dejavu_reg):
    pdfmetrics.registerFont(TTFont("DJ", dejavu_reg))

PAGE_W, PAGE_H = 595.5, 842.5

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal_mulai": "19/10/2026 08:30",
    "tanggal_selesai": "19/10/2026 12:00",
    "items": [
        {"q": "Saya mendapatkan penjelasan yang cukup memadai mengenai proses asesmen/uji kompetensi", "ya": True, "catatan": ""},
        {"q": "Saya diberikan kesempatan untuk mempelajari standar kompetensi yang akan diujikan dan menilai diri sendiri terhadap pencapaiannya", "ya": True, "catatan": ""},
        {"q": "Asesor memberikan kesempatan untuk mendiskusikan/menegosiasikan metoda, instrumen dan sumber asesmen serta jadwal asesmen", "ya": True, "catatan": ""},
        {"q": "Asesor berusaha menggali seluruh bukti pendukung yang sesuai dengan latar belakang pelatihan dan pengalaman yang saya miliki", "ya": True, "catatan": ""},
        {"q": "Saya sepenuhnya diberikan kesempatan untuk mendemonstrasikan kompetensi yang saya miliki selama asesmen", "ya": True, "catatan": ""},
        {"q": "Saya mendapatkan penjelasan yang memadai mengenai keputusan asesmen", "ya": True, "catatan": ""},
        {"q": "Asesor memberikan umpan balik yang mendukung setelah asesmen serta tindak lanjutnya", "ya": True, "catatan": ""},
        {"q": "Asesor bersama saya mempelajari semua dokumen asesmen serta menandatanganinya", "ya": True, "catatan": ""},
        {"q": "Saya mendapatkan jaminan kerahasiaan hasil asesmen serta penjelasan penanganan dokumen asesmen", "ya": True, "catatan": ""},
        {"q": "Asesor menggunakan keterampilan komunikasi yang efektif selama asesmen", "ya": True, "catatan": ""},
    ],
    "catatan_lainnya": "Proses asesmen berjalan lancar dan tertib sesuai jadwal yang telah ditentukan.",
}

def wrap_text(c, text, font, size, max_width):
    words = text.split()
    lines = []
    current = ""
    for w in words:
        test = (current + " " + w).strip()
        if c.stringWidth(test, font, size) <= max_width:
            current = test
        else:
            if current:
                lines.append(current)
            current = w
    if current:
        lines.append(current)
    return lines

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # 1. Header Box
    x0 = 71.5
    x1 = 560.0
    y_hdr_top = PAGE_H - 22.0
    y_hdr_bot = PAGE_H - 67.0
    c.setLineWidth(0.8)
    c.setStrokeColor(black)
    c.rect(x0, y_hdr_bot, x1 - x0, y_hdr_top - y_hdr_bot)
    
    # Logo divider
    x_logo_div = x0 + 82.0
    c.line(x_logo_div, y_hdr_top, x_logo_div, y_hdr_bot)
    
    # Draw Logo
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, y_hdr_bot + 4.0, 50.0, 37.0, mask="auto")
        
    # Header Text
    c.setFont("Cal-B", 11)
    c.drawString(x_logo_div + 15.0, y_hdr_bot + 18.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")
    
    # 2. Title
    c.setFont("Cal-B", 12)
    c.drawCentredString((x0 + x1) / 2, PAGE_H - 85.0, "FR.AK.03. UMPAN BALIK DAN CATATAN ASESMEN")
    
    # 3. Table 1: Metadata
    y_t1_top = PAGE_H - 99.1
    # 7 rows: Skema (2 rows), TUK (1), Asesor (1), Asesi (1), Tanggal (2)
    t1_row_heights = [17.7, 17.5, 17.3, 17.5, 17.5, 17.7, 18.0]
    y_t1_lines = [y_t1_top]
    curr = y_t1_top
    for h in t1_row_heights:
        curr -= h
        y_t1_lines.append(curr)
        
    c.rect(x0, y_t1_lines[-1], x1 - x0, y_t1_top - y_t1_lines[-1])
    
    # Inner column divisions
    x_col_b = 191.7
    x_col_c = 244.8
    x_col_d = 259.2
    
    # Horizontal lines inside T1
    c.line(x_col_b, y_t1_lines[1], x1, y_t1_lines[1])
    c.line(x0, y_t1_lines[2], x1, y_t1_lines[2])
    c.line(x0, y_t1_lines[3], x1, y_t1_lines[3])
    c.line(x0, y_t1_lines[4], x1, y_t1_lines[4])
    c.line(x0, y_t1_lines[5], x1, y_t1_lines[5])
    c.line(x_col_b, y_t1_lines[6], x1, y_t1_lines[6])
    
    # Vertical lines inside T1
    c.line(x_col_b, y_t1_lines[0], x_col_b, y_t1_lines[2])
    c.line(x_col_c, y_t1_lines[0], x_col_c, y_t1_lines[2])
    c.line(x_col_d, y_t1_lines[0], x_col_d, y_t1_lines[2])
    
    c.line(x_col_b, y_t1_lines[2], x_col_b, y_t1_lines[5])
    c.line(x_col_d, y_t1_lines[2], x_col_d, y_t1_lines[5])
    
    c.line(x_col_b, y_t1_lines[5], x_col_b, y_t1_lines[7])
    c.line(x_col_c, y_t1_lines[5], x_col_c, y_t1_lines[7])
    c.line(x_col_d, y_t1_lines[5], x_col_d, y_t1_lines[7])
    
    # T1 Text
    c.setFont("Cal", 10)
    c.drawString(x0 + 5.0, y_t1_lines[0] - 12.0, "Skema Sertifikasi")
    c.drawString(x0 + 5.0, y_t1_lines[1] - 12.0, "(KKNI/Okupasi/Klaster)")
    c.drawString(x_col_b + 5.0, y_t1_lines[0] - 12.0, "Judul")
    c.drawString(x_col_c + 3.0, y_t1_lines[0] - 12.0, ":")
    c.setFont("Cal-B", 10)
    c.drawString(x_col_d + 5.0, y_t1_lines[0] - 12.0, DATA["skema_nama"])
    
    c.setFont("Cal", 10)
    c.drawString(x_col_b + 5.0, y_t1_lines[1] - 12.0, "Nomor")
    c.drawString(x_col_c + 3.0, y_t1_lines[1] - 12.0, ":")
    c.setFont("Cal-B", 10)
    c.drawString(x_col_d + 5.0, y_t1_lines[1] - 12.0, DATA["skema_nomor"])
    
    c.setFont("Cal", 10)
    c.drawString(x0 + 5.0, y_t1_lines[2] - 12.0, "TUK")
    c.drawString(x_col_c + 3.0, y_t1_lines[2] - 12.0, ":")
    # TUK Options with strike-through
    tuk_str = "Sewaktu/Tempat Kerja/Mandiri*"
    c.drawString(x_col_d + 5.0, y_t1_lines[2] - 12.0, tuk_str)
    
    c.drawString(x0 + 5.0, y_t1_lines[3] - 12.0, "Nama Asesor")
    c.drawString(x_col_c + 3.0, y_t1_lines[3] - 12.0, ":")
    c.drawString(x_col_d + 5.0, y_t1_lines[3] - 12.0, DATA["asesor_nama"])
    
    c.drawString(x0 + 5.0, y_t1_lines[4] - 12.0, "Nama Asesi")
    c.drawString(x_col_c + 3.0, y_t1_lines[4] - 12.0, ":")
    c.drawString(x_col_d + 5.0, y_t1_lines[4] - 12.0, DATA["asesi_nama"])
    
    c.drawString(x0 + 5.0, y_t1_lines[5] - 12.0, "Tanggal Asesmen")
    c.drawString(x_col_b + 5.0, y_t1_lines[5] - 12.0, "Mulai")
    c.drawString(x_col_c + 3.0, y_t1_lines[5] - 12.0, ":")
    c.drawString(x_col_d + 5.0, y_t1_lines[5] - 12.0, DATA["tanggal_mulai"])
    
    c.drawString(x_col_b + 5.0, y_t1_lines[6] - 12.0, "Selesai")
    c.drawString(x_col_c + 3.0, y_t1_lines[6] - 12.0, ":")
    c.drawString(x_col_d + 5.0, y_t1_lines[6] - 12.0, DATA["tanggal_selesai"])
    
    # 4. Prompt Subtitle
    c.setFont("Cal-I", 9.5)
    c.drawString(x0, PAGE_H - 240.0, "Umpan balik dari Asesi (diisi oleh Asesi setelah pengambilan keputusan):")
    
    # 5. Table 2: Feedback Components
    y_t2_top = PAGE_H - 257.4
    t2_hdr1_h = 20.0
    t2_hdr2_h = 20.0
    
    # Column positions
    x_c1 = 375.1
    x_c2 = 401.9
    x_c3 = 434.0
    
    # Row Heights for items
    item_heights = [34.3, 36.4, 36.5, 36.5, 36.5, 34.3, 34.3, 34.5, 34.3, 34.7]
    catatan_h = 53.9
    
    y_t2_lines = [y_t2_top, y_t2_top - t2_hdr1_h, y_t2_top - t2_hdr1_h - t2_hdr2_h]
    curr = y_t2_lines[-1]
    for h in item_heights:
        curr -= h
        y_t2_lines.append(curr)
    curr -= catatan_h
    y_t2_lines.append(curr)
    
    # Outer border
    c.rect(x0, y_t2_lines[-1], x1 - x0, y_t2_top - y_t2_lines[-1])
    
    # Horizontal grid lines
    # Header sub-divider
    c.line(x_c1, y_t2_lines[1], x_c3, y_t2_lines[1])
    for y in y_t2_lines[2:]:
        c.line(x0, y, x1, y)
        
    # Vertical grid lines
    c.line(x_c1, y_t2_top, x_c1, y_t2_lines[-2])
    c.line(x_c2, y_t2_lines[1], x_c2, y_t2_lines[-2])
    c.line(x_c3, y_t2_top, x_c3, y_t2_lines[-2])
    
    # Header Labels
    c.setFont("Cal-B", 10)
    c.drawCentredString((x0 + x_c1) / 2, y_t2_top - 25.0, "KOMPONEN")
    c.drawCentredString((x_c1 + x_c3) / 2, y_t2_top - 14.0, "Hasil")
    c.setFont("Cal", 9.5)
    c.drawCentredString((x_c1 + x_c2) / 2, y_t2_top - 33.0, "Ya")
    c.drawCentredString((x_c2 + x_c3) / 2, y_t2_top - 33.0, "Tidak")
    c.setFont("Cal-B", 9.5)
    c.drawCentredString((x_c3 + x1) / 2, y_t2_top - 18.0, "Catatan/Komentar")
    c.drawCentredString((x_c3 + x1) / 2, y_t2_top - 30.0, "Asesi")
    
    # Render Items
    def draw_cb(x_ctr, y_ctr, checked):
        sz = 9
        c.setLineWidth(0.7)
        c.rect(x_ctr - sz / 2, y_ctr - sz / 2, sz, sz)
        if checked:
            c.setFont("DJ", 9)
            c.drawCentredString(x_ctr, y_ctr - sz / 2 + 1.0, "✓")
            
    for idx, item in enumerate(DATA["items"]):
        y_top_row = y_t2_lines[2 + idx]
        y_bot_row = y_t2_lines[3 + idx]
        row_mid = (y_top_row + y_bot_row) / 2
        
        # Text wrapping
        c.setFont("Cal", 9)
        lines = wrap_text(c, item["q"], "Cal", 9, (x_c1 - x0) - 14.0)
        start_y = y_top_row - 12.0
        for l in lines:
            c.drawString(x0 + 6.0, start_y, l)
            start_y -= 11.0
            
        # Checkboxes
        draw_cb((x_c1 + x_c2) / 2, row_mid, item["ya"])
        draw_cb((x_c2 + x_c3) / 2, row_mid, not item["ya"])
        
        # Comment
        if item.get("catatan"):
            c.setFont("Cal-I", 8.5)
            c.drawString(x_c3 + 5.0, row_mid - 3.0, item["catatan"])
            
    # Final Note Row
    y_cat_top = y_t2_lines[-2]
    c.setFont("Cal-B", 9.5)
    c.drawString(x0 + 6.0, y_cat_top - 14.0, "Catatan/komentar lainnya (apabila ada) :")
    if DATA.get("catatan_lainnya"):
        c.setFont("Cal-I", 9)
        c.drawString(x0 + 6.0, y_cat_top - 30.0, DATA["catatan_lainnya"])
        
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.03: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_03_generated.pdf")
    render(out_file)
