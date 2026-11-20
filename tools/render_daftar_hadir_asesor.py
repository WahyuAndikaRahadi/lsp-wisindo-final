#!/usr/bin/env python3
"""
Generator 2.4 DAFTAR HADIR ASESOR - LSP PrO Indo
Layout meniru template master BNSP aslinya secara presisi piksel; tanpa watermark.
Mendukung rendering data dinamis (alamat otomatis wrapping, nama asesor, No. MET, tanggal uji, jumlah asesi)
dan tanda tangan digital Asesor serta Penanggung Jawab TUK.
Mendukung multi-batch (5 asesor per halaman) jika terdapat lebih dari 5 asesor dalam satu jadwal.
"""

import os
import sys
import io
import json
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

register_reportlab_fonts(["Lib", "Lib-B", "Lib-I"])

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

def draw_signature(c, sig_data, x_left, y_bottom, max_w=100.0, max_h=30.0):
    if not sig_data:
        return False
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
            return False

        cleaned = clean_signature_image(raw)
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

PAGE_W, PAGE_H = 596.0, 842.0
Y = lambda top: PAGE_H - top

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "tuk_nama": "SMK Negeri 3 Karawang",
    "tuk_alamat": "Perumahan Griya Kondang Asri, Kelurahan Kondangjaya, Kecamatan Karawang Timur, Kabupaten Karawang, Jawa Barat (Kode Pos 41317)",
    "tuk_kota": "Karawang",
    "tanggal_uji": "19 Oktober 2026",
    "tanggal_dokumen": "19 Oktober 2026",
    "jumlah_asesi": "10 (Sepuluh) orang",
    "penanggungjawab_nama": "H. Ahmad Fauzi, S.Pd., M.M.",
    "penanggungjawab_jabatan": "Kepala TUK Sewaktu",
    "signature_penanggungjawab": None,
    "asesor": [],
}

def wrap_text(text, font_name, font_size, max_w):
    """Break text into lines that fit within max_w points without truncating."""
    text = str(text or "").strip()
    if not text:
        return []
    words = text.split()
    if not words:
        return []
    lines = []
    cur_line = []
    for w in words:
        test_line = " ".join(cur_line + [w])
        if pdfmetrics.stringWidth(test_line, font_name, font_size) <= max_w:
            cur_line.append(w)
        else:
            if cur_line:
                lines.append(" ".join(cur_line))
                cur_line = []
            if pdfmetrics.stringWidth(w, font_name, font_size) <= max_w:
                cur_line.append(w)
            else:
                sub = ""
                for ch in w:
                    if pdfmetrics.stringWidth(sub + ch, font_name, font_size) <= max_w:
                        sub += ch
                    else:
                        if sub:
                            lines.append(sub)
                        sub = ch
                if sub:
                    cur_line.append(sub)
    if cur_line:
        lines.append(" ".join(cur_line))
    return lines

def draw_multiline(c, lines, x, y_mid, leading, font_name, font_size, y_func, align="left", col_right=None):
    """Draw vertically centered multi-line text inside a table cell."""
    if not lines:
        return
    c.setFont(font_name, font_size)
    n = len(lines)
    for i, line in enumerate(lines):
        line_y_from_top = y_mid - ((n - 1) * leading) / 2.0 + i * leading
        baseline = y_func(line_y_from_top + font_size * 0.35)
        if align == "center" and col_right is not None:
            c.drawCentredString((x + col_right) / 2.0, baseline, line)
        elif align == "center":
            c.drawCentredString(x, baseline, line)
        elif align == "right" and col_right is not None:
            c.drawRightString(col_right, baseline, line)
        else:
            c.drawString(x, baseline, line)

def split_address(addr, max_chars=75):
    if not addr:
        return ["-"]
    words = addr.split()
    lines = []
    curr = ""
    for w in words:
        if len(curr) + len(w) + 1 <= max_chars:
            curr = curr + (" " if curr else "") + w
        else:
            if curr:
                lines.append(curr)
            curr = w
    if curr:
        lines.append(curr)
    return lines if lines else ["-"]

