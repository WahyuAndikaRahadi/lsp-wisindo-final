#!/usr/bin/env python3
"""
Generator FR.APL.02 (Asesmen Mandiri) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 6 halaman sesuai master document BNSP.
"""

import os
import sys
import json
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor, Color
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

BASE = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(BASE, ".."))
OUT = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/FR_APL_02_generated.pdf")
ASSETS = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/assets")
CELLS_FILE = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/apl02_master_cells.json")

# Dimensions A4: 595.28 x 841.89 pt
PAGE_W, PAGE_H = 595.0, 842.0

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

sw = pdfmetrics.stringWidth
GRAY_FILL = HexColor("#D9D9D9")
BLUE_NUM = Color(0.27, 0.45, 0.77)

# Helper for coordinates: top -> Y
Y = lambda top: PAGE_H - top

def draw_header(c):
    x0, x1 = 50.4, 535.7
    top, bot = 35.4, 98.8
    y_bot = Y(bot)
    y_top = Y(top)
    h = y_top - y_bot
    
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(x0, y_bot, x1 - x0, h)
    
    # Logo divider
    x_div = 147.7
    c.line(x_div, y_top, x_div, y_bot)
    
    # Logo image
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, y_bot + 6.0, 65.0, 50.0, mask="auto")
        
    # Text
    c.setFont("Lib-B", 11.5)
    c.setFillColor(black)
    c.drawString(x_div + 18.0, y_bot + 26.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

def wrap_text(c, text, font, size, max_width):
    lines = []
    for paragraph in text.split("\n"):
        words = paragraph.split()
        if not words:
            lines.append("")
            continue
        cur = ""
        for w in words:
            t = (cur + " " + w).strip()
            if c.stringWidth(t, font, size) <= max_width:
                cur = t
            else:
                if cur:
                    lines.append(cur)
                cur = w
        if cur:
            lines.append(cur)
    return lines

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    with open(CELLS_FILE) as f:
        master_pages = json.load(f)
        
    for p_data in master_pages:
        pnum = p_data["page"]
        
        # 1. Top Header Box on every page
        draw_header(c)
        
        # 2. Render Page Content
        if pnum == 1:
            render_page_1(c, p_data)
        elif pnum == 2:
            render_page_2(c, p_data)
        elif pnum == 3:
            render_page_3(c, p_data)
        elif pnum == 4:
            render_page_4(c, p_data)
        elif pnum == 5:
            render_page_5(c, p_data)
        elif pnum == 6:
            render_page_6(c, p_data)
            
        # Page number
        c.setFont("Cal", 10)
        c.setFillColor(BLUE_NUM)
        c.drawCentredString(PAGE_W / 2, 20.0, str(pnum))
        
        c.showPage()
        
    c.save()
    print(f"OK -> Sukses generate APL 02 (6 Halaman): {dest_path}")

# ================= PAGE 1 =================
def render_page_1(c, p_data):
    L, R = 53.6, 541.0
    
    # Title
    c.setFont("Lib-B", 12)
    c.setFillColor(black)
    c.drawString(L, Y(122.0), "FR.APL.02. ASESMEN MANDIRI")
    
    # Table 2: Skema Table (top=131.6 to 179.7)
    # Rows: 131.6, 156.5, 179.7 | Cols: 53.6, 166.6, 219.9, 234.2, 541.0
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(179.7), R - L, 179.7 - 131.6)
    c.line(166.6, Y(156.5), R, Y(156.5))
    c.line(166.6, Y(179.7), 166.6, Y(131.6))
    c.line(219.9, Y(179.7), 219.9, Y(131.6))
    c.line(234.2, Y(179.7), 234.2, Y(131.6))
    
    c.setFont("Lib-B", 9)
    c.drawString(L + 5.0, Y(148.0), "Skema Sertifikasi")
    c.drawString(L + 5.0, Y(160.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(166.6 + 5.0, Y(148.0), "Judul")
    c.drawString(219.9 + 4.0, Y(148.0), ":")
    c.drawString(234.2 + 5.0, Y(148.0), "Skema Sertifikasi Klaster Maintenance Gasoline Engine Injection System")
    
    c.drawString(166.6 + 5.0, Y(171.0), "Nomor")
    c.drawString(219.9 + 4.0, Y(171.0), ":")
    c.drawString(234.2 + 5.0, Y(171.0), "Skema 02/LSP PrO Indo/XI/2025")
    
    # Table 3: Panduan Asesmen Mandiri (top=192.7 to 293.9)
    # Header row: 192.7 to 207.3 (shaded)
    c.setFillColor(GRAY_FILL)
    c.rect(L, Y(207.3), R - L, 207.3 - 192.7, fill=1, stroke=0)
    c.rect(L, Y(293.9), R - L, 293.9 - 192.7, fill=0, stroke=1)
    c.line(L, Y(207.3), R, Y(207.3))
    
    c.setFont("Lib-B", 9.5)
    c.setFillColor(black)
    c.drawString(L + 6.0, Y(202.0), "PANDUAN ASESMEN MANDIRI")
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(221.0), "Instruksi:")
    c.drawString(L + 12.0, Y(236.0), "•  Baca setiap pertanyaan di kolom sebelah kiri")
    c.drawString(L + 12.0, Y(251.0), "•  Beri tanda centang (✓) pada kotak jika Anda yakin dapat melakukan tugas yang dijelaskan.")
    c.drawString(L + 12.0, Y(266.0), "•  Isi kolom di sebelah kanan dengan menuliskan bukti yang relevan anda miliki untuk menunjukkan bahwa")
    c.drawString(L + 21.0, Y(279.0), "anda melakukan pekerjaan")
    
    # Unit 1 Header (306.0 to 346.6)
    draw_unit_header(c, 1, "G.45OTO01.001.2", "Melaksanakan Keselamatan dan Kesehatan Kerja", 306.0, 346.6)
    
    # Unit 1 Elements (356.4 to 531.8)
    draw_elements_table(c, 356.4, [
        {"y_top": 368.4, "y_bot": 438.6, "ele": "1. Elemen: Menyiapkan prosedur Keselamatan dan Kesehatan Kerja", "kuks": ["1.1 Potensi bahaya pada area kerja diidentifikasi.", "1.2 APD disiapkan di tempat kerja."], "k": True},
        {"y_top": 438.6, "y_bot": 531.8, "ele": "2. Elemen: Melaksanakanan prosedur Keselamatan dan Kesehatan Kerja", "kuks": ["2.1 APD digunakan sesuai prosedur.", "2.2 Prosedur kesehatan dan keselamatan kerja dilaksanakan di tempat kerja.", "2.3 Pencegahan bahaya Keselamatan dan Kesehatan Kerja dilakukan"], "k": True}
    ])
    
    # Unit 2 Header (541.5 to 582.1)
    draw_unit_header(c, 2, "G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja", 541.5, 582.1)
    
    # Unit 2 Elements (594.2 to 769.5)
    draw_elements_table(c, 594.2, [
        {"y_top": 606.2, "y_bot": 687.8, "ele": "1. Elemen: Memilih peralatan dan perlengkapan tempat kerja secara aman", "kuks": ["1.1 Peralatan dan perlengkapan tempat kerja diidentifikasi.", "1.2 Peralatan dan perlengkapan disiapkan di tempat kerja."], "k": True},
        {"y_top": 687.8, "y_bot": 769.5, "ele": "2. Elemen: Menggunakan peralatan dan perlengkapan di tempat kerja", "kuks": ["2.1 Peralatan dan perlengkapan dikalibrasi sesuai jenis dan fungsi.", "2.2 Peralatan dan perlengkapan digunakan sesuai manual perbaikan."], "k": True}
    ])

# ================= PAGE 2 =================
def render_page_2(c, p_data):
    # Unit 3 Header (113.1 to 153.8)
    draw_unit_header(c, 3, "G.45OTO01.007.2", "Membaca Gambar Teknik", 113.1, 153.8)
    # Unit 3 Elements (165.8 to 306.8)
    draw_elements_table(c, 165.8, [
        {"y_top": 177.8, "y_bot": 236.5, "ele": "1. Elemen: Menyiapkan gambar teknik", "kuks": ["1.1 Gambar Teknik diidentifikasi sesuai kebutuhan.", "1.2 Gambar teknik disiapkan."], "k": True},
        {"y_top": 236.5, "y_bot": 306.8, "ele": "2. Elemen: Membaca gambar teknik", "kuks": ["2.1 Simbol dan kode gambar teknik diidentifikasi.", "2.2 Gambar teknik dijelaskan sesuai fungsi dan cara kerja."], "k": True}
    ])
    
    # Unit 4 Header (318.8 to 359.4)
    draw_unit_header(c, 4, "G.45OTO01.008.2", "Menggunakan Alat Ukur", 318.8, 359.4)
    # Unit 4 Elements (371.4 to 546.8)
    draw_elements_table(c, 371.4, [
        {"y_top": 383.4, "y_bot": 476.6, "ele": "1. Elemen: Menyiapkan alat ukur dan komponen", "kuks": ["1.1 Jenis alat ukur dan komponen diidentifikasi.", "1.2 Alat ukur yang sesuai disiapkan di tempat kerja.", "1.3 Alat ukur dikalibrasi sebelum digunakan."], "k": True},
        {"y_top": 476.6, "y_bot": 546.8, "ele": "2. Elemen: Menggunakan alat ukur", "kuks": ["2.1 Alat ukur digunakan sesuai prosedur.", "2.2 Alat ukur digunakan sesuai fungsi dan jenis alat ukur.", "2.3 Hasil pengukuran didokumentasikan."], "k": True}
    ])
    
    # Unit 5 Header (558.8 to 599.5)
    draw_unit_header(c, 5, "G.45OTO01.009.2", "Melaksanakan Diagnosis", 558.8, 599.5)
    # Unit 5 Elements Part 1 (611.5 to 775.3)
    draw_elements_table(c, 611.5, [
        {"y_top": 623.5, "y_bot": 693.7, "ele": "1. Elemen: Mempersiapkan diagnosis", "kuks": ["1.1 Komponen, alat, SST dan Diagnosis tools diidentifikasi.", "1.2 Komponen, alat, SST dan Diagnosis tools disiapkan di tempat kerja."], "k": True},
        {"y_top": 693.7, "y_bot": 775.3, "ele": "2. Elemen: Melakukan diagnosis", "kuks": ["2.1 Komponen, alat, SST dan Diagnosis tools dikalibrasi sebelum digunakan.", "2.2 Komponen, alat, SST dan Diagnosis tools digunakan sesuai manual perbaikan."], "k": True}
    ])

# ================= PAGE 3 =================
def render_page_3(c, p_data):
    L, R = 53.6, 541.0
    c1, c2, c3 = 321.1, 347.9, 375.0
    
    # Continuation of Unit 5 Elemen 2 (113.1 to 148.1)
    c.rect(L, Y(148.1), R - L, 148.1 - 113.1)
    c.line(c1, Y(148.1), c1, Y(113.1))
    c.line(c2, Y(148.1), c2, Y(113.1))
    c.line(c3, Y(148.1), c3, Y(113.1))
    
    c.setFont("Lib", 8.5)
    c.setFillColor(black)
    c.drawString(L + 12.0, Y(127.0), "2.3 Hasil diagnosis didokumentasikan")
    c.drawString(L + 12.0, Y(140.0), "2.4 Hasil diagnosis dievaluasi dan dipresentasikan.")
    
    draw_cb(c, (c1 + c2) / 2, (Y(113.1) + Y(148.1)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(113.1) + Y(148.1)) / 2, False)
    
    # Unit 6 Header (160.1 to 200.8)
    draw_unit_header(c, 6, "G.45OTO01.010.2", "Melakukan Perawatan Berkala Engine", 160.1, 200.8)
    
    # Unit 6 Elements (212.8 to 733.2)
    draw_elements_table(c, 212.8, [
        {
            "y_top": 224.8, "y_bot": 444.6,
            "ele": "1. Elemen: Menyiapkan Perawatan engine",
            "kuks": [
                "1.1 Manual perbaikan perawatan engine disiapkan sesuai kendaraan.",
                "1.2 Peralatan dan bahan disiapkan berdasarkan kebutuhan perawatan berkala.",
                "1.3 Kendaraan dan kelengkapannya disiapkan untuk dirawat.",
                "1.4 Perlengkapan pelindung kendaraan dipasang berdasarkan prosedur.",
                "1.5 Pengecekan awal kendaraan dilakukan sesuai manual perbaikan.",
                "1.6 Oli pelumas dan saringan oli engine diganti sesuai manual.",
                "1.7 Sistem pendingin engine diperiksa dan dirawat sesuai manual.",
                "1.8 Tali kipas diperiksa kelayakannya dan disetel ketegangannya."
            ],
            "k": True
        },
        {
            "y_top": 444.6, "y_bot": 733.2,
            "ele": "2. Elemen: Melakukan Pemeriksaan engine",
            "kuks": [
                "2.1 Saringan udara diperiksa dan dibersihkan atau diganti sesuai manual.",
                "2.2 Busi diperiksa, dibersihkan dan disetel celah elektrodanya sesuai manual.",
                "2.3 Celah katup diperiksa dan disetel sesuai manual perbaikan.",
                "2.4 Pengapian diperiksa dan disetel saat pengapiannya sesuai manual.",
                "2.5 Putaran idle dan campuran bahan bakar diperiksa dan disetel sesuai SOP.",
                "2.6 Tekanan kompresi engine diperiksa sesuai dengan manual perbaikan.",
                "2.7 Emisi gas buang diuji menggunakan exhaust gas analyzer.",
                "2.8 Laporan hasil perawatan berkala engine diisi dan dilaporkan ke pembimbing."
            ],
            "k": True
        }
    ])

# ================= PAGE 4 =================
def render_page_4(c, p_data):
    L, R = 53.6, 541.0
    c1, c2, c3 = 321.1, 347.9, 375.0
    
    # Unit 7 Header (113.1 to 153.8)
    draw_unit_header(c, 7, "G.45OTO01.065.2", "Memelihara Baterai", 113.1, 153.8)
    
    # Unit 7 Elements (165.8 to 675.9)
    draw_elements_table(c, 165.8, [
        {
            "y_top": 177.8, "y_bot": 397.6,
            "ele": "1. Elemen: Menyiapkan pemeliharaan baterai kendaraan ringan",
            "kuks": [
                "1.1 Manual perbaikan disiapkan sesuai dengan kendaraan yang digunakan.",
                "1.2 Peralatan dan perangkat kerja pendukung diperiksa kelayakan pakainya.",
                "1.3 Baterai yang digunakan disiapkan untuk menentukan proses pemeliharaan.",
                "1.4 Level cairan elektrolit diperiksa dan disesuaikan berdasarkan manual.",
                "1.5 Perlengkapan pelindung kendaraan dipasang berdasarkan prosedur."
            ],
            "k": True
        },
        {
            "y_top": 397.6, "y_bot": 594.3,
            "ele": "2. Elemen: Melaksanakan pemeliharaan baterai",
            "kuks": [
                "2.1 Kondisi fisik baterai dan terminal diperiksa sesuai manual perbaikan.",
                "2.2 Tegangan baterai diperiksa menggunakan alat ukur yang sesuai manual.",
                "2.3 Berat jenis air baterai diperiksa menggunakan hydrometer sesuai manual.",
                "2.4 Baterai dan terminal dibersihkan sesuai dengan manual perbaikan.",
                "2.5 Dokumen pemeriksaan baterai diisi sesuai hasil pelaksanaan pemeriksaan."
            ],
            "k": True
        },
        {
            "y_top": 594.3, "y_bot": 675.9,
            "ele": "3. Elemen: Menguji baterai pada kendaraan",
            "kuks": [
                "3.1 Baterai diuji dengan menggunakan diagnostic tool / battery load tester.",
                "3.2 Hasil pekerjaan menguji baterai didokumentasikan sesuai prosedur."
            ],
            "k": True
        }
    ])
    
    # Unit 8 Header (687.9 to 728.6)
    draw_unit_header(c, 8, "G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan", 687.9, 728.6)
    
    # Unit 8 Elements Table Head + Elemen 1 Title (740.6 to 776.1)
    c.setFillColor(GRAY_FILL)
    c.rect(L, Y(752.6), R - L, 752.6 - 740.6, fill=1, stroke=0)
    c.rect(L, Y(776.1), R - L, 776.1 - 740.6, fill=0, stroke=1)
    c.line(L, Y(752.6), R, Y(752.6))
    
    c.line(c1, Y(776.1), c1, Y(740.6))
    c.line(c2, Y(776.1), c2, Y(740.6))
    c.line(c3, Y(776.1), c3, Y(740.6))
    
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(L + 6.0, Y(749.0), "Dapatkah Saya ................?")
    c.drawCentredString((c1 + c2) / 2, Y(749.0), "K")
    c.drawCentredString((c2 + c3) / 2, Y(749.0), "BK")
    c.drawString(c3 + 8.0, Y(749.0), "Bukti yang relevan")
    
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(766.0), "1. Elemen: Menyiapkan pekerjaan memperbaiki kerusakan pada sistem pengaman kelistrikan")

