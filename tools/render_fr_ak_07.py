#!/usr/bin/env python3
"""
Generator FR.AK.07 (Ceklis Penyesuaian yang Wajar dan Beralasan) - LSP PrO Indo
Layout meniru master dokumen BNSP resmi secara presisi piksel; tanpa watermark.
Paginasi persis 4 halaman sesuai master dokumen asli.
"""

import os
import sys
import json
import io
import base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor, Color
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
ASSETS = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets")
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_AK_07_generated.pdf")

def find_font(filename, fallback="LiberationSans-Regular.ttf"):
    candidates = [
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/liberation",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/dejavu",
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
    "Cal-I": ("Carlito-Italic.ttf", "LiberationSans-Italic.ttf"),
    "DJ": ("DejaVuSans.ttf", "DejaVuSans.ttf"),
    "Times": ("LiberationSerif-Regular.ttf", "LiberationSerif-Regular.ttf"),
}
for name, (ffile, fallback) in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile, fallback)))

PAGE_W, PAGE_H = 595.56, 842.52
Y = lambda top: PAGE_H - top
GRAY_HEADER = HexColor("#D9D9D9")
BLUE_NUM = Color(0.267, 0.447, 0.769)

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "tanggal_ttd": "19 Oktober 2026",
    "penyesuaian_diperlukan": False,
    "signature_asesi": None,
    "signature_asesor": None,
    "potensi_asesi_index": 1,
    "agreement_acuan": "",
    "agreement_metode": "",
    "agreement_instrumen": "",
    "agreement_notes": "",
}

def clean_signature_image(raw_bytes_or_path):
    try:
        if isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path))
        else:
            im = Image.open(raw_bytes_or_path)
        im = im.convert("RGBA")
        w, h = im.size
        if w == 0 or h == 0:
            return None

        corners = []
        for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)):
            px = im.getpixel(pt)
            if isinstance(px, tuple) and len(px) >= 3:
                corners.append((px[0], px[1], px[2]))
            elif isinstance(px, (int, float)):
                corners.append((int(px), int(px), int(px)))

        if not corners:
            corners = [(255, 255, 255)]

        bg_r = sum(c[0] for c in corners) / len(corners)
        bg_g = sum(c[1] for c in corners) / len(corners)
        bg_b = sum(c[2] for c in corners) / len(corners)

        pixels = im.load()
        if pixels is None:
            return None

        for y in range(h):
            for x in range(w):
                px = pixels[x, y]
                if not isinstance(px, tuple) or len(px) < 4:
                    continue
                r, g, b, a = int(px[0]), int(px[1]), int(px[2]), int(px[3])
                if a == 0:
                    continue
                is_green = (g > 80 and g > r * 1.15 and g > b * 1.15)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5
                is_bg = dist_bg < 45
                is_white = (r > 235 and g > 235 and b > 235)

                if is_green or is_bg or is_white:
                    pixels[x, y] = (0, 0, 0, 0)
                elif dist_bg < 75 or (g > r and g > b and g > 70):
                    factor = min(1.0, max(0.0, (dist_bg - 45) / 30.0))
                    pixels[x, y] = (r, g, b, int(a * factor))

        out_io = io.BytesIO()
        im.save(out_io, format="PNG")
        out_io.seek(0)
        return out_io
    except Exception:
        return None

def draw_signature(c, sig_data, x, y_bot, w=75.0, h=26.0):
    if not sig_data:
        return
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
            cleaned = clean_signature_image(raw)
            img = ImageReader(cleaned if cleaned else io.BytesIO(raw))
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            cleaned = clean_signature_image(sig_data)
            img = ImageReader(cleaned if cleaned else sig_data)
        else:
            return
        c.drawImage(img, x, Y(y_bot), w, h, mask="auto")
    except Exception:
        pass

