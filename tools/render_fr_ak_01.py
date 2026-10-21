#!/usr/bin/env python3
"""
Generator FR.AK.01 (Persetujuan Asesmen dan Kerahasiaan) - LSP PrO Indo
Layout meniru template master BNSP aslinya secara presisi piksel 100%; tanpa watermark sesuai instruksi.
Tunggal tabel utuh sesuai master dokumen BNSP dengan data dummy lengkap.
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

def register_fonts():
    # Register Windows fonts with cross-platform fallbacks
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
    }
    for name, paths in font_candidates.items():
        registered = False
        for p in paths:
            if os.path.exists(p):
                pdfmetrics.registerFont(TTFont(name, p))
                registered = True
                break
        if not registered:
            # Fallback to standard Helvetica
            pass

register_fonts()

PAGE_W, PAGE_H = 595.56, 842.52
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
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    x_div = 166.0
    c.line(x_div, Y(top), x_div, Y(bot))
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, 84.45, Y(96.75), 69.43, 61.45, mask="auto")
        
    c.setFont("Tah-B", 11.0)
    c.setFillColor(black)
    c.drawString(206.0, Y(71.5), "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

def draw_cb(c, cx, cy_top, checked):
    sz = 9.5
    y_center = Y(cy_top)
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, y_center - sz / 2, sz, sz)
    if checked:
        c.setFont("Cal-B", 9.5)
        c.setFillColor(black)
        c.drawCentredString(cx, y_center - sz / 2 + 1.2, "✓")

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # 1. Header Box (Table 1): [70.4, 35.3, 554.0, 98.9]
    draw_header_box(c, 70.4, 554.0, 35.3, 98.9)
    
    # 2. Form Title: [69.5, 111.4]
    c.setFont("Cal-B", 12.0)
    c.setFillColor(black)
    c.drawString(69.5, Y(120.4), "FR.AK.01.  PERSETUJUAN ASESMEN DAN KERAHASIAAN")
    
    # 3. Main Single Unified Table (Table 2): [70.4, 136.9, 560.2, 757.8]
    L = 70.4
    R = 560.2
    
    row_y = [
        136.9, 179.7, 200.3, 220.9, 241.5, 266.1, 287.3,
        317.5, 347.6, 377.6, 429.5, 452.3, 474.9, 497.6,
        535.6, 609.7, 665.1, 757.8
    ]
    
    # Outer Border
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(L, Y(row_y[-1]), R - L, row_y[-1] - row_y[0])
    
    # Horizontal grid lines
    # r1: 179.7 (full)
    c.line(L, Y(row_y[1]), R, Y(row_y[1]))
    # r2: 200.3 (from 191.5 to R)
    c.line(191.5, Y(row_y[2]), R, Y(row_y[2]))
    # r3: 220.9 (full)
    c.line(L, Y(row_y[3]), R, Y(row_y[3]))
    # r4: 241.5 (full)
    c.line(L, Y(row_y[4]), R, Y(row_y[4]))
    # r5: 266.1 (full)
    c.line(L, Y(row_y[5]), R, Y(row_y[5]))
    # r6: 287.3 (full)
    c.line(L, Y(row_y[6]), R, Y(row_y[6]))
    
    # r7, r8, r9 (Bukti internal rows from 238.1 to R)
    c.line(238.1, Y(row_y[7]), R, Y(row_y[7]))
    c.line(238.1, Y(row_y[8]), R, Y(row_y[8]))
    c.line(238.1, Y(row_y[9]), R, Y(row_y[9]))
    
    # r10: 429.5 (full)
    c.line(L, Y(row_y[10]), R, Y(row_y[10]))
    
    # r11, r12 (Pelaksanaan internal rows from 255.2 to R)
    c.line(255.2, Y(row_y[11]), R, Y(row_y[11]))
    c.line(255.2, Y(row_y[12]), R, Y(row_y[12]))
    
    # r13: 497.6 (full)
    c.line(L, Y(row_y[13]), R, Y(row_y[13]))
    # r14: 535.6 (full)
    c.line(L, Y(row_y[14]), R, Y(row_y[14]))
    # r15: 609.7 (full)
    c.line(L, Y(row_y[15]), R, Y(row_y[15]))
    # r16: 665.1 (full)
    c.line(L, Y(row_y[16]), R, Y(row_y[16]))
    
    # Vertical grid lines
    # 191.5 (r1 to r3)
    c.line(191.5, Y(row_y[3]), 191.5, Y(row_y[1]))
    # 238.1 (r1 to r10)
    c.line(238.1, Y(row_y[10]), 238.1, Y(row_y[1]))
    # 255.2 (r1 to r13)
    c.line(255.2, Y(row_y[13]), 255.2, Y(row_y[1]))
    
    # Bukti columns (r6 to r10, top=287.3, bot=429.5)
    c.line(278.0, Y(row_y[10]), 278.0, Y(row_y[6]))
    c.line(400.3, Y(row_y[10]), 400.3, Y(row_y[6]))
    c.line(431.0, Y(row_y[10]), 431.0, Y(row_y[6]))
    
    # Pelaksanaan column (r10 to r13, top=429.5, bot=497.6)
    c.line(333.1, Y(row_y[13]), 333.1, Y(row_y[10]))
    
    # === TEXT CONTENT ===
    # Row 0: Persetujuan statement
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(149.0), "Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan")
    c.drawString(76.7, Y(164.0), "dan proses asesmen")
    
    # Row 1 & 2: Skema
    c.setFont("Cal", 11.0)
    c.drawString(78.4, Y(198.0), "Skema Sertifikasi")
    c.drawString(78.4, Y(212.0), "(KKNI/Okupasi/Klaster)")
    
    c.drawString(199.9, Y(194.0), "Judul")
    c.drawString(244.0, Y(194.0), ":")
    c.setFont("Cal-I", 11.0)
    c.drawString(261.0, Y(194.0), DATA["skema_nama"])
    
    c.setFont("Cal", 11.0)
    c.drawString(199.9, Y(214.0), "Nomor")
    c.drawString(244.0, Y(214.0), ":")
    c.drawString(261.0, Y(214.0), DATA["skema_nomor"])
    
    # Row 3: TUK
    c.setFont("Cal", 11.0)
    c.drawString(77.8, Y(235.0), "TUK")
    c.drawString(244.0, Y(235.0), ":")
    c.drawString(261.0, Y(235.0), DATA["tuk_tipe"])
    
    # Row 4: Nama Asesor
    c.drawString(77.8, Y(257.0), "Nama Asesor")
    c.drawString(244.0, Y(257.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(261.0, Y(257.0), DATA["asesor_nama"])
    
    # Row 5: Nama Asesi
    c.setFont("Cal", 11.0)
    c.drawString(77.8, Y(280.0), "Nama Asesi")
    c.drawString(244.0, Y(280.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(261.0, Y(280.0), DATA["asesi_nama"])
    
    # Row 6-9: Bukti-bukti yang dikumpulkan
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(330.0), "Bukti yang akan dikumpulkan :")
    
    c.drawString(244.0, Y(306.0), ":")
    c.drawString(244.0, Y(336.0), ":")
    c.drawString(244.0, Y(396.0), ":")
    
    # Row 6
    draw_cb(c, 266.6, 304.0, DATA["bukti_portofolio"])
    c.setFont("Cal", 11.0)
    c.drawString(282.9, Y(309.0), "Hasil Verifikasi Portofolio")
    draw_cb(c, 415.7, 304.0, DATA["bukti_reviu"])
    c.drawString(431.0, Y(309.0), "Hasil Reviu Produk")
    
    # Row 7
    draw_cb(c, 266.6, 334.0, DATA["bukti_observasi"])
    c.drawString(282.8, Y(339.0), "Hasil Observasi Langsung")
    draw_cb(c, 415.7, 334.0, DATA["bukti_terstruktur"])
    c.drawString(431.0, Y(339.0), "Hasil Kegiatan Terstruktur")
    
    # Row 8
    draw_cb(c, 266.6, 364.0, DATA["bukti_lisan"])
    c.drawString(281.5, Y(367.0), "Hasil Pertanyaan Lisan")
    draw_cb(c, 415.7, 364.0, DATA["bukti_tertulis"])
    c.drawString(431.9, Y(367.0), "Hasil Pertanyaan Tertulis")
    
    # Row 9
    draw_cb(c, 266.6, 396.0, DATA["bukti_lainnya"])
    c.drawString(281.9, Y(398.0), "Lainnya ……")
    draw_cb(c, 415.7, 396.0, DATA["bukti_wawancara"])
    c.drawString(434.9, Y(394.0), "Hasil Pertanyaan")
    c.drawString(434.9, Y(408.0), "Wawancara")
    
    # Row 10-12: Pelaksanaan Asesmen
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(460.0), "Pelaksanaan asesmen disepakati")
    c.drawString(76.7, Y(474.0), "pada:")
    
    c.drawString(259.2, Y(444.0), "Hari/ Tanggal  :")
    c.setFont("Cal-B", 11.0)
    c.drawString(338.0, Y(444.0), DATA["hari_tanggal"])
    
    c.setFont("Cal", 11.0)
    c.drawString(259.2, Y(466.0), "Waktu")
    c.drawString(325.0, Y(466.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(338.0, Y(466.0), DATA["waktu"])
    
    c.setFont("Cal", 11.0)
    c.drawString(259.2, Y(489.0), "TUK")
    c.drawString(325.0, Y(489.0), ":")
    c.setFont("Cal-B", 11.0)
    c.drawString(338.0, Y(489.0), DATA["tuk_nama"])
    
    # Row 13: Pernyataan Asesi 1
    c.setFont("Cal-B", 11.0)
    c.drawString(76.7, Y(512.0), "Asesi :")
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(528.0), "Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.")
    
    # Row 14: Pernyataan Asesor
    c.setFont("Cal-B", 11.0)
    c.drawString(76.7, Y(550.0), "Asesor :")
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(568.0), "Menyatakan tidak akan membuka hasil pekerjaan yang saya peroleh karena penugasan saya sebagai Asesor")
    c.drawString(76.7, Y(582.0), "dalam pekerjaan ")
    w_dp = c.stringWidth("dalam pekerjaan ", "Cal", 11.0)
    c.setFont("Cal-I", 11.0)
    c.drawString(76.7 + w_dp, Y(582.0), "Asesmen ")
    w_as = c.stringWidth("Asesmen ", "Cal-I", 11.0)
    c.setFont("Cal", 11.0)
    c.drawString(76.7 + w_dp + w_as, Y(582.0), "kepada siapapun atau organisasi apapun selain kepada pihak yang berwenang")
    c.drawString(76.7, Y(596.0), "sehubungan dengan kewajiban saya sebagai Asesor yang ditugaskan oleh LSP.")
    
    # Row 15: Pernyataan Asesi 2
    c.setFont("Cal-B", 11.0)
    c.drawString(76.7, Y(624.0), "Asesi :")
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(642.0), "Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan")
    c.drawString(76.7, Y(656.0), "untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.")
    
    # Row 16: Tanda Tangan Section (stacked vertically, exact master layout)
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(688.0), "Tanda tangan Asesor : ……………………………")
    c.drawString(292.9, Y(688.0), f"Tanggal : {DATA['tanggal_ttd']}")
    # Dummy name under signature
    c.setFont("Cal-B", 10.0)
    c.drawString(176.0, Y(702.0), f"( {DATA['asesor_nama']} )")
    
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(726.0), "Tanda tangan Asesi : ……………………………")
    c.drawString(292.9, Y(726.0), f"Tanggal : {DATA['tanggal_ttd']}")
    # Dummy name under signature
    c.setFont("Cal-B", 10.0)
    c.drawString(176.0, Y(740.0), f"( {DATA['asesi_nama']} )")
    
    # Below Table: Note
    c.setFont("Cal-I", 11.0)
    c.drawString(70.9, Y(770.0), "* Coret yang tidak perlu")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.01: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_01_generated.pdf")
    render(out_file)