# ================= PAGE 5 =================
def render_page_5(c, p_data):
    L, R = 53.6, 541.0
    c1, c2, c3 = 321.1, 347.9, 375.0
    
    # Unit 8 Continuation Table (113.1 to 530.7)
    # Rows: 113.1, 263.8, 391.6, 530.7
    c.rect(L, Y(530.7), R - L, 530.7 - 113.1)
    c.line(L, Y(263.8), R, Y(263.8))
    c.line(L, Y(391.6), R, Y(391.6))
    
    c.line(c1, Y(530.7), c1, Y(113.1))
    c.line(c2, Y(530.7), c2, Y(113.1))
    c.line(c3, Y(530.7), c3, Y(113.1))
    
    # Elemen 1 KUKs (113.1 to 263.8)
    c.setFont("Lib-I", 8.5)
    c.drawString(L + 6.0, Y(128.0), "• Kriteria Unjuk Kerja:")
    c.setFont("Lib", 8.5)
    c.drawString(L + 12.0, Y(144.0), "1.1 Manual perbaikan sistem pengaman disiapkan sesuai kendaraan.")
    c.drawString(L + 12.0, Y(160.0), "1.2 Peralatan dan perangkat kerja pendukung diperiksa atas kondisi dan kelayakan pakai.")
    c.drawString(L + 12.0, Y(176.0), "1.3 Sistem pengaman yang akan diperbaiki disiapkan di tempat kerja.")
    c.drawString(L + 12.0, Y(192.0), "1.4 Perlengkapan pelindung kendaraan dipasang berdasarkan prosedur.")
    draw_cb(c, (c1 + c2) / 2, (Y(113.1) + Y(263.8)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(113.1) + Y(263.8)) / 2, False)
    
    # Elemen 2 (263.8 to 391.6)
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(278.0), "2. Elemen: Melaksanakan pengujian pada sistem pengaman kelistrikan")
    c.setFont("Lib-I", 8.5)
    c.drawString(L + 6.0, Y(293.0), "• Kriteria Unjuk Kerja:")
    c.setFont("Lib", 8.5)
    c.drawString(L + 12.0, Y(309.0), "2.1 Sistem pengaman kelistrikan diuji sesuai manual perbaikan.")
    c.drawString(L + 12.0, Y(325.0), "2.2 Hasil pengujian sistem pengaman kelistrikan ditemukan berdasarkan manual.")
    c.drawString(L + 12.0, Y(341.0), "2.3 Dokumen pengujian sistem pengaman kelistrikan diisi sesuai hasil pengujian.")
    draw_cb(c, (c1 + c2) / 2, (Y(263.8) + Y(391.6)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(263.8) + Y(391.6)) / 2, False)
    
    # Elemen 3 (391.6 to 530.7)
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(406.0), "3. Elemen: Melaksanakan perbaikan pada sistem pengaman kelistrikan")
    c.setFont("Lib-I", 8.5)
    c.drawString(L + 6.0, Y(421.0), "• Kriteria Unjuk Kerja:")
    c.setFont("Lib", 8.5)
    c.drawString(L + 12.0, Y(437.0), "3.1 Sistem pengaman kelistrikan diperbaiki berdasarkan hasil pengujian.")
    c.drawString(L + 12.0, Y(453.0), "3.2 Hasil perbaikan sistem pengaman kelistrikan dilakukan berdasarkan manual.")
    c.drawString(L + 12.0, Y(469.0), "3.3 Dokumen perbaikan sistem pengaman kelistrikan diisi sesuai hasil pengujian.")
    draw_cb(c, (c1 + c2) / 2, (Y(391.6) + Y(530.7)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(391.6) + Y(530.7)) / 2, False)
    
    # Unit 9 Header (542.7 to 583.4)
    draw_unit_header(c, 9, "G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine", 542.7, 583.4)
    
    # Unit 9 Elements Part 1 (595.4 to 781.1)
    c.setFillColor(GRAY_FILL)
    c.rect(L, Y(607.4), R - L, 607.4 - 595.4, fill=1, stroke=0)
    c.rect(L, Y(781.1), R - L, 781.1 - 595.4, fill=0, stroke=1)
    c.line(L, Y(607.4), R, Y(607.4))
    
    c.line(c1, Y(781.1), c1, Y(595.4))
    c.line(c2, Y(781.1), c2, Y(595.4))
    c.line(c3, Y(781.1), c3, Y(595.4))
    
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(L + 6.0, Y(604.0), "Dapatkah Saya ................?")
    c.drawCentredString((c1 + c2) / 2, Y(604.0), "K")
    c.drawCentredString((c2 + c3) / 2, Y(604.0), "BK")
    c.drawString(c3 + 8.0, Y(604.0), "Bukti yang relevan")
    
    c.drawString(L + 6.0, Y(622.0), "1. Elemen: Menyiapkan perbaikan sistem manajemen engine")
    c.setFont("Lib-I", 8.5)
    c.drawString(L + 6.0, Y(637.0), "• Kriteria Unjuk Kerja:")
    c.setFont("Lib", 8.5)
    c.drawString(L + 12.0, Y(653.0), "1.1 Manual perbaikan sistem manajemen engine disiapkan sesuai prosedur.")
    c.drawString(L + 12.0, Y(669.0), "1.2 Peralatan dan bahan serta perangkat kerja lain diperiksa kondisinya.")
    c.drawString(L + 12.0, Y(685.0), "1.3 Perlengkapan pelindung kendaraan dipasang berdasarkan prosedur.")
    c.drawString(L + 12.0, Y(701.0), "1.4 Sistem manajemen engine diperiksa menggunakan diagnostic tool")

