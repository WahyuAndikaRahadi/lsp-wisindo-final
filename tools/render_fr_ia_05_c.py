#!/usr/bin/env python3
"""
Generator FR.IA.05.C (DPT - Lembar Jawaban Pilihan Ganda) - LSP PrO Indo
Layout meniru template master BNSP secara presisi piksel; tanpa watermark.
Paginasi persis 2 halaman sesuai master dokumen BNSP.
Mendukung input dinamis JSON untuk data asesi, asesor, skema, TUK, jawaban CBT (hanya abjad A, B, C, D),
dan checklist pencapaian Ya (Benar) / Tidak (Salah).
"""

import os
import sys
import json
import io
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

# ----------------- FONT REGISTRATION -----------------
def register_fonts():
    try:
        from font_helper import find_font
    except ImportError:
        sys.path.insert(0, os.path.dirname(__file__))
        from font_helper import find_font

    reg = find_font("Carlito-Regular.ttf", fallback="LiberationSans-Regular.ttf")
    bold = find_font("Carlito-Bold.ttf", fallback="LiberationSans-Bold.ttf")
    italic = find_font("Carlito-Italic.ttf", fallback="LiberationSans-Italic.ttf")

    if os.path.isfile(reg):
        pdfmetrics.registerFont(TTFont("Lib", reg))
        pdfmetrics.registerFont(TTFont("Cal", reg))
    else:
        pdfmetrics.registerFont(TTFont("Lib", "Helvetica"))
        pdfmetrics.registerFont(TTFont("Cal", "Helvetica"))

    if os.path.isfile(bold):
        pdfmetrics.registerFont(TTFont("Lib-B", bold))
        pdfmetrics.registerFont(TTFont("Cal-B", bold))
    else:
        pdfmetrics.registerFont(TTFont("Lib-B", "Helvetica-Bold"))
        pdfmetrics.registerFont(TTFont("Cal-B", "Helvetica-Bold"))

    if os.path.isfile(italic):
        pdfmetrics.registerFont(TTFont("Lib-I", italic))
        pdfmetrics.registerFont(TTFont("Cal-I", italic))
    else:
        pdfmetrics.registerFont(TTFont("Lib-I", "Helvetica-Oblique"))
        pdfmetrics.registerFont(TTFont("Cal-I", "Helvetica-Oblique"))

register_fonts()

PAGE_W, PAGE_H = 596.0, 843.0
Y = lambda top: PAGE_H - top

def clean_signature_image(raw_bytes_or_path):
    """Load signature and ensure strokes are solid, bold, and crisp on transparent background."""
    if not raw_bytes_or_path:
        return None
    try:
        im = None
        if isinstance(raw_bytes_or_path, str):
            raw_str = raw_bytes_or_path.strip()
            if not raw_str:
                return None
            if "base64," in raw_str:
                base64_data = raw_str.split("base64,", 1)[1].strip()
                img_data = base64.b64decode(base64_data)
                im = Image.open(io.BytesIO(img_data)).convert("RGBA")
            elif os.path.isfile(raw_str):
                im = Image.open(raw_str).convert("RGBA")
            elif os.path.isfile(os.path.join(PROJECT_ROOT, raw_str)):
                im = Image.open(os.path.join(PROJECT_ROOT, raw_str)).convert("RGBA")
            elif os.path.isfile(os.path.join(PROJECT_ROOT, "storage", "app", "public", raw_str)):
                im = Image.open(os.path.join(PROJECT_ROOT, "storage", "app", "public", raw_str)).convert("RGBA")
            else:
                try:
                    img_data = base64.b64decode(raw_str)
                    im = Image.open(io.BytesIO(img_data)).convert("RGBA")
                except Exception:
                    return None
        elif isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path)).convert("RGBA")
        else:
            return None

        if im is None:
            return None

        w, h = im.size
        if w == 0 or h == 0:
            return None

        pixels = im.load()
        corners_transparent = all(
            pixels[pt][3] < 50 for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1))
        )

        if corners_transparent:
            for y in range(h):
                for x in range(w):
                    r, g, b, a = pixels[x, y]
                    if a > 20:
                        pixels[x, y] = (10, 15, 30, min(255, int(a * 1.5)))
                    else:
                        pixels[x, y] = (0, 0, 0, 0)
        else:
            corners = [pixels[pt][:3] for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1))]
            bg_r = sum(c[0] for c in corners) / len(corners)
            bg_g = sum(c[1] for c in corners) / len(corners)
            bg_b = sum(c[2] for c in corners) / len(corners)

            for y in range(h):
                for x in range(w):
                    r, g, b, a = pixels[x, y]
                    dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5
                    if dist_bg < 45 or (r > 215 and g > 215 and b > 215):
                        pixels[x, y] = (0, 0, 0, 0)
                    else:
                        pixels[x, y] = (10, 15, 30, min(255, int(a * 1.5)))

        bbox = im.getbbox()
        if bbox:
            pad = 2
            im = im.crop((max(0, bbox[0] - pad), max(0, bbox[1] - pad), min(w, bbox[2] + pad), min(h, bbox[3] + pad)))

        out = io.BytesIO()
        im.save(out, format="PNG")
        return out.getvalue()
    except Exception:
        return None