def draw_header_box(c, pnum):
    x0, x1 = 61.7, 546.3
    top, bot = 36.25, 99.65
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top, fill=0, stroke=1)
    
    # Logo divider
    x_div = 147.7
    c.line(x_div, Y(top), x_div, Y(bot))
    
    # Logo
    logo_file = os.path.join(ASSETS, "logo.png")
    if not os.path.exists(logo_file):
        logo_file = os.path.join(PROJECT_ROOT, "public", "images", "logo-lsp-proindo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, 77.3, Y(97.8), 69.4, 61.5, mask="auto")
        
    # Title words matching original master position
    words = [
        ("L", "Lib-B", 198.4),
        ("embaga ", "Lib", 204.5),
        ("S", "Lib-B", 246.8),
        ("ertifikasi ", "Lib", 253.5),
        ("P", "Lib-B", 297.6),
        ("rofesi ", "Lib", 304.2),
        ("P", "Lib-B", 334.8),
        ("raktisi ", "Lib", 341.5),
        ("O", "Lib-B", 374.1),
        ("tomotif  ", "Lib", 383.0),
        ("LSP-PrO Indo", "Lib-B", 423.6),
    ]
    for text_val, f_name, pos_x in words:
        c.setFont(f_name, 11.0)
        c.setFillColor(black)
        c.drawString(pos_x, Y(73.5), text_val)

def draw_page_num(c, pnum):
    c.setFont("Times", 11.0)
    c.setFillColor(BLUE_NUM)
    c.drawCentredString(PAGE_W / 2, Y(792.0), str(pnum))

def draw_cb(c, cx, cy, checked=False):
    sz = 8.6
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz, fill=0, stroke=1)
    if checked:
        c.setFont("DJ", 8.5)
        c.setFillColor(black)
        c.drawCentredString(cx, cy - sz / 2 + 1.0, "✓")