# ================= PAGE 6 =================
def render_page_6(c, p_data):
    L, R = 55.7, 537.8
    c1, c2, c3 = 321.1, 347.9, 375.0
    
    # Unit 9 Continuation Table (113.1 to 507.1)
    # Rows: 113.1, 148.1, 333.4, 507.1
    c.rect(L, Y(507.1), R - L, 507.1 - 113.1)
    c.line(L, Y(148.1), R, Y(148.1))
    c.line(L, Y(333.4), R, Y(333.4))
    
    c.line(c1, Y(507.1), c1, Y(113.1))
    c.line(c2, Y(507.1), c2, Y(113.1))
    c.line(c3, Y(507.1), c3, Y(113.1))
    
    # Elemen 1 Tail (113.1 to 148.1)
    c.setFont("Lib", 8.5)
    c.setFillColor(black)
    c.drawString(L + 12.0, Y(127.0), "untuk memudahkan mengetahui kerusakan pada sistem manajemen engine.")
    draw_cb(c, (c1 + c2) / 2, (Y(113.1) + Y(148.1)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(113.1) + Y(148.1)) / 2, False)
    
    # Elemen 2 (148.1 to 333.4)
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(164.0), "2. Elemen: Mendiagnosa sistem manajemen engine")
    c.setFont("Lib-I", 8.5)
    c.drawString(L + 6.0, Y(179.0), "• Kriteria Unjuk Kerja:")
    c.setFont("Lib", 8.5)
    c.drawString(L + 12.0, Y(195.0), "2.1 Sistem manajemen engine dasar dan elektronis diperiksa menggunakan tool.")
    c.drawString(L + 12.0, Y(211.0), "2.2 Sistem Manajemen engine diperiksa secara visual untuk kemungkinan kerusakan.")
    c.drawString(L + 12.0, Y(227.0), "2.3 Dokumen pekerjaan Mendiagnosa sistem manajemen engine diisi sesuai hasil.")
    draw_cb(c, (c1 + c2) / 2, (Y(148.1) + Y(333.4)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(148.1) + Y(333.4)) / 2, False)
    
    # Elemen 3 (333.4 to 507.1)
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(349.0), "3. Elemen: Melaksanakan perbaikan sistem engine")
    c.setFont("Lib-I", 8.5)
    c.drawString(L + 6.0, Y(364.0), "• Kriteria Unjuk Kerja:")
    c.setFont("Lib", 8.5)
    c.drawString(L + 12.0, Y(380.0), "3.1 Sistem manajemen engine diperbaiki berdasarkan manual perbaikan.")
    c.drawString(L + 12.0, Y(396.0), "3.2 Peralatan, teknik dan bahan yang sesuai digunakan berdasarkan manual.")
    c.drawString(L + 12.0, Y(412.0), "3.3 Hasil perbaikan diuji dengan menggunakan diagnostic tool sesuai manual.")
    c.drawString(L + 12.0, Y(428.0), "3.4 Dokumen perbaikan diisi sesuai hasil perbaikan dan dilaporkan.")
    draw_cb(c, (c1 + c2) / 2, (Y(333.4) + Y(507.1)) / 2, True)
    draw_cb(c, (c2 + c3) / 2, (Y(333.4) + Y(507.1)) / 2, False)
    
    # Table 2: Rekomendasi & Signature Box (519.1 to 648.1)
    # Rows: 519.1, 536.7, 567.5, 581.2, 599.3, 617.3, 648.1
    # Cols: 55.7, 282.9, 375.2, 537.8
    c.rect(L, Y(648.1), R - L, 648.1 - 519.1)
    c.line(282.9, Y(648.1), 282.9, Y(519.1))
    
    # Horizontal lines inside right column
    c.line(282.9, Y(536.7), R, Y(536.7))
    c.line(282.9, Y(567.5), R, Y(567.5))
    c.line(282.9, Y(581.2), R, Y(581.2))
    c.line(282.9, Y(599.3), R, Y(599.3))
    c.line(282.9, Y(617.3), R, Y(617.3))
    
    # Inner column inside right section for labels
    c.line(375.2, Y(536.7), 375.2, Y(519.1))
    c.line(375.2, Y(567.5), 375.2, Y(536.7))
    c.line(375.2, Y(599.3), 375.2, Y(581.2))
    c.line(375.2, Y(617.3), 375.2, Y(599.3))
    c.line(375.2, Y(648.1), 375.2, Y(617.3))
    
    # Left Section: Rekomendasi
    c.setFont("Lib-B", 9)
    c.drawString(L + 8.0, Y(540.0), "Rekomendasi Asesor :")
    c.setFont("Lib", 9)
    c.drawString(L + 8.0, Y(560.0), "Asesmen dapat / tidak dapat dilanjutkan *)")
    
    # Right Section: Asesi
    c.setFont("Lib", 8.5)
    c.drawString(282.9 + 6.0, Y(531.0), "Nama")
    c.drawString(375.2 + 6.0, Y(531.0), ": Wahyu Andika Rahadi")
    
    c.drawString(282.9 + 6.0, Y(550.0), "Tanda tangan/")
    c.drawString(282.9 + 6.0, Y(562.0), "Tanggal")
    c.drawString(375.2 + 6.0, Y(555.0), ": [Tanda Tangan Digital] - 19/10/2026")
    
    # Right Section: Asesor
    c.setFont("Lib-B", 8.5)
    c.drawString(282.9 + 6.0, Y(576.0), "Ditinjau Oleh Asesor :")
    
    c.setFont("Lib", 8.5)
    c.drawString(282.9 + 6.0, Y(592.0), "Nama :")
    c.drawString(375.2 + 6.0, Y(592.0), ": Drs. Budi Santoso, M.T.")
    
    c.drawString(282.9 + 6.0, Y(610.0), "No. Reg:")
    c.drawString(375.2 + 6.0, Y(610.0), ": MET.000.001234 2022")
    
    c.drawString(282.9 + 6.0, Y(629.0), "Tanda tangan/")
    c.drawString(282.9 + 6.0, Y(641.0), "Tanggal")
    c.drawString(375.2 + 6.0, Y(635.0), ": [Tanda Tangan Digital] - 19/10/2026")

