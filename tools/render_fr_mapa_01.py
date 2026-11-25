#!/usr/bin/env python3
"""
Generator & Master Overlay FR.MAPA.01 (Merencanakan Aktivitas dan Proses Asesmen) - LSP PrO Indo
Layout, tabel, unit kompetensi, dan font presisi 100% identik dengan master template asli BNSP per skema.
Fitur Utama:
- Mengambil data dari mapa asesor (mapa01_data).
- Menghapus 100% watermark background (Word artifact header bezier curves, vector rg 0.722, dan background images).
- Menyesuaikan pilihan centang (✓) pada Pendekatan Asesi, Tujuan, Konteks, dan Standar Industri.
- Menyesuaikan coretan (Ada / Tidak Ada) dan rincian pada Modifikasi & Kontekstualisasi.
- Membiarkan tabel Penyusun dan Validator tetap utuh / baku sesuai template master BNSP (tanpa mengubah tanda tangan/nama).
"""

import os
import sys
import json
import io
import re
import base64
from PIL import Image

try:
    import pymupdf as fitz
except ImportError:
    import fitz

try:
    from font_helper import find_font
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import find_font

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
DEFAULT_MASTER_PDF = os.path.join(
    PROJECT_ROOT,
    "lsp-document-fixed",
    "MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection",
    "4. FR.MAPA.01. Merencanakan Aktivitas dan Proses .pdf"
)
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_MAPA_01_generated.pdf")

CALIBRI_REG = find_font("Carlito-Regular.ttf", fallback="LiberationSans-Regular.ttf")
CALIBRI_BOLD = find_font("Carlito-Bold.ttf", fallback="LiberationSans-Bold.ttf")
CALIBRI_ITALIC = find_font("Carlito-Italic.ttf", fallback="LiberationSans-Italic.ttf")

def clean_watermark(doc):
    """Strip all background watermarks (Word artifact header bezier curves, vector rg, and large images)."""
    cleaned = 0
    for page in doc:
        for xref in page.get_contents():
            try:
                stream = doc.xref_stream(xref).decode("latin1", errors="ignore")
                modified = False

                # 1. Color vector watermark (0.722 0.8 0.894 rg)
                wm_start = stream.find("0.722 0.8 0.894 rg")
                if wm_start != -1:
                    wm_end = stream.find("h\r\nf*\r\n", wm_start)
                    if wm_end != -1:
                        wm_end += len("h\r\nf*\r\n")
                        stream = stream[:wm_start] + stream[wm_end:]
                        modified = True
                        cleaned += 1

                # 2. Word Artifact Header bezier curves
                prefix = "/Artifact <</Attached [/Top]/Type/Pagination/Subtype/Header>> BDC"
                art_pos = stream.find(prefix)
                if art_pos != -1:
                    gs_match = re.search(r"/GS\d+\s+gs\s*", stream[art_pos:art_pos + 200])
                    if gs_match:
                        wm_start = art_pos + gs_match.end()
                        for term in ["h\r\nf*\r\nq", "h\nf*\nq", "h f* q"]:
                            term_idx = stream.find(term, wm_start)
                            if term_idx != -1 and (term_idx - wm_start) > 5000:
                                wm_end = term_idx + len(term) - 1
                                stream = stream[:wm_start] + stream[wm_end:]
                                modified = True
                                cleaned += 1
                                break

                # 3. Clean background watermark images if any (> 250x250 and not logo)
                for img in page.get_image_info(xrefs=True):
                    w = img.get("width", 0)
                    h = img.get("height", 0)
                    bbox = img.get("bbox", (0, 0, 0, 0))
                    img_w = bbox[2] - bbox[0]
                    img_h = bbox[3] - bbox[1]
                    # Don't delete small header logos
                    if (w > 400 and h > 400) or (img_w > 250 and img_h > 250 and bbox[1] > 100):
                        xref_img = img.get("xref")
                        if xref_img:
                            try:
                                page.delete_image(xref_img)
                                cleaned += 1
                            except Exception:
                                pass

                if modified:
                    doc.update_stream(xref, stream.encode("latin1"))
            except Exception:
                pass
    return cleaned

def clean_signature_image(raw_bytes_or_path):
    """Load signature and ensure strokes are solid, bold, and crisp on transparent background."""
    if not raw_bytes_or_path:
        return None
    try:
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

def draw_vector_checkmark(page, box_rect):
    """Draw a sharp vector checkmark inside a checkbox bounding box."""
    bx0, by0, bx1, by1 = box_rect
    cx = (bx0 + bx1) / 2.0
    cy = (by0 + by1) / 2.0 + 0.5

    p1 = fitz.Point(cx - 2.5, cy - 0.2)
    p2 = fitz.Point(cx - 0.6, cy + 2.4)
    p3 = fitz.Point(cx + 3.0, cy - 3.0)

    shape = page.new_shape()
    shape.draw_polyline([p1, p2, p3])
    shape.finish(color=(0, 0, 0), width=1.4, lineCap=1, lineJoin=1)
    shape.commit()

def set_checkbox_state(page, cb_rect, is_checked):
    """Blank out the old checkbox area and redraw clean checkbox with or without checkmark."""
    # Clean white background patch over existing character
    page.draw_rect(cb_rect, fill=(1, 1, 1), color=(1, 1, 1))

    # Box coordinates
    bx0 = cb_rect.x0 + 3.5
    by0 = cb_rect.y0 + 3.0
    bx1 = bx0 + 9.5
    by1 = by0 + 9.5
    box = fitz.Rect(bx0, by0, bx1, by1)

    page.draw_rect(box, color=(0, 0, 0), width=0.8)

    if is_checked:
        draw_vector_checkmark(page, box)