def render_page(c, chunk, chunk_offset, batch_idx, total_batches):
    # 1. Header (Kop Surat)
    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        kop_w = 539.0
        kop_h = 98.2
        kop_x = (PAGE_W - kop_w) / 2.0
        c.drawImage(kop_file, kop_x, Y(15.0 + kop_h), kop_w, kop_h, mask="auto")

    # 2. Title
    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PAGE_W / 2, Y(132.0), "DAFTAR HADIR ASESOR")
    if total_batches > 1:
        c.setFont("Lib", 8.5)
        c.drawCentredString(PAGE_W / 2, Y(145.0), f"(Halaman {batch_idx + 1} dari {total_batches})")

    # 3. Metadata List (1 to 5) with dynamic line wrapping
    c.setFont("Lib", 9.0)
    x_num = 87.0
    x_lbl = 99.2
    x_col = 177.0
    x_val = 183.1
    max_val_w = PAGE_W - x_val - 45.0

    skema_lines = wrap_text(DATA.get("skema_nama", ""), "Lib", 9.0, max_val_w) or ["-"]
    tuk_lines = wrap_text(DATA.get("tuk_nama", ""), "Lib", 9.0, max_val_w) or ["-"]
    addr_lines = wrap_text(DATA.get("tuk_alamat", ""), "Lib", 9.0, max_val_w) or ["-"]
    tgl_val = str(DATA.get("tanggal_uji", DATA.get("tanggal_dokumen", "")))
    jml_val = str(DATA.get("jumlah_asesi", ""))

    y_curr = 165.0
    # Item 1: Skema
    c.drawString(x_num, Y(y_curr), "1.")
    c.drawString(x_lbl, Y(y_curr), "Skema")
    c.drawString(x_col, Y(y_curr), ":")
    for i, line in enumerate(skema_lines):
        c.drawString(x_val, Y(y_curr + i * 13.0), line)
    y_curr += max(1, len(skema_lines)) * 13.0 + 2.0

    # Item 2: TUK (Institusi)
    c.drawString(x_num, Y(y_curr), "2.")
    c.drawString(x_lbl, Y(y_curr), "TUK")
    c.drawString(x_col, Y(y_curr), ":")
    for i, line in enumerate(tuk_lines):
        c.drawString(x_val, Y(y_curr + i * 13.0), line)
    y_curr += max(1, len(tuk_lines)) * 13.0 + 2.0

    # Item 3: Alamat
    c.drawString(x_num, Y(y_curr), "3.")
    c.drawString(x_lbl, Y(y_curr), "Alamat")
    c.drawString(x_col, Y(y_curr), ":")
    for i, line in enumerate(addr_lines):
        c.drawString(x_val, Y(y_curr + i * 13.0), line)
    y_curr += max(1, len(addr_lines)) * 13.0 + 2.0

    # Item 4: Tanggal Uji
    c.drawString(x_num, Y(y_curr), "4.")
    c.drawString(x_lbl, Y(y_curr), "Tanggal Uji")
    c.drawString(x_col, Y(y_curr), ":")
    c.drawString(x_val, Y(y_curr), tgl_val)
    y_curr += 15.0

    # Item 5: Jumlah Asesi
    c.drawString(x_num, Y(y_curr), "5.")
    c.drawString(x_lbl, Y(y_curr), "Jumlah Asesi")
    c.drawString(x_col, Y(y_curr), ":")
    c.drawString(x_val, Y(y_curr), jml_val)
    y_curr += 20.0

    # 4. Table Asesor (Fixed 5 rows per standard page with dynamic row wrapping)
    cols = [50.4, 76.5, 242.3, 391.5, 560.4]
    top_tbl = max(264.3, y_curr)
    hdr_h = 23.2
    n_rows = 5

    row_data = []
    row_heights = []
    for idx in range(n_rows):
        if idx < len(chunk):
            as_info = chunk[idx]
            lines_nama = wrap_text(as_info.get("nama", ""), "Lib", 8.5, (cols[2] - cols[1]) - 12.0)
            lines_met = wrap_text(as_info.get("no_met", ""), "Lib", 8.5, (cols[3] - cols[2]) - 10.0)
            max_l = max(1, len(lines_nama), len(lines_met))
            h = max(23.0, max_l * 10.0 + 4.0)
            row_data.append({
                "as_info": as_info,
                "lines_nama": lines_nama,
                "lines_met": lines_met,
            })
            row_heights.append(h)
        else:
            row_data.append({
                "as_info": None,
                "lines_nama": [],
                "lines_met": [],
            })
            row_heights.append(23.0)

    cur_y = top_tbl + hdr_h
    row_tops = []
    row_mids = []
    for h in row_heights:
        row_tops.append(cur_y)
        row_mids.append(cur_y + h / 2.0)
        cur_y += h
    bot_tbl = cur_y

    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    c.line(cols[0], Y(top_tbl + hdr_h), cols[-1], Y(top_tbl + hdr_h))

    for r in range(1, n_rows):
        c.line(cols[0], Y(row_tops[r]), cols[-1], Y(row_tops[r]))

    for x in cols[1:-1]:
        c.line(x, Y(top_tbl), x, Y(bot_tbl))

    # Headers
    c.setFont("Lib-B", 9.0)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y(top_tbl + 15.0), "No.")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y(top_tbl + 15.0), "Nama Asesor Kompetensi")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y(top_tbl + 15.0), "No.MET")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y(top_tbl + 15.0), "Tanda Tangan")

    # Rows
    for idx in range(n_rows):
        rd = row_data[idx]
        y_mid = row_mids[idx]
        this_row_h = row_heights[idx]
        global_no = chunk_offset + idx + 1
        c.setFont("Lib", 8.5)
        c.drawCentredString((cols[0] + cols[1]) / 2, Y(y_mid + 3.0), f"{global_no}.")

        if rd["as_info"]:
            as_info = rd["as_info"]
            draw_multiline(c, rd["lines_nama"], cols[1] + 6.0, y_mid, 10.0, "Lib", 8.5, Y, align="left")
            draw_multiline(c, rd["lines_met"], (cols[2] + cols[3]) / 2.0, y_mid, 10.0, "Lib", 8.5, Y, align="center", col_right=cols[3])

            sig_num = str(global_no)
            is_odd = (global_no % 2 != 0)
            slot_left = cols[3] + 6.0 if is_odd else (cols[3] + cols[4]) / 2 + 4.0
            sig_max_h = min(20.0, this_row_h - 4.0)

            sig_data = as_info.get("signature")
            drawn = draw_signature(c, sig_data, x_left=slot_left + 14.0, y_bottom=Y(y_mid + sig_max_h / 2.0), max_w=45.0, max_h=sig_max_h)
            c.setFont("Lib", 8.5)
            if drawn:
                c.drawString(slot_left, Y(y_mid + 3.0), f"{sig_num}.")
            else:
                c.drawString(slot_left, Y(y_mid + 3.0), f"{sig_num}. [Ttd]")

    # 5. Signatures below
    y_sig = bot_tbl + 25.0
    c.drawString(324.0, Y(y_sig), f"{DATA.get('tuk_kota', 'Karawang')}, {DATA.get('tanggal_dokumen', '')}")

    c.setFont("Lib-B", 9.0)
    c.drawString(324.0, Y(y_sig + 24.0), "Penanggungjawab kegiatan")

    c.setFont("Lib", 8.5)
    c.drawString(324.0, Y(y_sig + 39.0), "Nama")
    c.drawString(396.0, Y(y_sig + 39.0), f": {DATA.get('penanggungjawab_nama', '')}")
    c.drawString(324.0, Y(y_sig + 54.0), "Jabatan")
    c.drawString(396.0, Y(y_sig + 54.0), f": {DATA.get('penanggungjawab_jabatan', '')}")
    c.drawString(324.0, Y(y_sig + 69.0), "Tanda tangan")
    c.drawString(396.0, Y(y_sig + 69.0), ":")

    sig_pic = DATA.get("signature_penanggungjawab")
    drawn_pic = draw_signature(c, sig_pic, x_left=406.0, y_bottom=Y(y_sig + 100.0), max_w=120.0, max_h=34.0)

    c.drawString(324.0, Y(y_sig + 120.0), "………………………………….")

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    asesor_all = DATA.get("asesor", [])
    chunk_size = 5
    chunks = [asesor_all[i:i + chunk_size] for i in range(0, len(asesor_all), chunk_size)]
    if not chunks:
        chunks = [[]]
    total_batches = len(chunks)

    for idx, chunk in enumerate(chunks):
        chunk_offset = idx * chunk_size
        render_page(c, chunk, chunk_offset, idx, total_batches)
        c.showPage()

    c.save()
    print(f"OK -> Sukses generate DAFTAR HADIR ASESOR ({total_batches} halaman): {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "Daftar_Hadir_Asesor_generated.pdf")
    if len(sys.argv) > 1:
        if sys.argv[1].endswith(".json") and os.path.isfile(sys.argv[1]):
            with open(sys.argv[1], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        else:
            out_file = sys.argv[1]
    render(out_file)
