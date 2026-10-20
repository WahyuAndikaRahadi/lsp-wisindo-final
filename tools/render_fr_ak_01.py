#!/usr/bin/env python3
"""
Generator FR.AK.01 (Persetujuan Asesmen dan Kerahasiaan) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Tunggal tabel utuh sesuai master dokumen BNSP.
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
    "DJC": "DejaVuSansCondensed.ttf",
    "DJC-B": "DejaVuSansCondensed-Bold.ttf",
}
for name, ffile in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile)))

PAGE_W, PAGE_H = 595.0, 842.0
Y = lambda top: PAGE_H - top

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesi_nama": "Wahyu Andika Rahadi",
    "bukti_portofolio": True,
    "bukti_reviu": False,
    "bukti_observasi": True,
    "bukti_terstruktur": False,
    "bukti_lisan": True,
    "bukti_tertulis": True,
    "bukti_wawancara": False,
    "bukti_lainnya": False,
    "hari_tanggal": "Senin, 19 Oktober 2026",
    "waktu": "08.00 - 12.00 WIB",
    "tuk_nama": "TUK Otomotif PrO Indo",
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
    
    # 1. Header Box
    x0, x1 = 50.4, 535.7
    draw_header_box(c, x0, x1, 35.4, 98.8)
    
    # 2. Title
    c.setFont("Lib-B", 12)
    c.setFillColor(black)
    c.drawString(70.6, Y(118.0), "FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN")
    
    # 3. Main Single Unified Table
    # Left = 70.6, Right = 560.1 (width = 489.5 pt)
    L, R = 70.6, 560.1
    
    # Row Y coordinates (top):
    # r0: 137.2 to 179.7
    # r1: 179.7 to 200.3 (Skema Judul)
    # r2: 200.3 to 220.9 (Skema Nomor)
    # r3: 220.9 to 241.5 (TUK)
    # r4: 241.5 to 266.1 (Nama Asesor)
    # r5: 266.1 to 287.3 (Nama Asesi)
    # r6: 287.3 to 317.5 (Bukti row 1)
    # r7: 317.5 to 347.6 (Bukti row 2)
    # r8: 347.6 to 377.6 (Bukti row 3)
    # r9: 377.6 to 429.8 (Bukti row 4)
    # r10: 429.8 to 452.3 (Pelaksanaan - Hari/Tgl)
    # r11: 452.3 to 474.9 (Pelaksanaan - Waktu)
    # r12: 474.9 to 497.6 (Pelaksanaan - TUK)
    # r13: 497.6 to 535.6 (Pernyataan Asesi 1)
    # r14: 535.6 to 609.7 (Pernyataan Asesor)
    # r15: 609.7 to 665.1 (Pernyataan Asesi 2)
    # r16: 665.1 to 757.7 (Tanda Tangan)
    
    row_y = [
        137.2, 179.7, 200.3, 220.9, 241.5, 266.1, 287.3,
        317.5, 347.6, 377.6, 429.8, 452.3, 474.9, 497.6,
        535.6, 609.7, 665.1, 757.7
    ]
    
    # Outer Border
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(row_y[-1]), R - L, row_y[-1] - row_y[0])
    
    # Horizontal grid lines
    for y in row_y[1:-1]:
        c.line(L, Y(y), R, Y(y))
        
    # Content r0: Persetujuan statement
    c.setFont("Lib", 9)
    c.drawString(L + 6.0, Y(152.0), "Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan")
    c.drawString(L + 6.0, Y(166.0), "dan proses asesmen")
    
    # Vertical grid line for metadata (r1 to r5)
    c_meta_col1 = 191.7
    c_meta_col2 = 244.8
    c_meta_col3 = 259.2
    
    c.line(c_meta_col1, Y(row_y[3]), c_meta_col1, Y(row_y[1]))
    c.line(c_meta_col2, Y(row_y[3]), c_meta_col2, Y(row_y[1]))
    c.line(c_meta_col3, Y(row_y[3]), c_meta_col3, Y(row_y[1]))
    
    c.line(c_meta_col1, Y(row_y[6]), c_meta_col1, Y(row_y[3]))
    c.line(c_meta_col3, Y(row_y[6]), c_meta_col3, Y(row_y[3]))
    
    # r1 & r2: Skema
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(194.0), "Skema Sertifikasi")
    c.drawString(L + 6.0, Y(206.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(c_meta_col1 + 6.0, Y(194.0), "Judul")
    c.drawString(c_meta_col2 + 4.0, Y(194.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(194.0), DATA["skema_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c_meta_col1 + 6.0, Y(214.0), "Nomor")
    c.drawString(c_meta_col2 + 4.0, Y(214.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(214.0), DATA["skema_nomor"])
    
    # r3: TUK
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(234.0), "TUK")
    c.drawString(c_meta_col2 + 4.0, Y(234.0), ":")
    c.drawString(c_meta_col3 + 6.0, Y(234.0), DATA["tuk_tipe"])
    
    # r4: Asesor
    c.drawString(L + 6.0, Y(256.0), "Nama Asesor")
    c.drawString(c_meta_col2 + 4.0, Y(256.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(256.0), DATA["asesor_nama"])
    
    # r5: Asesi
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(278.0), "Nama Asesi")
    c.drawString(c_meta_col2 + 4.0, Y(278.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(278.0), DATA["asesi_nama"])
    
    # r6-r9: Bukti-bukti yang dikumpulkan
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(350.0), "Bukti yang akan dikumpulkan :")
    c.drawString(c_meta_col2 + 4.0, Y(350.0), ":")
    
    c.line(c_meta_col1, Y(row_y[10]), c_meta_col1, Y(row_y[6]))
    c.line(c_meta_col3, Y(row_y[10]), c_meta_col3, Y(row_y[6]))
    c.line((c_meta_col3 + R) / 2, Y(row_y[10]), (c_meta_col3 + R) / 2, Y(row_y[6]))
    
    # Sub items
    sub_col1 = c_meta_col3 + 8.0
    sub_col2 = (c_meta_col3 + R) / 2 + 8.0
    
    # Row 6
    draw_cb(c, sub_col1 + 5.0, Y(302.0), DATA["bukti_portofolio"])
    c.setFont("Lib", 8.5)
    c.drawString(sub_col1 + 16.0, Y(305.0), "Hasil Verifikasi Portofolio")
    draw_cb(c, sub_col2 + 5.0, Y(302.0), DATA["bukti_reviu"])
    c.drawString(sub_col2 + 16.0, Y(305.0), "Hasil Reviu Produk")
    
    # Row 7
    draw_cb(c, sub_col1 + 5.0, Y(332.0), DATA["bukti_observasi"])
    c.drawString(sub_col1 + 16.0, Y(335.0), "Hasil Observasi Langsung")
    draw_cb(c, sub_col2 + 5.0, Y(332.0), DATA["bukti_terstruktur"])
    c.drawString(sub_col2 + 16.0, Y(335.0), "Hasil Kegiatan Terstruktur")
    
    # Row 8
    draw_cb(c, sub_col1 + 5.0, Y(362.0), DATA["bukti_lisan"])
    c.drawString(sub_col1 + 16.0, Y(365.0), "Hasil Pertanyaan Lisan")
    draw_cb(c, sub_col2 + 5.0, Y(362.0), DATA["bukti_tertulis"])
    c.drawString(sub_col2 + 16.0, Y(365.0), "Hasil Pertanyaan Tertulis")
    
    # Row 9
    draw_cb(c, sub_col1 + 5.0, Y(403.0), DATA["bukti_lainnya"])
    c.drawString(sub_col1 + 16.0, Y(406.0), "Lainnya ……")
    draw_cb(c, sub_col2 + 5.0, Y(403.0), DATA["bukti_wawancara"])
    c.drawString(sub_col2 + 16.0, Y(406.0), "Hasil Pertanyaan Wawancara")
    
    # r10-r12: Pelaksanaan Asesmen
    c.line(c_meta_col1, Y(row_y[13]), c_meta_col1, Y(row_y[10]))
    c.line(c_meta_col3, Y(row_y[13]), c_meta_col3, Y(row_y[10]))
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(463.0), "Pelaksanaan asesmen disepakati pada:")
    
    c.drawString(c_meta_col1 + 6.0, Y(444.0), "Hari/ Tanggal")
    c.drawString(c_meta_col2 + 4.0, Y(444.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(444.0), DATA["hari_tanggal"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c_meta_col1 + 6.0, Y(466.0), "Waktu")
    c.drawString(c_meta_col2 + 4.0, Y(466.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(466.0), DATA["waktu"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c_meta_col1 + 6.0, Y(489.0), "TUK")
    c.drawString(c_meta_col2 + 4.0, Y(489.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_meta_col3 + 6.0, Y(489.0), DATA["tuk_nama"])
    
    # r13: Pernyataan Asesi 1
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(512.0), "Asesi :")
    c.setFont("Lib", 8.5)
    c.drawString(L + 42.0, Y(512.0), "Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.")
    
    # r14: Pernyataan Asesor
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(550.0), "Asesor :")
    c.setFont("Lib", 8.5)
    c.drawString(L + 48.0, Y(550.0), "Menyatakan tidak akan membuka hasil pekerjaan yang saya peroleh karena penugasan saya sebagai")
    c.drawString(L + 6.0, Y(564.0), "Asesor dalam pekerjaan Asesmen kepada siapapun atau organisasi apapun selain kepada pihak yang berwenang")
    c.drawString(L + 6.0, Y(578.0), "sehubungan dengan kewajiban saya sebagai Asesor yang ditugaskan oleh LSP.")
    
    # r15: Pernyataan Asesi 2
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(624.0), "Asesi :")
    c.setFont("Lib", 8.5)
    c.drawString(L + 42.0, Y(624.0), "Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan")
    c.drawString(L + 6.0, Y(638.0), "untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.")
    
    # r16: Tanda Tangan Section
    x_half = (L + R) / 2
    c.line(x_half, Y(row_y[17]), x_half, Y(row_y[16]))
    
    # Left: Asesor
    c.setFont("Lib", 8.5)
    c.drawString(L + 8.0, Y(680.0), "Tanda tangan Asesor : ……………………………")
    c.drawString(L + 8.0, Y(700.0), f"Tanggal : {DATA['tanggal_ttd']}")
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 8.0, Y(735.0), f"( {DATA['asesor_nama']} )")
    
    # Right: Asesi
    c.setFont("Lib", 8.5)
    c.drawString(x_half + 8.0, Y(680.0), "Tanda tangan Asesi : ……………………………")
    c.drawString(x_half + 8.0, Y(700.0), f"Tanggal : {DATA['tanggal_ttd']}")
    c.setFont("Lib-B", 8.5)
    c.drawString(x_half + 8.0, Y(735.0), f"( {DATA['asesi_nama']} )")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.01: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_01_generated.pdf")
    render(out_file)
