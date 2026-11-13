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
    top_hdr = 36.0
    bot_hdr = 101.3
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

    # Header Title
    c.setFont("Lib-B", 10.5)
    c.setFillColor(black)
    c.drawCentredString((166.9 + x1) / 2, Y(68.5), "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

    # Document Code & Title
    c.setFont("Lib-B", 11.5)
    c.drawString(x0, Y(118.0), "FR.AK.05.")
    c.drawString(143.9, Y(118.0), "LAPORAN ASESMEN")
    if total_pages > 1:
        c.setFont("Lib", 8.5)
        c.drawRightString(x1, Y(118.0), f"Halaman {page_idx + 1} dari {total_pages}")

    # ================= 2. META GRID =================
    top_meta = 137.3
    r_meta = [137.3, 164.1, 188.2, 209.4, 230.9, 252.6]
    c.rect(x0, Y(r_meta[-1]), tbl_w, r_meta[-1] - r_meta[0])
    for ym in r_meta[1:-1]:
        c.line(x0, Y(ym), x1, Y(ym))

    # Vertical dividers in meta grid
    c.line(203.8, Y(r_meta[2]), 203.8, Y(r_meta[0]))
    c.line(260.5, Y(r_meta[-1]), 260.5, Y(r_meta[0]))

    # Row 1: Skema Sertifikasi
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(158.0), "Skema Sertifikasi")
    skema_tipe = data.get("skema_tipe") or data.get("scheme_type") or "KLASTER"
    draw_kkni_options(c, x0 + 8.5, Y(172.5), font_name="Lib", font_size=8.0, scheme_type=skema_tipe)

    c.setFont("Lib-B", 8.5)
    skema_judul = str(data.get("skema_nama") or "-")
    skema_lines = wrap_text(c, skema_judul, "Lib-B", 8.5, x1 - 275.0)
    if len(skema_lines) == 1:
        c.drawString(203.8 + 8.0, Y(153.0), "Judul")
        c.drawCentredString(267.0, Y(153.0), ":")
        c.drawString(275.0, Y(153.0), skema_lines[0])
    elif len(skema_lines) >= 2:
        c.drawString(203.8 + 8.0, Y(148.0), "Judul")
        c.drawCentredString(267.0, Y(148.0), ":")
        c.drawString(275.0, Y(148.0), skema_lines[0])
        c.drawString(275.0, Y(159.0), skema_lines[1])

    c.setFont("Lib-B", 8.5)
    c.drawString(203.8 + 8.0, Y(178.5), "Nomor")
    c.drawCentredString(267.0, Y(178.5), ":")
    c.setFont("Lib", 8.5)
    c.drawString(275.0, Y(178.5), str(data.get("skema_nomor") or "-"))

    # Row 2: TUK
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(201.0), "TUK")
    c.drawCentredString(267.0, Y(201.0), ":")
    c.setFont("Lib", 8.5)
    tuk_display = str(data.get("tuk_nama") or "")
    if tuk_display and not tuk_display.startswith("Sewaktu"):
        tuk_text = f"Sewaktu/Tempat Kerja/Mandiri* ({tuk_display})"
    else:
        tuk_text = tuk_display or "Sewaktu/Tempat Kerja/Mandiri*"
    c.drawString(275.0, Y(201.0), tuk_text)

    # Row 3: Nama Asesor
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(222.5), "Nama Asesor")
    c.drawCentredString(267.0, Y(222.5), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(275.0, Y(222.5), str(data.get("asesor_nama") or "-"))

    # Row 4: Tanggal
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.5, Y(244.0), "Tanggal")
    c.drawCentredString(267.0, Y(244.0), ":")
    c.setFont("Lib", 8.5)
    c.drawString(275.0, Y(244.0), str(data.get("tanggal") or "-"))

    # ================= 3. CANDIDATES TABLE =================
    top_cand = 267.6
    hdr_h1 = 22.0
    hdr_h2 = 29.4
    hdr_h = hdr_h1 + hdr_h2  # 51.4

    cols = [x0, 105.2, 282.5, 325.0, 374.6, x1]
    n_display = max(3, len(page_peserta))
    row_h = 34.3 if n_display <= 3 else max(24.0, (140.0 / n_display))
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
    c.setFont("Lib-B", 9.0)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y(top_cand + 28.0), "No.")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y(top_cand + 28.0), "Nama Asesi")
    c.drawCentredString((cols[2] + cols[4]) / 2, Y(top_cand + 14.5), "Rekomendasi")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y(top_cand + hdr_h1 + 18.0), "K")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y(top_cand + hdr_h1 + 18.0), "BK")
    c.drawCentredString((cols[4] + cols[5]) / 2, Y(top_cand + 28.0), "Keterangan**")

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
        c.setFont("Lib", 9.0)
        c.drawCentredString((cols[0] + cols[1]) / 2, Y(y_mid_r + 3.0), f"{cand_no}.")

        if p_data:
            # Nama asesi
            nama_str = str(p_data.get("nama") or "")
            c.setFont("Lib-B", 9.0)
            c.drawString(cols[1] + 6.0, Y(y_mid_r + 3.0), nama_str)

            # Checkbox K & BK
            is_k = bool(p_data.get("is_k", False))
            is_bk = bool(p_data.get("is_bk", False))
            draw_cb(c, (cols[2] + cols[3]) / 2, Y(y_mid_r), is_k, sz=10.0)
            draw_cb(c, (cols[3] + cols[4]) / 2, Y(y_mid_r), is_bk, sz=10.0)

            # Keterangan
            ket_str = str(p_data.get("keterangan") or "")
            c.setFont("Lib", 8.5)
            c.drawString(cols[4] + 6.0, Y(y_mid_r + 3.0), ket_str)
        else:
            # Blank row matching master BNSP template
            draw_cb(c, (cols[2] + cols[3]) / 2, Y(y_mid_r), False, sz=10.0)
            draw_cb(c, (cols[3] + cols[4]) / 2, Y(y_mid_r), False, sz=10.0)

    # Footnote
    c.setFont("Lib-I", 7.8)
    c.drawString(x0, Y(bot_cand + 11.5), "** tuliskan Kode dan Judul Unit Kompetensi yang dinyatakan BK bila mengases satu skema")

    # ================= 4. FINDINGS BLOCK =================
    # Position findings block directly below candidate table
    top_find = bot_cand + 18.0
    r_find_h = [93.7, 39.5, 38.5]
    y_find_lines = [top_find]
    for h in r_find_h:
        y_find_lines.append(y_find_lines[-1] + h)
    bot_find = y_find_lines[-1]

    c.rect(x0, Y(bot_find), tbl_w, bot_find - top_find)
    c.line(267.8, Y(top_find), 267.8, Y(bot_find))
    for yf in y_find_lines[1:-1]:
        c.line(x0, Y(yf), x1, Y(yf))

    # Row 1: Aspek Negatif dan Positif
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 6.0, Y(top_find + 16.0), "Aspek Negatif dan Positif dalam")
    c.drawString(x0 + 6.0, Y(top_find + 28.0), "Asesmen")

    # Aspek Negatif
    c.setFont("Lib-B", 8.5)
    c.drawString(267.8 + 6.0, Y(top_find + 12.0), "Aspek Negatif :")
    neg_lines = wrap_text(c, data.get("aspek_negatif") or "Tidak ditemukan kendala signifikan selama proses asesmen berlangsung.", "Lib", 8.0, x1 - 274.0)
    c.setFont("Lib", 8.0)
    curr_y = top_find + 23.0
    for l in neg_lines[:3]:
        c.drawString(267.8 + 6.0, Y(curr_y), l)
        curr_y += 10.5

    # Aspek Positif
    c.setFont("Lib-B", 8.5)
    c.drawString(267.8 + 6.0, Y(top_find + 55.0), "Aspek Positif :")
    pos_lines = wrap_text(c, data.get("aspek_positif") or "Sarana dan prasarana bengkel TUK lengkap, ketersediaan alat ukur dan APD sesuai standar K3, asesi hadir tepat waktu.", "Lib", 8.0, x1 - 274.0)
    c.setFont("Lib", 8.0)
    curr_y = top_find + 66.0
    for l in pos_lines[:3]:
        c.drawString(267.8 + 6.0, Y(curr_y), l)
        curr_y += 10.5

    # Row 2: Pencatatan Penolakan
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 6.0, Y(y_find_lines[1] + 16.0), "Pencatatan Penolakan Hasil Asesmen")
    c.setFont("Lib-B", 8.5)
    c.drawString(267.8 + 6.0, Y(y_find_lines[1] + 16.0), "Ada Penolakan :")
    penolakan_text = str(data.get("ada_penolakan") or "Tidak ada penolakan dari asesi atas keputusan asesmen.")
    c.setFont("Lib", 8.0)
    pen_lines = wrap_text(c, penolakan_text, "Lib", 8.0, x1 - 345.0)
    if pen_lines:
        c.drawString(342.0, Y(y_find_lines[1] + 16.0), pen_lines[0])
        if len(pen_lines) > 1:
            c.drawString(267.8 + 6.0, Y(y_find_lines[1] + 28.0), pen_lines[1])

    # Row 3: Saran Perbaikan
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 6.0, Y(y_find_lines[2] + 14.0), "Saran Perbaikan :")
    c.setFont("Lib", 8.0)
    c.drawString(x0 + 6.0, Y(y_find_lines[2] + 25.0), "(Asesor/Personil Terkait)")

    saran_text = str(data.get("saran_perbaikan") or "Mempertahankan konsistensi kalibrasi peralatan tangan dan SST serta peningkatan durasi praktik mandiri.")
    saran_lines = wrap_text(c, saran_text, "Lib", 8.0, x1 - 274.0)
    c.setFont("Lib", 8.0)
    curr_y = y_find_lines[2] + 13.0
    for l in saran_lines[:3]:
        c.drawString(267.8 + 6.0, Y(curr_y), l)
        curr_y += 10.5

    # ================= 5. CATATAN & SIGNATURE =================
    top_sig = bot_find + 13.5
    bot_sig = min(PAGE_H - 35.0, top_sig + 138.0)
    split_sig = 331.0

    c.rect(x0, Y(bot_sig), tbl_w, bot_sig - top_sig)
    c.line(split_sig, Y(top_sig), split_sig, Y(bot_sig))

    # Left Box: Catatan
    c.setFont("Lib-B", 9.0)
    c.drawString(x0 + 8.0, Y(top_sig + 16.0), "Catatan :")
    catatan_text = str(data.get("catatan") or "Laporan asesmen ini dibuat berdasarkan seluruh bukti langsung, tidak langsung, dan tambahan yang dihimpun selama proses asesmen berlangsung di TUK.")
    cat_lines = wrap_text(c, catatan_text, "Lib", 8.0, split_sig - x0 - 16.0)
    c.setFont("Lib", 8.0)
    curr_y = top_sig + 30.0
    for l in cat_lines[:6]:
        c.drawString(x0 + 8.0, Y(curr_y), l)
        curr_y += 11.5

    # Right Box: Asesor Signature
    c.setFont("Lib-B", 9.0)
    c.drawString(split_sig + 8.0, Y(top_sig + 15.0), "Asesor :")

    y_sig_r1 = top_sig + 22.5
    y_sig_r2 = y_sig_r1 + 19.0
    y_sig_r3 = y_sig_r2 + 19.0
    c.line(split_sig, Y(y_sig_r1), x1, Y(y_sig_r1))
    c.line(split_sig, Y(y_sig_r2), x1, Y(y_sig_r2))
    c.line(split_sig, Y(y_sig_r3), x1, Y(y_sig_r3))

    col_colon = split_sig + 78.5
    c.line(col_colon, Y(y_sig_r1), col_colon, Y(bot_sig))

    # Nama Asesor
    c.setFont("Lib", 8.5)
    c.drawString(split_sig + 8.0, Y(y_sig_r1 + 13.0), "Nama")
    c.setFont("Lib-B", 8.5)
    c.drawString(col_colon + 6.0, Y(y_sig_r1 + 13.0), str(data.get("asesor_nama") or "-"))

    # No. Reg Asesor
    c.setFont("Lib", 8.5)
    c.drawString(split_sig + 8.0, Y(y_sig_r2 + 13.0), "No. Reg")
    c.setFont("Lib", 8.5)
    c.drawString(col_colon + 6.0, Y(y_sig_r2 + 13.0), str(data.get("asesor_no_reg") or "-"))

    # Tanda tangan & Tanggal
    c.setFont("Lib", 8.5)
    c.drawString(split_sig + 8.0, Y(y_sig_r3 + 18.0), "Tanda tangan/")
    c.drawString(split_sig + 8.0, Y(y_sig_r3 + 30.0), "Tanggal")

    # Render digital signature image
    sig_drawn = draw_signature(
        c,
        data.get("signature_asesor"),
        col_colon + 12.0,
        Y(bot_sig - 16.0),
        max_w=130.0,
        max_h=46.0,
    )

    # Tanggal bawah tanda tangan
    c.setFont("Lib", 8.0)
    c.drawString(col_colon + 8.0, Y(bot_sig - 6.0), str(data.get("tanggal") or "-"))


def render(dest_path, payload=None):
    """Render full FR.AK.05 document supporting dynamic candidate batches."""
    data = dict(DATA)
    if payload:
        data.update(payload)

    peserta_list = list(data.get("peserta") or [])

    # If > 10 candidates, chunk into batches of 10
    chunk_size = 10
    if len(peserta_list) > chunk_size:
        chunks = [peserta_list[i:i + chunk_size] for i in range(0, len(peserta_list), chunk_size)]
    else:
        chunks = [peserta_list]

    total_pages = len(chunks)
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))

    for idx, chunk in enumerate(chunks):
        render_page(c, chunk, idx, total_pages, data)
        c.showPage()

    c.save()
    print(f"OK -> Sukses generate FR.AK.05 Laporan Asesmen: {dest_path}")


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

