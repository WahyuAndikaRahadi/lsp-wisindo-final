#!/usr/bin/env python3
"""
Generator FR.AK.06 (Meninjau Proses Asesmen) - LSP PrO Indo
Layout meniru template master BNSP secara presisi piksel; 100% tanpa watermark.
Mendukung input dinamis JSON untuk data jadwal, skema, TUK, asesor, serta
tipe peninjauan ('langsung' vs 'tidak_langsung') dan catatan komentar peninjau.
"""

import os
import sys
import io
import json
import base64
from PIL import Image

from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor, white
from reportlab.pdfbase import pdfmetrics
from reportlab.lib.utils import ImageReader

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
ASSETS_DIR = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets")
LOGO_PATH = os.path.join(ASSETS_DIR, "logo.png")
if not os.path.isfile(LOGO_PATH):
    LOGO_PATH = os.path.join(PROJECT_ROOT, "public", "images", "logo-lsp-proindo.png")

# ----------------- FONT REGISTRATION -----------------
try:
    from font_helper import draw_checkmark, register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import draw_checkmark, register_reportlab_fonts

register_reportlab_fonts(["Lib", "Lib-B", "Lib-I", "Cal", "Cal-B", "Cal-I", "DJ"])

PAGE_W, PAGE_H = 595.56, 842.52
Y = lambda top: PAGE_H - top


def clean_signature_image(raw_bytes_or_path):
    """Load signature and ensure strokes are solid, bold, and crisp on transparent background."""
    if not raw_bytes_or_path:
        return None
    try:
        from PIL import ImageFilter
        if isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path))
        elif isinstance(raw_bytes_or_path, str) and raw_bytes_or_path.startswith("data:image"):
            b64_str = raw_bytes_or_path.split(",", 1)[1]
            raw_bytes = base64.b64decode(b64_str)
            im = Image.open(io.BytesIO(raw_bytes))
        elif isinstance(raw_bytes_or_path, str) and os.path.isfile(raw_bytes_or_path):
            im = Image.open(raw_bytes_or_path)
        else:
            return None

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
                if a < 15:
                    continue
                is_white = (r > 215 and g > 215 and b > 215)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5 if has_solid_bg else 999
                if is_white or (has_solid_bg and dist_bg < 45):
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


def draw_signature(c, sig_data, x_left, y_bottom, max_w=120.0, max_h=45.0):
    """Render digital signature cleanly preserving aspect ratio."""
    if not sig_data:
        return False
    try:
        cleaned = clean_signature_image(sig_data)
        if not cleaned:
            return False
        img = ImageReader(io.BytesIO(cleaned))
        iw, ih = img.getSize()
        if iw == 0 or ih == 0:
            return False
        scale = min(max_w / iw, max_h / ih)
        w = iw * scale
        h = ih * scale
        c.drawImage(img, x_left, y_bottom, w, h, mask="auto")
        return True
    except Exception:
        return False


def wrap_text(c, text, font, size, max_width):
    """Break text into lines that fit within max_width."""
    words = str(text or "").split()
    if not words:
        return []
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


def draw_cb(c, cx, cy, checked, sz=9.0):
    """Draw checkbox with crisp vector checkmark if checked."""
    c.saveState()
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.setFillColor(white)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz, fill=1, stroke=1)
    if checked:
        c.setLineWidth(1.2)
        c.setStrokeColor(black)
        p = c.beginPath()
        p.moveTo(cx - sz * 0.30, cy + sz * 0.02)
        p.lineTo(cx - sz * 0.08, cy - sz * 0.28)
        p.lineTo(cx + sz * 0.32, cy + sz * 0.32)
        c.drawPath(p, stroke=1, fill=0)
    c.restoreState()