# ================= PAGE 1 =================
def render_page_1(c):
    draw_header_box(c, 1)
    
    L, R = 61.7, 556.9
    
    # Title
    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawString(62.8, Y(127.3), "FR.AK.07.")
    c.drawString(203.4, Y(127.3), "CEKLIS PENYESUAIAN YANG WAJAR dan BERALASAN")
    
    # Table 1: Skema Metadata Table (139.2 to 244.9)
    r1_top, r1_bot = 139.2, 244.9
    c1, c2, c3 = 189.9, 238.0, 250.0
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(L, Y(r1_bot), R - L, r1_bot - r1_top)
    
    # Horizontal lines
    r1_lines = [160.1, 181.1, 197.0, 213.0, 228.9]
    c.line(c1, Y(160.1), R, Y(160.1))
    c.line(L, Y(181.1), R, Y(181.1))
    c.line(L, Y(197.0), R, Y(197.0))
    c.line(L, Y(213.0), R, Y(213.0))
    c.line(L, Y(228.9), R, Y(228.9))
    
    # Vertical lines
    c.line(c1, Y(r1_bot), c1, Y(r1_top))
    c.line(c2, Y(181.1), c2, Y(r1_top))
    c.line(c3, Y(181.1), c3, Y(r1_top))
    c.line(c2, Y(r1_bot), c2, Y(181.1))
    
    # Table 1 Content
    c.setFont("Lib-B", 9.5)
    c.drawString(L + 5.5, Y(154.0), "Skema Sertifikasi")
    c.setFont("Lib", 8.0)
    c.drawString(L + 5.5, Y(166.5), "(KKNI/Okupasi/Klaster)")
    # Strikethrough on KKNI/Okupasi/
    c.rect(L + 8.5, Y(163.5), 65.0, 0.6, fill=1, stroke=0)
    
    c.setFont("Lib-B", 9.5)
    c.drawString(c1 + 5.5, Y(154.0), "Judul")
    c.drawString(c2 + 3.0, Y(154.0), ":")
    skema_nama = DATA.get("skema_nama") or DATA.get("skema_judul") or "Maintenance Gasoline Engine Injection System"
    f_sz = 9.5
    while pdfmetrics.stringWidth(skema_nama, "Lib-I", f_sz) > (R - c3 - 10) and f_sz > 7.0:
        f_sz -= 0.5
    c.setFont("Lib-I", f_sz)
    c.drawString(c3 + 5.5, Y(154.0), skema_nama)
    
    c.setFont("Lib-B", 9.5)
    c.drawString(c1 + 5.5, Y(174.5), "Nomor")
    c.drawString(c2 + 3.0, Y(174.5), ":")
    c.setFont("Lib", 9.5)
    c.drawString(c3 + 5.5, Y(174.5), DATA.get("skema_nomor") or "02/LSP PrO Indo/I/2026")
    
    # TUK, Nama Asesor, Nama Asesi, Tanggal
    meta_rows = [
        (181.1, 197.0, "TUK", DATA.get("tuk_tipe") or DATA.get("tuk_nama") or "Sewaktu/Tempat Kerja/Mandiri*"),
        (197.0, 213.0, "Nama Asesor", DATA.get("asesor_nama") or "Drs. Budi Santoso, M.T."),
        (213.0, 228.9, "Nama Asesi", DATA.get("asesi_nama") or "Wahyu Andika Rahadi"),
        (228.9, 244.9, "Tanggal", DATA.get("tanggal") or "19 Oktober 2026"),
    ]
    for y_t, y_b, lbl, val in meta_rows:
        y_mid = (y_t + y_b) / 2
        c.setFont("Lib", 9.5)
        c.drawString(L + 5.5, Y(y_mid + 3.0), lbl)
        c.drawString(c2 + 3.0, Y(y_mid + 3.0), ":")
        c.drawString(c2 + 15.0, Y(y_mid + 3.0), val)
        
    c.setFont("Lib", 8.0)
    c.drawString(62.8, Y(256.0), "*Coret yang tidak perlu")
    
    # Table 2: Panduan Bagi Asesor (271.5 to 486.9)
    p_top, p_hdr_bot, p_bot = 271.5, 298.4, 486.9
    c.rect(L, Y(p_bot), R - L, p_bot - p_top)
    c.line(L, Y(p_hdr_bot), R, Y(p_hdr_bot))
    
    c.setFont("Lib-B", 10.5)
    c.drawString(73.6, Y(289.0), "PANDUAN BAGI ASESOR")
    
    c.setFont("Lib", 9.5)
    panduan_lines = [
        ("•", "Formulir ini dapat digunakan (sebelum pra asesmen, saat pelaksanaan pra asesmen, setelah pra", 314.0),
        ("", "asesmen)* jika ada asesi yang mempunyai keterbatasan sesuai karakteristik yang dimilikinya sehinga", 328.0),
        ("", "diperlukan penyesuaian yang wajar dan beralasan atau jika rencana asesmen dan perangkat asesmen", 342.0),
        ("", "tidak sesuai dengan acuan pembanding, potensi asesi dan konteks asesi atau ika asesi merasa", 356.0),
        ("", "keletihan, sakit, serta jika kondisi alam, listrik padam,……..", 370.0),
        ("•", "Coretlah pada tanda * yang tidak sesuai.", 394.0),
        ("•", "Berilah tanda √ pada kotak ‘☐ ‘ pada kolom potensi asesi", 418.0),
        ("•", "Jika jawaban Ya pada tanda ** berilah tanda √ di kotak ‘☐‘ kolom Ya dan selanjutanya pada kolom", 442.0),
        ("", "keterangan berilah tanda √ di kotak ‘☐‘ yang tersedia, pilihan boleh lebih dari satu.", 456.0),
        ("•", "atau jika jawaban Tidak pada tanda ** berilah tanda √ di kotak ‘☐ ‘ kolom tidak, dan tidak perlu", 470.0),
        ("", "memberi tanda tanda √ di kotak ‘☐ ‘ kolom keterangan.", 484.0),
    ]
    for bullet, txt, y_pos in panduan_lines:
        if bullet:
            c.drawString(73.6, Y(y_pos), bullet)
        c.drawString(88.0, Y(y_pos), txt)
        
    # Table 3: Potensi Asesi (500.9 to 645.5)
    pot_top, pot_bot = 500.9, 645.5
    c_pot_lbl = 189.9
    c_pot_cb = 216.7
    
    c.rect(L, Y(pot_bot), R - L, pot_bot - pot_top)
    c.line(c_pot_lbl, Y(pot_bot), c_pot_lbl, Y(pot_top))
    c.line(c_pot_cb, Y(pot_bot), c_pot_cb, Y(pot_top))
    
    c.setFont("Lib-B", 10.0)
    c.drawString(L + 8.0, Y(576.0), "Potensi Asesi")
    
    pot_idx = int(DATA.get("potensi_asesi_index") or 1)
    pot_rows = [
        (500.9, 534.8, [
            "Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan",
            "fasilitaspraktek mampu telusur terhadap standar kompetensi"
        ], pot_idx == 1),
        (534.8, 563.8, [
            "Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum",
            "berbasiskompetensi."
        ], pot_idx == 2),
        (563.8, 604.6, [
            "Pekerja berpengalaman, dimana berasal dari industri/tempat kerja",
            "yangdalam operasionalnya mampu telusur dengan standar",
            "kompetensi"
        ], pot_idx == 3),
        (604.6, 631.7, [
            "Pekerja berpengalaman, dimana berasal dari industri/tempat kerja",
            "yangdalam operasionalnya belum berbasis kompetensi."
        ], pot_idx == 4),
        (631.7, 645.5, [
            "Pelatihan / belajar mandiri atau otodidak."
        ], pot_idx == 5),
    ]
    for r_idx, (y_t, y_b, lines_txt, checked) in enumerate(pot_rows):
        if r_idx > 0:
            c.line(c_pot_lbl, Y(y_t), R, Y(y_t))
        ymid = (y_t + y_b) / 2
        draw_cb(c, (c_pot_lbl + c_pot_cb) / 2, Y(ymid), checked)
        
        c.setFont("Lib", 9.0)
        line_start_y = y_t + 12.0
        for lt in lines_txt:
            c.drawString(c_pot_cb + 6.0, Y(line_start_y), lt)
            line_start_y += 11.5
            
    # Table 4: Persyaratan Modifikasi (Part 1, 659.5 to 772.1)
    mod_top, mod_bot = 659.5, 772.1
    c_no = 98.1
    c_req = 261.0
    c_ya = 296.7
    c_tdk = 353.5
    c_kcb = 367.6
    
    c.rect(L, Y(mod_bot), R - L, mod_bot - mod_top)
    c.line(L, Y(686.9), R, Y(686.9))
    c.line(L, Y(703.9), R, Y(703.9))
    c.line(c_tdk, Y(744.7), R, Y(744.7))
    
    # Vertical lines
    c.line(c_no, Y(mod_bot), c_no, Y(mod_top))
    c.line(c_req, Y(mod_bot), c_req, Y(mod_top))
    c.line(c_ya, Y(mod_bot), c_ya, Y(686.9))
    c.line(c_tdk, Y(mod_bot), c_tdk, Y(mod_top))
    c.line(c_kcb, Y(mod_bot), c_kcb, Y(703.9))
    
    # Table Header text
    c.setFont("Lib-I", 9.5)
    c.drawCentredString((L + c_no) / 2, Y(683.0), "No")
    
    c.setFont("Lib", 9.0)
    c.drawString(c_no + 4.0, Y(672.0), "Mengidentifikasi Persyaratan")
    c.drawString(c_no + 4.0, Y(683.0), "Modifikasi dan Kontekstualisa")
    c.drawString(c_no + 4.0, Y(694.0), "si (karaktersitik asesi):")
    
    c.drawCentredString((c_req + c_tdk) / 2, Y(673.0), "Diperlukan")
    c.drawCentredString((c_req + c_tdk) / 2, Y(683.0), "penyesuaian)**")
    
    c.drawCentredString((c_req + c_ya) / 2, Y(697.5), "Ya")
    c.drawCentredString((c_ya + c_tdk) / 2, Y(697.5), "Tidak")
    
    c.drawCentredString((c_tdk + R) / 2, Y(685.0), "Keterangan")
    
    # Item 1 row (starts on page 1)
    c.setFont("Lib", 9.0)
    c.drawString(c_no + 5.0, Y(718.0), "Keterbatasan asesi terhadap")
    c.drawString(c_no + 5.0, Y(729.5), "persyaratan bahasa, literasi,")
    c.drawString(c_no + 5.0, Y(741.0), "numerasi.")
    
    # Option 1
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((703.9 + 744.7) / 2), False)
    c.drawString(c_kcb + 5.0, Y(717.0), "Memerlukan dukungan pembaca,")
    c.drawString(c_kcb + 5.0, Y(728.5), "penerjemah, pelayan, penulis. untuk")
    c.drawString(c_kcb + 5.0, Y(740.0), "merekam jawaban asesi.")
    
    # Option 2 (first 2 lines on page 1)
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((744.7 + 772.1) / 2), False)
    c.drawString(c_kcb + 5.0, Y(757.0), "Melakukan asesmen verbal (gunakan")
    c.drawString(c_kcb + 5.0, Y(768.5), "pertanyaan lisan/pertanyaan")
    
    draw_page_num(c, 1)

