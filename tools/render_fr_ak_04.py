#!/usr/bin/env python3
"""
Generator FR.AK.04 (Banding Asesmen) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# Setup fonts
try:
    from font_helper import draw_checkmark, register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import draw_checkmark, register_reportlab_fonts

register_reportlab_fonts(["Cal", "Cal-B", "Cal-I", "DJ"])

PAGE_W, PAGE_H = 595.5, 842.5

DATA = {
    "asesi_nama": "Wahyu Andika Rahadi",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "tanggal_asesmen": "19/10/2026",
    "pertanyaan_1_ya": True,
    "pertanyaan_2_ya": True,
    "pertanyaan_3_ya": False,
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "alasan_banding": "Asesi mampu mengerjakannya dan seluruh parameter pemeriksaan injeksi telah sesuai SOP bengkel resmi.",
    "tanggal_tanda_tangan": "20/10/2026",
}

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # Title
    c.setFont("Cal-B", 12)
    c.drawString(69.6, PAGE_H - 72.8, "FR.AK.04. BANDING ASESMEN")
    
    # Outer Table Coordinates
    x0 = 71.8
    x1 = 560.4
    y_top = PAGE_H - 98.6
    
    # Row Heights
    # R0: 21.6, R1: 21.6, R2: 21.7, R3: 28.3, R4: 35.0, R5: 35.0, R6: 35.2, R7: 74.2, R8: 82.5, R9: 49.3, R10: 79.1
    row_heights = [21.6, 21.6, 21.7, 28.3, 35.0, 35.0, 35.2, 74.2, 82.5, 49.3, 79.1]
    
    y_current = y_top
    y_lines = [y_current]
    for h in row_heights:
        y_current -= h
        y_lines.append(y_current)
        
    c.setLineWidth(0.8)
    c.setStrokeColor(black)
    
    # Draw horizontal table lines
    for y in y_lines:
        c.line(x0, y, x1, y)
        
    # Draw outer vertical lines
    c.line(x0, y_top, x0, y_lines[-1])
    c.line(x1, y_top, x1, y_lines[-1])
    
    # Inner vertical lines for question columns (Rows 3, 4, 5, 6)
    x_col1 = 452.6
    x_col2 = 502.1
    c.line(x_col1, y_lines[3], x_col1, y_lines[7])
    c.line(x_col2, y_lines[3], x_col2, y_lines[7])
    
    # Content: R0 (Nama Asesi)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[0] - 14.5, "Nama Asesi:")
    c.setFont("Cal", 11)
    c.drawString(x0 + 95.0, y_lines[0] - 14.5, DATA["asesi_nama"])
    
    # Content: R1 (Nama Asesor)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[1] - 14.5, "Nama Asesor:")
    c.setFont("Cal", 11)
    c.drawString(x0 + 95.0, y_lines[1] - 14.5, DATA["asesor_nama"])
    
    # Content: R2 (Tanggal Asesmen)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[2] - 14.5, "Tanggal Asesmen:")
    c.setFont("Cal", 11)
    c.drawString(x0 + 115.0, y_lines[2] - 14.5, DATA["tanggal_asesmen"])
    
    # Content: R3 (Header Pertanyaan)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[3] - 17.5, "Jawablah dengan Ya atau Tidak pertanyaan-pertanyaan berikut ini :")
    c.drawCentredString((x_col1 + x_col2) / 2, y_lines[3] - 17.5, "YA")
    c.drawCentredString((x_col2 + x1) / 2, y_lines[3] - 17.5, "TIDAK")
    
    # Helper to draw checkbox
    def draw_box(x_center, y_center, checked):
        size = 10
        bx = x_center - size / 2
        by = y_center - size / 2
        c.setLineWidth(0.8)
        c.rect(bx, by, size, size)
        if checked:
            draw_checkmark(c, x_center, by + 1.5, size=10.0, font_name="DJ")
            
    # Content: R4 (Pertanyaan 1)
    c.setFont("Cal", 11)
    c.drawString(x0 + 7.7, y_lines[4] - 20.0, "Apakah Proses Banding telah dijelaskan kepada Anda?")
    draw_box((x_col1 + x_col2) / 2, y_lines[4] - 17.5, DATA["pertanyaan_1_ya"])
    draw_box((x_col2 + x1) / 2, y_lines[4] - 17.5, not DATA["pertanyaan_1_ya"])
    
    # Content: R5 (Pertanyaan 2)
    c.drawString(x0 + 7.7, y_lines[5] - 20.0, "Apakah Anda telah mendiskusikan Banding dengan Asesor?")
    draw_box((x_col1 + x_col2) / 2, y_lines[5] - 17.5, DATA["pertanyaan_2_ya"])
    draw_box((x_col2 + x1) / 2, y_lines[5] - 17.5, not DATA["pertanyaan_2_ya"])
    
    # Content: R6 (Pertanyaan 3)
    c.drawString(x0 + 7.7, y_lines[6] - 14.5, "Apakah Anda mau melibatkan “orang lain” membantu Anda dalam Proses")
    c.drawString(x0 + 7.7, y_lines[6] - 27.5, "Banding?")
    draw_box((x_col1 + x_col2) / 2, y_lines[6] - 17.5, DATA["pertanyaan_3_ya"])
    draw_box((x_col2 + x1) / 2, y_lines[6] - 17.5, not DATA["pertanyaan_3_ya"])
    
    # Content: R7 (Keputusan Asesmen)
    c.drawString(x0 + 7.7, y_lines[7] - 15.0, "Banding ini diajukan atas Keputusan Asesmen yang dibuat terhadap Skema Sertifikasi")
    c.drawString(x0 + 7.7, y_lines[7] - 28.5, "(Kualifikasi/Klaster/Okupasi) berikut :")
    c.drawString(x0 + 7.7, y_lines[7] - 45.0, f"Skema Sertifikasi : {DATA['skema_nama']}")
    c.drawString(x0 + 7.7, y_lines[7] - 61.5, f"No. Skema Sertifikasi : {DATA['skema_nomor']}")
    
    # Content: R8 (Alasan Banding)
    c.drawString(x0 + 7.7, y_lines[8] - 16.0, "Banding ini diajukan atas alasan sebagai berikut :")
    c.setFont("Cal-I", 11)
    # wrap text
    alasan = DATA["alasan_banding"]
    c.drawString(x0 + 7.7, y_lines[8] - 34.0, alasan[:95])
    if len(alasan) > 95:
        c.drawString(x0 + 7.7, y_lines[8] - 48.0, alasan[95:190])
        
    # Content: R9 (Pemberitahuan Hak)
    c.setFont("Cal", 11)
    c.drawString(x0 + 7.7, y_lines[9] - 16.0, "Anda mempunyai hak mengajukan banding jika Anda menilai Proses Asesmen tidak sesuai SOP dan tidak")
    c.drawString(x0 + 7.7, y_lines[9] - 29.5, "memenuhi Prinsip Asesmen.")
    
    # Content: R10 (Tanda Tangan)
    c.drawString(x0 + 7.7, y_lines[10] - 38.0, "Tanda tangan Asesi : …………………………………………. Tanggal : …………………………….")
    c.setFont("Cal-B", 10)
    c.drawString(x0 + 130.0, y_lines[10] - 37.0, DATA["asesi_nama"])
    c.drawString(x0 + 360.0, y_lines[10] - 37.0, DATA["tanggal_tanda_tangan"])
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.04: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_04_generated.pdf")
    render(out_file)