def find_scheme_master_pdf(scheme_hint):
    """Find master FR.MAPA.01 template for a specific scheme from lsp-document-fixed."""
    hint = str(scheme_hint).lower()
    base_dir = os.path.join(PROJECT_ROOT, "lsp-document-fixed")
    if not os.path.isdir(base_dir):
        return None

    # Number mapping
    num_match = re.search(r'(?:skm|skema)[-_ ]?0?([1-7])', hint)
    num = int(num_match.group(1)) if num_match else None

    name_map = {
        1: "Engine Junior",
        2: "Maintenance Gasoline",
        3: "Electrical Vehicle",
        4: "Panel Bodi",
        5: "Mekanik Junior",
        6: "Sepeda Motor Listrik",
        7: "Sepeda Motor Injeksi"
    }

    subdirs = [d for d in os.listdir(base_dir) if os.path.isdir(os.path.join(base_dir, d)) and d.startswith("MUK")]

    target_dir = None
    if num and num in name_map:
        for d in subdirs:
            if f"SKEMA {num}" in d.upper() or name_map[num].lower() in d.lower():
                target_dir = os.path.join(base_dir, d)
                break

    if not target_dir:
        for d in subdirs:
            if any(k.lower() in d.lower() for k in hint.split() if len(k) > 3):
                target_dir = os.path.join(base_dir, d)
                break

    if not target_dir and subdirs:
        target_dir = os.path.join(base_dir, subdirs[0])

    if target_dir:
        for f in os.listdir(target_dir):
            fl = f.lower()
            if fl.endswith('.pdf') and ('mapa.01' in fl or 'mapa 01' in fl or 'mapa-01' in fl):
                return os.path.join(target_dir, f)

    return DEFAULT_MASTER_PDF

def put_text(page, pt, text, is_bold=False, is_italic=False, size=9.0, reg_font=None, bold_font=None, italic_font=None):
    if is_bold:
        ff = bold_font
        fn = "helv-bold"
    elif is_italic:
        ff = italic_font
        fn = "helv-oblique"
    else:
        ff = reg_font
        fn = "helv"

    if ff and os.path.isfile(ff):
        page.insert_text(pt, text, fontfile=ff, fontsize=size)
    else:
        page.insert_text(pt, text, fontname=fn, fontsize=size)