# ================= HELPER FUNCTIONS =================
def draw_unit_header(c, num, code, title, y_top, y_bot):
    L, R = 53.6, 541.0
    c1, c2, c3 = 159.9, 227.2, 242.7
    y_mid = (y_top + y_bot) / 2
    
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(y_bot), R - L, y_bot - y_top)
    c.line(c1, Y(y_mid), R, Y(y_mid))
    
    c.line(c1, Y(y_bot), c1, Y(y_top))
    c.line(c2, Y(y_bot), c2, Y(y_top))
    c.line(c3, Y(y_bot), c3, Y(y_top))
    
    c.setFont("Lib-B", 9)
    c.setFillColor(black)
    c.drawString(L + 6.0, Y(y_mid) + 3.0, f"Unit Kompetensi {num}")
    
    c.drawString(c1 + 6.0, Y((y_top + y_mid) / 2) - 3.0, "Kode Unit")
    c.drawString(c2 + 4.0, Y((y_top + y_mid) / 2) - 3.0, ":")
    c.drawString(c3 + 6.0, Y((y_top + y_mid) / 2) - 3.0, code)
    
    c.drawString(c1 + 6.0, Y((y_mid + y_bot) / 2) - 3.0, "Judul Unit")
    c.drawString(c2 + 4.0, Y((y_mid + y_bot) / 2) - 3.0, ":")
    c.drawString(c3 + 6.0, Y((y_mid + y_bot) / 2) - 3.0, title)

