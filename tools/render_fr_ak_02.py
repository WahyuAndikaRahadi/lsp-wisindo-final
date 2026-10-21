#!/usr/bin/env python3
"""
Generator FR.AK.02 (Rekaman Asesmen Kompetensi) - LSP PrO Indo
Layout meniru template master BNSP aslinya secara presisi piksel 100%; tanpa watermark sesuai instruksi.
Paginasi 3 halaman utuh sesuai master document dengan data dummy lengkap.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

def register_fonts():
    font_candidates = {
        "Cal": [
            "C:/Windows/Fonts/calibri.ttf",
            "/usr/share/fonts/truetype/carlito/Carlito-Regular.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
            "LiberationSans-Regular.ttf"
        ],
        "Cal-B": [
            "C:/Windows/Fonts/calibrib.ttf",
            "/usr/share/fonts/truetype/carlito/Carlito-Bold.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
            "LiberationSans-Bold.ttf"
        ],
        "Cal-I": [
            "C:/Windows/Fonts/calibrii.ttf",
            "/usr/share/fonts/truetype/carlito/Carlito-Italic.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Italic.ttf",
            "LiberationSans-Italic.ttf"
        ],
        "Tah": [
            "C:/Windows/Fonts/tahoma.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            "DejaVuSans.ttf"
        ],
        "Tah-B": [
            "C:/Windows/Fonts/tahomabd.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
            "DejaVuSans-Bold.ttf"
        ],
        "Arial": [
            "C:/Windows/Fonts/arial.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
            "LiberationSans-Regular.ttf"
        ],
        "Arial-B": [
            "C:/Windows/Fonts/arialbd.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
            "LiberationSans-Bold.ttf"
        ],
    }
    for name, paths in font_candidates.items():
        for p in paths:
            if os.path.exists(p):
                pdfmetrics.registerFont(TTFont(name, p))
                break

register_fonts()

PAGE_W, PAGE_H = 595.5, 842.5
Y = lambda top: PAGE_H - top

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal_mulai": "19/10/2026 08:30",
    "tanggal_selesai": "19/10/2026 12:00",
    "rekomendasi_kompeten": True,
    "tindak_lanjut": "Tidak ada tindakan perbaikan yang diperlukan. Seluruh unit kompetensi telah terpenuhi sesuai standar SKKNI.",
    "komentar_asesor": "Asesi menunjukkan pemahaman dan keterampilan kerja yang sangat baik dalam perawatan sistem injeksi bensin.",
    "units": [
        {"title": "Melaksanakan Keselamatan dan Kesehatan Kerja", "obs": True, "port": True, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Menggunakan Peralatan dan Perlengkapan Tempat Kerja", "obs": True, "port": False, "pihak3": True, "lisan": True, "tulis": False, "proyek": False, "lain": False},
        {"title": "Membaca Gambar Teknik", "obs": False, "port": True, "pihak3": False, "lisan": False, "tulis": True, "proyek": True, "lain": False},
        {"title": "Menggunakan Alat Ukur", "obs": True, "port": True, "pihak3": False, "lisan": True, "tulis": False, "proyek": False, "lain": False},
        {"title": "Melaksanakan Diagnosis", "obs": True, "port": False, "pihak3": True, "lisan": False, "tulis": True, "proyek": True, "lain": False},
        {"title": "Melakukan Perawatan Berkala Engine", "obs": True, "port": True, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": False},
        {"title": "Memelihara Baterai", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": False, "lain": True},
        {"title": "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan", "obs": True, "port": True, "pihak3": True, "lisan": False, "tulis": True, "proyek": False, "lain": False},
        {"title": "Memperbaiki Sistem Manajemen Engine", "obs": True, "port": False, "pihak3": False, "lisan": True, "tulis": True, "proyek": True, "lain": False},
    ],
    "tanggal_tanda_tangan": "19/10/2026",
}

def draw_header_box(c, x0=56.8, x1=542.9, top=21.8, bot=66.7):
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    x_div = 154.2
    c.line(x_div, Y(top), x_div, Y(bot))
    logo_file = os.path.join(ASSETS, "logo.png")
    
    # Calculate logo and text position relative to top and bot
    logo_top = top + 2.25
    logo_bot = logo_top + 38.9
    if os.path.exists(logo_file):
        c.drawImage(logo_file, 73.3, Y(logo_bot), 44.0, 38.9, mask="auto")
        
    c.setFont("Tah-B", 11.0)
    c.setFillColor(black)
    text_y = top + 26.2
    c.drawString(194.8, Y(text_y), "Lembaga Sertifikasi Profesi Praktisi Otomotif  LSP-PrO Indo")

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
    
    # ================= PAGE 1 =================
    draw_header_box(c, 56.8, 542.9, 21.8, 66.7)
    
    # Document Title
    c.setFont("Cal-B", 12.0)
    c.setFillColor(black)
    c.drawString(63.9, Y(94.0), "FR.AK.02. REKAMAN ASESMEN KOMPETENSI")
    
    # Single Unified Table on Page 1:
    # Outer boundaries: left=55.1, right=574.8, top=112.5, bot=780.0
    L = 55.1
    R = 574.8
    
    unit_tops = [
        367.0, 394.7, 420.6, 446.5, 472.4, 498.3, 524.2, 550.1, 576.0, 601.9
    ]
    
    # Outer border of Table 2
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(L, Y(780.0), R - L, 780.0 - 112.5)
    
    # Horizontal grid lines
    # r1: 130.6 (from 177.0 to R)
    c.line(177.0, Y(130.6), R, Y(130.6))
    # r2: 148.1 (full)
    c.line(L, Y(148.1), R, Y(148.1))
    # r3: 165.6 (full)
    c.line(L, Y(165.6), R, Y(165.6))
    # r4: 183.1 (full)
    c.line(L, Y(183.1), R, Y(183.1))
    # r5: 200.4 (full)
    c.line(L, Y(200.4), R, Y(200.4))
    # r6: 217.7 (from 177.0 to R)
    c.line(177.0, Y(217.7), R, Y(217.7))
    # r7: 235.9 (full)
    c.line(L, Y(235.9), R, Y(235.9))
    # r8: 289.0 (full)
    c.line(L, Y(289.0), R, Y(289.0))
    # r9: 367.0 (full)
    c.line(L, Y(367.0), R, Y(367.0))
    
    # unit lines
    for uy in unit_tops[1:]:
        c.line(L, Y(uy), R, Y(uy))
        
    # Rekomendasi line
    c.line(L, Y(627.4), R, Y(627.4))
    # Tindak lanjut line
    c.line(L, Y(691.1), R, Y(691.1))
    
    # Vertical grid lines on Page 1:
    # Metadata vertical lines (112.5 to 235.9)
    # x = 177.0 (r0 to r2, and r5 to r7)
    c.line(177.0, Y(148.1), 177.0, Y(112.5))
    c.line(177.0, Y(235.9), 177.0, Y(200.4))
    # x = 230.3 (r0 to r7)
    c.line(230.3, Y(235.9), 230.3, Y(112.5))
    # x = 244.4 (r0 to r7)
    c.line(244.4, Y(235.9), 244.4, Y(112.5))
    
    # Units & Evidences vertical columns (289.0 to 601.9):
    cols_x = [55.1, 298.0, 333.5, 368.9, 432.9, 468.3, 503.7, 539.3, 574.8]
    for cx in cols_x[1:-1]:
        c.line(cx, Y(601.9), cx, Y(289.0))
        
    # Vertical line for Rekomendasi, Tindak Lanjut, Komentar (601.9 to 780.0):
    c.line(cols_x[1], Y(780.0), cols_x[1], Y(601.9))
    
    # === TEXT ON PAGE 1 ===
    # Metadata text
    c.setFont("Cal", 11.0)
    c.drawString(64.5, Y(127.0), "Skema Sertifikasi")
    c.drawString(64.5, Y(140.0), "(KKNI/Okupasi/Klaster)")
    
    c.drawString(185.2, Y(124.0), "Judul")
    c.drawString(236.6, Y(124.0), ":")
    c.setFont("Cal-I", 11.0)
    c.drawString(248.0, Y(124.0), DATA["skema_nama"])
    
    c.setFont("Cal", 11.0)
    c.drawString(185.2, Y(142.0), "Nomor")
    c.drawString(236.6, Y(142.0), ":")
    c.drawString(248.0, Y(142.0), DATA["skema_nomor"])
    
    c.drawString(64.5, Y(160.0), "TUK")
    c.drawString(236.6, Y(160.0), ":")
    c.drawString(248.0, Y(160.0), DATA["tuk_tipe"])
    
    c.drawString(64.5, Y(177.0), "Nama Asesor")
    c.drawString(236.6, Y(177.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(248.0, Y(177.0), DATA["asesor_nama"])
    
    c.setFont("Cal", 11.0)
    c.drawString(64.5, Y(195.0), "Nama Asesi")
    c.drawString(236.6, Y(195.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(248.0, Y(195.0), DATA["asesi_nama"])
    
    c.setFont("Cal", 11.0)
    c.drawString(64.5, Y(221.0), "Tanggal Asesmen")
    c.drawString(185.2, Y(212.0), "Mulai")
    c.drawString(236.6, Y(212.0), ":")
    c.drawString(248.0, Y(212.0), DATA["tanggal_mulai"])
    
    c.drawString(185.2, Y(230.0), "Selesai")
    c.drawString(236.6, Y(230.0), ":")
    c.drawString(248.0, Y(230.0), DATA["tanggal_selesai"])
    
    # Instruction Row (Row 7)
    c.drawString(56.8, Y(262.0), "Beri tanda centang (√) di kolom yang sesuai untuk mencerminkan bukti yang diperoleh untuk menentukan")
    c.drawString(56.8, Y(275.5), "Kompetensi Asesi untuk setiap Unit Kompetensi.")
    
    # Table Header (Row 8)
    c.setFont("Cal-B", 11.0)
    c.drawString(139.5, Y(331.0), "Unit Kompetensi")
    
    # Rotated Evidence Headers
    # Col 1: Observasi Demonstrasi (mid = 316.0)
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(310.0, Y(354.0))
    c.rotate(90)
    c.drawString(0, 0, "Observasi")
    c.restoreState()
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(324.0, Y(360.0))
    c.rotate(90)
    c.drawString(0, 0, "Demonstrasi")
    c.restoreState()
    
    # Col 2: Portofolio (mid = 351.2)
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(352.0, Y(354.0))
    c.rotate(90)
    c.drawString(0, 0, "Portofolio")
    c.restoreState()
    
    # Col 3: Pernyataan Pihak Ketiga / Pertanyaan Wawancara (368.9 -> 432.9)
    c.saveState()
    c.setFont("Cal-B", 9.5)
    c.translate(382.0, Y(358.0))
    c.rotate(90)
    c.drawString(0, 0, "Pernyataan")
    c.restoreState()
    c.saveState()
    c.setFont("Cal-B", 9.5)
    c.translate(395.0, Y(360.0))
    c.rotate(90)
    c.drawString(0, 0, "Pihak Ketiga")
    c.restoreState()
    c.saveState()
    c.setFont("Cal-B", 9.5)
    c.translate(409.0, Y(360.0))
    c.rotate(90)
    c.drawString(0, 0, "Pertanyaan")
    c.restoreState()
    c.saveState()
    c.setFont("Cal-B", 9.5)
    c.translate(423.0, Y(360.0))
    c.rotate(90)
    c.drawString(0, 0, "Wawancara")
    c.restoreState()
    
    # Col 4: Pertanyaan Lisan (432.9 -> 468.3)
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(445.0, Y(358.0))
    c.rotate(90)
    c.drawString(0, 0, "Pertanyaan")
    c.restoreState()
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(459.0, Y(343.0))
    c.rotate(90)
    c.drawString(0, 0, "Lisan")
    c.restoreState()
    
    # Col 5: Pertanyaan Tertulis (468.3 -> 503.7)
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(481.0, Y(358.0))
    c.rotate(90)
    c.drawString(0, 0, "Pertanyaan")
    c.restoreState()
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(494.0, Y(349.0))
    c.rotate(90)
    c.drawString(0, 0, "Tertulis")
    c.restoreState()
    
    # Col 6: Proyek Kerja (503.7 -> 539.3)
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(522.0, Y(360.0))
    c.rotate(90)
    c.drawString(0, 0, "Proyek Kerja")
    c.restoreState()
    
    # Col 7: Lainnya (539.3 -> 574.8)
    c.saveState()
    c.setFont("Cal-B", 10.0)
    c.translate(558.0, Y(349.0))
    c.rotate(90)
    c.drawString(0, 0, "Lainnya")
    c.restoreState()
    
    # Draw 9 Unit Rows
    for idx, u in enumerate(DATA["units"]):
        y_top = unit_tops[idx]
        y_bot = unit_tops[idx + 1]
        y_mid = (y_top + y_bot) / 2
        
        c.setFont("Arial", 10.5)
        w_title = cols_x[1] - cols_x[0] - 12.0
        lines = wrap_text(c, u["title"], "Arial", 10.5, w_title)
        if len(lines) == 1:
            c.drawString(cols_x[0] + 5.0, Y(y_mid + 3.5), lines[0])
        else:
            c.drawString(cols_x[0] + 5.0, Y(y_top + 10.5), lines[0])
            c.drawString(cols_x[0] + 5.0, Y(y_top + 22.0), lines[1])
            
        # Draw checkmarks
        c.setFont("Cal-B", 12.0)
        checks = [u["obs"], u["port"], (u.get("pihak3", False) or u.get("wawancara", False)), u["lisan"], u["tulis"], u["proyek"], u["lain"]]
        for ci, chk in enumerate(checks):
            if chk:
                cx = (cols_x[1 + ci] + cols_x[2 + ci]) / 2
                c.drawCentredString(cx, Y(y_mid + 4.0), "✓")
                
    # Rekomendasi Row (601.9 -> 627.4)
    c.setFont("Cal", 11.0)
    c.drawString(cols_x[0] + 6.0, Y(617.0), "Rekomendasi hasil asesmen")
    
    # Checkbox Kompeten / Belum Kompeten (Calibri-Bold 15pt)
    c.setFont("Cal-B", 15.0)
    cb_sz = 11.0
    bx1 = cols_x[1] + 10.0
    by1 = 612.0
    c.rect(bx1, Y(by1 + cb_sz), cb_sz, cb_sz)
    if DATA["rekomendasi_kompeten"]:
        c.setFont("Cal-B", 11.0)
        c.drawCentredString(bx1 + cb_sz / 2, Y(by1 + cb_sz) + 1.5, "✓")
        
    c.setFont("Cal-B", 15.0)
    c.drawString(bx1 + 16.0, Y(622.0), "Kompeten /")
    
    bx2 = bx1 + 105.0
    c.rect(bx2, Y(by1 + cb_sz), cb_sz, cb_sz)
    if not DATA["rekomendasi_kompeten"]:
        c.setFont("Cal-B", 11.0)
        c.drawCentredString(bx2 + cb_sz / 2, Y(by1 + cb_sz) + 1.5, "✓")
        
    c.setFont("Cal-B", 15.0)
    c.drawString(bx2 + 16.0, Y(622.0), "Belum kompeten")
    
    # Tindak Lanjut Row (627.4 -> 691.1)
    c.setFont("Cal-B", 11.0)
    c.drawString(cols_x[0] + 6.0, Y(639.0), "Tindak lanjut yang dibutuhkan")
    c.setFont("Cal", 10.5)
    c.drawString(cols_x[0] + 6.0, Y(652.0), "(Masukkan pekerjaan tambahan dan asesmen yang")
    c.drawString(cols_x[0] + 6.0, Y(665.0), "diperlukan untuk mencapai kompetensi)")
    
    # Tindak lanjut text
    c.setFont("Cal", 10.5)
    tin_lines = wrap_text(c, DATA["tindak_lanjut"], "Cal", 10.5, cols_x[-1] - cols_x[1] - 16.0)
    ty = 645.0
    for l in tin_lines:
        c.drawString(cols_x[1] + 8.0, Y(ty), l)
        ty += 14.0
        
    # Komentar Row (691.1 -> 780.0)
    c.setFont("Cal", 11.0)
    c.drawString(cols_x[0] + 6.0, Y(722.5), "Komentar/ Observasi oleh asesor")
    
    # Komentar text
    kom_lines = wrap_text(c, DATA["komentar_asesor"], "Cal", 10.5, cols_x[-1] - cols_x[1] - 16.0)
    ky = 718.0
    for l in kom_lines:
        c.drawString(cols_x[1] + 8.0, Y(ky), l)
        ky += 14.0
        
    c.showPage()
    
    # ================= PAGE 2 =================
    draw_header_box(c, 56.8, 542.9, 21.8, 66.7)
    
    # Continuation box of Komentar (Table 2 top overflow on Page 2):
    # From y = 80.6 to 135.7
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(55.1, Y(135.7), 574.8 - 55.1, 135.7 - 80.6)
    c.line(298.0, Y(80.6), 298.0, Y(135.7))
    
    # Signature Box on Page 2:
    # Outer boundaries: left=56.3, right=571.3, top=149.9, bot=319.4
    sL = 56.3
    sR = 571.3
    sig_rows = [149.9, 164.8, 184.7, 227.1, 242.1, 260.9, 279.7, 319.4]
    
    c.rect(sL, Y(sig_rows[-1]), sR - sL, sig_rows[-1] - sig_rows[0])
    for sy in sig_rows[1:-1]:
        c.line(sL, Y(sy), sR, Y(sy))
        
    # Vertical dividers for Signature table
    s_col1 = 225.2
    s_col2 = 232.3
    # For Asesi: rows 1 to 3 (164.8 to 227.1)
    c.line(s_col1, Y(sig_rows[3]), s_col1, Y(sig_rows[1]))
    c.line(s_col2, Y(sig_rows[3]), s_col2, Y(sig_rows[1]))
    # For Asesor: rows 4 to 7 (242.1 to 319.4)
    c.line(s_col1, Y(sig_rows[7]), s_col1, Y(sig_rows[4]))
    c.line(s_col2, Y(sig_rows[7]), s_col2, Y(sig_rows[4]))
    
    # Signature Texts:
    # Row 0: Asesi :
    c.setFont("Cal-B", 11.0)
    c.drawString(63.3, Y(161.5), "Asesi :")
    
    # Row 1: Nama
    c.setFont("Cal", 11.0)
    c.drawString(63.3, Y(180.5), "Nama")
    c.drawString(227.5, Y(180.5), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(236.0, Y(180.5), DATA["asesi_nama"])
    
    # Row 2: Tanda tangan dan Tanggal
    c.setFont("Cal", 11.0)
    c.drawString(63.3, Y(202.0), "Tanda tangan")
    c.drawString(63.3, Y(218.0), "dan Tanggal")
    c.drawString(227.5, Y(210.0), ":")
    c.setFont("Cal-I", 10.0)
    c.drawString(236.0, Y(210.0), f"[Tanda Tangan Digital]   -   {DATA['tanggal_tanda_tangan']}")
    
    # Row 3: Asesor :
    c.setFont("Cal-B", 11.0)
    c.drawString(63.3, Y(239.5), "Asesor :")
    
    # Row 4: Nama
    c.setFont("Cal", 11.0)
    c.drawString(63.3, Y(257.0), "Nama")
    c.drawString(227.5, Y(257.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(236.0, Y(257.0), DATA["asesor_nama"])
    
    # Row 5: No. Reg
    c.setFont("Cal", 11.0)
    c.drawString(63.3, Y(275.0), "No. Reg")
    c.drawString(227.5, Y(275.0), ":")
    c.drawString(236.0, Y(275.0), DATA["asesor_no_reg"])
    
    # Row 6: Tanda tangan dan Tanggal
    c.drawString(63.3, Y(296.0), "Tanda tangan")
    c.drawString(63.3, Y(312.0), "dan Tanggal")
    c.drawString(227.5, Y(304.0), ":")
    c.setFont("Cal-I", 10.0)
    c.drawString(236.0, Y(304.0), f"[Tanda Tangan Digital]   -   {DATA['tanggal_tanda_tangan']}")
    
    c.showPage()
    
    # ================= PAGE 3 =================
    draw_header_box(c, 56.8, 542.9, 36.5, 81.4)
    
    c.setFont("Cal-B", 11.0)
    c.drawString(70.8, Y(108.0), "LAMPIRAN DOKUMEN:")
    
    lampiran = [
        "1. Dokumen APL 01 peserta",
        "2. Dokumen APL 02 peserta",
        "3. Bukti-bukti berkualitas peserta",
        "4. Tinjauan proses asesmen"
    ]
    
    c.setFont("Cal", 11.0)
    ly = 124.0
    for item in lampiran:
        c.drawString(70.8, Y(ly), item)
        ly += 16.0
        
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.02: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_02_generated.pdf")
    render(out_file)