def draw_clean_section1(page, data, reg_font=None, bold_font=None):
    """Draw Section 1 (Menentukan Pendekatan Asesmen) with clean, non-colliding table geometry."""
    m_sec1 = page.search_for("Menentukan Pendekatan Asesmen")
    m_nomor = page.search_for("Nomor")
    nomor_y1 = m_nomor[0].y1 if m_nomor else 100.0
    sec1_y = m_sec1[0].y0 if m_sec1 else 170.0

    cand_top = [d["rect"].y0 for d in page.get_drawings() if d["rect"].width > 250 and (nomor_y1 - 1.0) <= d["rect"].y0 <= (sec1_y + 1.0)]
    y_top = max(cand_top) if cand_top else (sec1_y - 12.0)

    cand_bot = [d["rect"].y1 for d in page.get_drawings() if d["rect"].width > 250 and d["rect"].y1 > 700.0]
    y_bot = max(cand_bot) if cand_bot else 795.0

    x0 = 71.5
    x1 = 561.0

    # 1. Blank out entire old Section 1 area cleanly (strictly below the Skema table!)
    page.draw_rect(fitz.Rect(60.0, y_top - 1.0, 575.0, y_bot + 1.5), fill=(1, 1, 1), color=(1, 1, 1))

    # Outer table border
    page.draw_rect(fitz.Rect(x0, y_top, x1, y_bot), color=(0, 0, 0), width=0.8)

    # Header Row: 1. Menentukan Pendekatan Asesmen
    hdr_h = 19.0
    y_hdr_bot = y_top + hdr_h
    page.draw_line(fitz.Point(x0, y_hdr_bot), fitz.Point(x1, y_hdr_bot), color=(0, 0, 0), width=0.6)
    put_text(page, fitz.Point(x0 + 6, y_top + 13), "1.   Menentukan Pendekatan Asesmen", is_bold=True, size=10.0, reg_font=reg_font, bold_font=bold_font)

    pendekatan = str(data.get("pendekatan_asesi", "")).lower()
    tujuan = str(data.get("tujuan_asesmen", "Sertifikasi")).lower()
    lingkungan = str(data.get("konteks_lingkungan", "simulasi")).lower()
    peluang = str(data.get("peluang_bukti", "Tersedia")).lower()
    hubungan = str(data.get("hubungan_standar", "mendukung asesmen")).lower()
    pelaksana = str(data.get("pelaksana_asesmen", "Lembaga Sertifikasi")).lower()
    orang_relevan = [str(x).lower() for x in data.get("orang_relevan", [])]

    def draw_cb(cx, cy, checked):
        sz = 8.5
        box = fitz.Rect(cx - sz / 2, cy - sz / 2, cx + sz / 2, cy + sz / 2)
        page.draw_rect(box, color=(0, 0, 0), width=0.7)
        if checked:
            shape = page.new_shape()
            shape.draw_polyline([
                fitz.Point(cx - 2.5, cy + 0.2),
                fitz.Point(cx - 0.7, cy + 2.4),
                fitz.Point(cx + 2.8, cy - 2.6)
            ])
            shape.finish(color=(0, 0, 0), width=1.3, lineCap=1, lineJoin=1)
            shape.commit()

    def draw_smilies(start_x, y_mid):
        faces = ["smile", "neutral", "frown"]
        r = 4.2
        for i, face in enumerate(faces):
            fcx = start_x + (i * 13.5)
            fcy = y_mid
            page.draw_circle(fitz.Point(fcx, fcy), r, color=(0, 0, 0), width=0.6)
            page.draw_rect(fitz.Rect(fcx - 1.8, fcy - 1.8, fcx - 0.9, fcy - 0.9), fill=(0, 0, 0), color=(0, 0, 0))
            page.draw_rect(fitz.Rect(fcx + 0.9, fcy - 1.8, fcx + 1.8, fcy - 0.9), fill=(0, 0, 0), color=(0, 0, 0))
            s = page.new_shape()
            if face == "smile":
                s.draw_polyline([fitz.Point(fcx - 2.0, fcy + 1.0), fitz.Point(fcx, fcy + 2.4), fitz.Point(fcx + 2.0, fcy + 1.0)])
            elif face == "neutral":
                s.draw_polyline([fitz.Point(fcx - 1.8, fcy + 1.6), fitz.Point(fcx + 1.8, fcy + 1.6)])
            elif face == "frown":
                s.draw_polyline([fitz.Point(fcx - 2.0, fcy + 2.4), fitz.Point(fcx, fcy + 1.0), fitz.Point(fcx + 2.0, fcy + 2.4)])
            s.finish(color=(0, 0, 0), width=0.6, lineCap=1)
            s.commit()

    # Calculate proportional heights
    rem_h = y_bot - y_hdr_bot
    h_as = rem_h * 0.278
    h_tj = rem_h * 0.148
    h_ling = rem_h * 0.054
    h_pel = rem_h * 0.082
    h_hub = rem_h * 0.118
    h_pelak = rem_h * 0.118
    # Remaining height for konfirmasi
    h_rel = rem_h - (h_as + h_tj + h_ling + h_pel + h_hub + h_pelak)

    # Section 1.1 Asesi
    y_as_top = y_hdr_bot
    y_as_bot = y_as_top + h_as
    x_sub1 = 104.0
    x_split_as = 175.0
    page.draw_line(fitz.Point(x_sub1, y_as_top), fitz.Point(x_sub1, y_as_bot), color=(0, 0, 0), width=0.6)
    page.draw_line(fitz.Point(x_split_as, y_as_top), fitz.Point(x_split_as, y_as_bot), color=(0, 0, 0), width=0.6)

    put_text(page, fitz.Point(x0 + 7, y_as_top + 16), "1.1.", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_sub1 + 8, y_as_top + 16), "Asesi", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font)

    asesi_opts = [
        ([
            "Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas",
            "praktek mampu telusur terhadap standar kompetensi"
        ], "kurikulum dan fasilitas" in pendekatan or ("mampu telusur" in pendekatan and "belum" not in pendekatan and "industri" not in pendekatan)),
        ([
            "Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum berbasis",
            "kompetensi."
        ], "kurikulum belum" in pendekatan and "industri" not in pendekatan),
        ([
            "Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang",
            "dalam operasionalnya mampu telusur dengan standar kompetensi"
        ], "industri" in pendekatan and "mampu telusur" in pendekatan and "belum" not in pendekatan),
        ([
            "Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang",
            "dalam operasionalnya belum berbasis kompetensi."
        ], "industri" in pendekatan and "belum berbasis" in pendekatan),
        ([
            "Pelatihan / belajar mandiri atau otodidak."
        ], "mandiri" in pendekatan or "otodidak" in pendekatan),
    ]

    curr_y = y_as_top
    row_h_as = h_as / 5.0
    for idx, (lines, is_chk) in enumerate(asesi_opts):
        r_top = curr_y
        r_bot = r_top + row_h_as
        if idx < 4:
            page.draw_line(fitz.Point(x_split_as, r_bot), fitz.Point(x1, r_bot), color=(0, 0, 0), width=0.5)
        draw_cb(187.0, (r_top + r_bot) / 2, is_chk)
        tx = 197.0
        if len(lines) == 1:
            put_text(page, fitz.Point(tx, (r_top + r_bot) / 2 + 3.2), lines[0], size=8.6, reg_font=reg_font, bold_font=bold_font)
        else:
            mid = (r_top + r_bot) / 2
            put_text(page, fitz.Point(tx, mid - 2.5), lines[0], size=8.6, reg_font=reg_font, bold_font=bold_font)
            put_text(page, fitz.Point(tx, mid + 9.5), lines[1], size=8.6, reg_font=reg_font, bold_font=bold_font)
        curr_y = r_bot

    # Section 1.2 Tujuan Asesmen
    page.draw_line(fitz.Point(x0, y_as_bot), fitz.Point(x1, y_as_bot), color=(0, 0, 0), width=0.6)
    y_tj_top = y_as_bot
    y_tj_bot = y_tj_top + h_tj
    page.draw_line(fitz.Point(x_split_as, y_tj_top), fitz.Point(x_split_as, y_tj_bot), color=(0, 0, 0), width=0.6)
    put_text(page, fitz.Point(x0 + 7, y_tj_top + 18), "Tujuan", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x0 + 7, y_tj_top + 31), "Asesmen", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font)

    tj_opts = [
        ("Sertifikasi", "sertifikasi" in tujuan),
        ("Pengakuan Kompetensi Terkini (PKT)", "terkini" in tujuan or "pkt" in tujuan),
        ("Rekognisi Pembelajaran Lampau", "rekognisi" in tujuan or "rpl" in tujuan),
        ("Lainnya", "lainnya" in tujuan),
    ]
    curr_y = y_tj_top
    row_h_tj = h_tj / 4.0
    for idx, (txt, is_chk) in enumerate(tj_opts):
        r_top = curr_y
        r_bot = r_top + row_h_tj
        if idx < 3:
            page.draw_line(fitz.Point(x_split_as, r_bot), fitz.Point(x1, r_bot), color=(0, 0, 0), width=0.5)
        draw_cb(187.0, (r_top + r_bot) / 2, is_chk)
        put_text(page, fitz.Point(197.0, (r_top + r_bot) / 2 + 3.2), txt, size=8.8, reg_font=reg_font, bold_font=bold_font)
        curr_y = r_bot

    # Section 1.3 Konteks Asesmen
    page.draw_line(fitz.Point(x0, y_tj_bot), fitz.Point(x1, y_tj_bot), color=(0, 0, 0), width=0.6)
    y_kt_top = y_tj_bot
    y_kt_bot = y_kt_top + (h_ling + h_pel + h_hub + h_pelak)
    x_k1 = 145.0
    x_k2 = 255.0
    page.draw_line(fitz.Point(x_k1, y_kt_top), fitz.Point(x_k1, y_kt_bot), color=(0, 0, 0), width=0.6)
    page.draw_line(fitz.Point(x_k2, y_kt_top), fitz.Point(x_k2, y_bot), color=(0, 0, 0), width=0.6)

    put_text(page, fitz.Point(x0 + 7, y_kt_top + 18), "Konteks", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x0 + 7, y_kt_top + 31), "Asesmen:", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font)

    # 4a. Lingkungan
    y_ling_bot = y_kt_top + h_ling
    page.draw_line(fitz.Point(x_k1, y_ling_bot), fitz.Point(x1, y_ling_bot), color=(0, 0, 0), width=0.5)
    put_text(page, fitz.Point(x_k1 + 8, (y_kt_top + y_ling_bot) / 2 + 3.0), "Lingkungan", size=8.8, reg_font=reg_font, bold_font=bold_font)
    draw_cb(267.0, (y_kt_top + y_ling_bot) / 2, "nyata" in lingkungan)
    put_text(page, fitz.Point(277.0, (y_kt_top + y_ling_bot) / 2 + 3.0), "Tempat kerja nyata", size=8.8, reg_font=reg_font, bold_font=bold_font)
    draw_cb(405.0, (y_kt_top + y_ling_bot) / 2, "simulasi" in lingkungan)
    put_text(page, fitz.Point(415.0, (y_kt_top + y_ling_bot) / 2 + 3.0), "Tempat kerja simulasi", size=8.8, reg_font=reg_font, bold_font=bold_font)

    # 4b. Peluang Bukti
    y_pel_bot = y_ling_bot + h_pel
    page.draw_line(fitz.Point(x_k1, y_pel_bot), fitz.Point(x1, y_pel_bot), color=(0, 0, 0), width=0.5)
    pel_mid = (y_ling_bot + y_pel_bot) / 2
    put_text(page, fitz.Point(x_k1 + 8, pel_mid - 11), "Peluang untuk", size=8.6, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_k1 + 8, pel_mid), "mengumpulkan bukti dalam", size=8.6, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_k1 + 8, pel_mid + 11), "sejumlah situasi", size=8.6, reg_font=reg_font, bold_font=bold_font)
    draw_cb(267.0, pel_mid, "tersedia" in peluang)
    put_text(page, fitz.Point(277.0, pel_mid + 3.0), "Tersedia", size=8.8, reg_font=reg_font, bold_font=bold_font)
    draw_cb(405.0, pel_mid, "terbatas" in peluang)
    put_text(page, fitz.Point(415.0, pel_mid + 3.0), "Terbatas", size=8.8, reg_font=reg_font, bold_font=bold_font)

    # 4c. Hubungan Standar
    y_hub_bot = y_pel_bot + h_hub
    page.draw_line(fitz.Point(x_k1, y_hub_bot), fitz.Point(x1, y_hub_bot), color=(0, 0, 0), width=0.5)
    hub_mid = (y_pel_bot + y_hub_bot) / 2
    put_text(page, fitz.Point(x_k1 + 8, hub_mid - 11), "Hubungan antara", size=8.6, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_k1 + 8, hub_mid + 2), "standar kompetensi", size=8.6, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_k1 + 8, hub_mid + 15), "dan:", size=8.6, reg_font=reg_font, bold_font=bold_font)

    hub_opts = [
        ("Bukti untuk mendukung asesmen :", "mendukung asesmen" in hubungan or "bukti untuk" in hubungan),
        ("Aktivitas kerja di tempat kerja Asesi:", "aktivitas kerja" in hubungan or "tempat kerja asesi" in hubungan),
        ("Kegiatan Pembelajaran:", "kegiatan pembelajaran" in hubungan),
    ]
    curr_y = y_pel_bot
    row_h_hub = h_hub / 3.0
    for idx, (txt, is_chk) in enumerate(hub_opts):
        r_top = curr_y
        r_bot = r_top + row_h_hub
        if idx < 2:
            page.draw_line(fitz.Point(x_k2, r_bot), fitz.Point(x1, r_bot), color=(0, 0, 0), width=0.4)
        draw_cb(267.0, (r_top + r_bot) / 2, is_chk)
        put_text(page, fitz.Point(277.0, (r_top + r_bot) / 2 + 3.0), txt, size=8.6, reg_font=reg_font, bold_font=bold_font)
        draw_smilies(475.0, (r_top + r_bot) / 2)
        curr_y = r_bot

    # 4d. Siapa Pelaksana
    y_pelak_bot = y_hub_bot + h_pelak
    page.draw_line(fitz.Point(x0, y_pelak_bot), fitz.Point(x1, y_pelak_bot), color=(0, 0, 0), width=0.6)
    pelak_mid = (y_hub_bot + y_pelak_bot) / 2
    put_text(page, fitz.Point(x_k1 + 8, pelak_mid - 11), "Siapa yang", size=8.6, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_k1 + 8, pelak_mid + 2), "melakukan asesmen/", size=8.6, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x_k1 + 8, pelak_mid + 15), "RPL", size=8.6, reg_font=reg_font, bold_font=bold_font)

    pelak_opts = [
        ("Lembaga Sertifikasi LSP Praktisi Otomotif Indonesia", "lembaga sertifikasi" in pelaksana or "lsp" in pelaksana),
        ("Organisasi Pelatihan", "organisasi pelatihan" in pelaksana),
        ("Asesor Perusahaan", "asesor perusahaan" in pelaksana),
    ]
    curr_y = y_hub_bot
    row_h_pelak = h_pelak / 3.0
    for idx, (txt, is_chk) in enumerate(pelak_opts):
        r_top = curr_y
        r_bot = r_top + row_h_pelak
        if idx < 2:
            page.draw_line(fitz.Point(x_k2, r_bot), fitz.Point(x1, r_bot), color=(0, 0, 0), width=0.4)
        draw_cb(267.0, (r_top + r_bot) / 2, is_chk)
        put_text(page, fitz.Point(277.0, (r_top + r_bot) / 2 + 3.0), txt, size=8.6, reg_font=reg_font, bold_font=bold_font)
        curr_y = r_bot

    # Section 1.4 Konfirmasi dengan orang yang relevan
    rel_mid = (y_pelak_bot + y_bot) / 2
    put_text(page, fitz.Point(x0 + 8, rel_mid - 7), "Konfirmasi dengan orang", size=9.0, reg_font=reg_font, bold_font=bold_font)
    put_text(page, fitz.Point(x0 + 8, rel_mid + 7), "yang relevan", size=9.0, reg_font=reg_font, bold_font=bold_font)

    relevan_opts = [
        (["Manajer sertifikasi"], any("manajer sertifikasi" in o for o in orang_relevan) or not orang_relevan),
        (["Master Asesor / Master Trainer / Lead Asesor Kompetensi"], any("master asesor" in o or "lead asesor" in o for o in orang_relevan) or not orang_relevan),
        ([
            "Manajer Pelatihan Lembaga Training terakreditasi /",
            "Lembaga Training terdaftar"
        ], any("manajer pelatihan" in o or "lembaga training" in o for o in orang_relevan)),
        (["Manajer atau supervisor ditempat kerja"], any("supervisor" in o or "ditempat kerja" in o for o in orang_relevan)),
    ]

    curr_y = y_pelak_bot
    # Proportional row heights for the 4 rows of Konfirmasi (row 3 is 2 lines, so ~1.35x weight)
    row_weights = [1.0, 1.0, 1.35, 1.0]
    total_w = sum(row_weights)
    for idx, (lines, is_chk) in enumerate(relevan_opts):
        rh = (h_rel * row_weights[idx]) / total_w
        r_top = curr_y
        r_bot = r_top + rh
        if idx < 3:
            page.draw_line(fitz.Point(x_k2, r_bot), fitz.Point(x1, r_bot), color=(0, 0, 0), width=0.4)
        draw_cb(267.0, (r_top + r_bot) / 2, is_chk)
        tx = 277.0
        if len(lines) == 1:
            put_text(page, fitz.Point(tx, (r_top + r_bot) / 2 + 3.2), lines[0], size=8.6, reg_font=reg_font, bold_font=bold_font)
        else:
            mid = (r_top + r_bot) / 2
            put_text(page, fitz.Point(tx, mid - 2.5), lines[0], size=8.6, reg_font=reg_font, bold_font=bold_font)
            put_text(page, fitz.Point(tx, mid + 9.5), lines[1], size=8.6, reg_font=reg_font, bold_font=bold_font)
        curr_y = r_bot

