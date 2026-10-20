#!/usr/bin/env python3
"""
Generator FR.AK.02 (Rekaman Asesmen Kompetensi) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi 3 halaman sesuai master document.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor
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
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal_mulai": "19/10/2026 08:30",
    "tanggal_selesai": "19/10/2026 12:00",
    "rekomendasi_kompeten": True,
    "tindak_lanjut": "Tidak ada tindakan perbaikan yang diperlukan. Seluruh unit kompetensi telah terpenuhi sesuai standar SKKNI.",
    "komentar_asesor": "Asesi menunjukkan pemahaman dan keterampilan kerja yang sangat baik dalam perawatan sistem injeksi bensin.",
    "units": [
        {"title": "Melaksanakan Keselamatan dan Kesehatan Kerja", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Menggunakan Peralatan dan Perlengkapan Tempat Kerja", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Membaca Gambar Teknik", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Menggunakan Alat Ukur", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Melaksanakan Diagnosis", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Melakukan Perawatan Berkala Engine", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Memelihara Baterai", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Memperbaiki Sistem Manajemen Engine", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
    ],
    "tanggal_tanda_tangan": "19/10/2026",
}

def draw_header_box(c, x0, x1, y_top, y_bot):
    c.setLineWidth(0.8)
    c.setStrokeColor(black)
    c.rect(x0, y_bot, x1 - x0, y_top - y_bot)
    x_logo_div = x0 + 82.0
    c.line(x_logo_div, y_top, x_logo_div, y_bot)
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, y_bot + 4.0, 50.0, 37.0, mask="auto")
    c.setFont("Cal-B", 11)
    c.drawString(x_logo_div + 15.0, y_bot + 18.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

def wrap_text(c, text, font, size, max_width):
    words = text.split()
    lines = []
    current = ""
    for w in words:
        test = (current + " " + w).strip()
        if c.stringWidth(test, font, size) <= max_width:
            current = test
        else:
            if current: lines.append(current)
            current = w
    if current: lines.append(current)
    return lines

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # Common layout bounds
    x0 = 55.1
    x1 = 574.4
    
    # ================= PAGE 1 =================
    draw_header_box(c, x0, x1, PAGE_H - 21.5, PAGE_H - 67.0)
    
    c.setFont("Cal-B", 12)
    c.drawCentredString((x0 + x1) / 2, PAGE_H - 85.0, "FR.AK.02. REKAMAN ASESMEN KOMPETENSI")
    
    # Metadata Table
    y_t1_top = PAGE_H - 105.0
    t1_row_heights = [17.5, 17.5, 17.5, 17.5, 17.5, 17.5, 17.5]
    y_t1_lines = [y_t1_top]
    curr = y_t1_top
    for h in t1_row_heights:
        curr -= h
        y_t1_lines.append(curr)
        
    c.rect(x0, y_t1_lines[-1], x1 - x0, y_t1_top - y_t1_lines[-1])
    
    x_col_b = x0 + 130.0
    x_col_c = x0 + 185.0
    x_col_d = x0 + 200.0
    
    c.line(x_col_b, y_t1_lines[1], x1, y_t1_lines[1])
    c.line(x0, y_t1_lines[2], x1, y_t1_lines[2])
    c.line(x0, y_t1_lines[3], x1, y_t1_lines[3])
    c.line(x0, y_t1_lines[4], x1, y_t1_lines[4])
    c.line(x0, y_t1_lines[5], x1, y_t1_lines[5])
    c.line(x_col_b, y_t1_lines[6], x1, y_t1_lines[6])
    
    c.line(x_col_b, y_t1_lines[0], x_col_b, y_t1_lines[2])
    c.line(x_col_c, y_t1_lines[0], x_col_c, y_t1_lines[2])
    c.line(x_col_d, y_t1_lines[0], x_col_d, y_t1_lines[2])
    c.line(x_col_b, y_t1_lines[2], x_col_b, y_t1_lines[5])
    c.line(x_col_d, y_t1_lines[2], x_col_d, y_t1_lines[5])
    c.line(x_col_b, y_t1_lines[5], x_col_b, y_t1_lines[7])
    c.line(x_col_c, y_t1_lines[5], x_col_c, y_t1_lines[7])
    c.line(x_col_d, y_t1_lines[5], x_col_d, y_t1_lines[7])
    
    c.setFont("Cal", 9.5)
    c.drawString(x0 + 5.0, y_t1_lines[0] - 12.0, "Skema Sertifikasi")
    c.drawString(x0 + 5.0, y_t1_lines[1] - 12.0, "(KKNI/Okupasi/Klaster)")
    c.drawString(x_col_b + 5.0, y_t1_lines[0] - 12.0, "Judul")
    c.drawString(x_col_c + 3.0, y_t1_lines[0] - 12.0, ":")
    c.setFont("Cal-B", 9.5)
    c.drawString(x_col_d + 5.0, y_t1_lines[0] - 12.0, DATA["skema_nama"])
    
    c.setFont("Cal", 9.5)
    c.drawString(x_col_b + 5.0, y_t1_lines[1] - 12.0, "Nomor")
    c.drawString(x_col_c + 3.0, y_t1_lines[1] - 12.0, ":")
    c.setFont("Cal-B", 9.5)
    c.drawString(x_col_d + 5.0, y_t1_lines[1] - 12.0, DATA["skema_nomor"])
    
    c.setFont("Cal", 9.5)
    c.drawString(x0 + 5.0, y_t1_lines[2] - 12.0, "TUK")
    c.drawString(x_col_c + 3.0, y_t1_lines[2] - 12.0, ":")
    c.drawString(x_col_d + 5.0, y_t1_lines[2] - 12.0, "Sewaktu/Tempat Kerja/Mandiri*")
    
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
    
    # Instruction Text
    c.setFont("Cal-I", 9)
    c.drawString(x0, PAGE_H - 245.0, "Beri tanda centang (√) di kolom yang sesuai untuk mencerminkan bukti yang diperoleh untuk menentukan")
    c.drawString(x0, PAGE_H - 257.0, "Kompetensi Asesi untuk setiap Unit Kompetensi.")
    
    # Table 3: Units and Evidences
    y_t3_top = PAGE_H - 270.0
    hdr_h = 75.0
    unit_h = 24.5
    rekom_h = 24.0
    tindak_h = 55.0
    komentar_h = 75.0
    
    # Columns
    cols_x = [55.1, 298.1, 333.4, 368.9, 432.9, 468.3, 503.7, 539.3, 574.4]
    
    y_t3_lines = [y_t3_top, y_t3_top - hdr_h]
    curr = y_t3_lines[-1]
    for _ in DATA["units"]:
        curr -= unit_h
        y_t3_lines.append(curr)
    curr -= rekom_h
    y_t3_lines.append(curr)
    curr -= tindak_h
    y_t3_lines.append(curr)
    curr -= komentar_h
    y_t3_lines.append(curr)
    
    # Outer Border
    c.rect(x0, y_t3_lines[-1], x1 - x0, y_t3_top - y_t3_lines[-1])
    
    # Horizontal lines
    for y in y_t3_lines[1:]:
        c.line(x0, y, x1, y)
        
    # Vertical lines for columns in header and unit rows
    for cx in cols_x[1:-1]:
        c.line(cx, y_t3_top, cx, y_t3_lines[1 + len(DATA["units"])])
        
    # Vertical line for Rekomendasi row
    c.line(cols_x[1], y_t3_lines[1 + len(DATA["units"])], cols_x[1], y_t3_lines[2 + len(DATA["units"])])
    
    # Vertical line for Tindak Lanjut and Komentar rows
    c.line(cols_x[1], y_t3_lines[2 + len(DATA["units"])], cols_x[1], y_t3_lines[-1])
    
    # Header Rotated Labels
    c.setFont("Cal-B", 10)
    c.drawCentredString((cols_x[0] + cols_x[1]) / 2, y_t3_top - 42.0, "Unit Kompetensi")
    
    header_titles = [
        "Observasi Demonstrasi",
        "Portofolio",
        "Pernyataan Pihak Ketiga /\nPertanyaan Wawancara",
        "Pertanyaan Lisan",
        "Pertanyaan Tertulis",
        "Proyek Kerja",
        "Lainnya"
    ]
    
    for idx, title in enumerate(header_titles):
        c_left = cols_x[1 + idx]
        c_right = cols_x[2 + idx]
        c_center = (c_left + c_right) / 2
        
        c.saveState()
        c.setFont("Cal-B", 8)
        if "\n" in title:
            lines = title.split("\n")
            c.translate(c_center - 4.0, y_t3_top - 70.0)
            c.rotate(90)
            c.drawString(0, 0, lines[0])
            c.restoreState()
            
            c.saveState()
            c.setFont("Cal-B", 8)
            c.translate(c_center + 5.0, y_t3_top - 70.0)
            c.rotate(90)
            c.drawString(0, 0, lines[1])
            c.restoreState()
        else:
            c.translate(c_center + 2.5, y_t3_top - 70.0)
            c.rotate(90)
            c.drawString(0, 0, title)
            c.restoreState()
            
    # Draw Unit Rows
    for u_idx, u in enumerate(DATA["units"]):
        y_top_u = y_t3_lines[1 + u_idx]
        y_bot_u = y_t3_lines[2 + u_idx]
        y_mid_u = (y_top_u + y_bot_u) / 2
        
        c.setFont("Cal", 8.5)
        # title
        t_lines = wrap_text(c, u["title"], "Cal", 8.5, cols_x[1] - cols_x[0] - 10.0)
        if len(t_lines) == 1:
            c.drawString(cols_x[0] + 5.0, y_mid_u - 3.0, t_lines[0])
        else:
            c.drawString(cols_x[0] + 5.0, y_top_u - 10.0, t_lines[0])
            c.drawString(cols_x[0] + 5.0, y_top_u - 19.0, t_lines[1])
            
        # Draw checkmarks
        checks = [u["obs"], u["port"], u["pihak3"], u["lisan"], u["tulis"], u["proyek"], u["lain"]]
        c.setFont("DJ", 10)
        for c_i, chk in enumerate(checks):
            if chk:
                cx = (cols_x[1 + c_i] + cols_x[2 + c_i]) / 2
                c.drawCentredString(cx, y_mid_u - 3.5, "✓")
                
    # Rekomendasi Row
    y_rek_top = y_t3_lines[1 + len(DATA["units"])]
    y_rek_bot = y_t3_lines[2 + len(DATA["units"])]
    c.setFont("Cal-B", 9.5)
    c.drawString(cols_x[0] + 5.0, (y_rek_top + y_rek_bot) / 2 - 3.0, "Rekomendasi hasil asesmen")
    
    # Checkbox Kompeten / Belum Kompeten
    c.setFont("Cal", 9.5)
    c_kmp = "Kompeten"
    c_b_kmp = "Belum kompeten"
    
    cb_size = 9
    bx1 = cols_x[1] + 15.0
    by1 = (y_rek_top + y_rek_bot) / 2 - cb_size / 2
    c.rect(bx1, by1, cb_size, cb_size)
    if DATA["rekomendasi_kompeten"]:
        c.setFont("DJ", 9)
        c.drawCentredString(bx1 + cb_size / 2, by1 + 1.0, "✓")
    c.setFont("Cal", 9.5)
    c.drawString(bx1 + 15.0, (y_rek_top + y_rek_bot) / 2 - 3.0, c_kmp)
    c.drawString(bx1 + 75.0, (y_rek_top + y_rek_bot) / 2 - 3.0, "/")
    
    bx2 = bx1 + 90.0
    c.rect(bx2, by1, cb_size, cb_size)
    if not DATA["rekomendasi_kompeten"]:
        c.setFont("DJ", 9)
        c.drawCentredString(bx2 + cb_size / 2, by1 + 1.0, "✓")
    c.setFont("Cal", 9.5)
    c.drawString(bx2 + 15.0, (y_rek_top + y_rek_bot) / 2 - 3.0, c_b_kmp)
    
    # Tindak Lanjut Row
    y_tin_top = y_t3_lines[2 + len(DATA["units"])]
    y_tin_bot = y_t3_lines[3 + len(DATA["units"])]
    c.setFont("Cal-B", 9)
    c.drawString(cols_x[0] + 5.0, y_tin_top - 14.0, "Tindak lanjut yang dibutuhkan")
    c.setFont("Cal-I", 8)
    c.drawString(cols_x[0] + 5.0, y_tin_top - 26.0, "(Masukkan pekerjaan tambahan dan asesmen yang")
    c.drawString(cols_x[0] + 5.0, y_tin_top - 37.0, "diperlukan untuk mencapai kompetensi)")
    
    c.setFont("Cal", 9)
    tin_lines = wrap_text(c, DATA["tindak_lanjut"], "Cal", 9, cols_x[-1] - cols_x[1] - 15.0)
    ty = y_tin_top - 16.0
    for l in tin_lines:
        c.drawString(cols_x[1] + 8.0, ty, l)
        ty -= 12.0
        
    # Komentar Row
    y_kom_top = y_t3_lines[3 + len(DATA["units"])]
    c.setFont("Cal-B", 9)
    c.drawString(cols_x[0] + 5.0, y_kom_top - 18.0, "Komentar/ Observasi oleh asesor")
    
    c.setFont("Cal", 9)
    kom_lines = wrap_text(c, DATA["komentar_asesor"], "Cal", 9, cols_x[-1] - cols_x[1] - 15.0)
    ky = y_kom_top - 18.0
    for l in kom_lines:
        c.drawString(cols_x[1] + 8.0, ky, l)
        ky -= 12.0
        
    c.showPage()
    
    # ================= PAGE 2 =================
    draw_header_box(c, x0, x1, PAGE_H - 21.5, PAGE_H - 67.0)
    
    # Signature Box
    y_sig_top = PAGE_H - 120.0
    sig_h = 180.0
    y_sig_bot = y_sig_top - sig_h
    
    c.rect(x0, y_sig_bot, x1 - x0, sig_h)
    
    x_sig_col1 = x0 + 170.0
    x_sig_col2 = x0 + 190.0
    
    # 7 rows in master signature table:
    # R0: Asesi:
    # R1: Nama
    # R2: Tanda tangan dan Tanggal
    # R3: Asesor:
    # R4: Nama
    # R5: No. Reg
    # R6: Tanda tangan dan Tanggal
    sig_row_h = sig_h / 7
    y_sr = [y_sig_top - i * sig_row_h for i in range(8)]
    
    for y in y_sr[1:-1]:
        c.line(x0, y, x1, y)
        
    c.line(x_sig_col1, y_sr[1], x_sig_col1, y_sr[3])
    c.line(x_sig_col2, y_sr[1], x_sig_col2, y_sr[3])
    c.line(x_sig_col1, y_sr[4], x_sig_col1, y_sr[7])
    c.line(x_sig_col2, y_sr[4], x_sig_col2, y_sr[7])
    
    # Asesi Section
    c.setFont("Cal-B", 10.5)
    c.drawString(x0 + 8.0, y_sr[0] - 16.0, "Asesi :")
    
    c.setFont("Cal", 10)
    c.drawString(x0 + 8.0, y_sr[1] - 16.0, "Nama")
    c.drawString(x_sig_col1 + 6.0, y_sr[1] - 16.0, ":")
    c.setFont("Cal-B", 10)
    c.drawString(x_sig_col2 + 8.0, y_sr[1] - 16.0, DATA["asesi_nama"])
    
    c.setFont("Cal", 10)
    c.drawString(x0 + 8.0, y_sr[2] - 16.0, "Tanda tangan dan Tanggal")
    c.drawString(x_sig_col1 + 6.0, y_sr[2] - 16.0, ":")
    c.setFont("Cal-I", 9.5)
    c.drawString(x_sig_col2 + 8.0, y_sr[2] - 16.0, f"[Tanda Tangan Digital]   -   {DATA['tanggal_tanda_tangan']}")
    
    # Asesor Section
    c.setFont("Cal-B", 10.5)
    c.drawString(x0 + 8.0, y_sr[3] - 16.0, "Asesor :")
    
    c.setFont("Cal", 10)
    c.drawString(x0 + 8.0, y_sr[4] - 16.0, "Nama")
    c.drawString(x_sig_col1 + 6.0, y_sr[4] - 16.0, ":")
    c.setFont("Cal-B", 10)
    c.drawString(x_sig_col2 + 8.0, y_sr[4] - 16.0, DATA["asesor_nama"])
    
    c.setFont("Cal", 10)
    c.drawString(x0 + 8.0, y_sr[5] - 16.0, "No. Reg")
    c.drawString(x_sig_col1 + 6.0, y_sr[5] - 16.0, ":")
    c.drawString(x_sig_col2 + 8.0, y_sr[5] - 16.0, DATA["asesor_no_reg"])
    
    c.setFont("Cal", 10)
    c.drawString(x0 + 8.0, y_sr[6] - 16.0, "Tanda tangan dan Tanggal")
    c.drawString(x_sig_col1 + 6.0, y_sr[6] - 16.0, ":")
    c.setFont("Cal-I", 9.5)
    c.drawString(x_sig_col2 + 8.0, y_sr[6] - 16.0, f"[Tanda Tangan Digital]   -   {DATA['tanggal_tanda_tangan']}")
    
    c.showPage()
    
    # ================= PAGE 3 =================
    draw_header_box(c, x0, x1, PAGE_H - 21.5, PAGE_H - 67.0)
    
    y_p3_top = PAGE_H - 120.0
    c.setFont("Cal-B", 11)
    c.drawString(x0, y_p3_top, "LAMPIRAN DOKUMEN:")
    
    lampiran = [
        "1. Dokumen APL 01 peserta",
        "2. Dokumen APL 02 peserta",
        "3. Bukti-bukti berkualitas peserta",
        "4. Tinjauan proses asesmen"
    ]
    
    c.setFont("Cal", 11)
    ly = y_p3_top - 25.0
    for item in lampiran:
        c.drawString(x0 + 15.0, ly, item)
        ly -= 22.0
        
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.02: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_02_generated.pdf")
    render(out_file)