def draw_elements_table(c, y_hdr_top, items):
    L, R = 53.6, 541.0
    c1, c2, c3 = 321.1, 347.9, 375.0
    y_hdr_bot = items[0]["y_top"]
    y_tbl_bot = items[-1]["y_bot"]
    
    # Table Header (shaded)
    c.setFillColor(GRAY_FILL)
    c.rect(L, Y(y_hdr_bot), R - L, y_hdr_bot - y_hdr_top, fill=1, stroke=0)
    c.rect(L, Y(y_tbl_bot), R - L, y_tbl_bot - y_hdr_top, fill=0, stroke=1)
    c.line(L, Y(y_hdr_bot), R, Y(y_hdr_bot))
    
    # Header labels
    c.setFont("Lib-B", 8.5)
    c.setFillColor(black)
    c.drawString(L + 6.0, Y(y_hdr_top + 8.5), "Dapatkah Saya ................?")
    c.drawCentredString((c1 + c2) / 2, Y(y_hdr_top + 8.5), "K")
    c.drawCentredString((c2 + c3) / 2, Y(y_hdr_top + 8.5), "BK")
    c.drawString(c3 + 8.0, Y(y_hdr_top + 8.5), "Bukti yang relevan")
    
    # Vertical lines
    c.line(c1, Y(y_tbl_bot), c1, Y(y_hdr_top))
    c.line(c2, Y(y_tbl_bot), c2, Y(y_hdr_top))
    c.line(c3, Y(y_tbl_bot), c3, Y(y_hdr_top))
    
    # Render Item Rows
    for it in items:
        yt = it["y_top"]
        yb = it["y_bot"]
        c.line(L, Y(yb), R, Y(yb))
        
        # Elemen Title
        c.setFont("Lib-B", 8.5)
        c.setFillColor(black)
        c.drawString(L + 6.0, Y(yt + 13.0), it["ele"])
        
        # KUKs
        c.setFont("Lib-I", 8.5)
        c.drawString(L + 6.0, Y(yt + 26.0), "• Kriteria Unjuk Kerja:")
        c.setFont("Lib", 8.5)
        ky = yt + 39.0
        for kuk in it.get("kuks", []):
            c.drawString(L + 12.0, Y(ky), kuk)
            ky += 13.0
            
        # Checkboxes
        draw_cb(c, (c1 + c2) / 2, (Y(yt) + Y(yb)) / 2, it.get("k", True))
        draw_cb(c, (c2 + c3) / 2, (Y(yt) + Y(yb)) / 2, not it.get("k", True))

def draw_cb(c, cx, cy, checked):
    sz = 8.5
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz)
    if checked:
        c.setFont("DJ", 8.5)
        c.setFillColor(black)
        c.drawCentredString(cx, cy - sz / 2 + 1.0, "✓")

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    render(OUT)
