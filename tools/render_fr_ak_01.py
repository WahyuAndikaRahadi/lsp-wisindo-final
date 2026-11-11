#!/usr/bin/env python3
"""
Generator FR.AK.01 (Persetujuan Asesmen dan Kerahasiaan) - LSP PrO Indo
Layout meniru template master BNSP aslinya secara presisi piksel 100%; tanpa watermark sesuai instruksi.
Tunggal tabel utuh sesuai master dokumen BNSP dengan data dummy lengkap.
"""

import os
import sys
import io
import base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

try:
    from font_helper import register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import register_reportlab_fonts

register_reportlab_fonts(["Cal", "Cal-B", "Cal-I", "Tah", "Tah-B"])

PAGE_W, PAGE_H = 595.56, 842.52
Y = lambda top: PAGE_H - top

def clean_signature_image(raw_bytes_or_path):
    try:
        from PIL import ImageFilter

        if isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path))
        else:
            im = Image.open(raw_bytes_or_path)
        im = im.convert("RGBA")
        w, h = im.size
        if w == 0 or h == 0:
            return None

        # Sample corner colors to detect background if opaque
        corners = []
        for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)):
            px = im.getpixel(pt)
            if isinstance(px, tuple) and len(px) >= 4 and px[3] > 200:
                corners.append((px[0], px[1], px[2]))

        has_solid_bg = len(corners) >= 3
        bg_r = sum(c[0] for c in corners) / len(corners) if has_solid_bg else 255
        bg_g = sum(c[1] for c in corners) / len(corners) if has_solid_bg else 255
        bg_b = sum(c[2] for c in corners) / len(corners) if has_solid_bg else 255

        out = Image.new("RGBA", (w, h), (0, 0, 0, 0))
        for y in range(h):
            for x in range(w):
                px = im.getpixel((x, y))
                if not isinstance(px, tuple) or len(px) < 4:
                    continue
                r, g, b, a = int(px[0]), int(px[1]), int(px[2]), int(px[3])
                if a < 12:
                    continue

                is_white = (r > 220 and g > 220 and b > 220)
                is_green = (g > 80 and g > r * 1.15 and g > b * 1.15)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5 if has_solid_bg else 999
                is_bg = (has_solid_bg and dist_bg < 45)

                if is_white or is_green or is_bg:
                    continue

                # Ink enhancement: boost opacity to solid deep black/slate
                boosted_a = min(255, int(a * 2.8))
                if boosted_a > 20:
                    out.putpixel((x, y), (10, 15, 30, min(255, int(boosted_a * 1.25))))

        # Auto-crop to bounding box of actual non-zero strokes
        bbox = out.getbbox()
        if bbox:
            pad = 4
            crop_box = (
                max(0, bbox[0] - pad),
                max(0, bbox[1] - pad),
                min(w, bbox[2] + pad),
                min(h, bbox[3] + pad)
            )
            out = out.crop(crop_box)
        else:
            return None

        # Dilate alpha channel (3x3 MaxFilter) so thin canvas lines don't fade away when scaled
        r_ch, g_ch, b_ch, a_ch = out.split()
        a_dilated = a_ch.filter(ImageFilter.MaxFilter(3))
        out_thick = Image.merge("RGBA", (r_ch, g_ch, b_ch, a_dilated))

        out_io = io.BytesIO()
        out_thick.save(out_io, format="PNG")
        out_io.seek(0)
        return out_io
    except Exception:
        return None

def draw_signature(c, sig_data, x_center=192.85, y_center=701.0, max_w=140.0, max_h=38.0):
    if not sig_data:
        return
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            with open(sig_data, "rb") as f:
                raw = f.read()
        else:
            return

        cleaned_io = clean_signature_image(raw)
        if not cleaned_io:
            return

        im = Image.open(cleaned_io)
        im_w, im_h = im.size
        if im_w == 0 or im_h == 0:
            return

        aspect = float(im_w) / float(im_h)
        if aspect > (max_w / max_h):
            w = max_w
            h = max_w / aspect
        else:
            h = max_h
            w = max_h * aspect

        x = x_center - (w / 2.0)
        # Vertically centered in the signature area
        y_bot_native = Y(y_center) - (h / 2.0)
        cleaned_io.seek(0)
        img = ImageReader(cleaned_io)
        c.drawImage(img, x, y_bot_native, w, h, mask="auto")
    except Exception:
        pass

