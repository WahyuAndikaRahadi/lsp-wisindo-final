#!/usr/bin/env python3
"""
Generator Surat Pernyataan Pemegang Sertifikat Kompetensi - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

try:
    from font_helper import register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import register_reportlab_fonts

register_reportlab_fonts(["Lib", "Lib-B"])

PAGE_W, PAGE_H = 595.3, 841.9

DATA = {
    "hari": "Senin",
    "tanggal": "19",
    "bulan": "Oktober",
    "tahun": "2026",
    "tempat": "TUK Otomotif PrO Indo, Bekasi",
    "nama": "Wahyu Andika Rahadi",
    "nik": "3201234567890001",
    "skema": "Maintenance Gasoline Engine Injection System",
    "no_blanko": "BLK-2026-009182",
    "no_sertifikat": "CERT-LSP-PRO-2026-00129",
    "kota_ttd": "Bekasi",
    "tanggal_ttd": "19 Oktober 2026",
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
            if current: lines.append(current)
            current = w
    if current: lines.append(current)
    return lines

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # 1. Letterhead / Kop Surat
    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        c.drawImage(kop_file, 27.0, PAGE_H - 110.0, 549.0, 96.5, mask="auto")
        
    # 2. Document Title
    c.setFont("Lib-B", 11)
    c.drawCentredString(PAGE_W / 2, PAGE_H - 123.0, "SURAT PERNYATAAN PEMEGANG SERTIFIKAT KOMPETENSI")
    c.drawCentredString(PAGE_W / 2, PAGE_H - 146.0, "LEMBAGA SERTIFIKASI PROFESI PIHAK KETIGA")
    c.drawCentredString(PAGE_W / 2, PAGE_H - 167.0, "PRAKTISI OTOMOTIF INDONESIA")
    c.drawCentredString(PAGE_W / 2, PAGE_H - 190.0, "(LSP P3 PRO INDO)")
    
    # 3. Date & Location Lead
    c.setFont("Lib", 11)
    intro_txt = f"Pada hari ini {DATA['hari']} Tanggal {DATA['tanggal']} Bulan {DATA['bulan']} Tahun {DATA['tahun']} bertempat di {DATA['tempat']}."
    c.drawString(72.0, PAGE_H - 235.0, intro_txt)
    
    # 4. Identity
    c.drawString(72.0, PAGE_H - 258.0, "Saya bertanda tangan dibawah ini :")
    
    c.drawString(72.0, PAGE_H - 282.0, "Nama")
    c.drawString(160.0, PAGE_H - 282.0, f": {DATA['nama']}")
    
    c.drawString(72.0, PAGE_H - 302.0, "NIK")
    c.drawString(160.0, PAGE_H - 302.0, f": {DATA['nik']}")
    
    c.drawString(72.0, PAGE_H - 326.0, "Skema")
    c.drawString(160.0, PAGE_H - 326.0, f": {DATA['skema']}")
    
    c.drawString(72.0, PAGE_H - 350.0, "No.Blanko")
    c.drawString(160.0, PAGE_H - 350.0, f": {DATA['no_blanko']}")
    
    c.drawString(72.0, PAGE_H - 370.0, "No. Sertifikat")
    c.drawString(160.0, PAGE_H - 370.0, f": {DATA['no_sertifikat']}")
    
    # 5. Statements
    c.drawString(72.0, PAGE_H - 418.0, "Dengan ini mengatakan bahwa saya akan:")
    
    points = [
        "1. Mematuhi ketentuan yang relevan dalam skema sertifikasi.",
        "2. Menggunakan sertifikat hanya untuk ruang lingkup sertifikasi yang diberikan.",
        "3. Tidak menggunakan sertifikat yang dapat mencemarkan / merugikan Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) dan tidak memberikan pernyataan terkait sertifikasi yang oleh Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) dianggap dapat menyesatkan atau tidak dapat dipertanggungjawabkan.",
        "4. Menghentikan penggunaan atau pengakuan sertifikat setelah sertifikat dibekukan atau dicabut oleh Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) dan mengembalikan sertifikat kepada Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo)."
    ]
    
    y = PAGE_H - 438.0
    for p in points:
        lines = wrap_text(c, p, "Lib", 11, PAGE_W - 144.0)
        for idx, ln in enumerate(lines):
            c.drawString(72.0 if idx == 0 else 88.0, y, ln)
            y -= 15.0
        y -= 7.0
        
    y -= 5.0
    c.drawString(72.0, y, "Demikian pernyataan ini saya buat dengan sebenar-benarnya dan penuh tanggung jawab.")
    
    # 6. Signature
    y_sig = y - 44.0
    sig_loc = f"{DATA['kota_ttd']}, {DATA['tanggal_ttd']}"
    c.drawRightString(PAGE_W - 72.0, y_sig, sig_loc)
    
    y_name = y_sig - 75.0
    c.drawRightString(PAGE_W - 72.0, y_name, f"( {DATA['nama']} )")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate Surat Pernyataan: {dest_path}")

if __name__ == "__main__":
    import json
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "Surat_Pernyataan_generated.pdf")
    if len(sys.argv) > 1:
        if sys.argv[1].endswith(".json") and os.path.isfile(sys.argv[1]):
            with open(sys.argv[1], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        else:
            out_file = sys.argv[1]
    render(out_file)