def draw_clean_section_1_2(page, data, reg_font=None, bold_font=None, italic_font=None):
    """Draw Section 1.2 (Standar Industri atau Tempat Kerja) with dynamic position detection and clean geometry."""
    m_si = page.search_for("Standar Industri")
    m_sec2 = page.search_for("Mempersiapkan Rencana Asesmen")
    if not m_si:
        return

    si_y = m_si[0].y0
    sec2_y = m_sec2[0].y0 if m_sec2 else (page.rect.height - 50.0)

    # 1. Inspect existing text in case scheme has default SKKNI or Repair Manual text
    orig_text = page.get_text("text")
    default_sk = ""
    default_kinerja = ""
    sk_match = re.search(r"Standar Kompetensi:\s*([^\n\r]+)", orig_text)
    if sk_match:
        default_sk = sk_match.group(1).strip()
    kinerja_match = re.search(r"Spesifikasi kinerja suatu perusahaan atau industri:\s*([^\n\r]+)", orig_text)
    if kinerja_match:
        default_kinerja = kinerja_match.group(1).strip()

    # Find the top border of Table 1.2: closest horizontal line above si_y (<= si_y + 1)
    top_cand = [d["rect"].y0 for d in page.get_drawings() if d["rect"].width > 250 and (si_y - 25) <= d["rect"].y0 <= (si_y + 1)]
    y_top = max(top_cand) if top_cand else (si_y - 12.0)

    # Find all table horizontal lines from y_top to sec2_y
    all_lines = sorted(list(set([
        round(d["rect"].y0, 1) for d in page.get_drawings()
        if d["rect"].width > 250 and (y_top - 1.0) <= d["rect"].y0 <= sec2_y
    ])))

    # We expect 6 lines for the 5-row table: [top, div1, div2, div3, div4, bot]
    # (plus optionally a 7th line for the Section 2 header top)
    if len(all_lines) >= 6:
        y_top = all_lines[0]
        div_lines = all_lines[1:5]
        y_bot = all_lines[5]
    else:
        # Fallback if drawings are not standard
        y_bot = sec2_y - 10.0
        step = (y_bot - y_top) / 5.0
        div_lines = [round(y_top + step * i, 1) for i in range(1, 5)]

    # 2. Clean whiteout over entire old Section 1.2 area
    page.draw_rect(fitz.Rect(65.0, y_top - 1.5, 568.0, y_bot + 1.5), fill=(1, 1, 1), color=(1, 1, 1))

    # 3. Outer table border
    x0 = 71.5
    x1 = 562.0
    x_col1 = 104.0
    x_col2 = 188.0
    page.draw_rect(fitz.Rect(x0, y_top, x1, y_bot), color=(0, 0, 0), width=0.8)

    # 4. Vertical dividers
    page.draw_line(fitz.Point(x_col1, y_top), fitz.Point(x_col1, y_bot), color=(0, 0, 0), width=0.6)
    page.draw_line(fitz.Point(x_col2, y_top), fitz.Point(x_col2, y_bot), color=(0, 0, 0), width=0.6)

    # 5. Column 1: "1.2"
    put_text(page, fitz.Point(x0 + 7, y_top + 15), "1.2", is_bold=True, size=9.2, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)

    # 6. Column 2: "Standar Industri atau Tempat Kerja"
    put_text(page, fitz.Point(x_col1 + 8, y_top + 15), "Standar Industri", size=8.8, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)
    put_text(page, fitz.Point(x_col1 + 8, y_top + 27), "atau Tempat", size=8.8, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)
    put_text(page, fitz.Point(x_col1 + 8, y_top + 39), "Kerja", size=8.8, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)

    # 7. Checkbox drawer
    def draw_cb(cx, cy, checked):
        sz = 8.5
        box = fitz.Rect(cx - sz / 2, cy - sz / 2, cx + sz / 2, cy + sz / 2)
        page.draw_rect(box, color=(0, 0, 0), width=0.7)
        if checked:
            shape = page.new_shape()
            shape.draw_polyline([
                fitz.Point(cx - 2.5, cy + 0.2),
                fitz.Point(cx - 0.7, cy + 2.4),
                fitz.Point(cx + 2.8, cy - 2.6)
            ])
            shape.finish(color=(0, 0, 0), width=1.3, lineCap=1, lineJoin=1)
            shape.commit()

    # 8. Options data extraction
    standar_industri = data.get("standar_industri", {})
    if not isinstance(standar_industri, dict):
        standar_industri = {}

    def parse_opt(key, def_chk=False, def_val=""):
        val = standar_industri.get(key, {})
        if isinstance(val, dict):
            chk = bool(val.get("checked", def_chk))
            txt = str(val.get("text", def_val) or "").strip()
            return chk, txt or def_val
        elif isinstance(val, bool):
            return val, def_val
        elif isinstance(val, str):
            return True, val.strip() or def_val
        return def_chk, def_val

    sk_chk, sk_val = parse_opt("standar_kompetensi", def_chk=True, def_val=default_sk or "SKKNI Nomor 97 Tahun 2018")
    kp_chk, kp_val = parse_opt("kurikulum_pelatihan", def_chk=False, def_val="")
    kin_chk, kin_val = parse_opt("spesifikasi_kinerja", def_chk=True, def_val=default_kinerja or "Repair Manual")
    prod_chk, prod_val = parse_opt("spesifikasi_produk", def_chk=False, def_val="")
    ped_chk, ped_val = parse_opt("pedoman_khusus", def_chk=False, def_val="")

    options = [
        # (label_prefix, value_text, is_checked, is_val_italic)
        ("Standar Kompetensi: ", sk_val, sk_chk, False),
        ("Kriteria asesmen dari kurikulum pelatihan", kp_val if kp_val and kp_val != "Kriteria asesmen dari kurikulum pelatihan" else "", kp_chk, False),
        ("Spesifikasi kinerja suatu perusahaan atau industri: ", kin_val, kin_chk, True),
        ("Spesifikasi Produk: ", prod_val, prod_chk, True),
        ("Pedoman khusus: ", ped_val, ped_chk, True),
    ]

    row_bounds = [y_top] + div_lines + [y_bot]

    for idx, (prefix, val, is_chk, val_italic) in enumerate(options):
        r_top = row_bounds[idx]
        r_bot = row_bounds[idx + 1]

        if idx < 4:
            page.draw_line(fitz.Point(x_col2, r_bot), fitz.Point(x1, r_bot), color=(0, 0, 0), width=0.5)

        cb_cx = x_col2 + 11.0
        cb_cy = (r_top + r_bot) / 2
        draw_cb(cb_cx, cb_cy, is_chk)

        tx = x_col2 + 22.0
        ty = cb_cy + 3.2

        display_prefix = prefix if val else prefix.rstrip()
        put_text(page, fitz.Point(tx, ty), display_prefix, size=8.8, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)

        if val:
            prefix_len = fitz.get_text_length(prefix, fontname="helv", fontsize=8.8)
            val_x = tx + prefix_len
            put_text(page, fitz.Point(val_x, ty), val, is_italic=val_italic, size=8.8, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)

