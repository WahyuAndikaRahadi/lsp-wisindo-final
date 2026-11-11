#!/usr/bin/env python3
"""
Generator Daftar Periksa Kelengkapan Dokumen MUK - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

try:
    from font_helper import draw_checkmark, register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import draw_checkmark, register_reportlab_fonts

register_reportlab_fonts(["Cal", "Cal-B", "Cal-I", "DJ"])

PAGE_W, PAGE_H = 612.0, 792.0

DATA = {
    "skema_nama": "MAINTENANCE GASOLINE ENGINE INJECTION SYSTEM",
    "manager_sertifikasi": "Karyanto",
    "ketua_tuk": "Agus Sugiyanto, S.T.",
    "items": [
        # Pelaksanaan
        {"section": "A. PELAKSANAAN ASESMEN"},
        {"no": "1", "name": "FR.APL.01. Permohonan Sertifikasi Kompetensi", "ada": True},
        {"no": "2", "name": "FR.APL.02. Asesmen Mandiri", "ada": True},
        {"no": "3", "name": "Portofolio Asesi", "ada": False},
        {"no": "4", "name": "FR.MAPA.01. Merencanakan Aktivitas dan Proses Asesmen", "ada": True},
        {"no": "5", "name": "a. Skema Sertifikasi", "ada": True},
        {"no": "", "name": "b. Standar Kompetensi & Peta Kompetensi Kerja", "ada": True},
        {"no": "6", "name": "FR-MAPA.02. Peta Instrumen Asesmen Hasil Pendekatan Asesmen...", "ada": True},
        {"no": "7", "name": "FR AK 07. Ceklis Penyesuaian yang Wajar", "ada": True},
        {"no": "8", "name": "FR.AK.04. Formulir Banding", "ada": True},
        {"no": "9", "name": "FR.AK.01. Persetujuan Asesmen dan Kerahasiaan", "ada": True},
        {"section": "INSTRUMEN ASESMEN (disesuaikan dengan MAPA-01/MAPA-02)"},
        {"no": "10", "name": "FR.IA.01.CL - Ceklis Observasi Aktivitas Di Tempat Kerja...", "ada": True},
        {"no": "11", "name": "FR.IA.02. TPD - Tugas Praktik Demonstrasi", "ada": True},
        {"no": "12", "name": "FR.IA.03. PMO - Pertanyaan Untuk Mendukung Observasi", "ada": True},
        {"no": "13", "name": "a. FR.IA.04. A DIT - Daftar Instruksi Terstruktur...", "ada": False},
        {"no": "", "name": "b. FR.IA.04B. DIT - Penilaian Proyek Singkat...", "ada": False},
        {"no": "14", "name": "FR.IA.05. DPT - Pertanyaan Tertulis Pilihan Ganda...", "ada": True},
        {"no": "15", "name": "FR.IA.06. A. DPT - Pertanyaan Tertulis Esai...", "ada": False},
        {"no": "16", "name": "FR.IA.07. DPL - Pertanyaan Lisan", "ada": False},
        {"no": "17", "name": "FR.IA.08. CVP - Ceklis Verifikasi Portofolio", "ada": False},
        {"no": "18", "name": "FR.IA.09. PW - Pertanyaan Wawancara", "ada": False},
        {"no": "19", "name": "FR.IA.10. VPK – Verifikasi Pihak Ketiga", "ada": False},
        {"no": "20", "name": "FR.IA.11. CRP - Ceklis Reviu Produk", "ada": False},
        {"section": "B. KEPUTUSAN :"},
        {"no": "21", "name": "FR.AK.02. Rekaman Asesmen Kompetensi", "ada": True},
        {"no": "22", "name": "FR.AK.03. Umpan Balik dan Catatan Asesmen", "ada": True},
        {"section": "C. LAPORAN ASESMEN"},
        {"no": "23", "name": "FR.AK.05. Laporan Asesmen", "ada": True},
        {"no": "24", "name": "FR.AK.06. Meninjau Proses Asesmen", "ada": True},
        {"section": "D. VALIDASI"},
        {"no": "25.", "name": "FR.VA. Memberikan Kontribusi dalam Validasi Asesmen", "ada": True},
    ]
}

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # Title
    c.setFont("Cal-B", 12)
    c.drawCentredString(PAGE_W / 2, PAGE_H - 35.0, "DAFTAR PERIKSA")
    c.setFont("Cal-B", 11)
    c.drawCentredString(PAGE_W / 2, PAGE_H - 49.0, f"KELENGKAPAN DOKUMEN {DATA['skema_nama']}")
    
    # Table layout
    x0 = 66.4
    x1 = 114.8
    x2 = 369.4
    x_mid = (369.4 + 466.9) / 2
    x3 = 466.9
    x4 = 555.4
    
    y_top = PAGE_H - 67.4
    h_hdr1 = 14.0
    h_hdr2 = 14.0
    
    row_h = 14.2
    
    # Outer & Header
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    
    # Header 2 rows
    c.rect(x0, y_top - h_hdr1 - h_hdr2, x4 - x0, h_hdr1 + h_hdr2)
    c.line(x0, y_top - h_hdr1 - h_hdr2, x4, y_top - h_hdr1 - h_hdr2)
    c.line(x2, y_top - h_hdr1, x3, y_top - h_hdr1)
    
    c.line(x1, y_top, x1, y_top - h_hdr1 - h_hdr2)
    c.line(x2, y_top, x2, y_top - h_hdr1 - h_hdr2)
    c.line(x_mid, y_top - h_hdr1, x_mid, y_top - h_hdr1 - h_hdr2)
    c.line(x3, y_top, x3, y_top - h_hdr1 - h_hdr2)
    
    c.setFont("Cal-B", 9)
    c.drawCentredString((x0 + x1) / 2, y_top - 18.0, "NO")
    c.drawCentredString((x1 + x2) / 2, y_top - 18.0, "JENIS DOKUMEN")
    c.drawCentredString((x2 + x3) / 2, y_top - 10.0, "STATUS*")
    c.setFont("Cal-B", 8)
    c.drawCentredString((x2 + x_mid) / 2, y_top - 23.0, "ADA")
    c.drawCentredString((x_mid + x3) / 2, y_top - 23.0, "TIDAK")
    c.setFont("Cal-B", 8.5)
    c.drawCentredString((x3 + x4) / 2, y_top - 18.0, "KETERANGAN")
    
    # Draw Rows
    curr_y = y_top - h_hdr1 - h_hdr2
    for item in DATA["items"]:
        next_y = curr_y - row_h
        
        c.rect(x0, next_y, x4 - x0, row_h)
        if "section" in item:
            # Section Header row
            c.setFont("Cal-B", 8)
            c.drawString(x0 + 6.0, curr_y - 10.5, item["section"])
        else:
            # Data row
            c.line(x1, curr_y, x1, next_y)
            c.line(x2, curr_y, x2, next_y)
            c.line(x_mid, curr_y, x_mid, next_y)
            c.line(x3, curr_y, x3, next_y)
            
            c.setFont("Cal", 8)
            if item.get("no"):
                c.drawCentredString((x0 + x1) / 2, curr_y - 10.5, str(item["no"]))
            c.drawString(x1 + 4.0, curr_y - 10.5, item["name"][:55])
            
            # Checkbox
            sz = 7
            mid_y = (curr_y + next_y) / 2
            
            # Ada
            bx_a = (x2 + x_mid) / 2 - sz / 2
            c.rect(bx_a, mid_y - sz / 2, sz, sz)
            if item.get("ada"):
                draw_checkmark(c, bx_a + sz / 2, mid_y - sz / 2 + 0.5, size=7.0, font_name="DJ")
                
            # Tidak
            bx_t = (x_mid + x3) / 2 - sz / 2
            c.rect(bx_t, mid_y - sz / 2, sz, sz)
            if not item.get("ada"):
                draw_checkmark(c, bx_t + sz / 2, mid_y - sz / 2 + 0.5, size=7.0, font_name="DJ")
                
        curr_y = next_y
        
    # Signature rows at bottom
    sig_h = 38.0
    c.rect(x0, curr_y - sig_h, x4 - x0, sig_h)
    x_half = (x0 + x4) / 2
    c.line(x_half, curr_y, x_half, curr_y - sig_h)
    
    c.setFont("Cal-B", 8)
    c.drawString(x0 + 8.0, curr_y - 11.0, "Ketua TUK :")
    c.setFont("Cal", 8)
    c.drawString(x0 + 8.0, curr_y - 23.0, f"Nama : {DATA['ketua_tuk']}")
    c.drawString(x0 + 8.0, curr_y - 34.0, "Paraf : [Terverifikasi]")
    
    c.setFont("Cal-B", 8)
    c.drawString(x_half + 8.0, curr_y - 11.0, "Manager Sertifikasi LSP PrO Indo :")
    c.setFont("Cal", 8)
    c.drawString(x_half + 8.0, curr_y - 23.0, f"Nama : {DATA['manager_sertifikasi']}")
    c.drawString(x_half + 8.0, curr_y - 34.0, "Paraf : [Terverifikasi]")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate Daftar Periksa: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "Daftar_Periksa_generated.pdf")
    render(out_file)
