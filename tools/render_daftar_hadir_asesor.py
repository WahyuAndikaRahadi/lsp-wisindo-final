#!/usr/bin/env python3
"""
Generator 2.4 DAFTAR HADIR ASESOR - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 1 halaman sesuai master dokumen BNSP.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

def find_font(filename, fallback="LiberationSans-Regular.ttf"):
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
    if fallback and fallback != filename:
        return find_font(fallback, fallback=None)
    return filename

font_map = {
    "Lib": ("LiberationSans-Regular.ttf", "LiberationSans-Regular.ttf"),
    "Lib-B": ("LiberationSans-Bold.ttf", "LiberationSans-Bold.ttf"),
    "Lib-I": ("LiberationSans-Italic.ttf", "LiberationSans-Italic.ttf"),
    "Cal": ("Carlito-Regular.ttf", "LiberationSans-Regular.ttf"),
    "Cal-B": ("Carlito-Bold.ttf", "LiberationSans-Bold.ttf"),
}
for name, (ffile, fallback) in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile, fallback)))

PAGE_W, PAGE_H = 596.0, 842.0
Y = lambda top: PAGE_H - top

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "tuk_nama": "SMK Negeri 3 Karawang",
    "tuk_alamat": "Perumahan Griya Kondang Asri, Kelurahan Kondangjaya, Kecamatan Karawang Timur, Kabupaten Karawang, Jawa Barat (Kode Pos 41317)",
    "tuk_kota": "Karawang",
    "tanggal_uji": "19 Oktober 2026",
    "tanggal_dokumen": "19 Oktober 2026",
    "jumlah_asesi": "10 (Sepuluh) orang",
    "penanggungjawab_nama": "H. Ahmad Fauzi, S.Pd., M.M.",
    "penanggungjawab_jabatan": "Kepala TUK Sewaktu",
    "asesor": [
        {"no": 1, "nama": "Drs. Budi Santoso, M.T.", "no_met": "MET.000.001234 2022"},
        {"no": 2, "nama": "Ir. Hendra Gunawan, S.T.", "no_met": "MET.000.005678 2023"},
    ]
}

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # 1. Header (Kop Surat)
    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        c.drawImage(kop_file, 28.5, Y(15.0 + 98.2), 539.0, 98.2, mask="auto")
        
    # 2. Title
    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PAGE_W / 2, Y(132.0), "DAFTAR HADIR ASESOR")
    
    # 3. Metadata List (1 to 5)
    c.setFont("Lib", 9.0)
    x_num = 87.0
    x_lbl = 99.2
    x_col = 177.0
    x_val = 183.1
    
    # Item 1
    c.drawString(x_num, Y(165.0), "1.")
    c.drawString(x_lbl, Y(165.0), "Skema")
    c.drawString(x_col, Y(165.0), ":")
    c.drawString(x_val, Y(165.0), DATA["skema_nama"])
    
    # Item 2
    c.drawString(x_num, Y(180.0), "2.")
    c.drawString(x_lbl, Y(180.0), "TUK")
    c.drawString(x_col, Y(180.0), ":")
    c.drawString(x_val, Y(180.0), DATA["tuk_nama"])
    
    # Item 3: Alamat (2 lines)
    c.drawString(x_num, Y(195.0), "3.")
    c.drawString(x_lbl, Y(195.0), "Alamat")
    c.drawString(x_col, Y(195.0), ":")
    c.drawString(x_val, Y(195.0), "Perumahan Griya Kondang Asri, Kelurahan Kondangjaya, Kecamatan Karawang")
    c.drawString(x_val, Y(209.0), "Timur, Kabupaten Karawang, Jawa Barat (Kode Pos 41317)")
    
    # Item 4
    c.drawString(x_num, Y(224.0), "4.")
    c.drawString(x_lbl, Y(224.0), "Tanggal Uji")
    c.drawString(x_col, Y(224.0), ":")
    c.drawString(x_val, Y(224.0), DATA["tanggal_uji"])
    
    # Item 5
    c.drawString(x_num, Y(239.0), "5.")
    c.drawString(x_lbl, Y(239.0), "Jumlah Asesi")
    c.drawString(x_col, Y(239.0), ":")
    c.drawString(x_val, Y(239.0), DATA["jumlah_asesi"])
    
    # 4. Table Asesor
    cols = [50.4, 76.5, 242.3, 391.5, 560.4]
    top_tbl = 264.3
    hdr_h = 23.2
    row_h = 23.0
    n_rows = 5
    bot_tbl = top_tbl + hdr_h + n_rows * row_h
    
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    c.line(cols[0], Y(top_tbl + hdr_h), cols[-1], Y(top_tbl + hdr_h))
    
    for r in range(1, n_rows):
        y_r = top_tbl + hdr_h + r * row_h
        c.line(cols[0], Y(y_r), cols[-1], Y(y_r))
        
    for x in cols[1:-1]:
        c.line(x, Y(top_tbl), x, Y(bot_tbl))
        
    # Headers
    c.setFont("Lib-B", 9.0)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y(top_tbl + 15.0), "No.")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y(top_tbl + 15.0), "Nama Asesor Kompetensi")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y(top_tbl + 15.0), "No.MET")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y(top_tbl + 15.0), "Tanda Tangan")
    
    # Rows
    c.setFont("Lib", 8.5)
    for idx in range(n_rows):
        y_mid = top_tbl + hdr_h + idx * row_h + row_h / 2
        c.drawCentredString((cols[0] + cols[1]) / 2, Y(y_mid + 3.0), f"{idx + 1}.")
        
        if idx < len(DATA["asesor"]):
            as_info = DATA["asesor"][idx]
            c.drawString(cols[1] + 6.0, Y(y_mid + 3.0), as_info["nama"])
            c.drawCentredString((cols[2] + cols[3]) / 2, Y(y_mid + 3.0), as_info["no_met"])
            
            sig_num = str(idx + 1)
            if (idx + 1) % 2 != 0:
                c.drawString(cols[3] + 8.0, Y(y_mid + 3.0), f"{sig_num}. [Ttd Digital]")
            else:
                c.drawString((cols[3] + cols[4]) / 2 + 5.0, Y(y_mid + 3.0), f"{sig_num}. [Ttd Digital]")
                
    # 5. Signatures below
    y_sig = bot_tbl + 25.0
    c.drawString(324.0, Y(y_sig), f"{DATA['tuk_kota']}, {DATA['tanggal_dokumen']}")
    
    c.setFont("Lib-B", 9.0)
    c.drawString(324.0, Y(y_sig + 24.0), "Penanggungjawab kegiatan")
    
    c.setFont("Lib", 8.5)
    c.drawString(324.0, Y(y_sig + 39.0), "Nama")
    c.drawString(396.0, Y(y_sig + 39.0), f": {DATA['penanggungjawab_nama']}")
    c.drawString(324.0, Y(y_sig + 54.0), "Jabatan")
    c.drawString(396.0, Y(y_sig + 54.0), f": {DATA['penanggungjawab_jabatan']}")
    c.drawString(324.0, Y(y_sig + 69.0), "Tanda tangan")
    c.drawString(396.0, Y(y_sig + 69.0), f": [Ttd Digital]")
    c.drawString(324.0, Y(y_sig + 120.0), "………………………………….")
    
    c.save()
    print(f"OK -> Sukses generate DAFTAR HADIR ASESOR: {dest_path}")

if __name__ == "__main__":
    import json
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "Daftar_Hadir_Asesor_generated.pdf")
    if len(sys.argv) > 1:
        if sys.argv[1].endswith(".json") and os.path.isfile(sys.argv[1]):
            with open(sys.argv[1], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        else:
            out_file = sys.argv[1]
    render(out_file)