def draw_clean_section_2(doc, data, reg_font=None, bold_font=None):
    """
    Synchronize Table 2 (Rencana Asesmen Berdasarkan Unit Kompetensi) with FR.AK.01:
    - Jenis Bukti (L, TL, T)
    - Metode dan Perangkat Asesmen (CL, DIT, DPT/DPL/PW, VP, CRP, VPK)
    - Bukti-Bukti description prefix
    Preserves exact master table borders and typography.
    """
    raw_types = data.get("evidence_types", ["L", "T"])
    if isinstance(raw_types, str):
        evidence_types = [x.strip().upper() for x in raw_types.replace("dan", ",").split(",") if x.strip()]
    elif isinstance(raw_types, list):
        evidence_types = [str(x).strip().upper() for x in raw_types]
    else:
        evidence_types = ["L", "T"]

    raw_metode = data.get("metode_codes", ["CL", "DPT"])
    if isinstance(raw_metode, list):
        metode_codes = [("CVP" if str(x).strip().upper() == "VP" else str(x).strip().upper()) for x in raw_metode]
    else:
        metode_codes = ["CL", "DPT"]

    evidence_lines = data.get("evidence_lines", [])
    if not isinstance(evidence_lines, list) or len(evidence_lines) != 3:
        evidence_lines = None

    # Cells bounds inside column dividers (preserving 100% of divider lines without touching them)
    cells = [
        (249.5, 264.0),  # L
        (266.0, 281.0),  # TL
        (283.0, 298.0),  # T
        (300.5, 348.5),  # Observasi (CL)
        (351.0, 384.0),  # Kegiatan Terstruktur (DIT)
        (386.5, 432.5),  # Tanya Jawab (DPT, DPL, PW)
        (435.5, 482.0),  # Verifikasi Portofolio (CVP)
        (485.0, 518.5),  # Reviu Produk (CRP)
        (521.0, 560.5),  # Verifikasi Pihak Ketiga (VPK)
    ]

    for page in doc:
        words = page.get_text("words")
        # Find data row coordinates on this page
        # A data row has 'L' in 245..265 (and no 'TL' on same line)
        l_words = [w for w in words if w[4] == "L" and 245.0 <= w[0] <= 265.0 and w[1] > 110.0]
        data_rows = []
        for lw in l_words:
            # Check if header (header has 'TL' on same row)
            same_line = [w2[4] for w2 in words if abs(w2[1] - lw[1]) < 4.0 and 264.0 <= w2[0] <= 283.0]
            if "TL" in same_line:
                continue
            data_rows.append((lw[1] + lw[3]) / 2.0)

        # Fallback only if some row had no 'L' but had 'CL' (use 15pt threshold to avoid duplicates)
        cl_words = [w for w in words if w[4] == "CL" and 305.0 <= w[0] <= 335.0 and w[1] > 110.0]
        for cw in cl_words:
            cy = (cw[1] + cw[3]) / 2.0
            if not any(abs(ry - cy) < 15.0 for ry in data_rows):
                # Ensure not header
                near_dit = any("DIT" in w2[4] and abs(w2[1] - cw[1]) < 4.0 for w2 in words)
                if not near_dit:
                    data_rows.append(cy)

        data_rows.sort()
        if not data_rows:
            continue

        # Process each data row
        for ry in data_rows:
            # Find all words in this row across the evidence/method columns (248 <= x <= 561)
            row_words = [w for w in words if 248.0 <= w[0] <= 561.0 and abs(w[1] - ry) < 14.0]
            if row_words:
                min_y = min(w[1] for w in row_words) - 1.5
                max_y = max(w[3] for w in row_words) + 1.5
                mid_y = (min_y + max_y) / 2.0
            else:
                min_y = ry - 7.5
                max_y = ry + 7.5
                mid_y = ry

            # 1. Whiteout inside each of the 9 cells
            for x0, x1 in cells:
                page.draw_rect(fitz.Rect(x0, min_y, x1, max_y), fill=(1, 1, 1), color=(1, 1, 1))

            # Helper to center text horizontally inside a column cell
            def draw_centered(cx, text, sz=8.8):
                w = fitz.get_text_length(text, fontname="helv", fontsize=sz)
                put_text(page, fitz.Point(cx - w / 2.0, mid_y + 3.0), text, size=sz, reg_font=reg_font, bold_font=bold_font)

            # 2. Draw active Jenis Bukti
            if "L" in evidence_types:
                draw_centered(256.75, "L", 8.8)
            if "TL" in evidence_types:
                draw_centered(273.5, "TL", 8.8)
            if "T" in evidence_types:
                draw_centered(290.5, "T", 8.8)

            # 3. Draw active Metode & Perangkat
            if "CL" in metode_codes:
                draw_centered(324.5, "CL", 8.8)
            if "DIT" in metode_codes:
                draw_centered(367.5, "DIT", 8.8)

            # Tanya Jawab (DPT / DPL / PW)
            tj_items = [x for x in ["DPT", "DPL", "PW"] if x in metode_codes]
            if tj_items:
                tj_str = ", ".join(tj_items)
                if len(tj_items) >= 3:
                    sz = 6.6
                elif len(tj_items) == 2:
                    sz = 7.6
                else:
                    sz = 8.8
                draw_centered(409.5, tj_str, sz)

            if "CVP" in metode_codes:
                draw_centered(458.75, "CVP", 8.8)
            if "CRP" in metode_codes:
                draw_centered(501.75, "CRP", 8.8)
            if "VPK" in metode_codes:
                draw_centered(540.75, "VPK", 8.8)

        # 4. Optional: Update Column 2 (Bukti-Bukti) description prefix if evidence_lines provided
        if evidence_lines:
            hasils = [w for w in words if w[4] == "Hasil" and 156.0 <= w[0] <= 248.0]
            for h in hasils:
                phrase_words = [w for w in words if 156.0 <= w[0] <= 248.0 and h[1] - 2.0 <= w[1] <= h[1] + 35.0]
                tentangs = [w for w in phrase_words if w[4] == "tentang"]
                if tentangs:
                    t = tentangs[0]
                    page.draw_rect(fitz.Rect(158.0, h[1] - 1.5, 247.0, t[3] + 1.0), fill=(1, 1, 1), color=(1, 1, 1))
                    x_text = 160.0
                    line1_y = h[1] + 9.5
                    line2_y = line1_y + 13.0
                    line3_y = line2_y + 13.0
                    put_text(page, fitz.Point(x_text, line1_y), evidence_lines[0], size=8.6, reg_font=reg_font, bold_font=bold_font)
                    put_text(page, fitz.Point(x_text, line2_y), evidence_lines[1], size=8.6, reg_font=reg_font, bold_font=bold_font)
                    put_text(page, fitz.Point(x_text, line3_y), evidence_lines[2], size=8.4, reg_font=reg_font, bold_font=bold_font)

