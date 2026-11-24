#!/usr/bin/env python3
"""
Generator FR.AK.04 (Banding Asesmen) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
"""

import os
import sys
import json
import io
import base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

# Setup fonts
try:
    from font_helper import draw_checkmark, register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import draw_checkmark, register_reportlab_fonts

register_reportlab_fonts(["Cal", "Cal-B", "Cal-I", "DJ"])

PAGE_W, PAGE_H = 595.5, 842.5

DATA = {
    "is_filled": False,
    "asesi_nama": "",
    "asesor_nama": "",
    "tanggal_asesmen": "",
    "pertanyaan_1_ya": None,
    "pertanyaan_2_ya": None,
    "pertanyaan_3_ya": None,
    "skema_nama": "",
    "skema_nomor": "",
    "alasan_banding": "",
    "signature_asesi": None,
    "tanggal_tanda_tangan": "",
}

def clean_signature_image(raw_bytes):
    """Clean signature image, making light background transparent and boosting ink contrast."""
    if not raw_bytes:
        return None
    try:
        im = Image.open(io.BytesIO(raw_bytes))
        im = im.convert("RGBA")
        w, h = im.size
        if w == 0 or h == 0:
            return None

        corners = [im.getpixel((x, y)) for x, y in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1))]
        corners_transparent = all(c[3] < 30 for c in corners)

        out = Image.new("RGBA", (w, h), (0, 0, 0, 0))
        if corners_transparent:
            for y in range(h):
                for x in range(w):
                    r, g, b, a = im.getpixel((x, y))
                    if a > 20:
                        out.putpixel((x, y), (10, 15, 30, min(255, int(a * 1.5))))
        else:
            bg_r = sum(c[0] for c in corners) / 4.0
            bg_g = sum(c[1] for c in corners) / 4.0
            bg_b = sum(c[2] for c in corners) / 4.0
            for y in range(h):
                for x in range(w):
                    r, g, b, a = im.getpixel((x, y))
                    dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5
                    if dist_bg > 40 and not (r > 215 and g > 215 and b > 215):
                        out.putpixel((x, y), (10, 15, 30, min(255, int(a * 1.5))))

        bbox = out.getbbox()
        if bbox:
            pad = 4
            out = out.crop((max(0, bbox[0] - pad), max(0, bbox[1] - pad), min(w, bbox[2] + pad), min(h, bbox[3] + pad)))
        else:
            return None

        out_io = io.BytesIO()
        out.save(out_io, format="PNG")
        out_io.seek(0)
        return out_io
    except Exception:
        return None

