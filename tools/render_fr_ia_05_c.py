#!/usr/bin/env python3
"""
Generator FR.IA.05.C (Lembar Jawaban Pilihan Ganda) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 2 halaman sesuai master dokumen BNSP.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

def find_font(filename):
    candidates = [
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/liberation",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/dejavu"
    ]
    for d in candidates:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            return p
    for root, _, files in os.walk("/usr/share/fonts"):
        if filename in files:
            return os.path.join(root, filename)
    return filename

font_map = {
    "Lib": "LiberationSans-Regular.ttf",
    "Lib-B": "LiberationSans-Bold.ttf",
    "Lib-I": "LiberationSans-Italic.ttf",
    "Cal": "Carlito-Regular.ttf",
    "Cal-B": "Carlito-Bold.ttf",
    "DJ": "DejaVuSans.ttf",
}
for name, ffile in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile)))

PAGE_W, PAGE_H = 596.0, 843.0
Y = lambda top: PAGE_H - top

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "waktu": "08.00 - 09.00 WIB (60 Menit)",
    "answers": {
        1: ("A", True), 2: ("C", True), 3: ("B", True), 4: ("D", True), 5: ("A", True),
        6: ("C", True), 7: ("B", True), 8: ("A", True), 9: ("D", True), 10: ("B", True),
        11: ("C", True), 12: ("A", True), 13: ("D", True), 14: ("B", True), 15: ("C", True),
        16: ("A", True), 17: ("D", True), 18: ("B", True), 19: ("C", True), 20: ("A", True),
        21: ("B", True), 22: ("C", True), 23: ("D", True), 24: ("A", True), 25: ("C", True),
        26: ("B", True), 27: ("A", True), 28: ("D", True), 29: ("C", True), 30: ("B", True),
    },
    "umpan_balik": "Aspek pengetahuan seluruh unit pada kelompok pekerjaan yang diujikan (tercapai)*",
    "tanggal_ttd": "19 Oktober 2026",
}

def draw_header_box(c, x0, x1, top, bot):
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    x_div = 147.7
    c.line(x_div, Y(top), x_div, Y(bot))
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, Y(bot) + 6.0, 65.0, 50.0, mask="auto")
    c.setFont("Lib-B", 11.5)
    c.setFillColor(black)
    c.drawString(x_div + 18.0, Y(bot) + 26.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

def draw_cb(c, cx, cy, checked):
    sz = 8.5
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz)
    if checked:
        c.setFont("DJ", 8.5)
        c.setFillColor(black)
        c.drawCentredString(cx, cy - sz / 2 + 1.0, "✓")

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # ================= PAGE 1 =================
    x0, x1 = 50.4, 535.7
    draw_header_box(c, x0, x1, 13.2, 76.7)
    
    # Title
    c.setFont("Lib-B", 11.5)
    c.drawString(72.2, Y(98.0), "FR.IA.05.C. DPT - LEMBAR JAWABAN PILIHAN GANDA")
    
    # Table 2: Metadata (top=121.3 to 244.4)
    # Left = 72.2, Right = 568.1
    L, R = 72.2, 568.1
    c1, c2, c3 = 191.7, 244.8, 259.2
    
    # Row Y: 121.3, 141.8, 162.3, 182.8, 203.3, 223.8, 244.4
    r_y = [121.3, 141.8, 162.3, 182.8, 203.3, 223.8, 244.4]
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(r_y[-1]), R - L, r_y[-1] - r_y[0])
    
    for y in r_y[1:-1]:
        c.line(L, Y(y), R, Y(y))
        
    c.line(c1, Y(r_y[2]), c1, Y(r_y[0]))
    c.line(c2, Y(r_y[-1]), c2, Y(r_y[0]))
    c.line(c3, Y(r_y[-1]), c3, Y(r_y[0]))
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(133.0), "Skema Sertifikasi")
    c.drawString(L + 6.0, Y(153.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(c1 + 6.0, Y(133.0), "Judul")
    c.drawString(c2 + 4.0, Y(133.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(133.0), DATA["skema_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c1 + 6.0, Y(153.0), "Nomor")
    c.drawString(c2 + 4.0, Y(153.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(153.0), DATA["skema_nomor"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(174.0), "TUK")
    c.drawString(c2 + 4.0, Y(174.0), ":")
    c.drawString(c3 + 6.0, Y(174.0), DATA["tuk_tipe"])
    
    c.drawString(L + 6.0, Y(194.0), "Nama Asesor")
    c.drawString(c2 + 4.0, Y(194.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(194.0), DATA["asesor_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(215.0), "Nama Asesi")
    c.drawString(c2 + 4.0, Y(215.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(215.0), DATA["asesi_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(235.0), "Tanggal")
    c.drawString(c2 + 4.0, Y(235.0), ":")
    c.drawString(c3 + 6.0, Y(235.0), DATA["tanggal"])
    
    c.setFont("Lib-I", 8)
    c.drawString(L, Y(255.0), "*Coret yang tidak perlu")
    c.setFont("Lib-B", 9)
    c.drawString(L, Y(274.0), "Lembar Jawaban Pertanyaan Tertulis – Pilihan Ganda :")
    
    # Tables 3 & 4: Answer Grid (top=286.2 to 703.8)
    # T3 (Left: 1-15): x0=72.2 to 313.5
    # T4 (Right: 16-30): x0=327.7 to 568.1
    # Total row height for 15 rows + 2 header rows = 417.6 pt -> ~24.5 pt per row
    top_grid = 286.2
    bot_grid = 703.8
    h_hdr1 = 15.0
    h_hdr2 = 15.0
    h_row = (bot_grid - (top_grid + h_hdr1 + h_hdr2)) / 15
    
    def render_grid_col(x_start, x_end, q_start):
        col_w = x_end - x_start
        c_no = x_start + 28.0
        c_ans = x_start + 140.0
        c_ya = c_ans + 45.0
        
        # Outer
        c.rect(x_start, Y(bot_grid), col_w, bot_grid - top_grid)
        # Header hlines
        c.line(x_start, Y(top_grid + h_hdr1), x_end, Y(top_grid + h_hdr1))
        c.line(x_start, Y(top_grid + h_hdr1 + h_hdr2), x_end, Y(top_grid + h_hdr1 + h_hdr2))
        
        # Header vlines
        c.line(c_no, Y(bot_grid), c_no, Y(top_grid))
        c.line(c_ans, Y(bot_grid), c_ans, Y(top_grid))
        c.line(c_ya, Y(bot_grid), c_ya, Y(top_grid + h_hdr1))
        
        # Header labels
        c.setFont("Lib-B", 8.5)
        c.drawCentredString((x_start + c_no) / 2, Y(top_grid + 20.0), "No.")
        c.drawCentredString((c_no + c_ans) / 2, Y(top_grid + 20.0), "Jawaban")
        c.drawCentredString((c_ans + x_end) / 2, Y(top_grid + 11.0), "Pencapaian")
        c.drawCentredString((c_ans + c_ya) / 2, Y(top_grid + 25.0), "Ya")
        c.drawCentredString((c_ya + x_end) / 2, Y(top_grid + 25.0), "Tidak")
        
        # Rows
        curr_y = top_grid + h_hdr1 + h_hdr2
        for i in range(15):
            q_num = q_start + i
            next_y = curr_y + h_row
            c.line(x_start, Y(next_y), x_end, Y(next_y))
            
            # Text
            c.setFont("Lib", 8.5)
            c.drawCentredString((x_start + c_no) / 2, Y((curr_y + next_y) / 2 + 3.0), f"{q_num}.")
            
            # Answer
            ans_info = DATA["answers"].get(q_num, ("", True))
            ans_val, is_correct = ans_info
            c.setFont("Lib-B", 9)
            c.drawCentredString((c_no + c_ans) / 2, Y((curr_y + next_y) / 2 + 3.0), ans_val)
            
            # Checkbox Ya / Tidak
            draw_cb(c, (c_ans + c_ya) / 2, Y((curr_y + next_y) / 2), is_correct)
            draw_cb(c, (c_ya + x_end) / 2, Y((curr_y + next_y) / 2), not is_correct)
            
            curr_y = next_y
            
    render_grid_col(72.2, 313.5, 1)
    render_grid_col(327.7, 568.1, 16)
    
    c.showPage()
    
    # ================= PAGE 2 =================
    draw_header_box(c, 50.4, 535.7, 13.2, 76.7)
    
    # Table 2: Feedback & Signatures (top=92.6 to 344.1)
    # x0=73.4, x1=570.0
    L2, R2 = 73.4, 570.0
    c_split = 200.0
    c_colon = 215.0
    
    # Rows: 92.6, 175.5, 189.1, 209.1, 252.0, 266.4, 285.4, 304.1, 344.1
    p2_r = [92.6, 175.5, 189.1, 209.1, 252.0, 266.4, 285.4, 304.1, 344.1]
    
    c.rect(L2, Y(p2_r[-1]), R2 - L2, p2_r[-1] - p2_r[0])
    for y in p2_r[1:-1]:
        c.line(L2, Y(y), R2, Y(y))
        
    c.line(c_split, Y(p2_r[1]), c_split, Y(p2_r[0]))
    c.line(c_colon, Y(p2_r[1]), c_colon, Y(p2_r[0]))
    
    c.line(c_split, Y(p2_r[4]), c_split, Y(p2_r[2]))
    c.line(c_colon, Y(p2_r[4]), c_colon, Y(p2_r[2]))
    
    c.line(c_split, Y(p2_r[8]), c_split, Y(p2_r[5]))
    c.line(c_colon, Y(p2_r[8]), c_colon, Y(p2_r[5]))
    
    # r0: Feedback
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(112.0), "Umpan balik untuk asesi")
    c.drawString(c_split + 4.0, Y(112.0), ":")
    c.setFont("Lib", 8.5)
    c.drawString(c_colon + 6.0, Y(112.0), "Aspek pengetahuan seluruh unit pada kelompok pekerjaan yang")
    c.drawString(c_colon + 6.0, Y(126.0), "diujikan (tercapai/ belum tercapai)*")
    c.drawString(c_colon + 6.0, Y(145.0), "Tuliskan unit/elemen/KUK jika belum tercapai: ….")
    
    # r1: Asesi Header
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(185.0), "Asesi :")
    
    # r2: Nama Asesi
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(202.0), "Nama")
    c.drawString(c_split + 4.0, Y(202.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_colon + 6.0, Y(202.0), DATA["asesi_nama"])
    
    # r3: TTD Asesi
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(224.0), "Tanda tangan/Tanggal")
    c.drawString(c_split + 4.0, Y(224.0), ":")
    c.drawString(c_colon + 6.0, Y(224.0), f"[Tanda Tangan Digital]   -   {DATA['tanggal_ttd']}")
    
    # r4: Asesor Header
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(262.0), "Asesor :")
    
    # r5: Nama Asesor
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(278.0), "Nama")
    c.drawString(c_split + 4.0, Y(278.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_colon + 6.0, Y(278.0), DATA["asesor_nama"])
    
    # r6: No Reg
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(297.0), "No. Reg")
    c.drawString(c_split + 4.0, Y(297.0), ":")
    c.drawString(c_colon + 6.0, Y(297.0), DATA["asesor_no_reg"])
    
    # r7: TTD Asesor
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(320.0), "Tanda tangan/ Tanggal")
    c.drawString(c_split + 4.0, Y(320.0), ":")
    c.drawString(c_colon + 6.0, Y(320.0), f"[Tanda Tangan Digital]   -   {DATA['tanggal_ttd']}")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.IA.05.C: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_IA_05_C_generated.pdf")
    render(out_file)