# ================= PAGE 2 =================
def render_page_2(c):
    draw_header_box(c, 2)
    
    L, R = 62.5, 557.8
    t_top, t_bot = 112.9, 760.5
    c_no = 98.1
    c_req = 261.0
    c_ya = 296.7
    c_tdk = 353.5
    c_kcb = 367.6
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(L, Y(t_bot), R - L, t_bot - t_top)
    
    # Major horizontal dividing lines between Item 1, 2, 3, 4
    c.line(L, Y(238.6), R, Y(238.6))
    c.line(L, Y(355.2), R, Y(355.2))
    c.line(L, Y(540.4), R, Y(540.4))
    
    # Minor horizontal lines in Keterangan column
    lines_ket = [
        140.3, 162.1, 189.4, 216.7,
        279.3, 333.5,
        382.5, 404.3, 426.1, 447.8, 469.6, 491.5, 518.7,
        562.3, 589.6, 643.9, 684.6, 738.8
    ]
    for y_l in lines_ket:
        c.line(c_tdk, Y(y_l), R, Y(y_l))
        
    # Vertical lines
    c.line(c_no, Y(t_bot), c_no, Y(t_top))
    c.line(c_req, Y(t_bot), c_req, Y(t_top))
    c.line(c_ya, Y(t_bot), c_ya, Y(t_top))
    c.line(c_tdk, Y(t_bot), c_tdk, Y(t_top))
    c.line(c_kcb, Y(t_bot), c_kcb, Y(t_top))
    
    # Item 1 Continuation (112.9 to 238.6)
    # Option 2 wrap
    c.setFont("Lib", 9.0)
    c.drawString(c_kcb + 5.0, Y(125.0), "wawancara) dengan dilengkapi gambar")
    c.drawString(c_kcb + 5.0, Y(136.5), "diagram dan bentuk-benbentuk visual.")
    
    # Option 3
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((140.3 + 162.1) / 2), False)
    c.drawString(c_kcb + 5.0, Y(154.0), "Menggunakan Hasil produksi")
    
    # Option 4
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((162.1 + 189.4) / 2), False)
    c.drawString(c_kcb + 5.0, Y(174.0), "Mengunakan Ceklis")
    c.drawString(c_kcb + 5.0, Y(185.5), "observasi/demonstrasi.")
    
    # Option 5
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((189.4 + 216.7) / 2), False)
    c.drawString(c_kcb + 5.0, Y(202.0), "Menggunakan daftar instruksi")
    c.drawString(c_kcb + 5.0, Y(213.5), "terstruktur.")
    
    # Option 6
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((216.7 + 238.6) / 2), False)
    c.drawString(c_kcb + 5.0, Y(230.0), "………………………………….")
    
    # Item 2 (238.6 to 355.2)
    c.drawString(c_no + 5.0, Y(254.0), "Penyediaan dukungan pemba")
    c.drawString(c_no + 5.0, Y(265.5), "ca,penerjemah, pelayan,")
    c.drawString(c_no + 5.0, Y(277.0), "penulis.")
    
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((238.6 + 279.3) / 2), False)
    c.drawString(c_kcb + 5.0, Y(252.0), "Menggunakan pertanyaan lisan")
    c.drawString(c_kcb + 5.0, Y(263.5), "dengan dilengkapi gambar diagram")
    c.drawString(c_kcb + 5.0, Y(275.0), "dan bentuk-bentuk visual.")
    
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((279.3 + 333.5) / 2), False)
    c.drawString(c_kcb + 5.0, Y(295.0), "Menggunakan pertanyaan")
    c.drawString(c_kcb + 5.0, Y(306.5), "wawancara dengan dilengkapi")
    c.drawString(c_kcb + 5.0, Y(318.0), "gambar diagram dan bentuk-bentuk")
    c.drawString(c_kcb + 5.0, Y(329.5), "visual.")
    
    draw_cb(c, (c_tdk + c_kcb) / 2, Y((333.5 + 355.2) / 2), False)
    c.drawString(c_kcb + 5.0, Y(347.0), "……………………………………..")
    
    # Item 3 (355.2 to 540.4)
    c.setFont("Lib-I", 9.5)
    c.drawCentredString((L + c_no) / 2, Y(367.0), "3")
    
    c.setFont("Lib", 9.0)
    c.drawString(c_no + 5.0, Y(370.0), "Penggunaan teknologi adaptif")
    c.drawString(c_no + 5.0, Y(381.5), "atau peralatan khusus. (Tidak")
    c.drawString(c_no + 5.0, Y(393.0), "dapat menggunakan teknologi")
    c.drawString(c_no + 5.0, Y(404.5), "adaptif (misal: mengoperasikan")
    c.drawString(c_no + 5.0, Y(416.0), "komputer dan printer, peralatan")
    c.drawString(c_no + 5.0, Y(427.5), "digital dsb).")
    
    opt_item3 = [
        (355.2, 382.5, ["Ceklis observasi/demonstrasi", "Demonstrasi."]),
        (382.5, 404.3, ["Pertanyaan lisan"]),
        (404.3, 426.1, ["Pertanyaan tertulis."]),
        (426.1, 447.8, ["Pertanyaan wawancara."]),
        (447.8, 469.6, ["Daftar instruksi terstruktur."]),
        (469.6, 491.5, ["Ceklis verifikasi portofolio."]),
        (491.5, 518.7, ["Menggunakan dukungan operator", "komputer."]),
        (518.7, 540.4, ["……………………………………….."]),
    ]
    for y_t, y_b, lines_txt in opt_item3:
        ym = (y_t + y_b) / 2
        draw_cb(c, (c_tdk + c_kcb) / 2, Y(ym), False)
        ys = y_t + 11.0 if len(lines_txt) == 1 else y_t + 9.5
        for lt in lines_txt:
            c.drawString(c_kcb + 5.0, Y(ys), lt)
            ys += 11.5
            
    # Item 4 (540.4 to 760.5)
    c.drawString(c_no + 5.0, Y(554.0), "Pelaksanaan asesmen secara")
    c.drawString(c_no + 5.0, Y(565.5), "fleksibel karena alasan")
    c.drawString(c_no + 5.0, Y(577.0), "keletihan atau keperluan")
    c.drawString(c_no + 5.0, Y(588.5), "pengobatan.")
    
    opt_item4 = [
        (540.4, 562.3, ["Menggunakan juru tulis."]),
        (562.3, 589.6, ["Menggunakan kamaramen perekam", "vidio/ataudio."]),
        (589.6, 643.9, ["Memperbolehkan periode waktu yang", "lebih panjang untuk menyelesaikan", "tugas pekrejaan dalam asesmen."]),
        (643.9, 684.6, ["Melakukan tugas pekerjaan dalam", "asesmen dengan waktu lebih pendek."]),
        (684.6, 738.8, ["Menggunakan instruksi-instruksi", "spesifik pada proyek yang dapat", "dilakukan pada berbagai tingkatan."]),
        (738.8, 760.5, ["………………………………………….."]),
    ]
    for y_t, y_b, lines_txt in opt_item4:
        ym = (y_t + y_b) / 2
        draw_cb(c, (c_tdk + c_kcb) / 2, Y(ym), False)
        ys = y_t + 11.0 if len(lines_txt) == 1 else (y_t + 9.0 if len(lines_txt) == 2 else y_t + 10.0)
        for lt in lines_txt:
            c.drawString(c_kcb + 5.0, Y(ys), lt)
            ys += 11.5
            
    draw_page_num(c, 2)