def draw_signature_block(c, sig_data, x_start, y_top, row_height, date_str):
    """Draw signature image cleanly via ImageReader or fallback text with date."""
    sig_bytes = clean_signature_image(sig_data)
    if sig_bytes:
        try:
            img = ImageReader(io.BytesIO(sig_bytes))
            iw, ih = img.getSize()
            if iw > 0 and ih > 0:
                max_h = min(row_height - 6.0, 34.0)
                max_w = 115.0
                scale = min(max_w / iw, max_h / ih)
                w_pt = iw * scale
                h_pt = ih * scale
                # Center vertically within the row:
                # In ReportLab, row bottom is Y(y_top + row_height).
                # y_bottom for drawImage is row bottom + vertical padding.
                v_pad = (row_height - h_pt) / 2.0
                y_bottom = Y(y_top + row_height) + v_pad
                c.drawImage(img, x_start, y_bottom, width=w_pt, height=h_pt, mask="auto")
                c.setFont("Lib", 8.5)
                c.drawString(x_start + w_pt + 12.0, Y(y_top + row_height / 2.0 + 3.0), f"/  {date_str}")
                return True
        except Exception:
            pass

    c.setFont("Lib", 8.5)
    c.drawString(x_start, Y(y_top + row_height / 2.0 + 3.0), f"[Tanda Tangan Digital]   -   {date_str}")
    return False

def draw_header_box(c, x0, x1, top, bot):
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    x_div = 147.7
    c.line(x_div, Y(top), x_div, Y(bot))
    logo_file = LOGO_PATH
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, Y(bot) + 6.0, 65.0, 50.0, mask="auto")
    c.setFont("Lib-B", 11.5)
    c.setFillColor(black)
    c.drawString(x_div + 18.0, Y(bot) + 26.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

def draw_cb(c, cx, cy, checked):
    """Draw checkbox with crisp vector checkmark if checked."""
    sz = 8.5
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz)
    if checked:
        c.setLineWidth(1.1)
        c.setStrokeColor(black)
        p = c.beginPath()
        p.moveTo(cx - 2.5, cy)
        p.lineTo(cx - 0.5, cy - 2.3)
        p.lineTo(cx + 3.0, cy + 2.5)
        c.drawPath(p, stroke=1, fill=0)
        c.setLineWidth(0.7)

