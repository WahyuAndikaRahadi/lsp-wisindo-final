#!/usr/bin/env python3
"""
Generator FR.IA.03 (Pertanyaan Mendukung Observasi - PMO) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 4 halaman sesuai master dokumen BNSP.
"""

import os
import sys
import json
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

PAGE_W, PAGE_H = 595.5, 842.5
Y = lambda top: PAGE_H - top
GRAY_FILL = HexColor("#D9D9D9")

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "tanggal_ttd": "19 Oktober 2026",
    "tanggapan_k1_1": "Menggunakan APD lengkap sesuai SOP, memastikan area kerja bersih dan bebas ceceran oli.",
    "tanggapan_k1_2": "Agar peralatan tidak cepat rusak, hasil pengukuran presisi, dan menjaga keselamatan kerja mekanik.",
    "tanggapan_k1_3": "Untuk kesiapsiagaan darurat jika terjadi percikan api atau korsleting saat perbaikan sistem injeksi.",
    "tanggapan_k2_1": "Untuk mencegah keausan dini komponen mesin dan mempertahankan performa optimal kendaraan.",
    "tanggapan_k2_2": "Melakukan pemeriksaan visual, pengecekan celah katup, dan pengecekan kebocoran kompresi.",
    "tanggapan_k2_3": "Mencatat semua hasil pengukuran ke report sheet dan menyerahkan ke asesor/supervisor bengkel.",
    "tanggapan_k3_1": "Melakukan pemeriksaan berat jenis air aki dan melakukan charging ulang jika kapasitas drop.",
    "tanggapan_k3_2": "Dapat menyebabkan korsleting listrik dan membakar komponen ECU/sensor elektronik mobil.",
    "tanggapan_k3_3": "Karena sistem EFI dikontrol komputer (ECU) sehingga error code hanya bisa dibaca akurat via scanner.",
}