def draw_kkni_options(c, x, y, font_name="Lib", font_size=8.0, scheme_type="KLASTER"):
    """Draw (KKNI/Okupasi/Klaster) with precise strikethrough according to scheme type."""
    st = str(scheme_type or "KLASTER").strip().upper()
    c.saveState()
    c.setFont(font_name, font_size)
    c.setFillColor(black)

    w_open = pdfmetrics.stringWidth("(", font_name, font_size)
    w_kkni = pdfmetrics.stringWidth("KKNI", font_name, font_size)
    w_s1 = pdfmetrics.stringWidth("/", font_name, font_size)
    w_okupasi = pdfmetrics.stringWidth("Okupasi", font_name, font_size)
    w_s2 = pdfmetrics.stringWidth("/", font_name, font_size)
    w_klaster = pdfmetrics.stringWidth("Klaster", font_name, font_size)

    # Draw text parts
    c.drawString(x, y, "(")
    curr_x = x + w_open

    kkni_x = curr_x
    c.drawString(curr_x, y, "KKNI")
    curr_x += w_kkni

    c.drawString(curr_x, y, "/")
    curr_x += w_s1

    okupasi_x = curr_x
    c.drawString(curr_x, y, "Okupasi")
    curr_x += w_okupasi

    c.drawString(curr_x, y, "/")
    curr_x += w_s2

    klaster_x = curr_x
    c.drawString(curr_x, y, "Klaster")
    curr_x += w_klaster

    c.drawString(curr_x, y, ")")

    # Strikethrough line (centered on text height)
    strike_y = y + font_size * 0.30
    c.setLineWidth(0.7)
    c.setStrokeColor(black)

    if "KLASTER" in st:
        c.line(kkni_x - 0.5, strike_y, okupasi_x + w_okupasi + w_s2, strike_y)
    elif "OKUPASI" in st:
        c.line(kkni_x - 0.5, strike_y, kkni_x + w_kkni + w_s1, strike_y)
        c.line(klaster_x - w_s2, strike_y, klaster_x + w_klaster + 0.5, strike_y)
    elif "KKNI" in st:
        c.line(okupasi_x - w_s1, strike_y, klaster_x + w_klaster + 0.5, strike_y)
    else:
        c.line(kkni_x - 0.5, strike_y, okupasi_x + w_okupasi + w_s2, strike_y)

    c.restoreState()


DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/XI/2025",
    "skema_tipe": "KLASTER",
    "tuk_nama": "SMKN 1 Bali",
    "asesor_nama": "Randi Ardiansyah",
    "asesor_no_reg": "MET.000.002498 2013",
    "tanggal": "02 November 2026",
    "signature_asesor": None,
    "template_type": "langsung",
    "catatan_peninjau": "Proses asesmen telah memenuhi seluruh standar mutu penjaminan asesmen BNSP.",
    "rekomendasi_prosedur": "Seluruh prosedur asesmen terlaksana secara valid, reliabel, fleksibel dan adil sesuai pedoman BNSP 301.",
    "rekomendasi_dimensi": "MUK yang digunakan sangat komprehensif. Disarankan pembaruan berkelanjutan pada bank soal dan instrumen asesmen.",
}


def draw_header_kop(c, x0, x1, title_text="Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo"):
    """Draw standardized BNSP LSP header kop."""
    top_hdr = 34.0
    bot_hdr = 99.3
    tbl_w = x1 - x0
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot_hdr), tbl_w, bot_hdr - top_hdr)
    c.line(166.9, Y(top_hdr), 166.9, Y(bot_hdr))

    # Logo
    if os.path.isfile(LOGO_PATH):
        try:
            c.drawImage(LOGO_PATH, 86.25, Y(bot_hdr - 3.0), 69.45, 61.5, mask="auto")
        except Exception:
            pass

    # Title
    c.setFont("Lib-B", 10.5)
    c.setFillColor(black)
    c.drawCentredString((166.9 + x1) / 2, Y(68.5), title_text)