# ================= PAGE 3 =================
def render_page_3(c):
    draw_header_box(c, 3)
    
    L, R = 62.5, 557.8
    t_top, t_bot = 112.9, 665.3
    c_no = 98.1
    c_req = 261.0
    c_ya = 296.7
    c_tdk = 353.5
    c_kcb = 367.6
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(L, Y(t_bot), R - L, t_bot - t_top)
    
    # Major horizontal lines
    c.line(L, Y(183.7), R, Y(183.7))
    c.line(L, Y(336.2), R, Y(336.2))
    c.line(L, Y(548.0), R, Y(548.0))
    
    # Minor horizontal lines in Keterangan column
    lines_ket = [
        134.7, 162.1,
        205.6, 227.4, 249.1, 270.9, 292.7, 314.5,
        363.5, 404.3, 431.7, 526.1,
        575.5, 602.7, 643.5
    ]
    for y_l in lines_ket:
        c.line(c_tdk, Y(y_l), R, Y(y_l))
        
    # Vertical lines
    c.line(c_no, Y(t_bot), c_no, Y(t_top))
    c.line(c_req, Y(t_bot), c_req, Y(t_top))
    c.line(c_ya, Y(t_bot), c_ya, Y(t_top))
    c.line(c_tdk, Y(t_bot), c_tdk, Y(t_top))
    c.line(c_kcb, Y(t_bot), c_kcb, Y(t_top))
    
    # Item 5 (112.9 to 183.7)
    c.setFont("Lib", 9.0)
    c.drawString(c_no + 5.0, Y(125.0), "Penyediaan peralatan")
    c.drawString(c_no + 5.0, Y(136.5), "asesmen berupa braille,")
    c.drawString(c_no + 5.0, Y(148.0), "audio/video-tape.")
    
    opt_item5 = [
        (112.9, 134.7, ["Menggunakan pertanyaan lisan."]),
        (134.7, 162.1, ["Menggunakan pertanyaan", "wawancara."]),
        (162.1, 183.7, ["…………………………………….."]),
    ]
    for y_t, y_b, lines_txt in opt_item5:
        ym = (y_t + y_b) / 2
        draw_cb(c, (c_tdk + c_kcb) / 2, Y(ym), False)
        ys = y_t + 11.0 if len(lines_txt) == 1 else y_t + 9.5
        for lt in lines_txt:
            c.drawString(c_kcb + 5.0, Y(ys), lt)
            ys += 11.5
            
    # Item 6 (183.7 to 336.2)
    c.drawString(c_no + 5.0, Y(196.0), "Penyesuaian tempat")
    c.drawString(c_no + 5.0, Y(207.5), "fisik/lingkungan asesmen")
    
    opt_item6 = [
        (183.7, 205.6, ["Pertanyaan lisan."]),
        (205.6, 227.4, ["Pertanyaan tulis."]),
        (227.4, 249.1, ["Pertanyaan wawancara."]),
        (249.1, 270.9, ["Ceklis Verifikasi portofolio."]),
        (270.9, 292.7, ["Ceklis reviu produk."]),
        (292.7, 314.5, ["Daftar instruksi terstruktur."]),
        (314.5, 336.2, ["…………………………………….."]),
    ]
    for y_t, y_b, lines_txt in opt_item6:
        ym = (y_t + y_b) / 2
        draw_cb(c, (c_tdk + c_kcb) / 2, Y(ym), False)
        c.drawString(c_kcb + 5.0, Y(y_t + 11.5), lines_txt[0])
        
    # Item 7 (336.2 to 548.0)
    c.drawString(c_no + 5.0, Y(349.0), "Pertimbangan umur/usia")
    c.drawString(c_no + 5.0, Y(360.5), "lanjut/gender asesi.")
    c.drawString(c_no + 5.0, Y(372.0), "(Adanya perbedaan usia dengan")
    c.drawString(c_no + 5.0, Y(383.5), "asesor yang lebih muda).")
    
    opt_item7 = [
        (336.2, 363.5, ["Menggunakan studi kasus/daftar", "instruksi terstrukut"]),
        (363.5, 404.3, ["Menggunakan instrumen asesmen", "dengan huruf normal jangan terlalu", "kecil."]),
        (404.3, 431.7, ["Menggunakan asesor dengan jenis", "kelamin yang sama dengan asesi."]),
        (431.7, 526.1, [
            "Menggunakan instrumen asesmen",
            "yang sama walaupun berbeda jenis",
            "kelamain (tidak boleh memberi",
            "tanda tambahan pada instrumen",
            "asesmen yang digunakan dengan",
            "tujuan untuk membedakan jenis",
            "kelamin)."
        ]),
        (526.1, 548.0, ["…………………………………….."]),
    ]
    for y_t, y_b, lines_txt in opt_item7:
        ym = (y_t + y_b) / 2
        draw_cb(c, (c_tdk + c_kcb) / 2, Y(ym), False)
        ys = y_t + 10.0 if len(lines_txt) > 2 else y_t + 9.5
        for lt in lines_txt:
            c.drawString(c_kcb + 5.0, Y(ys), lt)
            ys += 11.5
            
    # Item 8 (548.0 to 665.3)
    c.setFont("Lib-B", 9.5)
    c.drawCentredString((L + c_no) / 2, Y(559.0), "8")
    
    c.setFont("Lib", 9.0)
    c.drawString(c_no + 5.0, Y(559.0), "Pertimbangan")
    c.drawString(c_no + 5.0, Y(570.5), "budaya/tradisi/agama.")
    
    opt_item8 = [
        (548.0, 575.5, ["Menggunakan studi kasus daftar", "instruksi terstrukut"]),
        (575.5, 602.7, ["Menggunakan asesor tanpa", "pertimbangan budaya/tradisi/agama."]),
        (602.7, 643.5, ["Menggunakan instrumen asesmen yang", "sama walaupun berbeda", "budaya/tradisi/agama)."]),
        (643.5, 665.3, ["…………………………………….."]),
    ]
    for y_t, y_b, lines_txt in opt_item8:
        ym = (y_t + y_b) / 2
        draw_cb(c, (c_tdk + c_kcb) / 2, Y(ym), False)
        ys = y_t + 10.0
        for lt in lines_txt:
            c.drawString(c_kcb + 5.0, Y(ys), lt)
            ys += 11.5
            
    draw_page_num(c, 3)