def draw_header_box(c, x0, x1, top, bot):
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    x_div = 147.7
    c.line(x_div, Y(top), x_div, Y(bot))
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, Y(bot) + 4.0, 50.0, 36.0, mask="auto")
    c.setFont("Lib-B", 11.5)
    c.setFillColor(black)
    c.drawString(x_div + 18.0, Y(bot) + 18.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

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
    x0, x1 = 73.2, 551.4
    draw_header_box(c, x0, x1, 14.5, 59.9)
    
    # Title
    c.setFont("Lib-B", 11.5)
    c.drawString(x0, Y(78.0), "FR.IA.03. PMO - PERTANYAAN MENDUKUNG OBSERVASI")
    
    # Table 2: Metadata (top=99.1 to 204.7)
    c1, c2, c3 = 191.7, 244.8, 259.2
    r_meta = [99.1, 119.7, 140.3, 161.4, 182.5, 204.7]
    c.rect(x0, Y(r_meta[-1]), x1 - x0, r_meta[-1] - r_meta[0])
    for y in r_meta[1:-1]:
        c.line(x0, Y(y), x1, Y(y))
        
    c.line(c1, Y(r_meta[2]), c1, Y(r_meta[0]))
    c.line(c2, Y(r_meta[-1]), c2, Y(r_meta[0]))
    c.line(c3, Y(r_meta[-1]), c3, Y(r_meta[0]))
    
    c.setFont("Lib", 8.5)
    c.drawString(x0 + 6.0, Y(111.0), "Skema Sertifikasi")
    c.drawString(x0 + 6.0, Y(131.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(c1 + 6.0, Y(111.0), "Judul")
    c.drawString(c2 + 4.0, Y(111.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(111.0), DATA["skema_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c1 + 6.0, Y(131.0), "Nomor")
    c.drawString(c2 + 4.0, Y(131.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(131.0), DATA["skema_nomor"])
    
    c.setFont("Lib", 8.5)
    c.drawString(x0 + 6.0, Y(152.0), "TUK")
    c.drawString(c2 + 4.0, Y(152.0), ":")
    c.drawString(c3 + 6.0, Y(152.0), DATA["tuk_tipe"])
    
    c.drawString(x0 + 6.0, Y(173.0), "Nama Asesor")
    c.drawString(c2 + 4.0, Y(173.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(173.0), DATA["asesor_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(x0 + 6.0, Y(194.0), "Nama Asesi")
    c.drawString(c2 + 4.0, Y(194.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(194.0), DATA["asesi_nama"])
    
    # Table 3: Panduan Asesor (top=231.4 to 506.1)
    c.setFillColor(GRAY_FILL)
    c.rect(x0, Y(248.0), x1 - x0, 248.0 - 231.4, fill=1, stroke=0)
    c.rect(x0, Y(506.1), x1 - x0, 506.1 - 231.4, fill=0, stroke=1)
    c.line(x0, Y(248.0), x1, Y(248.0))
    
    c.setFont("Lib-B", 9)
    c.setFillColor(black)
    c.drawString(x0 + 6.0, Y(242.0), "PANDUAN BAGI ASESOR")
    
    c.setFont("Lib", 8)
    panduan_lines = [
        "• Formulir ini di isi oleh asesor kompetensi dapat sebelum, pada saat atau setelah melakukan asesmen dengan",
        "  metode observasi demonstrasi.",
        "• Pertanyaan dibuat dengan tujuan untuk menggali, dapat berisi pertanyaan yang berkaitan dengan dimensi",
        "  kompetensi, batasan variabel dan aspek kritis yang relevan dengan skenario tugas dan praktik demonstrasi.",
        "• Jika pertanyaan disampaikan sebelum asesi melakukan praktik demonstrasi, maka pertanyaan dibuat berkaitan",
        "  dengan aspek K3L, SOP, penggunaan peralatan dan perlengkapan.",
        "• Jika setelah asesi melakukan praktik demonstrasi terdapat item pertanyaan pendukung observasi telah terpenuhi,",
        "  maka pertanyaan tersebut tidak perlu ditanyakan lagi dan cukup memberi catatan bahwa sudah terpenuhi.",
        "• Jika pada saat observasi ada hal yang perlu dikonfirmasi sedangkan di instrumen daftar pertanyaan tidak ada,",
        "  maka asesor dapat memberikan pertanyaan tambahan dengan syarat relevan dengan tugas praktik.",
        "• Tanggapan asesi ditulis pada kolom tanggapan."
    ]
    py = 265.0
    for pl in panduan_lines:
        c.drawString(x0 + 6.0, Y(py), pl)
        py += 21.0
        
    # Table 4: Kelompok Pekerjaan 1 (top=520.6 to 689.9)
    # Rows: 520.6, 545.0, 575.0, 605.0, 635.0, 665.0, 689.9
    c_k_split = 220.0
    c_no_k = 245.0
    c_kd_k = 345.0
    
    r_k1 = [520.6, 545.0, 575.0, 605.0, 635.0, 665.0, 689.9]
    c.rect(x0, Y(r_k1[-1]), x1 - x0, r_k1[-1] - r_k1[0])
    for y in r_k1[1:-1]:
        c.line(x0, Y(y), x1, Y(y))
        
    c.line(c_k_split, Y(r_k1[-1]), c_k_split, Y(r_k1[0]))
    c.line(c_no_k, Y(r_k1[-1]), c_no_k, Y(r_k1[0]))
    c.line(c_kd_k, Y(r_k1[-1]), c_kd_k, Y(r_k1[0]))
    
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 6.0, Y(535.0), "Kelompok Pekerjaan 1 General Service")
    c.drawCentredString((c_k_split + c_no_k) / 2, Y(535.0), "No.")
    c.drawCentredString((c_no_k + c_kd_k) / 2, Y(535.0), "Kode Unit")
    c.drawString(c_kd_k + 8.0, Y(535.0), "Judul Unit")
    
    k1_units = [
        ("1.", "G.45OTO01.001.2", "Melaksanakan Keselamatan dan Kesehatan Kerja"),
        ("2.", "G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja"),
        ("3.", "G.45OTO01.007.2", "Membaca Gambar Teknik"),
        ("4.", "G.45OTO01.008.2", "Menggunakan Alat Ukur"),
        ("5.", "G.45OTO01.009.2", "Melaksanakan Diagnosis")
    ]
    for idx, (no_u, kd_u, jd_u) in enumerate(k1_units):
        y_mid = (r_k1[1 + idx] + r_k1[2 + idx]) / 2 + 3.0
        c.setFont("Lib", 8)
        c.drawCentredString((c_k_split + c_no_k) / 2, Y(y_mid), no_u)
        c.drawCentredString((c_no_k + c_kd_k) / 2, Y(y_mid), kd_u)
        c.drawString(c_kd_k + 8.0, Y(y_mid), jd_u)
        
    c.showPage()
    
    # ================= PAGE 2 =================
    # Table 1: Questions Kelompok 1 (top=84.1 to 370.1)
    L2, R2 = 70.9, 554.2
    c_q_no = 92.0
    c_q_txt = 475.0
    c_mid_ya = (c_q_txt + R2) / 2
    
    # Header: 84.1 to 110.0 (Ya/Tdk)
    # Q1: 110.0 to 145.0 (Tanya), 145.0 to 195.0 (Tanggapan)
    # Q2: 195.0 to 230.0 (Tanya), 230.0 to 280.0 (Tanggapan)
    # Q3: 280.0 to 315.0 (Tanya), 315.0 to 370.1 (Tanggapan)
    r_q1 = [84.1, 98.0, 112.0, 145.0, 195.0, 230.0, 280.0, 315.0, 370.1]
    c.rect(L2, Y(r_q1[-1]), R2 - L2, r_q1[-1] - r_q1[0])
    for y in r_q1[1:-1]:
        c.line(L2, Y(y), R2, Y(y))
        
    c.line(c_q_no, Y(r_q1[-1]), c_q_no, Y(r_q1[0]))
    c.line(c_q_txt, Y(r_q1[-1]), c_q_txt, Y(r_q1[0]))
    c.line(c_mid_ya, Y(r_q1[-1]), c_mid_ya, Y(r_q1[1]))
    
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((L2 + c_q_txt) / 2, Y(93.0), "Pertanyaan")
    c.drawCentredString((c_q_txt + R2) / 2, Y(93.0), "Pencapaian")
    c.drawCentredString((c_q_txt + c_mid_ya) / 2, Y(106.0), "Ya")
    c.drawCentredString((c_mid_ya + R2) / 2, Y(106.0), "Tdk")
    
    # Q1
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_q_no) / 2, Y(130.0), "1.")
    c.drawString(c_q_no + 5.0, Y(125.0), "Jelaskan bagaimana anda melaksanakan Prosedur kesehatan dan keselamatan kerja")
    c.drawString(c_q_no + 5.0, Y(137.0), "di tempat kerja!")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(158.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(172.0), DATA["tanggapan_k1_1"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(170.0), True)
    draw_cb(c, (c_mid_ya + R2) / 2, Y(170.0), False)
    
    # Q2
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_q_no) / 2, Y(215.0), "2.")
    c.drawString(c_q_no + 5.0, Y(210.0), "Kenapa menggunakan Peralatan dan perlengkapan harus sesuai manual perbaikan?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(243.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(257.0), DATA["tanggapan_k1_2"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(255.0), True)
    draw_cb(c, (c_mid_ya + R2) / 2, Y(255.0), False)
    
    # Q3
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_q_no) / 2, Y(300.0), "3.")
    c.drawString(c_q_no + 5.0, Y(300.0), "Kenapa APAR harus selalu ada di tempat kerja?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(330.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(344.0), DATA["tanggapan_k1_3"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(342.0), True)
    draw_cb(c, (c_mid_ya + R2) / 2, Y(342.0), False)
    
    # Table 2: Kelompok 2 Header (top=385.2 to 450.3)
    r_k2 = [385.2, 410.0, 450.3]
    c.rect(L2, Y(r_k2[-1]), R2 - L2, r_k2[-1] - r_k2[0])
    c.line(L2, Y(r_k2[1]), R2, Y(r_k2[1]))
    c.line(c_k_split, Y(r_k2[-1]), c_k_split, Y(r_k2[0]))
    c.line(c_no_k, Y(r_k2[-1]), c_no_k, Y(r_k2[0]))
    c.line(c_kd_k, Y(r_k2[-1]), c_kd_k, Y(r_k2[0]))
    
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(400.0), "Kelompok Pekerjaan 2 Engine")
    c.drawCentredString((c_k_split + c_no_k) / 2, Y(400.0), "No.")
    c.drawCentredString((c_no_k + c_kd_k) / 2, Y(400.0), "Kode Unit")
    c.drawString(c_kd_k + 8.0, Y(400.0), "Judul Unit")
    
    c.setFont("Lib", 8)
    c.drawCentredString((c_k_split + c_no_k) / 2, Y(432.0), "1.")
    c.drawCentredString((c_no_k + c_kd_k) / 2, Y(432.0), "G.45OTO01.010.2")
    c.drawString(c_kd_k + 8.0, Y(432.0), "Melakukan Perawatan Berkala Engine")
    
    # Table 3: Questions Kelompok 2 (top=464.2 to 766.2)
    r_q2 = [464.2, 478.0, 492.0, 530.0, 580.0, 620.0, 675.0, 715.0, 766.2]
    c.rect(L2, Y(r_q2[-1]), R2 - L2, r_q2[-1] - r_q2[0])
    for y in r_q2[1:-1]:
        c.line(L2, Y(y), R2, Y(y))
        
    c.line(c_q_no, Y(r_q2[-1]), c_q_no, Y(r_q2[0]))
    c.line(c_q_txt, Y(r_q2[-1]), c_q_txt, Y(r_q2[0]))
    c.line(c_mid_ya, Y(r_q2[-1]), c_mid_ya, Y(r_q2[1]))
    
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((L2 + c_q_txt) / 2, Y(473.0), "Pertanyaan")
    c.drawCentredString((c_q_txt + R2) / 2, Y(473.0), "Pencapaian")
    c.drawCentredString((c_q_txt + c_mid_ya) / 2, Y(486.0), "Ya")
    c.drawCentredString((c_mid_ya + R2) / 2, Y(486.0), "Tdk")
    
    # Q1 Engine
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_q_no) / 2, Y(512.0), "1.")
    c.drawString(c_q_no + 5.0, Y(512.0), "Jelaskan mengapa Engine harus dirawat secara Berkala:")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(545.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(559.0), DATA["tanggapan_k2_1"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(555.0), True)
    draw_cb(c, (c_mid_ya + R2) / 2, Y(555.0), False)
    
    # Q2 Engine
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_q_no) / 2, Y(602.0), "2.")
    c.drawString(c_q_no + 5.0, Y(602.0), "Bagaimana langkah yang harus anda lakukan, jika suara mesin tidak normal?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(637.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(651.0), DATA["tanggapan_k2_2"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(647.0), True)
    draw_cb(c, (c_mid_ya + R2) / 2, Y(647.0), False)
    
    # Q3 Engine
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_q_no) / 2, Y(697.0), "3.")
    c.drawString(c_q_no + 5.0, Y(697.0), "Bagaimana anda mendokumentasikan dan melaporkan hasil pemeriksaan?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(730.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(744.0), DATA["tanggapan_k2_3"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(740.0), True)
    draw_cb(c, (c_mid_ya + R2) / 2, Y(740.0), False)
    
    c.showPage()
    
    # ================= PAGE 3 =================
    draw_header_box(c, 69.6, 552.8, 14.5, 59.9)
    L3, R3 = 69.6, 552.8
    
    # Table 2: Kelompok 3 Header (top=74.3 to 194.2)
    r_k3 = [74.3, 98.0, 130.0, 162.0, 194.2]
    c.rect(L3, Y(r_k3[-1]), R3 - L3, r_k3[-1] - r_k3[0])
    for y in r_k3[1:-1]:
        c.line(L3, Y(y), R3, Y(y))
        
    c.line(c_k_split, Y(r_k3[-1]), c_k_split, Y(r_k3[0]))
    c.line(c_no_k, Y(r_k3[-1]), c_no_k, Y(r_k3[0]))
    c.line(c_kd_k, Y(r_k3[-1]), c_kd_k, Y(r_k3[0]))
    
    c.setFont("Lib-B", 8.5)
    c.drawString(L3 + 6.0, Y(88.0), "Kelompok Pekerjaan 3 Electrical")
    c.drawCentredString((c_k_split + c_no_k) / 2, Y(88.0), "No.")
    c.drawCentredString((c_no_k + c_kd_k) / 2, Y(88.0), "Kode Unit")
    c.drawString(c_kd_k + 8.0, Y(88.0), "Judul Unit")
    
    k3_units = [
        ("1.", "G.45OTO01.065.2", "Memelihara Baterai"),
        ("2.", "G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan"),
        ("3.", "G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine")
    ]
    for idx, (no_u, kd_u, jd_u) in enumerate(k3_units):
        y_mid = (r_k3[1 + idx] + r_k3[2 + idx]) / 2 + 3.0
        c.setFont("Lib", 8)
        c.drawCentredString((c_k_split + c_no_k) / 2, Y(y_mid), no_u)
        c.drawCentredString((c_no_k + c_kd_k) / 2, Y(y_mid), kd_u)
        c.drawString(c_kd_k + 8.0, Y(y_mid), jd_u)
        
    # Table 3: Questions Kelompok 3 (top=208.1 to 660.5)
    r_q3 = [208.1, 222.0, 236.0, 280.0, 350.0, 395.0, 470.0, 515.0, 660.5]
    c.rect(L3, Y(r_q3[-1]), R3 - L3, r_q3[-1] - r_q3[0])
    for y in r_q3[1:-1]:
        c.line(L3, Y(y), R3, Y(y))
        
    c.line(c_q_no, Y(r_q3[-1]), c_q_no, Y(r_q3[0]))
    c.line(c_q_txt, Y(r_q3[-1]), c_q_txt, Y(r_q3[0]))
    c.line(c_mid_ya, Y(r_q3[-1]), c_mid_ya, Y(r_q3[1]))
    
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((L3 + c_q_txt) / 2, Y(217.0), "Pertanyaan")
    c.drawCentredString((c_q_txt + R3) / 2, Y(217.0), "Pencapaian")
    c.drawCentredString((c_q_txt + c_mid_ya) / 2, Y(230.0), "Ya")
    c.drawCentredString((c_mid_ya + R3) / 2, Y(230.0), "Tdk")
    
    # Q1 Electrical
    c.setFont("Lib", 8)
    c.drawCentredString((L3 + c_q_no) / 2, Y(255.0), "1.")
    c.drawString(c_q_no + 5.0, Y(255.0), "Bagaimana langkah yang harus anda lakukan, jika tegangan Baterai drop?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(295.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(310.0), DATA["tanggapan_k3_1"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(315.0), True)
    draw_cb(c, (c_mid_ya + R3) / 2, Y(315.0), False)
    
    # Q2 Electrical
    c.setFont("Lib", 8)
    c.drawCentredString((L3 + c_q_no) / 2, Y(370.0), "2.")
    c.drawString(c_q_no + 5.0, Y(370.0), "Apa yang terjadi jika Perbaikan atau penggantian komponen sistem pengaman tidak sesuai SOP?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(415.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(430.0), DATA["tanggapan_k3_2"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(432.0), True)
    draw_cb(c, (c_mid_ya + R3) / 2, Y(432.0), False)
    
    # Q3 Electrical
    c.setFont("Lib", 8)
    c.drawCentredString((L3 + c_q_no) / 2, Y(495.0), "3.")
    c.drawString(c_q_no + 5.0, Y(495.0), "Mengapa diagnostic tool digunakan saat memperbaiki Sistem Manajemen Engine?")
    c.setFont("Lib-B", 8)
    c.drawString(c_q_no + 5.0, Y(540.0), "Tanggapan:")
    c.setFont("Lib-I", 8)
    c.drawString(c_q_no + 5.0, Y(555.0), DATA["tanggapan_k3_3"])
    draw_cb(c, (c_q_txt + c_mid_ya) / 2, Y(560.0), True)
    draw_cb(c, (c_mid_ya + R3) / 2, Y(560.0), False)
    
    c.showPage()
    
    # ================= PAGE 4 =================
    draw_header_box(c, 70.8, 550.2, 14.5, 59.9)
    L4, R4 = 70.8, 555.7
    c_split_sig = 200.0
    c_colon_sig = 215.0
    
    r_sig4 = [86.6, 102.0, 126.0, 165.0, 185.0, 210.0, 230.0, 255.9]
    c.rect(L4, Y(r_sig4[-1]), R4 - L4, r_sig4[-1] - r_sig4[0])
    for y in r_sig4[1:-1]:
        c.line(L4, Y(y), R4, Y(y))
        
    c.line(c_split_sig, Y(r_sig4[3]), c_split_sig, Y(r_sig4[1]))
    c.line(c_colon_sig, Y(r_sig4[3]), c_colon_sig, Y(r_sig4[1]))
    
    c.line(c_split_sig, Y(r_sig4[7]), c_split_sig, Y(r_sig4[4]))
    c.line(c_colon_sig, Y(r_sig4[7]), c_colon_sig, Y(r_sig4[4]))
    
    # Asesi
    c.setFont("Lib-B", 8.5)
    c.drawString(L4 + 6.0, Y(97.0), "Asesi :")
    c.setFont("Lib", 8.5)
    c.drawString(L4 + 6.0, Y(116.0), "Nama")
    c.drawString(c_split_sig + 4.0, Y(116.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_colon_sig + 6.0, Y(116.0), DATA["asesi_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L4 + 6.0, Y(145.0), "Tanda tangan/ Tanggal")
    c.drawString(c_split_sig + 4.0, Y(145.0), ":")
    c.drawString(c_colon_sig + 6.0, Y(145.0), f"[Tanda Tangan Digital]   -   {DATA['tanggal_ttd']}")
    
    # Asesor
    c.setFont("Lib-B", 8.5)
    c.drawString(L4 + 6.0, Y(178.0), "Asesor :")
    c.setFont("Lib", 8.5)
    c.drawString(L4 + 6.0, Y(200.0), "Nama")
    c.drawString(c_split_sig + 4.0, Y(200.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_colon_sig + 6.0, Y(200.0), DATA["asesor_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L4 + 6.0, Y(222.0), "No. Reg")
    c.drawString(c_split_sig + 4.0, Y(222.0), ":")
    c.drawString(c_colon_sig + 6.0, Y(222.0), DATA["asesor_no_reg"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L4 + 6.0, Y(245.0), "Tanda tangan / Tanggal")
    c.drawString(c_split_sig + 4.0, Y(245.0), ":")
    c.drawString(c_colon_sig + 6.0, Y(245.0), f"[Tanda Tangan Digital]   -   {DATA['tanggal_ttd']}")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.IA.03: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_IA_03_generated.pdf")
    render(out_file)