def draw_signature(c, sig_data, x_center=235.9, y_center=318.0, max_w=110.0, max_h=34.0):
    """Draw cleaned transparent signature image centered at specified position."""
    if not sig_data:
        return
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            with open(sig_data, "rb") as f:
                raw = f.read()
        elif isinstance(sig_data, (bytes, bytearray)):
            raw = sig_data
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
        y = y_center - (h / 2.0)
        cleaned_io.seek(0)
        img = ImageReader(cleaned_io)
        c.drawImage(img, x, y, w, h, mask="auto")
    except Exception:
        pass

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    is_filled = bool(DATA.get("is_filled", False))
    
    # Title
    c.setFont("Cal-B", 12)
    c.drawString(69.6, PAGE_H - 72.8, "FR.AK.04. BANDING ASESMEN")
    
    # Outer Table Coordinates
    x0 = 71.8
    x1 = 560.4
    y_top = PAGE_H - 98.6
    
    # Row Heights
    # R0: 21.6, R1: 21.6, R2: 21.7, R3: 28.3, R4: 35.0, R5: 35.0, R6: 35.2, R7: 74.2, R8: 82.5, R9: 49.3, R10: 79.1
    row_heights = [21.6, 21.6, 21.7, 28.3, 35.0, 35.0, 35.2, 74.2, 82.5, 49.3, 79.1]
    
    y_current = y_top
    y_lines = [y_current]
    for h in row_heights:
        y_current -= h
        y_lines.append(y_current)
        
    c.setLineWidth(0.8)
    c.setStrokeColor(black)
    
    # Draw horizontal table lines
    for y in y_lines:
        c.line(x0, y, x1, y)
        
    # Draw outer vertical lines
    c.line(x0, y_top, x0, y_lines[-1])
    c.line(x1, y_top, x1, y_lines[-1])
    
    # Inner vertical lines for question columns (Rows 3, 4, 5, 6)
    x_col1 = 452.6
    x_col2 = 502.1
    c.line(x_col1, y_lines[3], x_col1, y_lines[7])
    c.line(x_col2, y_lines[3], x_col2, y_lines[7])
    
    # Content: R0 (Nama Asesi)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[0] - 14.5, "Nama Asesi:")
    c.setFont("Cal", 11)
    c.drawString(x0 + 95.0, y_lines[0] - 14.5, DATA["asesi_nama"])
    
    # Content: R1 (Nama Asesor)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[1] - 14.5, "Nama Asesor:")
    c.setFont("Cal", 11)
    c.drawString(x0 + 95.0, y_lines[1] - 14.5, DATA["asesor_nama"])
    
    # Content: R2 (Tanggal Asesmen)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[2] - 14.5, "Tanggal Asesmen:")
    c.setFont("Cal", 11)
    c.drawString(x0 + 115.0, y_lines[2] - 14.5, DATA["tanggal_asesmen"])
    
    # Content: R3 (Header Pertanyaan)
    c.setFont("Cal-B", 11)
    c.drawString(x0 + 7.7, y_lines[3] - 17.5, "Jawablah dengan Ya atau Tidak pertanyaan-pertanyaan berikut ini :")
    c.drawCentredString((x_col1 + x_col2) / 2, y_lines[3] - 17.5, "YA")
    c.drawCentredString((x_col2 + x1) / 2, y_lines[3] - 17.5, "TIDAK")
    
    # Helper to draw checkbox
    def draw_box(x_center, y_center, checked):
        size = 10
        bx = x_center - size / 2
        by = y_center - size / 2
        c.setLineWidth(0.8)
        c.rect(bx, by, size, size)
        if checked:
            c.saveState()
            c.setStrokeColor(black)
            c.setLineWidth(1.4)
            c.setLineCap(1)
            c.setLineJoin(1)
            p = c.beginPath()
            p.moveTo(x_center - size * 0.32, y_center + size * 0.05)
            p.lineTo(x_center - size * 0.08, y_center - size * 0.25)
            p.lineTo(x_center + size * 0.35, y_center + size * 0.32)
            c.drawPath(p, stroke=1, fill=0)
            c.restoreState()
            
    # Content: R4 (Pertanyaan 1)
    c.setFont("Cal", 11)
    c.drawString(x0 + 7.7, y_lines[4] - 20.0, "Apakah Proses Banding telah dijelaskan kepada Anda?")
    p1 = DATA.get("pertanyaan_1_ya")
    draw_box((x_col1 + x_col2) / 2, y_lines[4] - 17.5, is_filled and p1 is True)
    draw_box((x_col2 + x1) / 2, y_lines[4] - 17.5, is_filled and p1 is False)
    
    # Content: R5 (Pertanyaan 2)
    c.drawString(x0 + 7.7, y_lines[5] - 20.0, "Apakah Anda telah mendiskusikan Banding dengan Asesor?")
    p2 = DATA.get("pertanyaan_2_ya")
    draw_box((x_col1 + x_col2) / 2, y_lines[5] - 17.5, is_filled and p2 is True)
    draw_box((x_col2 + x1) / 2, y_lines[5] - 17.5, is_filled and p2 is False)
    
    # Content: R6 (Pertanyaan 3)
    c.drawString(x0 + 7.7, y_lines[6] - 14.5, "Apakah Anda mau melibatkan “orang lain” membantu Anda dalam Proses")
    c.drawString(x0 + 7.7, y_lines[6] - 27.5, "Banding?")
    p3 = DATA.get("pertanyaan_3_ya")
    draw_box((x_col1 + x_col2) / 2, y_lines[6] - 17.5, is_filled and p3 is True)
    draw_box((x_col2 + x1) / 2, y_lines[6] - 17.5, is_filled and p3 is False)
    
    # Content: R7 (Keputusan Asesmen)
    c.drawString(x0 + 7.7, y_lines[7] - 15.0, "Banding ini diajukan atas Keputusan Asesmen yang dibuat terhadap Skema Sertifikasi")
    c.drawString(x0 + 7.7, y_lines[7] - 28.5, "(Kualifikasi/Klaster/Okupasi) berikut :")
    c.drawString(x0 + 7.7, y_lines[7] - 45.0, f"Skema Sertifikasi : {DATA.get('skema_nama', '')}")
    c.drawString(x0 + 7.7, y_lines[7] - 61.5, f"No. Skema Sertifikasi : {DATA.get('skema_nomor', '')}")
    
    # Content: R8 (Alasan Banding)
    c.drawString(x0 + 7.7, y_lines[8] - 16.0, "Banding ini diajukan atas alasan sebagai berikut :")
    alasan = str(DATA.get("alasan_banding", "") or "").strip()
    if is_filled and alasan:
        c.setFont("Cal-I", 11)
        c.drawString(x0 + 7.7, y_lines[8] - 34.0, alasan[:95])
        if len(alasan) > 95:
            c.drawString(x0 + 7.7, y_lines[8] - 48.0, alasan[95:190])
        
    # Content: R9 (Pemberitahuan Hak)
    c.setFont("Cal", 11)
    c.drawString(x0 + 7.7, y_lines[9] - 16.0, "Anda mempunyai hak mengajukan banding jika Anda menilai Proses Asesmen tidak sesuai SOP dan tidak")
    c.drawString(x0 + 7.7, y_lines[9] - 29.5, "memenuhi Prinsip Asesmen.")
    
    # Content: R10 (Tanda Tangan)
    c.setFont("Cal", 11)
    c.drawString(x0 + 7.7, y_lines[10] - 42.0, "Tanda tangan Asesi :")
    c.drawString(x0 + 310.0, y_lines[10] - 42.0, "Tanggal :")
    if is_filled:
        sig_asesi = DATA.get("signature_asesi") or DATA.get("asesi_signature")
        if sig_asesi:
            draw_signature(c, sig_asesi, x_center=x0 + 205.0, y_center=y_lines[10] - 32.0, max_w=120.0, max_h=34.0)

        # Name
        if DATA.get("asesi_nama"):
            c.setFont("Cal-B", 10)
            if sig_asesi:
                c.drawCentredString(x0 + 205.0, y_lines[10] - 58.0, f"({DATA['asesi_nama']})")
            else:
                c.drawCentredString(x0 + 205.0, y_lines[10] - 42.0, DATA["asesi_nama"])

        # Date
        if DATA.get("tanggal_tanda_tangan"):
            c.setFont("Cal", 11)
            c.drawString(x0 + 360.0, y_lines[10] - 42.0, DATA["tanggal_tanda_tangan"])
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.04: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_04_generated.pdf")

    if len(sys.argv) > 1 and sys.argv[1] != "-":
        payload_arg = sys.argv[1]
        if os.path.isfile(payload_arg):
            with open(payload_arg, "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
        else:
            try:
                DATA.update(json.loads(payload_arg))
            except Exception:
                pass
    elif len(sys.argv) > 1 and sys.argv[1] == "-":
        DATA.update(json.load(sys.stdin))

    if len(sys.argv) > 2:
        out_file = sys.argv[2]

    render(out_file)