def render_page_1(c, data):
    """Render Page 1 of FR.AK.06."""
    x0 = 70.9
    x1 = 564.8
    tbl_w = x1 - x0

    # 1. Header Kop
    draw_header_kop(c, x0, x1)

    # 2. Document Title
    template_type = str(data.get("template_type") or "langsung").lower()
    type_label = "(TIDAK LANGSUNG)" if template_type == "tidak_langsung" else "(LANGSUNG)"

    c.setFont("Lib-B", 11.0)
    c.drawString(x0, Y(116.0), "FR.AK.06.")
    c.drawString(138.0, Y(116.0), f"MENINJAU PROSES ASESMEN {type_label}")
    c.setFont("Lib", 8.5)
    c.drawRightString(x1, Y(116.0), "Halaman 1 dari 2")

    # 3. Meta Grid
    top_meta = 127.0
    r_meta = [127.0, 153.8, 177.9, 199.1, 220.6, 242.3]
    c.rect(x0, Y(r_meta[-1]), tbl_w, r_meta[-1] - r_meta[0])
    for ym in r_meta[1:-1]:
        c.line(x0, Y(ym), x1, Y(ym))

    c.line(203.8, Y(r_meta[2]), 203.8, Y(r_meta[0]))
    c.line(260.5, Y(r_meta[-1]), 260.5, Y(r_meta[0]))

    # Row 1: Skema Sertifikasi
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(147.5), "Skema Sertifikasi")
    skema_tipe = data.get("skema_tipe") or data.get("scheme_type") or "KLASTER"
    draw_kkni_options(c, x0 + 8.5, Y(162.0), font_name="Lib", font_size=8.0, scheme_type=skema_tipe)

    # Row 1 Right: Judul
    c.setFont("Lib-B", 8.5)
    skema_judul = str(data.get("skema_nama") or "-")
    skema_lines = wrap_text(c, skema_judul, "Lib-B", 8.5, x1 - 275.0)
    if len(skema_lines) == 1:
        c.drawString(203.8 + 8.0, Y(142.5), "Judul")
        c.drawCentredString(267.0, Y(142.5), ":")
        c.drawString(275.0, Y(142.5), skema_lines[0])
    elif len(skema_lines) >= 2:
        c.drawString(203.8 + 8.0, Y(138.0), "Judul")
        c.drawCentredString(267.0, Y(138.0), ":")
        c.drawString(275.0, Y(138.0), skema_lines[0])
        c.drawString(275.0, Y(148.5), skema_lines[1])

    # Row 2 Right: Nomor
    c.setFont("Lib-B", 8.5)
    c.drawString(203.8 + 8.0, Y(168.0), "Nomor")
    c.drawCentredString(267.0, Y(168.0), ":")
    c.setFont("Lib", 8.5)
    c.drawString(275.0, Y(168.0), str(data.get("skema_nomor") or "-"))

    # Row 2: TUK
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(190.5), "TUK")
    c.drawCentredString(267.0, Y(190.5), ":")
    c.setFont("Lib", 8.5)
    tuk_display = str(data.get("tuk_nama") or "")
    if tuk_display and not tuk_display.startswith("Sewaktu"):
        tuk_text = f"Sewaktu/Tempat Kerja/Mandiri* ({tuk_display})"
    else:
        tuk_text = tuk_display or "Sewaktu/Tempat Kerja/Mandiri*"
    c.drawString(275.0, Y(190.5), tuk_text)

    # Row 3: Nama Asesor
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(212.0), "Nama Asesor")
    c.drawCentredString(267.0, Y(212.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(275.0, Y(212.0), str(data.get("asesor_nama") or "-"))

    # Row 4: Tanggal
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(233.5), "Tanggal")
    c.drawCentredString(267.0, Y(233.5), ":")
    c.setFont("Lib", 8.5)
    c.drawString(275.0, Y(233.5), str(data.get("tanggal") or "-"))

    # 4. Penjelasan Block
    top_penj = 250.0
    bot_penj = 304.0
    c.rect(x0, Y(bot_penj), tbl_w, bot_penj - top_penj)
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 8.0, Y(top_penj + 13.0), "Penjelasan:")
    c.setFont("Lib", 7.8)
    penj_lines = [
        "1. Peninjauan dapat dilakukan oleh lead asesor atau asesor yang melaksanakan asesmen.",
        "2. Peninjauan dapat dilakukan secara terpadu dalam skema sertifikasi dan / atau peserta kelompok yang homogen.",
        "3. Isilah pemenuhan dimensi kompetensi dengan menuliskan jenis bukti dan instrumen yang digunakan pada saat asesmen sebagai",
        "    bukti terpenuhinya dimensi kompetensi.",
    ]
    curr_py = top_penj + 24.5
    for pl in penj_lines:
        c.drawString(x0 + 8.0, Y(curr_py), pl)
        curr_py += 10.0

    # 5. Table 1: Kesesuaian dengan Prinsip Asesmen
    top_t1 = 312.0
    h_hdr_t1 = 30.0
    h_row_t1 = 17.0
    n_rows_t1 = 6  # Prosedur + 5 sub-items
    h_rekom_t1 = 32.0
    bot_t1 = top_t1 + h_hdr_t1 + (n_rows_t1 * h_row_t1) + h_rekom_t1  # 312 + 30 + 102 + 32 = 476.0

    c.rect(x0, Y(bot_t1), tbl_w, bot_t1 - top_t1)

    # Vertical dividers for Table 1
    # Col 1: Aspek yang ditinjau (width 253.9, from x0 to 324.8)
    col_t1_prinsip = 324.8
    c.line(col_t1_prinsip, Y(top_t1), col_t1_prinsip, Y(top_t1 + h_hdr_t1 + (n_rows_t1 * h_row_t1)))

    # Sub-columns for 4 Prinsip (each 60.0 pt: 324.8, 384.8, 444.8, 504.8, 564.8)
    sub_cols_t1 = [324.8, 384.8, 444.8, 504.8, x1]
    for sc in sub_cols_t1[1:-1]:
        c.line(sc, Y(top_t1 + 14.0), sc, Y(top_t1 + h_hdr_t1 + (n_rows_t1 * h_row_t1)))

    # Horizontal divider between header row 1 and row 2
    c.line(col_t1_prinsip, Y(top_t1 + 14.0), x1, Y(top_t1 + 14.0))
    # Horizontal line below header
    c.line(x0, Y(top_t1 + h_hdr_t1), x1, Y(top_t1 + h_hdr_t1))

    # Header text
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((x0 + col_t1_prinsip) / 2, Y(top_t1 + 19.0), "Aspek yang ditinjau")
    c.drawCentredString((col_t1_prinsip + x1) / 2, Y(top_t1 + 10.5), "Kesesuaian dengan prinsip asesmen")

    c.setFont("Lib-B", 8.0)
    c.drawCentredString((sub_cols_t1[0] + sub_cols_t1[1]) / 2, Y(top_t1 + 24.5), "Validitas")
    c.drawCentredString((sub_cols_t1[1] + sub_cols_t1[2]) / 2, Y(top_t1 + 24.5), "Reliabel")
    c.drawCentredString((sub_cols_t1[2] + sub_cols_t1[3]) / 2, Y(top_t1 + 24.5), "Fleksibel")
    c.drawCentredString((sub_cols_t1[3] + sub_cols_t1[4]) / 2, Y(top_t1 + 24.5), "Adil")

    # Table 1 Items
    items_t1 = [
        ("Prosedur asesmen:", True),
        ("  • Rencana asesmen", False),
        ("  • Persiapan asesmen", False),
        ("  • Implementasi asesmen", False),
        ("  • Keputusan asesmen", False),
        ("  • Umpan balik asesmen", False),
    ]

    curr_row_y = top_t1 + h_hdr_t1
    for idx, (label, is_bold) in enumerate(items_t1):
        y_bot = curr_row_y + h_row_t1
        c.line(x0, Y(y_bot), x1, Y(y_bot))

        font_item = "Lib-B" if is_bold else "Lib"
        c.setFont(font_item, 8.2)
        c.drawString(x0 + 8.0, Y(curr_row_y + 11.5), label)

        # Draw 4 checked checkboxes
        mid_y = curr_row_y + h_row_t1 / 2
        for col_idx in range(4):
            cx = (sub_cols_t1[col_idx] + sub_cols_t1[col_idx + 1]) / 2
            draw_cb(c, cx, Y(mid_y), True, sz=8.5)

        curr_row_y = y_bot

    # Row: Rekomendasi untuk peningkatan (Table 1)
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 8.0, Y(curr_row_y + 12.0), "Rekomendasi untuk peningkatan :")
    rekom_t1_text = str(data.get("rekomendasi_prosedur") or "Seluruh prosedur asesmen terlaksana secara valid, reliabel, fleksibel dan adil sesuai pedoman BNSP 301.")
    rekom_t1_lines = wrap_text(c, rekom_t1_text, "Lib", 8.0, tbl_w - 16.0)
    c.setFont("Lib", 8.0)
    ry = curr_row_y + 22.5
    for rl in rekom_t1_lines[:2]:
        c.drawString(x0 + 8.0, Y(ry), rl)
        ry += 9.5

    # 6. Table 2: Pemenuhan Dimensi Kompetensi
    top_t2 = bot_t1 + 9.0
    h_hdr_t2 = 32.0
    h_content_t2 = 56.0
    h_rekom_t2 = 30.0
    bot_t2 = top_t2 + h_hdr_t2 + h_content_t2 + h_rekom_t2  # ~635.0

    c.rect(x0, Y(bot_t2), tbl_w, bot_t2 - top_t2)

    # Column 1 width ~130 pt, remaining 363.9 pt split across 5 dimension columns (~72.78 pt each)
    col1_w = 130.0
    div_t2_c1 = x0 + col1_w
    c.line(div_t2_c1, Y(top_t2), div_t2_c1, Y(top_t2 + h_hdr_t2 + h_content_t2))

    dim_cols = [div_t2_c1]
    dim_w = (x1 - div_t2_c1) / 5.0
    for di in range(1, 6):
        dim_cols.append(div_t2_c1 + di * dim_w)

    for dc in dim_cols[1:-1]:
        c.line(dc, Y(top_t2 + 14.0), dc, Y(top_t2 + h_hdr_t2 + h_content_t2))

    # Divider lines
    c.line(div_t2_c1, Y(top_t2 + 14.0), x1, Y(top_t2 + 14.0))
    c.line(x0, Y(top_t2 + h_hdr_t2), x1, Y(top_t2 + h_hdr_t2))
    c.line(x0, Y(top_t2 + h_hdr_t2 + h_content_t2), x1, Y(top_t2 + h_hdr_t2 + h_content_t2))

    # Header Table 2
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((x0 + div_t2_c1) / 2, Y(top_t2 + 20.0), "Aspek yang ditinjau")
    c.drawCentredString((div_t2_c1 + x1) / 2, Y(top_t2 + 10.5), "Pemenuhan dimensi kompetensi")

    dim_headers = [
        ["Task Skills"],
        ["Task", "Management Skills"],
        ["Contingency", "Management Skills"],
        ["Job Role / Env", "Skills"],
        ["Transfer", "Skills"],
    ]
    for di, dh in enumerate(dim_headers):
        cx = (dim_cols[di] + dim_cols[di + 1]) / 2
        c.setFont("Lib-B", 7.2)
        if len(dh) == 1:
            c.drawCentredString(cx, Y(top_t2 + 24.0), dh[0])
        else:
            c.drawCentredString(cx, Y(top_t2 + 20.0), dh[0])
            c.drawCentredString(cx, Y(top_t2 + 28.5), dh[1])

    # Table 2 Content
    c.setFont("Lib-B", 8.0)
    c.drawString(x0 + 6.0, Y(top_t2 + h_hdr_t2 + 13.0), "Konsistensi keputusan")
    c.drawString(x0 + 6.0, Y(top_t2 + h_hdr_t2 + 22.5), "asesmen")
    c.setFont("Lib", 7.5)
    c.drawString(x0 + 6.0, Y(top_t2 + h_hdr_t2 + 33.0), "Bukti dari berbagai asesmen")
    c.drawString(x0 + 6.0, Y(top_t2 + h_hdr_t2 + 42.5), "diperiksa untuk konsistensi")
    c.drawString(x0 + 6.0, Y(top_t2 + h_hdr_t2 + 52.0), "dimensi kompetensi")

    # Fill in dimension columns
    for di in range(5):
        cx = (dim_cols[di] + dim_cols[di + 1]) / 2
        top_c = top_t2 + h_hdr_t2
        if template_type == "tidak_langsung":
            if di == 2:
                # Contingency: Wawancara T / PW
                c.setFont("Lib-B", 8.0)
                c.drawCentredString(cx, Y(top_c + 18.0), "T / PW")
                c.setFont("Lib", 7.5)
                c.drawCentredString(cx, Y(top_c + 32.0), "FR.IA.09")
            else:
                c.setFont("Lib-B", 8.0)
                c.drawCentredString(cx, Y(top_c + 18.0), "TL / CLO")
                c.setFont("Lib", 7.5)
                c.drawCentredString(cx, Y(top_c + 32.0), "FR.IA.08")
        else:
            # Langsung (L / CL)
            c.setFont("Lib-B", 8.0)
            c.drawCentredString(cx, Y(top_c + 14.0), "L / CL")
            c.setFont("Lib", 7.2)
            c.drawCentredString(cx, Y(top_c + 25.5), "FR.IA.01, 02,")
            c.drawCentredString(cx, Y(top_c + 35.0), "FR.IA.03")
            c.setFont("Lib-B", 7.0)
            c.drawCentredString(cx, Y(top_c + 46.5), "T / DPT IA.05")

    # Table 2 Rekomendasi
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 8.0, Y(top_t2 + h_hdr_t2 + h_content_t2 + 11.5), "Rekomendasi untuk peningkatan :")
    rekom_t2_text = str(data.get("rekomendasi_dimensi") or "MUK yang digunakan sangat komprehensif. Disarankan pembaruan berkelanjutan pada bank soal dan instrumen asesmen.")
    rekom_t2_lines = wrap_text(c, rekom_t2_text, "Lib", 8.0, tbl_w - 16.0)
    c.setFont("Lib", 8.0)
    ry2 = top_t2 + h_hdr_t2 + h_content_t2 + 21.5
    for rl in rekom_t2_lines[:1]:
        c.drawString(x0 + 8.0, Y(ry2), rl)


