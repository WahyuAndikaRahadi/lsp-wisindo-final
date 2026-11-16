#!/usr/bin/env python3
"""
Generator Surat Pernyataan Pemegang Sertifikat Kompetensi - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
"""

import os
import sys
import io
import base64
from PIL import Image, ImageFilter
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

register_reportlab_fonts(["Lib", "Lib-B"])

PAGE_W, PAGE_H = 595.3, 841.9

DATA = {
    "hari": "Senin",
    "tanggal": "19",
    "bulan": "Oktober",
    "tahun": "2026",
    "tempat": "TUK Otomotif Mandiri",
    "nama": "Wahyu Andika Rahadi",
    "nik": "3201234567890001",
    "skema": "Maintenance Gasoline Engine Injection System",
    "no_blanko": "",
    "no_sertifikat": "",
    "kota_ttd": "Karawang",
    "tanggal_ttd": "19 Oktober 2026",
    "signature_asesi": None,
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
                if is_white or is_green or (has_solid_bg and dist_bg < 45):
                    continue
                boosted_a = min(255, int(a * 2.8))
                if boosted_a > 20:
                    out.putpixel((x, y), (10, 15, 30, min(255, int(boosted_a * 1.25))))

        bbox = out.getbbox()
        if not bbox:
            return None
        pad = 4
        crop_box = (
            max(0, bbox[0] - pad),
            max(0, bbox[1] - pad),
            min(w, bbox[2] + pad),
            min(h, bbox[3] + pad)
        )
        out = out.crop(crop_box)
        r_ch, g_ch, b_ch, a_ch = out.split()
        a_dilated = a_ch.filter(ImageFilter.MaxFilter(3))
        out_thick = Image.merge("RGBA", (r_ch, g_ch, b_ch, a_dilated))

        out_io = io.BytesIO()
        out_thick.save(out_io, format="PNG")
        return out_io.getvalue()
    except Exception:
        return None

def draw_signature(c, sig_data, x_center, y_center, max_w=130.0, max_h=55.0):
    if not sig_data:
        return False
    try:
        if isinstance(sig_data, str):
            sig_data = sig_data.replace(r"\/", "/")
            if sig_data.startswith("data:image"):
                sig_data = sig_data.split(",", 1)[1]
                raw = base64.b64decode(sig_data)
            elif os.path.isfile(sig_data):
                with open(sig_data, "rb") as f:
                    raw = f.read()
            elif len(sig_data) > 50:
                try:
                    raw = base64.b64decode(sig_data)
                except Exception:
                    return False
            else:
                return False
        elif isinstance(sig_data, (bytes, bytearray)):
            raw = sig_data
        else:
            return False

        cleaned = clean_signature_image(raw)
        if not cleaned:
            cleaned = raw

        img = ImageReader(io.BytesIO(cleaned))
        iw, ih = img.getSize()
        if iw <= 0 or ih <= 0:
            return False
        scale = min(max_w / iw, max_h / ih)
        w = iw * scale
        h = ih * scale
        x_left = x_center - w / 2.0
        y_bottom = y_center - h / 2.0
        c.drawImage(img, x_left, y_bottom, w, h, mask="auto")
        return True
    except Exception:
        return False

def wrap_text(c, text, font, size, max_width):
    if not text:
        return []
    words = str(text).split()
    lines = []
    current = ""
    for w in words:
        test = (current + " " + w).strip()
        if c.stringWidth(test, font, size) <= max_width:
            current = test
        else:
            if current:
                lines.append(current)
            current = w
    if current:
        lines.append(current)
    return lines

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # Extract values with fallbacks
    hari = DATA.get("hari") or "Senin"
    tanggal = DATA.get("tanggal") or "19"
    bulan = DATA.get("bulan") or "Oktober"
    tahun = DATA.get("tahun") or "2026"
    tempat = DATA.get("tempat") or DATA.get("tuk_nama") or "TUK Otomotif Mandiri"
    nama = DATA.get("nama") or DATA.get("asesi_nama") or ""
    nik = DATA.get("nik") or DATA.get("asesi_nik") or "-"
    skema = DATA.get("skema") or DATA.get("skema_nama") or ""
    no_blanko = DATA.get("no_blanko") or ""
    no_sertifikat = DATA.get("no_sertifikat") or ""
    kota_ttd = DATA.get("kota_ttd") or DATA.get("tuk_kota") or "Karawang"
    tanggal_ttd = DATA.get("tanggal_ttd") or DATA.get("tanggal") or f"{tanggal} {bulan} {tahun}"
    sig_asesi = DATA.get("signature_asesi") or DATA.get("asesi_signature")

    # 1. Letterhead / Kop Surat (Centered)
    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        kop_w = 540.0
        kop_h = 96.5
        kop_x = (PAGE_W - kop_w) / 2.0
        c.drawImage(kop_file, kop_x, PAGE_H - 110.0, kop_w, kop_h, mask="auto")
        
    # 2. Document Title
    c.setFont("Lib-B", 11)
    c.drawCentredString(PAGE_W / 2, PAGE_H - 123.0, "SURAT PERNYATAAN PEMEGANG SERTIFIKAT KOMPETENSI")
    c.drawCentredString(PAGE_W / 2, PAGE_H - 146.0, "LEMBAGA SERTIFIKASI PROFESI PIHAK KETIGA")
    c.drawCentredString(PAGE_W / 2, PAGE_H - 167.0, "PRAKTISI OTOMOTIF INDONESIA")
    c.drawCentredString(PAGE_W / 2, PAGE_H - 190.0, "(LSP P3 PRO INDO)")
    
    # 3. Date & Location Lead (Wrap text if TUK name / text is long)
    c.setFont("Lib", 11)
    content_w = PAGE_W - 144.0  # 72.0 left and right margins
    tempat_clean = tempat.rstrip(".")
    intro_txt = f"Pada hari ini {hari} Tanggal {tanggal} Bulan {bulan} Tahun {tahun} bertempat di {tempat_clean}."
    intro_lines = wrap_text(c, intro_txt, "Lib", 11, content_w)
    
    y = PAGE_H - 235.0
    for ln in intro_lines:
        c.drawString(72.0, y, ln)
        y -= 15.0
    
    y -= 8.0
    
    # 4. Identity
    c.drawString(72.0, y, "Saya bertanda tangan dibawah ini :")
    y -= 24.0
    
    c.drawString(72.0, y, "Nama")
    c.drawString(160.0, y, f": {nama}")
    y -= 20.0
    
    c.drawString(72.0, y, "NIK")
    c.drawString(160.0, y, f": {nik}")
    y -= 24.0
    
    c.drawString(72.0, y, "Skema")
    skema_lines = wrap_text(c, f": {skema}", "Lib", 11, PAGE_W - 160.0 - 72.0)
    if not skema_lines:
        skema_lines = [": -"]
    for idx, sln in enumerate(skema_lines):
        c.drawString(160.0, y, sln)
        if idx < len(skema_lines) - 1:
            y -= 15.0
    y -= 24.0
    
    # No.Blanko (leave blank if empty)
    c.drawString(72.0, y, "No.Blanko")
    val_blanko = f": {no_blanko}".rstrip() if not no_blanko else f": {no_blanko}"
    c.drawString(160.0, y, val_blanko)
    y -= 20.0
    
    # No. Sertifikat (leave blank if empty)
    c.drawString(72.0, y, "No. Sertifikat")
    val_cert = f": {no_sertifikat}".rstrip() if not no_sertifikat else f": {no_sertifikat}"
    c.drawString(160.0, y, val_cert)
    y -= 48.0
    
    # 5. Statements
    c.drawString(72.0, y, "Dengan ini mengatakan bahwa saya akan:")
    y -= 20.0
    
    points = [
        "1. Mematuhi ketentuan yang relevan dalam skema sertifikasi.",
        "2. Menggunakan sertifikat hanya untuk ruang lingkup sertifikasi yang diberikan.",
        "3. Tidak menggunakan sertifikat yang dapat mencemarkan / merugikan Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) dan tidak memberikan pernyataan terkait sertifikasi yang oleh Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) dianggap dapat menyesatkan atau tidak dapat dipertanggungjawabkan.",
        "4. Menghentikan penggunaan atau pengakuan sertifikat setelah sertifikat dibekukan atau dicabut oleh Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) dan mengembalikan sertifikat kepada Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo)."
    ]
    
    for p in points:
        lines = wrap_text(c, p, "Lib", 11, content_w)
        for idx, ln in enumerate(lines):
            c.drawString(72.0 if idx == 0 else 88.0, y, ln)
            y -= 15.0
        y -= 7.0
        
    y -= 5.0
    c.drawString(72.0, y, "Demikian pernyataan ini saya buat dengan sebenar-benarnya dan penuh tanggung jawab.")
    
    # 6. Signature Block
    y_sig = y - 44.0
    sig_loc = f"{kota_ttd}, {tanggal_ttd}"
    c.drawRightString(PAGE_W - 72.0, y_sig, sig_loc)
    
    y_name = y_sig - 75.0
    name_str = f"( {nama} )"
    c.drawRightString(PAGE_W - 72.0, y_name, name_str)
    
    # Assessee signature placement
    if sig_asesi:
        sig_loc_w = c.stringWidth(sig_loc, "Lib", 11)
        name_w = c.stringWidth(name_str, "Lib", 11)
        center_x = (PAGE_W - 72.0) - max(sig_loc_w, name_w) / 2.0
        center_y = (y_sig + y_name) / 2.0
        draw_signature(c, sig_asesi, center_x, center_y, max_w=130.0, max_h=55.0)
    
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