def draw_fitted_line(c, x, y, text_str, font="Cal", max_sz=10.5, max_w=474.0):
    sz = max_sz
    w = pdfmetrics.stringWidth(text_str, font, sz)
    if w > max_w:
        sz = sz * (max_w / w)
    c.setFont(font, sz)
    c.drawString(x, y, text_str)
    return sz

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
    c.rect(cx - sz / 2, y_center - sz / 2, sz, sz, fill=0, stroke=1)
    if checked:
        c.setLineWidth(1.3)
        c.setStrokeColor(black)
        p = c.beginPath()
        p.moveTo(cx - 2.8, y_center - 0.2)
        p.lineTo(cx - 0.7, y_center - 2.8)
        p.lineTo(cx + 3.2, y_center + 3.2)
        c.drawPath(p, stroke=1, fill=0)

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
    draw_fitted_line(c, 76.7, Y(149.0), "Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan", "Cal", 10.5, 474.0)
    c.setFont("Cal", 10.0)
    c.drawString(76.7, Y(164.0), "dan proses asesmen")
    
    # Row 1 & 2: Skema
    c.setFont("Cal", 11.0)
    c.drawString(78.4, Y(198.0), "Skema Sertifikasi")
    c.drawString(78.4, Y(212.0), "(KKNI/Okupasi/Klaster)")
    
    c.drawString(199.9, Y(194.0), "Judul")
    c.drawString(244.0, Y(194.0), ":")
    draw_fitted_line(c, 261.0, Y(194.0), DATA["skema_nama"], "Cal-I", 11.0, 290.0)
    
    c.setFont("Cal", 11.0)
    c.drawString(199.9, Y(214.0), "Nomor")
    c.drawString(244.0, Y(214.0), ":")
    draw_fitted_line(c, 261.0, Y(214.0), DATA["skema_nomor"], "Cal", 11.0, 290.0)
    
    # Row 3: TUK
    c.setFont("Cal", 11.0)
    c.drawString(77.8, Y(235.0), "TUK")
    c.drawString(244.0, Y(235.0), ":")
    draw_fitted_line(c, 261.0, Y(235.0), DATA["tuk_tipe"], "Cal", 11.0, 290.0)
    
    # Row 4: Nama Asesor
    c.setFont("Cal", 11.0)
    c.drawString(77.8, Y(257.0), "Nama Asesor")
    c.drawString(244.0, Y(257.0), ":")
    draw_fitted_line(c, 261.0, Y(257.0), DATA["asesor_nama"], "Cal-B", 11.0, 290.0)
    
    # Row 5: Nama Asesi
    c.setFont("Cal", 11.0)
    c.drawString(77.8, Y(280.0), "Nama Asesi")
    c.drawString(244.0, Y(280.0), ":")
    draw_fitted_line(c, 261.0, Y(280.0), DATA["asesi_nama"], "Cal-B", 11.0, 290.0)
    
    # Row 6-9: Bukti-bukti yang dikumpulkan
    c.setFont("Cal", 11.0)
    c.drawString(76.7, Y(330.0), "Bukti yang akan dikumpulkan :")
    
    c.drawString(244.0, Y(306.0), ":")
    c.drawString(244.0, Y(336.0), ":")
    c.drawString(244.0, Y(396.0), ":")
    
    # Row 6
    draw_cb(c, 266.6, 304.0, DATA["bukti_portofolio"])
    c.setFont("Cal", 10.0)
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
    draw_fitted_line(c, 338.0, Y(444.0), DATA["hari_tanggal"], "Cal-B", 11.0, 215.0)
    
    c.setFont("Cal", 11.0)
    c.drawString(259.2, Y(466.0), "Waktu")
    c.drawString(325.0, Y(466.0), ":")
    draw_fitted_line(c, 338.0, Y(466.0), DATA["waktu"], "Cal-B", 11.0, 215.0)
    
    c.setFont("Cal", 11.0)
    c.drawString(259.2, Y(489.0), "TUK")
    c.drawString(325.0, Y(489.0), ":")
    draw_fitted_line(c, 338.0, Y(489.0), DATA["tuk_nama"], "Cal-B", 11.0, 215.0)
    
    # Row 13: Pernyataan Asesi 1
    c.setFont("Cal-B", 10.5)
    c.drawString(76.7, Y(512.0), "Asesi :")
    draw_fitted_line(c, 76.7, Y(528.0), "Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.", "Cal", 10.5, 474.0)
    
    # Row 14: Pernyataan Asesor
    c.setFont("Cal-B", 10.5)
    c.drawString(76.7, Y(550.0), "Asesor :")
    draw_fitted_line(c, 76.7, Y(568.0), "Menyatakan tidak akan membuka hasil pekerjaan yang saya peroleh karena penugasan saya sebagai Asesor", "Cal", 10.5, 474.0)
    sz_r14_2 = 9.8
    c.setFont("Cal", sz_r14_2)
    c.drawString(76.7, Y(582.0), "dalam pekerjaan ")
    w_dp = c.stringWidth("dalam pekerjaan ", "Cal", sz_r14_2)
    c.setFont("Cal-I", sz_r14_2)
    c.drawString(76.7 + w_dp, Y(582.0), "Asesmen ")
    w_as = c.stringWidth("Asesmen ", "Cal-I", sz_r14_2)
    c.setFont("Cal", sz_r14_2)
    c.drawString(76.7 + w_dp + w_as, Y(582.0), "kepada siapapun atau organisasi apapun selain kepada pihak yang berwenang")
    c.drawString(76.7, Y(596.0), "sehubungan dengan kewajiban saya sebagai Asesor yang ditugaskan oleh LSP.")
    
    # Row 15: Pernyataan Asesi 2
    c.setFont("Cal-B", 10.5)
    c.drawString(76.7, Y(624.0), "Asesi :")
    draw_fitted_line(c, 76.7, Y(642.0), "Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan", "Cal", 10.5, 474.0)
    c.setFont("Cal", 10.0)
    c.drawString(76.7, Y(656.0), "untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.")
    
    # Row 16: Tanda Tangan Section (2 symmetrical columns, date placed underneath signature)
    tgl_asesor = DATA.get("tanggal_ttd_asesor") or DATA.get("tanggal_ttd", "")
    tgl_asesi = DATA.get("tanggal_ttd_asesi") or DATA.get("tanggal_ttd", "")

    L = 70.4
    R = 560.2
    c_mid = 315.3

    # Vertical separator line between Asesor and Asesi
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.line(c_mid, Y(665.1), c_mid, Y(757.8))

    # Left Column: Asesor
    x_left_ctr = (L + c_mid) / 2 # 192.85
    c.setFont("Cal", 10.5)
    c.drawString(L + 8.0, Y(676.0), "Tanda tangan Asesor :")
    draw_signature(c, DATA.get("signature_asesor") or DATA.get("assessor_signature"), x_center=x_left_ctr, y_center=701.0, max_w=140.0, max_h=38.0)
    
    asesor_str = f"( {DATA.get('asesor_nama', '')} )"
    sz_asesor = 10.0
    w_asesor = pdfmetrics.stringWidth(asesor_str, "Cal-B", sz_asesor)
    if w_asesor > 220.0:
        sz_asesor = sz_asesor * (220.0 / w_asesor)
    c.setFont("Cal-B", sz_asesor)
    c.drawCentredString(x_left_ctr, Y(727.0), asesor_str)
    c.setFont("Cal", 9.0)
    c.drawCentredString(x_left_ctr, Y(743.0), f"Tanggal : {tgl_asesor}")

    # Right Column: Asesi
    x_right_ctr = (c_mid + R) / 2 # 437.75
    c.setFont("Cal", 10.5)
    c.drawString(c_mid + 8.0, Y(676.0), "Tanda tangan Asesi :")
    draw_signature(c, DATA.get("signature_asesi") or DATA.get("asesi_signature"), x_center=x_right_ctr, y_center=701.0, max_w=140.0, max_h=38.0)
    
    asesi_str = f"( {DATA.get('asesi_nama', '')} )"
    sz_asesi = 10.0
    w_asesi = pdfmetrics.stringWidth(asesi_str, "Cal-B", sz_asesi)
    if w_asesi > 220.0:
        sz_asesi = sz_asesi * (220.0 / w_asesi)
    c.setFont("Cal-B", sz_asesi)
    c.drawCentredString(x_right_ctr, Y(727.0), asesi_str)
    c.setFont("Cal", 9.0)
    c.drawCentredString(x_right_ctr, Y(743.0), f"Tanggal : {tgl_asesi}")

    # Below Table: Note
    c.setFont("Cal-I", 11.0)
    c.drawString(70.9, Y(770.0), "* Coret yang tidak perlu")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.01: {dest_path}")

if __name__ == "__main__":
    import json
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_01_generated.pdf")
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