def render_page_2(c, data):
    """Render Page 2 of FR.AK.06."""
    x0 = 70.9
    x1 = 564.8
    tbl_w = x1 - x0

    # 1. Header Kop
    draw_header_kop(c, x0, x1)

    # 2. Document Title
    template_type = str(data.get("template_type") or "langsung").lower()
    type_label = "(TIDAK LANGSUNG)" if template_type == "tidak_langsung" else "(LANGSUNG)"

    c.setFont("Lib-B", 11.0)
    c.drawString(x0, Y(116.0), "FR.AK.06.")
    c.drawString(138.0, Y(116.0), f"MENINJAU PROSES ASESMEN {type_label}")
    c.setFont("Lib", 8.5)
    c.drawRightString(x1, Y(116.0), "Halaman 2 dari 2")

    # 3. Rekomendasi Peningkatan Section
    top_sec = 135.0
    c.setFont("Lib-B", 9.5)
    c.drawString(x0, Y(top_sec), "Rekomendasi untuk peningkatan :")

    box_top = top_sec + 8.0
    box_h = 70.0
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(box_top + box_h), tbl_w, box_h)

    catatan_rekom = [
        "1. Mempertahankan konsistensi implementasi SOP dan keselamatan kerja (K3) di Tempat Uji Kompetensi (TUK).",
        "2. Mengoptimalkan kalibrasi alat ukur dan validasi berkala pada instrumen asesmen praktik dan tes tertulis.",
        "3. Peningkatan integrasi sistem digital asesmen LSP PrO Indo untuk efisiensi dan transparansi pelaporan hasil asesmen.",
    ]
    c.setFont("Lib", 8.2)
    cy = box_top + 16.0
    for cr in catatan_rekom:
        c.drawString(x0 + 10.0, Y(cy), cr)
        cy += 16.0

    # 4. Signature & Lead Assessor Review Table
    top_sig = box_top + box_h + 20.0
    h_hdr_sig = 25.0
    h_body_sig = 115.0
    bot_sig = top_sig + h_hdr_sig + h_body_sig

    c.rect(x0, Y(bot_sig), tbl_w, bot_sig - top_sig)

    # Column dividers
    # Col 1: Nama Lead Asesor/Asesor (x0 to 205.0) -> width 134.1 pt
    # Col 2: Tanggal & Tanda Tangan (205.0 to 335.0) -> width 130.0 pt
    # Col 3: Komentar (335.0 to x1) -> width 229.8 pt
    col_sig1 = 205.0
    col_sig2 = 335.0

    c.line(col_sig1, Y(top_sig), col_sig1, Y(bot_sig))
    c.line(col_sig2, Y(top_sig), col_sig2, Y(bot_sig))
    c.line(x0, Y(top_sig + h_hdr_sig), x1, Y(top_sig + h_hdr_sig))

    # Header Row
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((x0 + col_sig1) / 2, Y(top_sig + 15.5), "Nama Lead Asesor / Asesor")
    c.drawCentredString((col_sig1 + col_sig2) / 2, Y(top_sig + 15.5), "Tanggal & Tanda Tangan")
    c.drawCentredString((col_sig2 + x1) / 2, Y(top_sig + 15.5), "Komentar")

    # Column 1 Content: Nama Asesor
    top_body = top_sig + h_hdr_sig
    c.setFont("Lib", 8.0)
    c.drawString(x0 + 8.0, Y(top_body + 18.0), "Nama :")
    c.setFont("Lib-B", 8.5)
    c.drawString(x0 + 8.0, Y(top_body + 31.0), str(data.get("asesor_nama") or "-"))

    c.setFont("Lib", 8.0)
    c.drawString(x0 + 8.0, Y(top_body + 55.0), "No. Reg :")
    c.setFont("Lib", 8.2)
    c.drawString(x0 + 8.0, Y(top_body + 68.0), str(data.get("asesor_no_reg") or "-"))

    c.setFont("Lib-I", 7.5)
    c.drawString(x0 + 8.0, Y(top_body + 95.0), "Lead Asesor / Asesor Kompetensi")

    # Column 2 Content: Tanggal & Tanda Tangan
    c.setFont("Lib", 8.0)
    c.drawString(col_sig1 + 8.0, Y(top_body + 16.0), f"Tanggal : {data.get('tanggal') or '-'}")

    # Digital Signature
    sig_drawn = draw_signature(
        c,
        data.get("signature_asesor"),
        col_sig1 + 10.0,
        Y(bot_sig - 16.0),
        max_w=110.0,
        max_h=55.0,
    )

    if not sig_drawn:
        c.setFont("Lib-I", 8.0)
        c.drawCentredString((col_sig1 + col_sig2) / 2, Y(top_body + 60.0), "(Tanda Tangan Digital)")

    # Dotted line under signature
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.line(col_sig1 + 12.0, Y(bot_sig - 18.0), col_sig2 - 12.0, Y(bot_sig - 18.0))

    # Column 3 Content: Komentar
    c.setFont("Lib-B", 8.5)
    c.drawString(col_sig2 + 8.0, Y(top_body + 16.0), "Komentar Peninjau:")

    catatan_peninjau = str(data.get("catatan_peninjau") or "Proses asesmen telah memenuhi seluruh standar mutu penjaminan asesmen BNSP.")
    komentar_lines = wrap_text(c, catatan_peninjau, "Lib", 8.0, (x1 - col_sig2) - 16.0)

    c.setFont("Lib", 8.0)
    k_y = top_body + 29.0
    for kl in komentar_lines[:8]:
        c.drawString(col_sig2 + 8.0, Y(k_y), kl)
        k_y += 11.5


def render(dest_path, payload=None):
    """Render full FR.AK.06 2-page document."""
    data = dict(DATA)
    if payload:
        data.update(payload)

    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    render_page_1(c, data)
    c.showPage()
    render_page_2(c, data)
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.AK.06 Meninjau Proses Asesmen: {dest_path}")


if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_06_generated.pdf")
    if len(sys.argv) > 1:
        if sys.argv[1].endswith(".json") and os.path.isfile(sys.argv[1]):
            with open(sys.argv[1], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        else:
            out_file = sys.argv[1]
    render(out_file)

