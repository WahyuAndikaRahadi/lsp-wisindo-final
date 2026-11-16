#!/usr/bin/env python3
"""
Generator FR.AK.06 (Meninjau Proses Asesmen) - LSP PrO Indo
Layout meniru template master BNSP secara presisi piksel; 100% tanpa watermark.
Mendukung input dinamis JSON untuk data jadwal, skema, TUK, asesor, serta
dua tipe peninjauan yang menentukan pemenuhan seluruh 5 dimensi kompetensi:
1. 'langsung'        -> Bukti Langsung (L / CL): FR.IA.01, FR.IA.02, FR.IA.03 di semua 5 dimensi.
2. 'tidak_langsung'  -> Bukti Tidak Langsung (TL / CLO & T / PW): FR.IA.08 & FR.IA.09 di semua dimensi.
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

register_reportlab_fonts(["Lib", "Lib-B", "Lib-I", "Cal", "Cal-B", "Cal-I", "Tah", "Tah-B", "DJ"])

# Standard A4: 595.5 x 842.0 pt
PAGE_W, PAGE_H = 595.5, 842.0
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


def draw_signature(c, sig_data, x_left, y_bottom, max_w=95.0, max_h=46.0):
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
    c.setLineWidth(0.6)
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


def draw_kkni_options(c, x, y, font_name="Cal", font_size=10.5, scheme_type="KLASTER"):
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
    strike_y = y + font_size * 0.32
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
    "catatan_peninjau": "Proses asesmen telah memenuhi seluruh standar mutu penjaminan asesmen BNSP. Keputusan asesmen yang diambil adalah valid, reliabel, fleksibel, dan adil.",
    "rekomendasi_prosedur": "Seluruh prosedur asesmen terlaksana secara valid, reliabel, fleksibel dan adil sesuai pedoman BNSP 301.",
    "rekomendasi_dimensi": "MUK yang digunakan sangat komprehensif. Disarankan pembaruan berkelanjutan pada bank soal dan instrumen asesmen.",
}


def draw_header_kop(c, x0=75.2, x1=565.3):
    """Draw standardized BNSP LSP header kop exactly matching master coordinates."""
    top_hdr = 26.9
    bot_hdr = 79.9
    tbl_w = x1 - x0
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot_hdr), tbl_w, bot_hdr - top_hdr)
    c.line(169.6, Y(top_hdr), 169.6, Y(bot_hdr))

    # Logo
    if os.path.isfile(LOGO_PATH):
        try:
            c.drawImage(LOGO_PATH, 88.0, Y(bot_hdr - 3.5), 65.0, 46.0, mask="auto")
        except Exception:
            pass

    # Title
    c.setFont("Tah-B", 11.0)
    c.setFillColor(black)
    c.drawCentredString((169.6 + x1) / 2, Y(55.0), "Lembaga Sertifikasi Profesi Praktisi Otomotif  LSP-PrO Indo")


def render_page_1(c, data):
    """Render Page 1 of FR.AK.06: Table 1 & Table 2 unified on Page 1."""
    x0 = 72.8
    x1 = 573.8
    tbl_w = x1 - x0  # 501.0 pt

    # 1. Header Kop
    draw_header_kop(c, 75.2, 565.3)

    # 2. Document Title (Dinamis: LANGSUNG vs TIDAK LANGSUNG)
    template_type = str(data.get("template_type") or "langsung").lower()
    type_label = "(TIDAK LANGSUNG)" if template_type == "tidak_langsung" else "(LANGSUNG)"

    c.setFont("Cal-B", 11.5)
    c.drawString(x0, Y(104.0), "FR.AK.06.")
    c.drawString(138.0, Y(104.0), f"MENINJAU PROSES ASESMEN {type_label}")

    # 3. Meta Grid (y: 118.0 to 238.0)
    r_meta = [118.0, 146.0, 169.0, 192.0, 215.0, 238.0]
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(r_meta[-1]), tbl_w, r_meta[-1] - r_meta[0])
    for ym in r_meta[1:-1]:
        c.line(x0, Y(ym), x1, Y(ym))

    # Dividers
    c.line(204.9, Y(r_meta[2]), 204.9, Y(r_meta[0]))
    c.line(261.5, Y(r_meta[-1]), 261.5, Y(r_meta[0]))

    # Row 1: Skema Sertifikasi
    c.setFont("Cal", 10.5)
    c.drawString(x0 + 8.0, Y(130.0), "Skema Sertifikasi")
    skema_tipe = data.get("skema_tipe") or data.get("scheme_type") or "KLASTER"
    draw_kkni_options(c, x0 + 8.0, Y(142.0), font_name="Cal", font_size=10.0, scheme_type=skema_tipe)

    # Row 1 Right: Judul
    c.setFont("Cal", 10.5)
    c.drawString(204.9 + 8.0, Y(133.0), "Judul")
    c.drawCentredString(267.0, Y(133.0), ":")
    skema_judul = str(data.get("skema_nama") or "-")
    skema_lines = wrap_text(c, skema_judul, "Cal", 9.5, x1 - 275.0)
    if len(skema_lines) == 1:
        c.drawString(275.0, Y(133.0), skema_lines[0])
    elif len(skema_lines) >= 2:
        c.drawString(275.0, Y(129.0), skema_lines[0])
        c.drawString(275.0, Y(140.0), skema_lines[1])

    # Row 2 Right: Nomor
    c.drawString(204.9 + 8.0, Y(159.0), "Nomor")
    c.drawCentredString(267.0, Y(159.0), ":")
    c.drawString(275.0, Y(159.0), str(data.get("skema_nomor") or "-"))

    # Row 3: TUK
    c.drawString(x0 + 8.0, Y(182.0), "TUK")
    c.drawCentredString(267.0, Y(182.0), ":")
    tuk_display = str(data.get("tuk_nama") or "")
    if tuk_display and not tuk_display.startswith("Sewaktu"):
        tuk_text = f"Sewaktu/Tempat Kerja/Mandiri* ({tuk_display})"
    else:
        tuk_text = tuk_display or "Sewaktu/Tempat Kerja/Mandiri*"
    c.drawString(275.0, Y(182.0), tuk_text)

    # Row 4: Nama Asesor
    c.drawString(x0 + 8.0, Y(205.0), "Nama Asesor")
    c.drawCentredString(267.0, Y(205.0), ":")
    c.setFont("Cal-B", 10.5)
    c.drawString(275.0, Y(205.0), str(data.get("asesor_nama") or "-"))

    # Row 5: Tanggal
    c.setFont("Cal", 10.5)
    c.drawString(x0 + 8.0, Y(228.0), "Tanggal")
    c.drawCentredString(267.0, Y(228.0), ":")
    c.drawString(275.0, Y(228.0), str(data.get("tanggal") or "-"))

    # 4. Penjelasan Block (y: 248.0 to 344.0)
    top_penj = 248.0
    bot_penj = 344.0
    c.rect(x0, Y(bot_penj), tbl_w, bot_penj - top_penj)
    c.line(x0, Y(268.0), x1, Y(268.0))

    c.setFont("Cal-B", 10.5)
    c.drawString(x0 + 8.0, Y(261.0), "Penjelasan:")

    c.setFont("Cal", 9.5)
    c.drawString(x0 + 8.0, Y(282.0), "1.  Peninjauan dapat dilakukan oleh lead asesor atau asesor yang melaksanakan asesmen.")
    c.drawString(x0 + 8.0, Y(299.0), "2.  Peninjauan dapat dilakukan secara terpadu dalam skema sertifikasi dan / atau peserta kelompok yang homogen.")
    c.drawString(x0 + 8.0, Y(316.0), "3.  Isilah pemenuhan dimensi kompetensi dengan menuliskan jenis bukti dan instrumen yang digunakan pada saat")
    c.drawString(x0 + 22.0, Y(329.0), "asesmen sebagai bukti terpenuhinya dimensi kompetensi.")

    # 5. Table 1: Kesesuaian dengan Prinsip Asesmen (y: 356.0 to 522.0)
    top_t1 = 356.0
    bot_t1 = 522.0
    col_t1_prinsip = 333.2
    sub_cols_t1 = [333.2, 406.9, 463.6, 524.0, x1]

    c.rect(x0, Y(bot_t1), tbl_w, bot_t1 - top_t1)

    # Header horizontal dividers
    c.line(x0, Y(392.0), x1, Y(392.0))
    c.line(col_t1_prinsip, Y(374.0), x1, Y(374.0))

    # Header vertical dividers
    c.line(col_t1_prinsip, Y(top_t1), col_t1_prinsip, Y(498.0))
    for sc in sub_cols_t1[1:-1]:
        c.line(sc, Y(374.0), sc, Y(498.0))

    # Header text
    c.setFont("Cal-B", 10.5)
    c.drawCentredString((x0 + col_t1_prinsip) / 2, Y(378.0), "Aspek yang ditinjau")
    c.drawCentredString((col_t1_prinsip + x1) / 2, Y(367.0), "Kesesuaian dengan prinsip asesmen")

    c.setFont("Cal-I", 10.0)
    c.drawCentredString((sub_cols_t1[0] + sub_cols_t1[1]) / 2, Y(386.0), "Validitas")
    c.drawCentredString((sub_cols_t1[1] + sub_cols_t1[2]) / 2, Y(386.0), "Reliabel")
    c.drawCentredString((sub_cols_t1[2] + sub_cols_t1[3]) / 2, Y(386.0), "Fleksibel")
    c.drawCentredString((sub_cols_t1[3] + sub_cols_t1[4]) / 2, Y(386.0), "Adil")

    # Table 1 Items:
    # 6 rows of data: 392.0 to 498.0 (each ~17.6 pt)
    row_y_t1 = [392.0, 409.6, 427.2, 444.8, 462.4, 480.0, 498.0]
    for ry in row_y_t1[1:]:
        c.line(x0, Y(ry), x1, Y(ry))

    items_t1 = [
        ("Prosedur asesmen:", True, []),
        ("  ●  Rencana asesmen", False, []),
        ("  ●  Persiapan asesmen", False, []),
        ("  ●  Implementasi asesmen", False, []),
        ("  ●  Keputusan asesmen", False, [2]),
        ("  ●  Umpan balik asesmen", False, [2]),
    ]

    for idx, (label, is_bold, disabled_cols) in enumerate(items_t1):
        y_top_r = row_y_t1[idx]
        y_bot_r = row_y_t1[idx + 1]
        mid_y = (y_top_r + y_bot_r) / 2

        # Blacken disabled columns (e.g. Fleksibel on Keputusan & Umpan Balik)
        for col_idx in disabled_cols:
            c.saveState()
            c.setFillColor(HexColor("#262626"))
            c.rect(
                sub_cols_t1[col_idx],
                Y(y_bot_r),
                sub_cols_t1[col_idx + 1] - sub_cols_t1[col_idx],
                y_bot_r - y_top_r,
                fill=1,
                stroke=0,
            )
            c.restoreState()

        # Text label
        font_item = "Cal-B" if is_bold else "Cal"
        c.setFont(font_item, 9.5)
        c.drawString(x0 + 8.0, Y(mid_y + 3.0), label)

        # Draw checkboxes with checkmarks for enabled columns (Validitas, Reliabel, Adil)
        for col_idx in range(4):
            if col_idx not in disabled_cols:
                cx = (sub_cols_t1[col_idx] + sub_cols_t1[col_idx + 1]) / 2
                draw_cb(c, cx, Y(mid_y), True, sz=9.0)

    # Bottom Row of Table 1: Rekomendasi untuk peningkatan (498.0 to 522.0)
    c.setFont("Cal-B", 9.5)
    c.drawString(x0 + 8.0, Y(512.0), "Rekomendasi untuk peningkatan :")
    rekom_t1_text = str(data.get("rekomendasi_prosedur") or "Seluruh prosedur asesmen terlaksana secara valid, reliabel, fleksibel dan adil sesuai pedoman BNSP 301.")
    c.setFont("Cal", 8.2)
    c.drawString(x0 + 172.0, Y(512.0), rekom_t1_text)

    # 6. Table 2: Pemenuhan Dimensi Kompetensi (y: 534.0 to 768.0)
    # DISESUAIKAN PERSIS DENGAN FORMAT WEB / TEMPLATE MASTER BNSP (TANPA BARIS REKOMENDASI GANDA)
    top_t2 = 534.0
    bot_t2 = 768.0
    c.rect(x0, Y(bot_t2), tbl_w, bot_t2 - top_t2)

    # Table 2 Dividers
    div_t2_c1 = 199.4
    dim_cols = [199.4, 267.0, 339.0, 415.5, 484.1, x1]

    # Horizontal lines
    c.line(div_t2_c1, Y(553.0), x1, Y(553.0))
    c.line(x0, Y(592.0), x1, Y(592.0))

    # Vertical lines
    c.line(div_t2_c1, Y(top_t2), div_t2_c1, Y(bot_t2))
    for dc in dim_cols[1:-1]:
        c.line(dc, Y(553.0), dc, Y(bot_t2))

    # Header Table 2
    c.setFont("Cal-B", 10.5)
    c.drawCentredString((x0 + div_t2_c1) / 2, Y(568.0), "Aspek yang ditinjau")
    c.drawCentredString((div_t2_c1 + x1) / 2, Y(546.0), "Pemenuhan dimensi kompetensi")

    # Dimension Subheaders (y: 553.0 to 592.0)
    c.setFont("Cal-I", 9.2)
    # 1. Task Skills
    c.drawCentredString((dim_cols[0] + dim_cols[1]) / 2, Y(575.0), "Task Skills")
    # 2. Task Management Skills
    cx_tm = (dim_cols[1] + dim_cols[2]) / 2
    c.drawCentredString(cx_tm, Y(564.0), "Task")
    c.drawCentredString(cx_tm, Y(575.0), "Management")
    c.drawCentredString(cx_tm, Y(586.0), "Skills")
    # 3. Contingency Management Skills
    cx_cm = (dim_cols[2] + dim_cols[3]) / 2
    c.drawCentredString(cx_cm, Y(564.0), "Contingency")
    c.drawCentredString(cx_cm, Y(575.0), "Management")
    c.drawCentredString(cx_cm, Y(586.0), "Skills")
    # 4. Job Role / Env Skills
    cx_jr = (dim_cols[3] + dim_cols[4]) / 2
    c.drawCentredString(cx_jr, Y(564.0), "Job Role /")
    c.drawCentredString(cx_jr, Y(575.0), "Environment")
    c.drawCentredString(cx_jr, Y(586.0), "Skills")
    # 5. Transfer Skills
    c.drawCentredString((dim_cols[4] + dim_cols[5]) / 2, Y(575.0), "Transfer Skills")

    # Table 2 Content Row (y: 592.0 to 768.0)
    # Left Column: Aspek yang ditinjau
    c.setFont("Cal-B", 10.0)
    c.drawString(x0 + 6.0, Y(630.0), "Konsistensi keputusan")
    c.drawString(x0 + 6.0, Y(644.0), "asesmen")
    c.setFont("Cal", 9.2)
    c.drawString(x0 + 6.0, Y(668.0), "Bukti dari berbagai")
    c.drawString(x0 + 6.0, Y(681.0), "asesmen diperiksa")
    c.drawString(x0 + 6.0, Y(694.0), "untuk konsistensi")
    c.drawString(x0 + 6.0, Y(707.0), "dimensi kompetensi")

    # Dimension Columns: Mengikuti 2 pilihan template resmi
    if template_type == "tidak_langsung":
        # FORMAT TIDAK LANGSUNG (TL / CLO & T / PW)
        for di in range(5):
            cx = (dim_cols[di] + dim_cols[di + 1]) / 2
            if di == 2:
                # Contingency Management Skills: T / PW
                c.setFont("Cal-B", 10.0)
                c.drawCentredString(cx, Y(660.0), "T / PW")
                c.setFont("Cal", 9.0)
                c.drawCentredString(cx, Y(684.0), "FR.IA 09")
            else:
                # Task Skills, Task Management, Job Role/Env, Transfer: TL / CLO
                c.setFont("Cal-B", 10.0)
                c.drawCentredString(cx, Y(660.0), "TL / CLO")
                c.setFont("Cal", 9.0)
                c.drawCentredString(cx, Y(684.0), "FR.IA 08")
    else:
        # FORMAT LANGSUNG (L / CL) - Templated ke SEMUA 5 Dimensi
        for di in range(5):
            cx = (dim_cols[di] + dim_cols[di + 1]) / 2
            c.setFont("Cal-B", 10.0)
            c.drawCentredString(cx, Y(655.0), "L / CL")
            c.setFont("Cal", 8.8)
            c.drawCentredString(cx, Y(678.0), "FR.IA 01, FR IA 02,")
            c.drawCentredString(cx, Y(694.0), "FR IA 03")


def render_page_2(c, data):
    """Render Page 2 of FR.AK.06: Lead Assessor Review & Signatures."""
    x0 = 72.8
    x1 = 573.8
    tbl_w = x1 - x0

    # 1. Header Kop
    draw_header_kop(c, 75.2, 565.3)

    # 2. Rekomendasi Peningkatan Section (y: 93.8 to 129.3)
    top_rekom = 93.8
    bot_rekom = 129.3
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot_rekom), tbl_w, bot_rekom - top_rekom)

    c.setFont("Cal-B", 10.5)
    c.drawString(x0 + 8.0, Y(111.0), "Rekomendasi untuk peningkatan :")

    rekom_p2 = str(data.get("rekomendasi_dimensi") or data.get("rekomendasi_peningkatan") or "MUK yang digunakan sangat komprehensif. Disarankan pembaruan berkelanjutan pada bank soal dan instrumen asesmen.")
    c.setFont("Cal", 8.8)
    rekom_lines = wrap_text(c, rekom_p2, "Cal", 8.8, tbl_w - 185.0)
    if len(rekom_lines) == 1:
        c.drawString(x0 + 175.0, Y(111.0), rekom_lines[0])
    elif len(rekom_lines) >= 2:
        c.drawString(x0 + 175.0, Y(106.0), rekom_lines[0])
        c.drawString(x0 + 175.0, Y(118.0), rekom_lines[1])

    # 3. Signature & Lead Assessor Review Table (y: 129.3 to 238.1)
    top_sig = 129.3
    bot_sig = 238.1
    h_hdr_sig = 36.7
    y_line_hdr = top_sig + h_hdr_sig  # 166.0

    c.rect(x0, Y(bot_sig), tbl_w, bot_sig - top_sig)
    c.line(x0, Y(y_line_hdr), x1, Y(y_line_hdr))

    # Column dividers
    col_sig1 = 198.9
    col_sig2 = 303.8

    c.line(col_sig1, Y(top_sig), col_sig1, Y(bot_sig))
    c.line(col_sig2, Y(top_sig), col_sig2, Y(bot_sig))

    # Header Row Text
    c.setFont("Cal-B", 10.5)
    c.drawCentredString((x0 + col_sig1) / 2, Y(145.0), "Nama")
    c.drawCentredString((x0 + col_sig1) / 2, Y(158.0), "Lead Asesor/Asesor")

    c.drawCentredString((col_sig1 + col_sig2) / 2, Y(145.0), "Tanggal & Tanda")
    c.drawCentredString((col_sig1 + col_sig2) / 2, Y(158.0), "Tangan")

    c.drawCentredString((col_sig2 + x1) / 2, Y(152.0), "Komentar")

    # Column 1 Content: Nama Asesor
    c.setFont("Cal", 9.5)
    c.drawString(x0 + 8.0, Y(180.0), "Nama :")
    c.setFont("Cal-B", 10.0)
    c.drawString(x0 + 8.0, Y(194.0), str(data.get("asesor_nama") or "-"))

    c.setFont("Cal", 9.0)
    c.drawString(x0 + 8.0, Y(212.0), "No. Reg :")
    c.setFont("Cal", 9.0)
    c.drawString(x0 + 8.0, Y(224.0), str(data.get("asesor_no_reg") or "-"))

    # Column 2 Content: Tanggal & Tanda Tangan
    c.setFont("Cal", 8.5)
    c.drawCentredString((col_sig1 + col_sig2) / 2, Y(178.0), f"Tanggal : {data.get('tanggal') or '-'}")

    # Digital Signature
    sig_drawn = draw_signature(
        c,
        data.get("signature_asesor"),
        col_sig1 + 8.0,
        Y(bot_sig - 10.0),
        max_w=88.0,
        max_h=46.0,
    )

    if not sig_drawn:
        c.setFont("Cal-I", 8.0)
        c.drawCentredString((col_sig1 + col_sig2) / 2, Y(210.0), "(Tanda Tangan Digital)")

    # Column 3 Content: Komentar
    c.setFont("Cal-B", 9.5)
    c.drawString(col_sig2 + 8.0, Y(180.0), "Komentar Peninjau:")

    catatan_peninjau = str(data.get("catatan_peninjau") or "Proses asesmen telah memenuhi seluruh standar mutu penjaminan asesmen BNSP. Keputusan asesmen yang diambil adalah valid, reliabel, fleksibel, dan adil.")
    komentar_lines = wrap_text(c, catatan_peninjau, "Cal", 8.5, (x1 - col_sig2) - 16.0)
    c.setFont("Cal", 8.5)
    k_y = 194.0
    for kl in komentar_lines[:4]:
        c.drawString(col_sig2 + 8.0, Y(k_y), kl)
        k_y += 11.0


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