def render_mapa_overlay(master_pdf_path, data, dest_path):
    """Overlay assessor's MAPA data onto official clean master template without watermarks."""
    if not os.path.isfile(master_pdf_path):
        raise FileNotFoundError(f"Master PDF not found: {master_pdf_path}")

    doc = fitz.open(master_pdf_path)

    # 1. Strip ALL watermarks from all pages
    clean_watermark(doc)

    reg_font = CALIBRI_REG if os.path.isfile(CALIBRI_REG) else None
    bold_font = CALIBRI_BOLD if os.path.isfile(CALIBRI_BOLD) else None
    italic_font = CALIBRI_ITALIC if os.path.isfile(CALIBRI_ITALIC) else None

    # -------------------------------------------------------------
    # PAGE 1: Pendekatan Asesmen & Konteks Asesmen (Clean, Structured Redraw)
    # -------------------------------------------------------------
    p1 = doc[0]
    draw_clean_section1(p1, data, reg_font=reg_font, bold_font=bold_font)

    # -------------------------------------------------------------
    # PAGE 2: Standar Industri atau Tempat Kerja (Clean, Structured Redraw)
    # -------------------------------------------------------------
    p_si = None
    for page in doc:
        if page.search_for("Standar Industri"):
            p_si = page
            break
    if p_si is not None:
        draw_clean_section_1_2(p_si, data, reg_font=reg_font, bold_font=bold_font, italic_font=italic_font)

    # -------------------------------------------------------------
    # SECTION 2: Mempersiapkan Rencana Asesmen (Dynamic with FR.AK.01)
    # -------------------------------------------------------------
    draw_clean_section_2(doc, data, reg_font=reg_font, bold_font=bold_font)

    # -------------------------------------------------------------
    # SECTION 3: Mengidentifikasi Persyaratan Modifikasi (Penultimate Page)
    # -------------------------------------------------------------
    # Locate page with Section 3
    p3_idx = None
    for idx, page in enumerate(doc):
        if "Persyaratan Modifikasi dan Kontekstualisasi" in page.get_text():
            p3_idx = idx
            break

    if p3_idx is not None:
        p3 = doc[p3_idx]

        mod_items = [
            ("karakteristik khusus", data.get("has_karakteristik", False), data.get("karakteristik_detail", "")),
            ("kebutuhan kontekstualisasi", data.get("has_kontekstualisasi", False), data.get("kontekstualisasi_detail", "")),
            ("saran", data.get("has_saran", False), data.get("saran_detail", "")),
            ("penyesuaian", data.get("has_penyesuaian", False), data.get("penyesuaian_detail", "")),
            ("peluang", data.get("has_terintegrasi", True), data.get("terintegrasi_detail", "")),
        ]

        ada_matches = p3.search_for("Ada / tidak ada")
        for i, (key, has_item, detail) in enumerate(mod_items):
            if i < len(ada_matches):
                r = ada_matches[i]
                if has_item:
                    # Strike out "tidak ada"
                    strike_rect = fitz.Rect(r.x0 + 26, r.y0 + 5.5, r.x1 - 2, r.y0 + 5.5)
                    p3.draw_line(strike_rect.tl, strike_rect.br, color=(0, 0, 0), width=1.2)
                    if detail:
                        if reg_font:
                            p3.insert_text(fitz.Point(r.x0, r.y1 + 13), f"Detail: {detail[:70]}", fontsize=9, fontfile=reg_font)
                        else:
                            p3.insert_text(fitz.Point(r.x0, r.y1 + 13), f"Detail: {detail[:70]}", fontsize=9, fontname="helv")
                else:
                    # Strike out "Ada"
                    strike_rect = fitz.Rect(r.x0, r.y0 + 5.5, r.x0 + 21, r.y0 + 5.5)
                    p3.draw_line(strike_rect.tl, strike_rect.br, color=(0, 0, 0), width=1.2)

    # -------------------------------------------------------------
    # LAST PAGE: PENYUSUN DAN VALIDATOR
    # -------------------------------------------------------------
    # Tabel PENYUSUN DAN VALIDATOR dibiarkan murni 100% utuh sesuai master template BNSP.
    # Tidak mengubah nama siapapun dan tidak memasukkan tanda tangan asesor/sistem.

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    meta = doc.metadata or {}
    meta["title"] = f"FR.MAPA.01 ({asesi_name})" if asesi_name else f"FR.MAPA.01 - {skema_nama}"
    meta["author"] = "LSP PrO Indo"
    meta["subject"] = "FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen"
    meta["creator"] = "LSP PrO Indo MUK System"
    doc.set_metadata(meta)
    doc.save(dest_path, deflate=True)
    doc.close()
    print(f"OK -> Berhasil generate FR.MAPA.01: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)

    out_file = OUT_FILE
    master_pdf = None

    DATA = {}
    if len(sys.argv) > 1 and sys.argv[1] != "-":
        payload_arg = sys.argv[1]
        if os.path.isfile(payload_arg):
            with open(payload_arg, "r", encoding="utf-8") as f:
                DATA = json.load(f)
        else:
            try:
                DATA = json.loads(payload_arg)
            except Exception:
                pass
    elif len(sys.argv) > 1 and sys.argv[1] == "-":
        DATA = json.load(sys.stdin)

    if len(sys.argv) > 2:
        out_file = sys.argv[2]

    if len(sys.argv) > 3 and sys.argv[3] and os.path.isfile(sys.argv[3]):
        master_pdf = sys.argv[3]
    else:
        scheme_hint = DATA.get("skema_nomor") or DATA.get("skema_nama") or ""
        master_pdf = find_scheme_master_pdf(scheme_hint)

    if not master_pdf or not os.path.isfile(master_pdf):
        master_pdf = DEFAULT_MASTER_PDF

    render_mapa_overlay(master_pdf, DATA, out_file)
