#!/usr/bin/env python3
"""
Generator FR.AK.05 (Laporan Asesmen) - LSP PrO Indo
Layout meniru template master BNSP secara presisi piksel; tanpa watermark.
Mendukung input dinamis JSON untuk data jadwal, skema, TUK, asesor, daftar asesi & rekomendasi,
serta catatan aspek positif/negatif, penolakan, dan saran perbaikan dari asesor.
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
from reportlab.pdfbase.ttfonts import TTFont
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
        if iw <= 0 or ih <= 0:
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
    w_close = pdfmetrics.stringWidth(")", font_name, font_size)

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
        # Cross out KKNI and Okupasi with continuous line through KKNI/Okupasi/
        c.line(kkni_x - 0.5, strike_y, okupasi_x + w_okupasi + w_s2, strike_y)
    elif "OKUPASI" in st:
        # Cross out KKNI/ and /Klaster
        c.line(kkni_x - 0.5, strike_y, kkni_x + w_kkni + w_s1, strike_y)
        c.line(klaster_x - w_s2, strike_y, klaster_x + w_klaster + 0.5, strike_y)
    elif "KKNI" in st:
        # Cross out /Okupasi/Klaster
        c.line(okupasi_x - w_s1, strike_y, klaster_x + w_klaster + 0.5, strike_y)
    else:
        # Default fallback: cross out KKNI and Okupasi
        c.line(kkni_x - 0.5, strike_y, okupasi_x + w_okupasi + w_s2, strike_y)

    c.restoreState()


def draw_tuk_options(c, x, y, font_name="Lib", font_size=8.5, tuk_type="Sewaktu"):
    """Draw Sewaktu/Tempat Kerja/Mandiri* with precise strikethrough leaving only the selected type."""
    tt = str(tuk_type or "Sewaktu").strip().upper()
    c.saveState()
    c.setFont(font_name, font_size)
    c.setFillColor(black)

    w_sewaktu = pdfmetrics.stringWidth("Sewaktu", font_name, font_size)
    w_s1 = pdfmetrics.stringWidth("/", font_name, font_size)
    w_tk = pdfmetrics.stringWidth("Tempat Kerja", font_name, font_size)
    w_s2 = pdfmetrics.stringWidth("/", font_name, font_size)
    w_mandiri = pdfmetrics.stringWidth("Mandiri", font_name, font_size)

    curr_x = x

    sewaktu_x = curr_x
    c.drawString(curr_x, y, "Sewaktu")
    curr_x += w_sewaktu

    s1_x = curr_x
    c.drawString(curr_x, y, "/")
    curr_x += w_s1

    tk_x = curr_x
    c.drawString(curr_x, y, "Tempat Kerja")
    curr_x += w_tk

    s2_x = curr_x
    c.drawString(curr_x, y, "/")
    curr_x += w_s2

    mandiri_x = curr_x
    c.drawString(curr_x, y, "Mandiri")
    curr_x += w_mandiri

    c.drawString(curr_x, y, "*")

    # Strikethrough line (centered on text height)
    strike_y = y + font_size * 0.30
    c.setLineWidth(0.7)
    c.setStrokeColor(black)

    if "TEMPAT" in tt or "KERJA" in tt:
        # Sisakan Tempat Kerja -> coret Sewaktu/ dan /Mandiri
        c.line(sewaktu_x - 0.5, strike_y, sewaktu_x + w_sewaktu + w_s1, strike_y)
        c.line(s2_x - 0.5, strike_y, mandiri_x + w_mandiri + 0.5, strike_y)
    elif "MANDIRI" in tt:
        # Sisakan Mandiri -> coret Sewaktu/Tempat Kerja/
        c.line(sewaktu_x - 0.5, strike_y, tk_x + w_tk + w_s2, strike_y)
    else:
        # Default fallback: Sisakan Sewaktu -> coret /Tempat Kerja/Mandiri
        c.line(s1_x - 0.5, strike_y, mandiri_x + w_mandiri + 0.5, strike_y)

    c.restoreState()


DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/XI/2025",
    "skema_tipe": "KLASTER",
    "tuk_nama": "SMKN 1 Bali",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Randi Ardiansyah",
    "asesor_no_reg": "MET.000.001234 2022",
    "tanggal": "02 November 2026",
    "signature_asesor": None,
    "peserta": [
        {"no": 1, "nama": "Asesi Ani Rahayu", "is_k": True, "is_bk": False, "keterangan": "Memenuhi seluruh KUK"},
        {"no": 2, "nama": "Asesi Budi Pratama", "is_k": False, "is_bk": True, "keterangan": "Perlu uji ulang"},
        {"no": 3, "nama": "Wahyu Andika Rahadi", "is_k": False, "is_bk": False, "keterangan": "Belum Selesai"},
    ],
    "aspek_negatif": "Tidak ditemukan kendala signifikan selama proses asesmen berlangsung.",
    "aspek_positif": "Sarana dan prasarana bengkel TUK lengkap, ketersediaan alat ukur dan APD sesuai standar K3, asesi hadir tepat waktu.",
    "ada_penolakan": "Tidak ada penolakan dari asesi atas keputusan asesmen.",
    "saran_perbaikan": "Mempertahankan konsistensi kalibrasi peralatan tangan dan SST serta peningkatan durasi praktik mandiri.",
    "catatan": "Laporan asesmen ini dibuat berdasarkan seluruh bukti langsung, tidak langsung, dan tambahan yang dihimpun selama proses asesmen berlangsung di TUK.",
}


def render_page(c, page_peserta, page_idx, total_pages, data):
    """Render a single page of FR.AK.05."""
    x0 = 70.9
    x1 = 564.8
    tbl_w = x1 - x0

    # ================= 1. HEADER KOP =================
    top_hdr = 32.0
    bot_hdr = 92.0
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot_hdr), tbl_w, bot_hdr - top_hdr)
    c.line(166.9, Y(top_hdr), 166.9, Y(bot_hdr))

    # Logo
    if os.path.isfile(LOGO_PATH):
        try:
            c.drawImage(LOGO_PATH, 86.25, Y(bot_hdr - 3.5), 65.0, 53.0, mask="auto")
        except Exception:
            pass

    # Header Title
    c.setFont("Lib-B", 10.5)
    c.setFillColor(black)
    c.drawCentredString((166.9 + x1) / 2, Y(62.0), "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

    # Document Code & Title
    c.setFont("Lib-B", 11.0)
    c.drawString(x0, Y(108.0), "FR.AK.05.")
    c.drawString(143.9, Y(108.0), "LAPORAN ASESMEN")
    if total_pages > 1:
        c.setFont("Lib", 8.5)
        c.drawRightString(x1, Y(108.0), f"Halaman {page_idx + 1} dari {total_pages}")

    # ================= 2. META GRID =================
    top_meta = 120.0
    r_meta = [120.0, 144.0, 166.0, 187.0, 208.0, 229.0]
    c.rect(x0, Y(r_meta[-1]), tbl_w, r_meta[-1] - r_meta[0])
    for ym in r_meta[1:-1]:
        c.line(x0, Y(ym), x1, Y(ym))

    # Vertical dividers in meta grid
    c.line(203.8, Y(r_meta[2]), 203.8, Y(r_meta[0]))
    c.line(260.5, Y(r_meta[-1]), 260.5, Y(r_meta[0]))

    # Row 1: Skema Sertifikasi
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 8.5, Y(139.0), "Skema Sertifikasi")
    skema_tipe = data.get("skema_tipe") or data.get("scheme_type") or "KLASTER"
    draw_kkni_options(c, x0 + 8.5, Y(153.0), font_name="Lib", font_size=8.0, scheme_type=skema_tipe)

    c.setFont("Lib-B", 8.5)
    skema_judul = str(data.get("skema_nama") or "-")
    skema_lines = wrap_text(c, skema_judul, "Lib-B", 8.5, x1 - 275.0)
    if len(skema_lines) == 1:
        c.drawString(203.8 + 8.0, Y(138.0), "Judul")
        c.drawCentredString(267.0, Y(138.0), ":")
        c.drawString(275.0, Y(138.0), skema_lines[0])
    elif len(skema_lines) >= 2:
        c.drawString(203.8 + 8.0, Y(133.0), "Judul")
        c.drawCentredString(267.0, Y(133.0), ":")
        c.drawString(275.0, Y(133.0), skema_lines[0])
        c.drawString(275.0, Y(143.5), skema_lines[1])

    c.setFont("Lib-B", 8.5)
    c.drawString(203.8 + 8.0, Y(158.0), "Nomor")
    c.drawCentredString(267.0, Y(158.0), ":")
    c.setFont("Lib", 8.5)
    c.drawString(275.0, Y(158.0), str(data.get("skema_nomor") or "-"))

    # Row 2: TUK
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 8.5, Y(179.0), "TUK")
    c.drawCentredString(267.0, Y(179.0), ":")
    draw_tuk_options(c, 275.0, Y(179.0), font_name="Lib", font_size=8.5, tuk_type=data.get("tuk_tipe"))

    # Row 3: Nama Asesor
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 8.5, Y(200.0), "Nama Asesor")
    c.drawCentredString(267.0, Y(200.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(275.0, Y(200.0), str(data.get("asesor_nama") or "-"))

    # Row 4: Tanggal
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 8.5, Y(221.0), "Tanggal")
    c.drawCentredString(267.0, Y(221.0), ":")
    c.setFont("Lib", 8.5)
    c.drawString(275.0, Y(221.0), str(data.get("tanggal") or "-"))

    # ================= 3. CANDIDATES TABLE =================
    top_cand = 240.0
    hdr_h1 = 17.0
    hdr_h2 = 18.0
    hdr_h = hdr_h1 + hdr_h2  # 35.0

    cols = [x0, 105.2, 282.5, 325.0, 374.6, x1]
    n_display = len(page_peserta) if page_peserta else 1

    if n_display <= 3:
        row_h = 28.0
        r_find_h = [78.0, 32.0, 32.0]
        h_sig = 122.0
    elif n_display <= 5:
        row_h = 24.0
        r_find_h = [65.0, 27.0, 27.0]
        h_sig = 115.0
    elif n_display <= 8:
        row_h = 20.0
        r_find_h = [65.0, 27.0, 27.0]
        h_sig = 115.0
    else:
        row_h = 18.0
        r_find_h = [65.0, 27.0, 27.0]
        h_sig = 115.0

    bot_cand = top_cand + hdr_h + n_display * row_h

    # Outer table rectangle
    c.rect(x0, Y(bot_cand), tbl_w, bot_cand - top_cand)

    # Header horizontal lines
    c.line(x0, Y(top_cand + hdr_h), x1, Y(top_cand + hdr_h))
    c.line(cols[2], Y(top_cand + hdr_h1), cols[4], Y(top_cand + hdr_h1))

    # Header vertical lines
    c.line(cols[1], Y(top_cand + hdr_h), cols[1], Y(top_cand))
    c.line(cols[2], Y(top_cand + hdr_h), cols[2], Y(top_cand))
    c.line(cols[3], Y(top_cand + hdr_h), cols[3], Y(top_cand + hdr_h1))
    c.line(cols[4], Y(top_cand + hdr_h), cols[4], Y(top_cand))

    # Header text
    c.setFont("Lib-B", 8.8)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y(top_cand + 22.0), "No.")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y(top_cand + 22.0), "Nama Asesi")
    c.drawCentredString((cols[2] + cols[4]) / 2, Y(top_cand + 12.0), "Rekomendasi")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y(top_cand + hdr_h1 + 13.0), "K")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y(top_cand + hdr_h1 + 13.0), "BK")
    c.drawCentredString((cols[4] + cols[5]) / 2, Y(top_cand + 22.0), "Keterangan**")

    # Render candidate rows
    for r in range(n_display):
        y_top_r = top_cand + hdr_h + r * row_h
        y_bot_r = y_top_r + row_h
        y_mid_r = (y_top_r + y_bot_r) / 2

        c.line(x0, Y(y_bot_r), x1, Y(y_bot_r))
        c.line(cols[1], Y(y_bot_r), cols[1], Y(y_top_r))
        c.line(cols[2], Y(y_bot_r), cols[2], Y(y_top_r))
        c.line(cols[3], Y(y_bot_r), cols[3], Y(y_top_r))
        c.line(cols[4], Y(y_bot_r), cols[4], Y(y_top_r))

        p_data = page_peserta[r] if r < len(page_peserta) else None
        cand_no = p_data.get("no") if p_data else (r + 1)
        c.setFont("Lib", 8.5)
        c.drawCentredString((cols[0] + cols[1]) / 2, Y(y_mid_r + 2.5), f"{cand_no}.")

        if p_data:
            # Nama asesi
            nama_str = str(p_data.get("nama") or "")
            c.setFont("Lib-B", 8.5)
            c.drawString(cols[1] + 6.0, Y(y_mid_r + 2.5), nama_str)

            # Checkbox K & BK
            is_k = bool(p_data.get("is_k", False))
            is_bk = bool(p_data.get("is_bk", False))
            cb_sz = 9.0 if row_h < 24.0 else 9.5
            draw_cb(c, (cols[2] + cols[3]) / 2, Y(y_mid_r), is_k, sz=cb_sz)
            draw_cb(c, (cols[3] + cols[4]) / 2, Y(y_mid_r), is_bk, sz=cb_sz)

            # Keterangan
            ket_str = str(p_data.get("keterangan") or "")
            c.setFont("Lib", 8.0)
            c.drawString(cols[4] + 6.0, Y(y_mid_r + 2.5), ket_str)
        else:
            # Blank row matching master BNSP template
            cb_sz = 9.0 if row_h < 24.0 else 9.5
            draw_cb(c, (cols[2] + cols[3]) / 2, Y(y_mid_r), False, sz=cb_sz)
            draw_cb(c, (cols[3] + cols[4]) / 2, Y(y_mid_r), False, sz=cb_sz)

    # Footnote
    c.setFont("Lib-I", 7.5)
    c.drawString(x0, Y(bot_cand + 10.0), "** tuliskan Kode dan Judul Unit Kompetensi yang dinyatakan BK bila mengases satu skema")

    # ================= 4. FINDINGS BLOCK =================
    top_find = bot_cand + 15.0
    y_find_lines = [top_find]
    for h in r_find_h:
        y_find_lines.append(y_find_lines[-1] + h)
    bot_find = y_find_lines[-1]

    c.rect(x0, Y(bot_find), tbl_w, bot_find - top_find)
    c.line(267.8, Y(top_find), 267.8, Y(bot_find))
    for yf in y_find_lines[1:-1]:
        c.line(x0, Y(yf), x1, Y(yf))

    # Row 1: Aspek Negatif dan Positif
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 6.0, Y(top_find + 14.0), "Aspek Negatif dan Positif dalam")
    c.drawString(x0 + 6.0, Y(top_find + 25.0), "Asesmen")

    # Aspek Negatif
    c.setFont("Lib-B", 8.2)
    c.drawString(267.8 + 6.0, Y(top_find + 11.0), "Aspek Negatif :")
    neg_lines = wrap_text(c, data.get("aspek_negatif") or "Tidak ditemukan kendala signifikan selama proses asesmen berlangsung.", "Lib", 7.8, x1 - 274.0)
    c.setFont("Lib", 7.8)
    curr_y = top_find + 21.0
    for l in neg_lines[:2 if n_display > 5 else 3]:
        c.drawString(267.8 + 6.0, Y(curr_y), l)
        curr_y += 9.5

    # Aspek Positif
    pos_top = top_find + (42.0 if n_display > 3 else 48.0)
    c.setFont("Lib-B", 8.2)
    c.drawString(267.8 + 6.0, Y(pos_top), "Aspek Positif :")
    pos_lines = wrap_text(c, data.get("aspek_positif") or "Sarana dan prasarana bengkel TUK lengkap, ketersediaan alat ukur dan APD sesuai standar K3, asesi hadir tepat waktu.", "Lib", 7.8, x1 - 274.0)
    c.setFont("Lib", 7.8)
    curr_y = pos_top + 10.0
    for l in pos_lines[:2 if n_display > 5 else 3]:
        c.drawString(267.8 + 6.0, Y(curr_y), l)
        curr_y += 9.5

    # Row 2: Pencatatan Penolakan
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 6.0, Y(y_find_lines[1] + 15.0), "Pencatatan Penolakan Hasil Asesmen")
    c.setFont("Lib-B", 8.2)
    c.drawString(267.8 + 6.0, Y(y_find_lines[1] + 15.0), "Ada Penolakan :")
    penolakan_text = str(data.get("ada_penolakan") or "Tidak ada penolakan dari asesi atas keputusan asesmen.")
    c.setFont("Lib", 7.8)
    pen_lines = wrap_text(c, penolakan_text, "Lib", 7.8, x1 - 345.0)
    if pen_lines:
        c.drawString(342.0, Y(y_find_lines[1] + 15.0), pen_lines[0])
        if len(pen_lines) > 1:
            c.drawString(267.8 + 6.0, Y(y_find_lines[1] + 25.0), pen_lines[1])

    # Row 3: Saran Perbaikan
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 6.0, Y(y_find_lines[2] + 13.0), "Saran Perbaikan :")
    c.setFont("Lib", 7.8)
    c.drawString(x0 + 6.0, Y(y_find_lines[2] + 23.0), "(Asesor/Personil Terkait)")

    saran_text = str(data.get("saran_perbaikan") or "Mempertahankan konsistensi kalibrasi peralatan tangan dan SST serta peningkatan durasi praktik mandiri.")
    saran_lines = wrap_text(c, saran_text, "Lib", 7.8, x1 - 274.0)
    c.setFont("Lib", 7.8)
    curr_y = y_find_lines[2] + 12.0
    for l in saran_lines[:2]:
        c.drawString(267.8 + 6.0, Y(curr_y), l)
        curr_y += 9.5

    # ================= 5. CATATAN & SIGNATURE =================
    top_sig = bot_find + 11.0
    bot_sig = top_sig + h_sig
    split_sig = 331.0

    c.rect(x0, Y(bot_sig), tbl_w, bot_sig - top_sig)
    c.line(split_sig, Y(top_sig), split_sig, Y(bot_sig))

    # Left Box: Catatan
    c.setFont("Lib-B", 8.8)
    c.drawString(x0 + 8.0, Y(top_sig + 15.0), "Catatan :")
    catatan_text = str(data.get("catatan") or "Laporan asesmen ini dibuat berdasarkan seluruh bukti langsung, tidak langsung, dan tambahan yang dihimpun selama proses asesmen berlangsung di TUK.")
    cat_lines = wrap_text(c, catatan_text, "Lib", 7.8, split_sig - x0 - 16.0)
    c.setFont("Lib", 7.8)
    curr_y = top_sig + 28.0
    for l in cat_lines[:6]:
        c.drawString(x0 + 8.0, Y(curr_y), l)
        curr_y += 10.5

    # Right Box: Asesor Signature
    c.setFont("Lib-B", 8.8)
    c.drawString(split_sig + 8.0, Y(top_sig + 14.0), "Asesor :")

    y_sig_r1 = top_sig + 20.0
    y_sig_r2 = y_sig_r1 + 18.0
    y_sig_r3 = y_sig_r2 + 18.0
    c.line(split_sig, Y(y_sig_r1), x1, Y(y_sig_r1))
    c.line(split_sig, Y(y_sig_r2), x1, Y(y_sig_r2))
    c.line(split_sig, Y(y_sig_r3), x1, Y(y_sig_r3))

    col_colon = split_sig + 78.5
    c.line(col_colon, Y(y_sig_r1), col_colon, Y(bot_sig))

    # Nama Asesor
    c.setFont("Lib", 8.2)
    c.drawString(split_sig + 8.0, Y(y_sig_r1 + 12.0), "Nama")
    c.setFont("Lib-B", 8.2)
    c.drawString(col_colon + 6.0, Y(y_sig_r1 + 12.0), str(data.get("asesor_nama") or "-"))

    # No. Reg Asesor
    c.setFont("Lib", 8.2)
    c.drawString(split_sig + 8.0, Y(y_sig_r2 + 12.0), "No. Reg")
    c.setFont("Lib", 8.2)
    c.drawString(col_colon + 6.0, Y(y_sig_r2 + 12.0), str(data.get("asesor_no_reg") or "-"))

    # Tanda tangan & Tanggal
    c.setFont("Lib", 8.2)
    c.drawString(split_sig + 8.0, Y(y_sig_r3 + 16.0), "Tanda tangan/")
    c.drawString(split_sig + 8.0, Y(y_sig_r3 + 27.0), "Tanggal")

    # Render digital signature image
    sig_drawn = draw_signature(
        c,
        data.get("signature_asesor"),
        col_colon + 10.0,
        Y(bot_sig - 14.0),
        max_w=125.0,
        max_h=42.0,
    )

    # Tanggal bawah tanda tangan
    c.setFont("Lib", 7.8)
    c.drawString(col_colon + 8.0, Y(bot_sig - 5.0), str(data.get("tanggal") or "-"))


def render(dest_path, payload=None):
    """Render full FR.AK.05 document supporting multi-assessor batches (max 10 asesi per sheet)."""
    data = dict(DATA)
    if payload:
        data.update(payload)

    assessors = data.get("assessors")
    if not assessors or not isinstance(assessors, list):
        assessors = [{
            "asesor_nama": data.get("asesor_nama"),
            "asesor_no_reg": data.get("asesor_no_reg"),
            "signature_asesor": data.get("signature_asesor"),
            "peserta": data.get("peserta") or [],
            "aspek_negatif": data.get("aspek_negatif"),
            "aspek_positif": data.get("aspek_positif"),
            "ada_penolakan": data.get("ada_penolakan"),
            "saran_perbaikan": data.get("saran_perbaikan"),
            "catatan": data.get("catatan"),
        }]

    # Build list of page tasks
    page_tasks = []
    chunk_size = 10

    for as_data in assessors:
        merged_data = dict(data)
        merged_data.update(as_data)

        cand_list = list(as_data.get("peserta") or [])
        if len(cand_list) > chunk_size:
            chunks = [cand_list[i:i + chunk_size] for i in range(0, len(cand_list), chunk_size)]
        else:
            chunks = [cand_list]

        for chunk in chunks:
            page_tasks.append({
                "data": merged_data,
                "peserta_chunk": chunk,
            })

    total_pages = len(page_tasks)
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))

    for idx, task in enumerate(page_tasks):
        render_page(c, page_peserta=task["peserta_chunk"], page_idx=idx, total_pages=total_pages, data=task["data"])
        c.showPage()

    c.save()
    print(f"OK -> Sukses generate FR.AK.05 Laporan Asesmen ({total_pages} halaman, {len(assessors)} asesor): {dest_path}")


if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_AK_05_generated.pdf")
    if len(sys.argv) > 1:
        if sys.argv[1].endswith(".json") and os.path.isfile(sys.argv[1]):
            with open(sys.argv[1], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        else:
            out_file = sys.argv[1]
    render(out_file)

