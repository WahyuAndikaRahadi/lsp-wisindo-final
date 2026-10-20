#!/usr/bin/env python3
"""
Generator FR.AK.07 (Penyesuaian Wajar dan Alasan) - LSP PrO Indo
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
CELLS_FILE = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "ak07_master_cells.json")

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

PAGE_W, PAGE_H = 595.56, 842.52
Y = lambda top: PAGE_H - top
GRAY_FILL = HexColor("#D9D9D9")

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "tanggal_ttd": "19 Oktober 2026",
    "penyesuaian_diperlukan": False,
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
    
    with open(CELLS_FILE) as f:
        master_pages = json.load(f)
        
    for p_idx in range(4):
        pnum = p_idx + 1
        x0, x1 = 61.7, 546.3
        draw_header_box(c, x0, x1, 36.2, 99.6)
        
        if pnum == 1:
            render_page_1(c)
        elif pnum == 2:
            render_page_2(c)
        elif pnum == 3:
            render_page_3(c)
        elif pnum == 4:
            render_page_4(c)
            
        # Page Footer
        c.setFont("Cal", 9)
        c.setFillColor(black)
        c.drawRightString(PAGE_W - 50.0, 25.0, str(pnum))
        c.showPage()
        
    c.save()
    print(f"OK -> Sukses generate FR.AK.07: {dest_path}")

def render_page_1(c):
    L, R = 61.7, 556.9
    
    # Title
    c.setFont("Lib-B", 11.5)
    c.drawString(L, Y(118.0), "FR.AK.07. CEKLIS PENYESUAIAN YANG WAJAR DAN BERALASAN")
    
    # Table 2: Metadata (top=139.2 to 244.9)
    c1, c2, c3 = 191.7, 244.8, 259.2
    r_meta = [139.2, 159.8, 180.4, 201.5, 222.6, 244.9]
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(r_meta[-1]), R - L, r_meta[-1] - r_meta[0])
    for y in r_meta[1:-1]:
        c.line(L, Y(y), R, Y(y))
        
    c.line(c1, Y(r_meta[2]), c1, Y(r_meta[0]))
    c.line(c2, Y(r_meta[-1]), c2, Y(r_meta[0]))
    c.line(c3, Y(r_meta[-1]), c3, Y(r_meta[0]))
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(151.0), "Skema Sertifikasi")
    c.drawString(L + 6.0, Y(171.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(c1 + 6.0, Y(151.0), "Judul")
    c.drawString(c2 + 4.0, Y(151.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(151.0), DATA["skema_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c1 + 6.0, Y(171.0), "Nomor")
    c.drawString(c2 + 4.0, Y(171.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(171.0), DATA["skema_nomor"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(192.0), "TUK")
    c.drawString(c2 + 4.0, Y(192.0), ":")
    c.drawString(c3 + 6.0, Y(192.0), DATA["tuk_tipe"])
    
    c.drawString(L + 6.0, Y(213.0), "Nama Asesor")
    c.drawString(c2 + 4.0, Y(213.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(213.0), DATA["asesor_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(234.0), "Nama Asesi")
    c.drawString(c2 + 4.0, Y(234.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(234.0), DATA["asesi_nama"])
    
    # Table 3: Panduan Asesor (top=271.5 to 486.9)
    c.setFillColor(GRAY_FILL)
    c.rect(L, Y(288.0), R - L, 288.0 - 271.5, fill=1, stroke=0)
    c.rect(L, Y(486.9), R - L, 486.9 - 271.5, fill=0, stroke=1)
    c.line(L, Y(288.0), R, Y(288.0))
    
    c.setFont("Lib-B", 9)
    c.setFillColor(black)
    c.drawString(L + 6.0, Y(282.0), "PANDUAN BAGI ASESOR")
    
    c.setFont("Lib", 8)
    panduan = [
        "• Formulir ini dapat digunakan (sebelum pra asesmen, saat pelaksanaan pra asesmen, setelah pra asesmen)* jika ada",
        "  asesi yang mempunyai keterbatasan sesuai karakteristik yang dimilikinya sehingga diperlukan penyesuaian yang",
        "  wajar dan beralasan atau jika rencana asesmen dan perangkat asesmen tidak sesuai dengan acuan pembanding,",
        "  potensi asesi dan konteks asesi atau jika asesi merasa keletihan, sakit, serta jika kondisi alam, listrik padam, dst.",
        "• Coretlah pada tanda * yang tidak sesuai.",
        "• Berilah tanda √ pada kotak '☐' pada kolom potensi asesi.",
        "• Jika jawaban Ya pada tanda ** berilah tanda √ di kotak '☐' kolom Ya dan selanjutnya pada kolom keterangan",
        "  berilah tanda √ di kotak '☐' yang tersedia, pilihan boleh lebih dari satu.",
        "• Atau jika jawaban Tidak pada tanda ** berilah tanda √ di kotak '☐' kolom tidak, dan tidak perlu memberi tanda √",
        "  di kotak '☐' kolom keterangan."
    ]
    py = 304.0
    for pl in panduan:
        c.drawString(L + 6.0, Y(py), pl)
        py += 18.0
        
    # Table 4: Potensi Asesi (top=500.9 to 645.5)
    # Rows: 500.9, 530.0, 560.0, 590.0, 618.0, 645.5
    r_pot = [500.9, 530.0, 560.0, 590.0, 618.0, 645.5]
    c.rect(L, Y(r_pot[-1]), R - L, r_pot[-1] - r_pot[0])
    for y in r_pot[1:-1]:
        c.line(L, Y(y), R, Y(y))
        
    c.line(160.0, Y(r_pot[-1]), 160.0, Y(r_pot[0]))
    c.line(185.0, Y(r_pot[-1]), 185.0, Y(r_pot[0]))
    
    c.setFont("Lib-B", 8.5)
    c.drawString(L + 6.0, Y(575.0), "Potensi Asesi")
    
    c.setFont("Lib", 8)
    pot_opts = [
        "Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas praktek mampu telusur standar kompetensi",
        "Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum berbasis kompetensi.",
        "Pekerja berpengalaman, dimana berasal dari industri / tempat kerja yang relevan.",
        "Pekerja berpengalaman, namun belum pernah mengikuti pelatihan / sertifikasi sebelumnya.",
        "Asesi dengan kebutuhan khusus / disabilitas fisik."
    ]
    for idx, opt in enumerate(pot_opts):
        ymid = (r_pot[idx] + r_pot[idx + 1]) / 2
        draw_cb(c, 172.5, Y(ymid), idx == 0)
        c.drawString(192.0, Y(ymid + 3.0), opt[:75])
        
    # Table 5: Header Modifikasi (top=659.5 to 772.1)
    r_mod1 = [659.5, 680.0, 700.0, 772.1]
    c.rect(L, Y(r_mod1[-1]), R - L, r_mod1[-1] - r_mod1[0])
    for y in r_mod1[1:-1]:
        c.line(L, Y(y), R, Y(y))
        
    c_mod_no = 85.0
    c_mod_req = 310.0
    c_mod_ya = 345.0
    c_mod_tdk = 380.0
    
    c.line(c_mod_no, Y(r_mod1[-1]), c_mod_no, Y(r_mod1[0]))
    c.line(c_mod_req, Y(r_mod1[-1]), c_mod_req, Y(r_mod1[0]))
    c.line(c_mod_ya, Y(r_mod1[-1]), c_mod_ya, Y(r_mod1[1]))
    c.line(c_mod_tdk, Y(r_mod1[-1]), c_mod_tdk, Y(r_mod1[0]))
    
    c.setFont("Lib-B", 8)
    c.drawCentredString((L + c_mod_no) / 2, Y(688.0), "No")
    c.drawCentredString((c_mod_no + c_mod_req) / 2, Y(680.0), "Mengidentifikasi Persyaratan Modifikasi dan")
    c.drawCentredString((c_mod_no + c_mod_req) / 2, Y(692.0), "Kontekstualisasi (karakteristik asesi):")
    c.drawCentredString((c_mod_req + c_mod_tdk) / 2, Y(670.0), "Penyesuaian)**")
    c.drawCentredString((c_mod_req + c_mod_ya) / 2, Y(690.0), "Ya")
    c.drawCentredString((c_mod_ya + c_mod_tdk) / 2, Y(690.0), "Tidak")
    c.drawCentredString((c_mod_tdk + R) / 2, Y(688.0), "Keterangan")
    
    # Row 1 item
    c.setFont("Lib", 8)
    c.drawCentredString((L + c_mod_no) / 2, Y(736.0), "1.")
    c.drawString(c_mod_no + 5.0, Y(720.0), "Ada keterbatasan dalam kemampuan bahasa atau literasi:")
    c.drawString(c_mod_no + 5.0, Y(735.0), "Asesi kesulitan membaca teks materi uji standar.")
    draw_cb(c, (c_mod_req + c_mod_ya) / 2, Y(736.0), False)
    draw_cb(c, (c_mod_ya + c_mod_tdk) / 2, Y(736.0), True)
    c.drawString(c_mod_tdk + 8.0, Y(736.0), "Tidak diperlukan penyesuaian khusus.")

def render_page_2(c):
    L, R = 62.5, 557.8
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    
    # Table of modifications continuation
    c.rect(L, Y(760.5), R - L, 760.5 - 112.9)
    
    c_no = 85.0
    c_req = 310.0
    c_ya = 345.0
    c_tdk = 380.0
    
    c.line(c_no, Y(760.5), c_no, Y(112.9))
    c.line(c_req, Y(760.5), c_req, Y(112.9))
    c.line(c_ya, Y(760.5), c_ya, Y(112.9))
    c.line(c_tdk, Y(760.5), c_tdk, Y(112.9))
    
    items_p2 = [
        ("2.", "Ada keterbatasan penglihatan / persepsi visual", "Bahan uji cetak diperbesar (font besar), pencahayaan ruangan memadai."),
        ("3.", "Ada keterbatasan pendengaran / komunikasi verbal", "Instruksi tertulis jelas, konfirmasi isyarat, penerjemah jika perlu."),
        ("4.", "Ada keterbatasan mobilitas fisik / motorik halus", "Aksesibilitas TUK ramah disabilitas, penyesuaian posisi peralatan."),
        ("5.", "Keletihan / kondisi fisik drop / sakit mendadak", "Pemberian waktu istirahat tambahan / reschedule sesuai SOP."),
    ]
    
    y_step = (760.5 - 112.9) / len(items_p2)
    for idx, (no_i, req_i, ket_i) in enumerate(items_p2):
        yt = 112.9 + idx * y_step
        yb = yt + y_step
        if idx > 0:
            c.line(L, Y(yt), R, Y(yt))
            
        ymid = (yt + yb) / 2
        c.setFont("Lib", 8)
        c.drawCentredString((L + c_no) / 2, Y(ymid), no_i)
        c.drawString(c_no + 5.0, Y(ymid + 4.0), req_i[:48])
        draw_cb(c, (c_req + c_ya) / 2, Y(ymid), False)
        draw_cb(c, (c_ya + c_tdk) / 2, Y(ymid), True)
        c.drawString(c_tdk + 6.0, Y(ymid + 4.0), ket_i[:38])

def render_page_3(c):
    L, R = 62.5, 557.8
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    
    c.rect(L, Y(665.3), R - L, 665.3 - 112.9)
    c_no = 85.0
    c_req = 310.0
    c_ya = 345.0
    c_tdk = 380.0
    
    c.line(c_no, Y(665.3), c_no, Y(112.9))
    c.line(c_req, Y(665.3), c_req, Y(112.9))
    c.line(c_ya, Y(665.3), c_ya, Y(112.9))
    c.line(c_tdk, Y(665.3), c_tdk, Y(112.9))
    
    items_p3 = [
        ("6.", "Peralatan asesmen alternatif (Audio/Visual/Simulasi)", "Menggunakan pertanyaan lisan / demonstrasi langsung."),
        ("7.", "Penyesuaian waktu pelaksanaan uji kompetensi", "Perpanjangan waktu maksimal 25% sesuai ketentuan BNSP."),
        ("8.", "Kondisi darurat lingkungan (listrik padam / bencana)", "Penggunaan genset darurat / penjadwalan ulang asesmen.")
    ]
    
    y_step = (665.3 - 112.9) / len(items_p3)
    for idx, (no_i, req_i, ket_i) in enumerate(items_p3):
        yt = 112.9 + idx * y_step
        yb = yt + y_step
        if idx > 0:
            c.line(L, Y(yt), R, Y(yt))
            
        ymid = (yt + yb) / 2
        c.setFont("Lib", 8)
        c.drawCentredString((L + c_no) / 2, Y(ymid), no_i)
        c.drawString(c_no + 5.0, Y(ymid + 4.0), req_i[:48])
        draw_cb(c, (c_req + c_ya) / 2, Y(ymid), False)
        draw_cb(c, (c_ya + c_tdk) / 2, Y(ymid), True)
        c.drawString(c_tdk + 6.0, Y(ymid + 4.0), ket_i[:38])

def render_page_4(c):
    L, R = 67.2, 556.3
    c.setFont("Lib", 8.5)
    c.drawString(84.3, Y(120.0), "Hasil kesepakatan antara asesi dengan asesor (Diperlukan penyesuaian atau tidak)*")
    c.drawString(84.3, Y(136.0), "Jika diperlukan penyesuaian maka, tuliskan kesepakatan dibawah ini:")
    
    c.drawString(92.0, Y(165.0), "1) Acuan Pembanding Asesmen: SKKNI Nomor 97 Tahun 2018 (Maintenance Gasoline Engine Injection)")
    c.drawString(92.0, Y(194.0), "2) Metode Asesmen: Observasi Demonstrasi, Pertanyaan Lisan, dan Uji Tertulis Pilihan Ganda")
    c.drawString(92.0, Y(223.0), "3) Instrumen Asesmen: FR.IA.01, FR.IA.02, FR.IA.03, FR.IA.05")
    
    # Signature Box (top=255.5 to 352.7)
    r_sig = [255.5, 304.0, 352.7]
    c_mid_s = 310.0
    c.rect(L, Y(r_sig[-1]), R - L, r_sig[-1] - r_sig[0])
    c.line(L, Y(r_sig[1]), R, Y(r_sig[1]))
    c.line(c_mid_s, Y(r_sig[-1]), c_mid_s, Y(r_sig[0]))
    
    # Asesor
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(280.0), f"Nama Asesor : {DATA['asesor_nama']}")
    c.drawString(c_mid_s + 6.0, Y(280.0), f"Tanda Tangan Asesor : [Ttd Digital] - {DATA['tanggal_ttd']}")
    
    # Asesi
    c.drawString(L + 6.0, Y(330.0), f"Nama Asesi : {DATA['asesi_nama']}")
    c.drawString(c_mid_s + 6.0, Y(330.0), f"Tanda Tangan Asesi : [Ttd Digital] - {DATA['tanggal_ttd']}")
    
    c.setFont("Lib-I", 7.5)
    c.drawString(63.9, Y(370.0), "Modul Melaksanakan Asesmen hal 32-35")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_07_generated.pdf")
    render(out_file)