def render(data, dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))

    skema_nama = str(data.get("skema_nama") or "Maintenance Gasoline Engine Injection System")
    skema_nomor = str(data.get("skema_nomor") or "02/LSP PrO Indo/I/2026")
    tuk_tipe = str(data.get("tuk_nama") or data.get("tuk_tipe") or "Sewaktu/Tempat Kerja/Mandiri*")
    asesor_nama = str(data.get("asesor_nama") or "-")
    asesor_no_reg = str(data.get("asesor_no_reg") or "-")
    asesi_nama = str(data.get("asesi_nama") or "-")
    tanggal = str(data.get("tanggal") or "-")
    waktu = str(data.get("waktu") or "60 Menit")
    umpan_balik = str(data.get("umpan_balik") or "")
    tanggal_ttd = str(data.get("tanggal_ttd") or tanggal)
    is_passed = bool(data.get("is_passed", True))

    answers_raw = data.get("answers", {})
    # answers can be dict { "1": {"answer": "A", "is_correct": True, "is_active": True}, ... }
    # or { "1": ("A", True), ... }

    # ================= PAGE 1 =================
    x0, x1 = 50.4, 535.7
    draw_header_box(c, x0, x1, 13.2, 76.7)

    # Title
    c.setFont("Lib-B", 11.5)
    c.drawString(72.2, Y(98.0), "FR.IA.05.C. DPT - LEMBAR JAWABAN PILIHAN GANDA")

    # Table 2: Metadata (top=121.3 to 244.4)
    # Left = 72.2, Right = 568.1
    L, R = 72.2, 568.1
    c1, c2, c3 = 191.7, 244.8, 259.2

    # Row Y: 121.3, 141.8, 162.3, 182.8, 203.3, 223.8, 244.4
    r_y = [121.3, 141.8, 162.3, 182.8, 203.3, 223.8, 244.4]
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(r_y[-1]), R - L, r_y[-1] - r_y[0])

    for y in r_y[1:-1]:
        c.line(L, Y(y), R, Y(y))

    c.line(c1, Y(r_y[2]), c1, Y(r_y[0]))
    c.line(c2, Y(r_y[-1]), c2, Y(r_y[0]))
    c.line(c3, Y(r_y[-1]), c3, Y(r_y[0]))

    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(133.0), "Skema Sertifikasi")
    c.drawString(L + 6.0, Y(153.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(c1 + 6.0, Y(133.0), "Judul")
    c.drawString(c2 + 4.0, Y(133.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(133.0), skema_nama[:55])

    c.setFont("Lib", 8.5)
    c.drawString(c1 + 6.0, Y(153.0), "Nomor")
    c.drawString(c2 + 4.0, Y(153.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(153.0), skema_nomor)

    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(174.0), "TUK")
    c.drawString(c2 + 4.0, Y(174.0), ":")
    c.drawString(c3 + 6.0, Y(174.0), tuk_tipe)

    c.drawString(L + 6.0, Y(194.0), "Nama Asesor")
    c.drawString(c2 + 4.0, Y(194.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(194.0), asesor_nama)

    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(215.0), "Nama Asesi")
    c.drawString(c2 + 4.0, Y(215.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(215.0), asesi_nama)

    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(235.0), "Tanggal")
    c.drawString(c2 + 4.0, Y(235.0), ":")
    c.drawString(c3 + 6.0, Y(235.0), f"{tanggal}   (Waktu: {waktu})")

    c.setFont("Lib-I", 8)
    c.drawString(L, Y(255.0), "*Coret yang tidak perlu")
    c.setFont("Lib-B", 9)
    c.drawString(L, Y(274.0), "Lembar Jawaban Pertanyaan Tertulis – Pilihan Ganda :")

    # Tables 3 & 4: Answer Grid (top=286.2 to 703.8)
    # T3 (Left: 1-15): x0=72.2 to 313.5
    # T4 (Right: 16-30): x0=327.7 to 568.1
    top_grid = 286.2
    bot_grid = 703.8
    h_hdr1 = 15.0
    h_hdr2 = 15.0
    h_row = (bot_grid - (top_grid + h_hdr1 + h_hdr2)) / 15

    def render_grid_col(x_start, x_end, q_start):
        col_w = x_end - x_start
        c_no = x_start + 28.0
        c_ans = x_start + 140.0
        c_ya = c_ans + 45.0

        # Outer
        c.rect(x_start, Y(bot_grid), col_w, bot_grid - top_grid)
        # Header hlines
        c.line(x_start, Y(top_grid + h_hdr1), x_end, Y(top_grid + h_hdr1))
        c.line(x_start, Y(top_grid + h_hdr1 + h_hdr2), x_end, Y(top_grid + h_hdr1 + h_hdr2))

        # Header vlines
        c.line(c_no, Y(bot_grid), c_no, Y(top_grid))
        c.line(c_ans, Y(bot_grid), c_ans, Y(top_grid))
        c.line(c_ya, Y(bot_grid), c_ya, Y(top_grid + h_hdr1))

        # Header labels
        c.setFont("Lib-B", 8.5)
        c.drawCentredString((x_start + c_no) / 2, Y(top_grid + 20.0), "No.")
        c.drawCentredString((c_no + c_ans) / 2, Y(top_grid + 20.0), "Jawaban")
        c.drawCentredString((c_ans + x_end) / 2, Y(top_grid + 11.0), "Pencapaian")
        c.drawCentredString((c_ans + c_ya) / 2, Y(top_grid + 25.0), "Ya")
        c.drawCentredString((c_ya + x_end) / 2, Y(top_grid + 25.0), "Tidak")

        # Rows
        curr_y = top_grid + h_hdr1 + h_hdr2
        for i in range(15):
            q_num = q_start + i
            next_y = curr_y + h_row
            c.line(x_start, Y(next_y), x_end, Y(next_y))

            # Number
            c.setFont("Lib", 8.5)
            c.drawCentredString((x_start + c_no) / 2, Y((curr_y + next_y) / 2 + 3.0), f"{q_num}.")

            # Answer info lookup
            ans_info = answers_raw.get(str(q_num)) or answers_raw.get(q_num)
            ans_val = ""
            is_correct = None
            is_active = False

            if ans_info is not None:
                if isinstance(ans_info, dict):
                    raw_val = ans_info.get("answer")
                    is_correct = ans_info.get("is_correct")
                    is_active = ans_info.get("is_active", True)
                elif isinstance(ans_info, (list, tuple)) and len(ans_info) >= 2:
                    raw_val = ans_info[0]
                    is_correct = ans_info[1]
                    is_active = True
                else:
                    raw_val = str(ans_info)
                    is_correct = None
                    is_active = True

                if raw_val is not None:
                    cleaned_val = str(raw_val).strip().upper()
                    # User requested: "hanya abjad saja seperti a b c atau d"
                    if len(cleaned_val) == 1 and cleaned_val in "ABCD":
                        ans_val = cleaned_val
                    elif cleaned_val.startswith(("A", "B", "C", "D")) and (len(cleaned_val) == 1 or cleaned_val[1] in ".) "):
                        ans_val = cleaned_val[0]
                    elif cleaned_val:
                        ans_val = cleaned_val[:1]

            # Render alphabet answer
            if ans_val:
                c.setFont("Lib-B", 9.5)
                c.drawCentredString((c_no + c_ans) / 2, Y((curr_y + next_y) / 2 + 3.2), ans_val)

            # Checkbox Ya (Benar) / Tidak (Salah)
            if is_active:
                draw_cb(c, (c_ans + c_ya) / 2, Y((curr_y + next_y) / 2), is_correct is True)
                draw_cb(c, (c_ya + x_end) / 2, Y((curr_y + next_y) / 2), is_correct is False)
            else:
                # Beyond candidate test question count: both checkboxes remain blank
                draw_cb(c, (c_ans + c_ya) / 2, Y((curr_y + next_y) / 2), False)
                draw_cb(c, (c_ya + x_end) / 2, Y((curr_y + next_y) / 2), False)

            curr_y = next_y

    render_grid_col(72.2, 313.5, 1)
    render_grid_col(327.7, 568.1, 16)

    c.showPage()

    # ================= PAGE 2 =================
    draw_header_box(c, 50.4, 535.7, 13.2, 76.7)

    # Table 2: Feedback & Signatures (top=92.6 to 344.1)
    # x0=73.4, x1=570.0
    L2, R2 = 73.4, 570.0
    c_split = 200.0
    c_colon = 215.0

    # Rows: 92.6, 175.5, 189.1, 209.1, 252.0, 266.4, 285.4, 304.1, 344.1
    p2_r = [92.6, 175.5, 189.1, 209.1, 252.0, 266.4, 285.4, 304.1, 344.1]

    c.rect(L2, Y(p2_r[-1]), R2 - L2, p2_r[-1] - p2_r[0])
    for y in p2_r[1:-1]:
        c.line(L2, Y(y), R2, Y(y))

    c.line(c_split, Y(p2_r[1]), c_split, Y(p2_r[0]))
    c.line(c_colon, Y(p2_r[1]), c_colon, Y(p2_r[0]))

    c.line(c_split, Y(p2_r[4]), c_split, Y(p2_r[2]))
    c.line(c_colon, Y(p2_r[4]), c_colon, Y(p2_r[2]))

    c.line(c_split, Y(p2_r[8]), c_split, Y(p2_r[5]))
    c.line(c_colon, Y(p2_r[8]), c_colon, Y(p2_r[5]))

    # r0: Feedback
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(112.0), "Umpan balik untuk asesi")
    c.drawString(c_split + 4.0, Y(112.0), ":")
    c.setFont("Lib", 8.5)
    c.drawString(c_colon + 6.0, Y(112.0), "Aspek pengetahuan seluruh unit pada kelompok pekerjaan yang")

    # Mark tercapai / belum tercapai based on test result
    if is_passed:
        c.drawString(c_colon + 6.0, Y(126.0), "diujikan ")
        w_pfx = c.stringWidth("diujikan ", "Lib", 8.5)
        c.setFont("Lib-B", 8.5)
        c.drawString(c_colon + 6.0 + w_pfx, Y(126.0), "TERCAPAI")
        w_ter = c.stringWidth("TERCAPAI", "Lib-B", 8.5)
        c.setFont("Lib", 8.5)
        c.drawString(c_colon + 6.0 + w_pfx + w_ter + 4.0, Y(126.0), "/ belum tercapai*")
        c.line(c_colon + 6.0 + w_pfx + w_ter + 10.0, Y(123.0), c_colon + 6.0 + w_pfx + w_ter + 72.0, Y(123.0))
    else:
        c.drawString(c_colon + 6.0, Y(126.0), "diujikan tercapai / ")
        w_pfx = c.stringWidth("diujikan tercapai / ", "Lib", 8.5)
        c.setFont("Lib-B", 8.5)
        c.drawString(c_colon + 6.0 + w_pfx, Y(126.0), "BELUM TERCAPAI*")
        c.line(c_colon + 6.0 + 38.0, Y(123.0), c_colon + 6.0 + 74.0, Y(123.0))

    c.setFont("Lib", 8.5)
    if umpan_balik:
        fb_clean = umpan_balik.replace("\n", " ")
        c.drawString(c_colon + 6.0, Y(145.0), f"Catatan Asesor: {fb_clean[:65]}")
        if len(fb_clean) > 65:
            c.drawString(c_colon + 6.0, Y(158.0), fb_clean[65:130])
    else:
        c.drawString(c_colon + 6.0, Y(145.0), "Tuliskan unit/elemen/KUK jika belum tercapai: -")

    # r1: Asesi Header
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(185.0), "Asesi :")

    # r2: Nama Asesi
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(202.0), "Nama")
    c.drawString(c_split + 4.0, Y(202.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_colon + 6.0, Y(202.0), asesi_nama)

    # r3: TTD Asesi
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(233.0), "Tanda tangan/Tanggal")
    c.drawString(c_split + 4.0, Y(233.0), ":")
    draw_signature_block(c, data.get("signature_asesi"), c_colon + 6.0, 209.1, 42.9, tanggal_ttd)

    # r4: Asesor Header
    c.setFont("Lib-B", 8.5)
    c.drawString(L2 + 6.0, Y(262.0), "Asesor :")

    # r5: Nama Asesor
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(278.0), "Nama")
    c.drawString(c_split + 4.0, Y(278.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c_colon + 6.0, Y(278.0), asesor_nama)

    # r6: No Reg
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(297.0), "No. Reg")
    c.drawString(c_split + 4.0, Y(297.0), ":")
    c.drawString(c_colon + 6.0, Y(297.0), asesor_no_reg)

    # r7: TTD Asesor
    c.setFont("Lib", 8.5)
    c.drawString(L2 + 6.0, Y(326.0), "Tanda tangan/ Tanggal")
    c.drawString(c_split + 4.0, Y(326.0), ":")
    draw_signature_block(c, data.get("signature_asesor"), c_colon + 6.0, 304.1, 40.0, tanggal_ttd)

    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.IA.05.C: {dest_path}")

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "waktu": "08.00 - 09.00 WIB (60 Menit)",
    "answers": {
        "1": {"answer": "A", "is_correct": True, "is_active": True},
        "2": {"answer": "C", "is_correct": True, "is_active": True},
        "3": {"answer": "B", "is_correct": True, "is_active": True},
        "4": {"answer": "D", "is_correct": True, "is_active": True},
        "5": {"answer": "A", "is_correct": True, "is_active": True},
        "6": {"answer": "C", "is_correct": True, "is_active": True},
        "7": {"answer": "B", "is_correct": True, "is_active": True},
        "8": {"answer": "A", "is_correct": True, "is_active": True},
        "9": {"answer": "D", "is_correct": True, "is_active": True},
        "10": {"answer": "B", "is_correct": True, "is_active": True},
        "11": {"answer": "C", "is_correct": True, "is_active": True},
        "12": {"answer": "A", "is_correct": True, "is_active": True},
        "13": {"answer": "D", "is_correct": True, "is_active": True},
        "14": {"answer": "B", "is_correct": True, "is_active": True},
        "15": {"answer": "C", "is_correct": True, "is_active": True},
        "16": {"answer": "A", "is_correct": True, "is_active": True},
        "17": {"answer": "D", "is_correct": True, "is_active": True},
        "18": {"answer": "B", "is_correct": True, "is_active": True},
        "19": {"answer": "C", "is_correct": True, "is_active": True},
        "20": {"answer": "A", "is_correct": True, "is_active": True},
    },
    "umpan_balik": "Aspek pengetahuan seluruh unit pada kelompok pekerjaan yang diujikan tercapai.",
    "tanggal_ttd": "19 Oktober 2026",
    "is_passed": True,
}

def main():
    if len(sys.argv) < 3:
        out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
        os.makedirs(out_dir, exist_ok=True)
        out_file = os.path.join(out_dir, "FR_IA_05_C_generated.pdf")
        render(DATA, out_file)
        return

    json_arg = sys.argv[1]
    dest_path = sys.argv[2]

    if os.path.isfile(json_arg):
        with open(json_arg, "r", encoding="utf-8") as f:
            data = json.load(f)
    else:
        data = json.loads(json_arg)

    render(data, dest_path)

if __name__ == "__main__":
    main()