# ================= PAGE 4 =================
def render_page_4(c):
    draw_header_box(c, 4)
    
    # Agreement Box (112.7 to 238.0)
    box_x0, box_x1 = 63.0, 555.1
    box_top, box_bot = 112.7, 238.0
    
    c.setLineWidth(0.65)
    c.setStrokeColor(black)
    c.rect(box_x0, Y(box_bot), box_x1 - box_x0, box_bot - box_top)
    
    c.setFont("Lib-B", 10.0)
    c.drawString(84.3, Y(125.0), "Hasil kesepakatan antara asesi dengan asesor (Diperlukan penyesuaian atau tidak)*")
    
    # Strikethrough according to penyesuaian_diperlukan
    if not DATA.get("penyesuaian_diperlukan", False):
        w_p = pdfmetrics.stringWidth("Hasil kesepakatan antara asesi dengan asesor (", "Lib-B", 10.0)
        w_strike = pdfmetrics.stringWidth("Diperlukan penyesuaian", "Lib-B", 10.0)
        c.rect(84.3 + w_p, Y(122.0), w_strike, 0.6, fill=1, stroke=0)
    else:
        w_p = pdfmetrics.stringWidth("Hasil kesepakatan antara asesi dengan asesor (Diperlukan penyesuaian ", "Lib-B", 10.0)
        w_strike = pdfmetrics.stringWidth("atau tidak", "Lib-B", 10.0)
        c.rect(84.3 + w_p, Y(122.0), w_strike, 0.6, fill=1, stroke=0)

    c.setFont("Lib", 9.5)
    c.drawString(84.3, Y(139.0), "Jika diperlukan penyesuaian maka, tuliskan kesepakatan dibawah ini:")
    
    c.setFont("Lib", 9.5)
    c.drawString(92.1, Y(167.0), "1) Acuan Pembanding Asesmen: ( tuliskan nama acuan pembanding yang disepakati)")
    c.drawString(92.1, Y(196.0), "2) Metode Asesmen: ( tuliskan nama metode yang disepakati )")
    c.drawString(92.1, Y(224.0), "3) Instrumen Asesmen: (kode formulir instrumen asesmen yang disepakati )")

    # Dynamic agreement text if provided
    acuan = DATA.get("agreement_acuan") or ""
    metode = DATA.get("agreement_metode") or ""
    instrumen = DATA.get("agreement_instrumen") or ""
    notes = DATA.get("agreement_notes") or ""

    c.setFont("Lib-I", 9.0)
    if acuan:
        c.drawString(105.0, Y(180.0), str(acuan)[:75])
    elif notes:
        c.drawString(105.0, Y(180.0), str(notes)[:75])

    if metode:
        c.drawString(105.0, Y(209.0), str(metode)[:75])
    if instrumen:
        c.drawString(105.0, Y(237.0), str(instrumen)[:75])
    
    # 2x2 Signatures Table (255.0 to 353.2)
    t_x0, t_x1 = 66.7, 556.5
    t_top, t_mid, t_bot = 255.0, 304.0, 353.2
    c_mid = 312.0
    
    c.rect(t_x0, Y(t_bot), t_x1 - t_x0, t_bot - t_top)
    c.line(t_x0, Y(t_mid), t_x1, Y(t_mid))
    c.line(c_mid, Y(t_bot), c_mid, Y(t_top))
    
    asesor_nama = DATA.get("asesor_nama") or ""
    asesi_nama = DATA.get("asesi_nama") or ""
    tanggal_ttd = DATA.get("tanggal_ttd") or DATA.get("tanggal") or ""
    sig_asesor = DATA.get("signature_asesor") or DATA.get("assessor_signature")
    sig_asesi = DATA.get("signature_asesi") or DATA.get("asesi_signature")

    # Row 1: Asesor
    c.setFont("Lib", 9.5)
    c.drawString(73.6, Y(269.0), f"Nama Asesor : {asesor_nama}")
    c.drawString(318.4, Y(269.0), "Tanggal dan Tanda Tangan Asesor:")
    c.drawString(318.4, Y(286.0), f"Tanggal: {tanggal_ttd}")
    if sig_asesor:
        draw_signature(c, sig_asesor, 435.0, 300.0, w=85.0, h=28.0)
    else:
        c.drawString(435.0, Y(286.0), "[Ttd Digital]")
    
    # Row 2: Asesi
    c.drawString(73.6, Y(318.0), f"Nama Asesi: {asesi_nama}")
    c.drawString(318.4, Y(318.0), "Tanggal dan Tanda Tangan Asesi:")
    c.drawString(318.4, Y(335.0), f"Tanggal: {tanggal_ttd}")
    if sig_asesi:
        draw_signature(c, sig_asesi, 435.0, 349.0, w=85.0, h=28.0)
    else:
        c.drawString(435.0, Y(335.0), "[Ttd Digital]")
    
    # Footnote
    c.setFont("Lib-I", 8.5)
    c.drawString(63.9, Y(363.0), "Modul Melaksanakan Asesmen hal 32-35")
    
    draw_page_num(c, 4)

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    render_page_1(c)
    c.showPage()
    render_page_2(c)
    c.showPage()
    render_page_3(c)
    c.showPage()
    render_page_4(c)
    c.showPage()
    c.save()
    print(f"Generated {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.dirname(OUT_FILE)
    os.makedirs(out_dir, exist_ok=True)
    out_file = OUT_FILE
    if len(sys.argv) > 1:
        arg1 = sys.argv[1]
        if arg1.endswith(".json") and os.path.isfile(arg1):
            with open(arg1, "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        elif len(sys.argv) > 2 and sys.argv[2].endswith(".json") and os.path.isfile(sys.argv[2]):
            out_file = arg1
            with open(sys.argv[2], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
        else:
            out_file = arg1
    render(out_file)
