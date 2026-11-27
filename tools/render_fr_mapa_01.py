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

def render_mapa_dynamic(data, dest_path):
    """Generate clean, fully dynamic FR.MAPA.01 PDF from assessor MAPA.01 + AK.01 + scheme units."""
    from reportlab.pdfgen import canvas as rl_canvas
    from reportlab.lib.colors import black as rl_black, Color as rl_Color
    from reportlab.pdfbase import pdfmetrics as rl_pdfmetrics
    from reportlab.lib.utils import ImageReader as rl_ImageReader
    from font_helper import register_reportlab_fonts

    register_reportlab_fonts(["Lib", "Lib-B", "Lib-I", "Cal", "Cal-B", "Times"])

    PAGE_W, PAGE_H = 595.28, 841.89
    Y = lambda top: PAGE_H - top
    BLUE_NUM = rl_Color(0.267, 0.447, 0.769)
    L, R = 50.4, 544.8
    Y_TOP_CONT = 112.0
    Y_MAX = 780.0

    def wrap_w(txt, font_name, font_size, max_w):
        words = str(txt or "").split()
        if not words:
            return []
        lines, cur = [], []
        for w in words:
            test = " ".join(cur + [w])
            if rl_pdfmetrics.stringWidth(test, font_name, font_size) <= max_w:
                cur.append(w)
            else:
                if cur:
                    lines.append(" ".join(cur))
                    cur = [w]
                else:
                    lines.append(w)
                    cur = []
        if cur:
            lines.append(" ".join(cur))
        return lines

    def draw_header(c):
        x0, x1 = 50.4, 544.8
        top, bot = 35.4, 99.3
        c.setLineWidth(0.65)
        c.setStrokeColor(rl_black)
        c.rect(x0, Y(bot), x1 - x0, bot - top)
        x_div = 147.7
        c.line(x_div, Y(top), x_div, Y(bot))

        logo_file = os.path.join(PROJECT_ROOT, "public", "images", "logo-wisindo-alt-bawah.png")
        if not os.path.exists(logo_file):
            logo_file = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets", "logo.png")
        if os.path.exists(logo_file):
            try:
                with Image.open(logo_file) as im:
                    iw, ih = im.size
                aspect = (iw / ih) if ih else 1.0
            except Exception:
                aspect = 1.0
            max_w, max_h = 82.0, 56.0
            draw_h = max_h
            draw_w = draw_h * aspect
            if draw_w > max_w:
                draw_w = max_w
                draw_h = draw_w / aspect
            lx = x0 + ((x_div - x0) - draw_w) / 2.0
            ly = Y(bot) + ((bot - top) - draw_h) / 2.0
            c.drawImage(logo_file, lx, ly, draw_w, draw_h, mask="auto")

        segs = [("L", "Lib-B"), ("embaga ", "Lib"), ("S", "Lib-B"), ("ertifikasi ", "Lib"),
                ("P", "Lib-B"), ("rofesi ", "Lib"), ("W", "Lib-B"), ("isata ", "Lib"),
                ("I", "Lib-B"), ("ndonesia", "Lib")]
        fsize = 13.0
        tot_w = sum(rl_pdfmetrics.stringWidth(t, f, fsize) for t, f in segs)
        xp = x_div + ((x1 - x_div) - tot_w) / 2.0
        yp = Y((top + bot) / 2.0) - fsize * 0.32
        for t, f in segs:
            c.setFont(f, fsize)
            c.setFillColor(rl_black)
            c.drawString(xp, yp, t)
            xp += rl_pdfmetrics.stringWidth(t, f, fsize)

    def draw_pnum(c, pnum):
        c.setFont("Times", 11.0)
        c.setFillColor(BLUE_NUM)
        c.drawCentredString(PAGE_W / 2.0, 34.0, str(pnum))

    def draw_cb(c, cx, cy_top, checked=False):
        sz = 8.2
        c.setLineWidth(0.65)
        c.setStrokeColor(rl_black)
        c.rect(cx - sz / 2.0, Y(cy_top + sz / 2.0), sz, sz, fill=0, stroke=1)
        if checked:
            c.setLineWidth(1.3)
            p = c.beginPath()
            p.moveTo(cx - 2.4, Y(cy_top + 0.2))
            p.lineTo(cx - 0.6, Y(cy_top + 2.4))
            p.lineTo(cx + 2.6, Y(cy_top - 2.4))
            c.drawPath(p, stroke=1, fill=0)

    def draw_smilies(c, start_x, cy_top):
        r = 4.0
        cy = Y(cy_top)
        for i, face in enumerate(["smile", "neutral", "frown"]):
            fcx = start_x + i * 13.0
            c.setLineWidth(0.55)
            c.setStrokeColor(rl_black)
            c.circle(fcx, cy, r, stroke=1, fill=0)
            c.circle(fcx - 1.4, cy + 1.2, 0.5, stroke=0, fill=1)
            c.circle(fcx + 1.4, cy + 1.2, 0.5, stroke=0, fill=1)
            p = c.beginPath()
            if face == "smile":
                p.moveTo(fcx - 1.8, cy - 0.8)
                p.lineTo(fcx, cy - 2.1)
                p.lineTo(fcx + 1.8, cy - 0.8)
            elif face == "neutral":
                p.moveTo(fcx - 1.6, cy - 1.4)
                p.lineTo(fcx + 1.6, cy - 1.4)
            else:
                p.moveTo(fcx - 1.8, cy - 2.1)
                p.lineTo(fcx, cy - 0.8)
                p.lineTo(fcx + 1.8, cy - 2.1)
            c.drawPath(p, stroke=1, fill=0)

    def draw_sig(c, sig_data, x, y_bot_topcoord, w=70.0, h=22.0):
        cleaned = clean_signature_image(sig_data)
        if not cleaned:
            return
        try:
            bio = io.BytesIO(cleaned)
            im = Image.open(bio)
            iw, ih = im.size
            if iw == 0 or ih == 0:
                return
            aspect = float(iw) / float(ih)
            if aspect > (w / h):
                dw, dh = w, w / aspect
            else:
                dh, dw = h, h * aspect
            bio.seek(0)
            c.drawImage(rl_ImageReader(bio), x + (w - dw) / 2.0, Y(y_bot_topcoord), dw, dh, mask="auto")
        except Exception:
            pass

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    c = rl_canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    c.setTitle("FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen")
    c.setAuthor("LSP Wisata Indonesia")

    pnum = 1
    draw_header(c)

    # ================= PAGE 1 =================
    c.setFont("Lib-B", 10.0)
    c.setFillColor(rl_black)
    c.drawString(L, Y(119.0), "FR.MAPA.01- MERENCANAKAN AKTIVITAS DAN PROSES ASESMEN")

    # Skema Table
    sc1, sc2, sc3 = 175.0, 224.0, 236.0
    st_top = 128.0
    skema_nama = str(data.get("skema_nama") or "-")
    judul_lines = wrap_w(skema_nama, "Lib-I", 9.5, R - (sc3 + 8.0)) or [skema_nama]
    st_mid = st_top + max(20.0, 8.0 + len(judul_lines) * 11.5)
    st_bot = st_mid + 20.0
    c.setLineWidth(0.75)
    c.setStrokeColor(rl_black)
    c.rect(L, Y(st_bot), R - L, st_bot - st_top)
    c.line(sc1, Y(st_mid), R, Y(st_mid))
    for sx in (sc1, sc2, sc3):
        c.line(sx, Y(st_bot), sx, Y(st_top))
    c.setFont("Lib-B", 9.5)
    c.drawString(L + 6.0, Y(st_top + 14.0), "Skema Sertifikasi")
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(st_top + 26.0), "(KKNI/Okupasi/Klaster)")
    c.setFont("Lib", 9.5)
    c.drawString(sc1 + 6.0, Y(st_top + 14.0), "Judul")
    c.drawString(sc2 + 4.0, Y(st_top + 14.0), ":")
    c.setFont("Lib-I", 9.5)
    for idx, jl in enumerate(judul_lines):
        c.drawString(sc3 + 6.0, Y(st_top + 14.0 + idx * 11.5), jl)
    c.setFont("Lib", 9.5)
    c.drawString(sc1 + 6.0, Y(st_mid + 14.0), "Nomor")
    c.drawString(sc2 + 4.0, Y(st_mid + 14.0), ":")
    c.drawString(sc3 + 6.0, Y(st_mid + 14.0), str(data.get("skema_nomor") or "-"))

    # Section 1 Table
    s1_top = st_bot + 4.0
    s1_bot = 782.0
    c.setLineWidth(0.75)
    c.rect(L, Y(s1_bot), R - L, s1_bot - s1_top)
    hdr_bot = s1_top + 18.0
    c.line(L, Y(hdr_bot), R, Y(hdr_bot))
    c.setFont("Lib-B", 9.5)
    c.drawString(L + 6.0, Y(s1_top + 12.5), "1.   Menentukan Pendekatan Asesmen")

    pendekatan = str(data.get("pendekatan_asesi", "")).lower()
    tujuan = str(data.get("tujuan_asesmen", "Sertifikasi")).lower()
    lingkungan = str(data.get("konteks_lingkungan", "simulasi")).lower()
    peluang = str(data.get("peluang_bukti", "Tersedia")).lower()
    hubungan = str(data.get("hubungan_standar", "mendukung asesmen")).lower()
    pelaksana = str(data.get("pelaksana_asesmen", "Lembaga Sertifikasi")).lower()
    orang_relevan = [str(x).lower() for x in data.get("orang_relevan", [])]

    rem = s1_bot - hdr_bot
    h_as = rem * 0.285
    h_tj = rem * 0.145
    h_ling = rem * 0.055
    h_pel = rem * 0.085
    h_hub = rem * 0.115
    h_pelak = rem * 0.115
    h_rel = rem - (h_as + h_tj + h_ling + h_pel + h_hub + h_pelak)

    # 1.1 Asesi
    y_as_top = hdr_bot
    y_as_bot = y_as_top + h_as
    x_sub1 = L + 30.0
    x_sp = L + 100.0
    c.setLineWidth(0.55)
    c.line(x_sub1, Y(y_as_top), x_sub1, Y(y_as_bot))
    c.line(x_sp, Y(y_as_top), x_sp, Y(y_as_bot))
    c.line(L, Y(y_as_bot), R, Y(y_as_bot))
    c.setFont("Lib-B", 9.0)
    c.drawString(L + 6.0, Y(y_as_top + 15.0), "1.1.")
    c.drawString(x_sub1 + 6.0, Y(y_as_top + 15.0), "Asesi")

    asesi_opts = [
        (["Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas", "praktek mampu telusur terhadap standar kompetensi"],
         "kurikulum dan fasilitas" in pendekatan or ("mampu telusur" in pendekatan and "belum" not in pendekatan and "industri" not in pendekatan)),
        (["Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum berbasis", "kompetensi."],
         "kurikulum belum" in pendekatan and "industri" not in pendekatan),
        (["Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang", "dalam operasionalnya mampu telusur dengan standar kompetensi"],
         "industri" in pendekatan and "mampu telusur" in pendekatan and "belum" not in pendekatan),
        (["Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang", "dalam operasionalnya belum berbasis kompetensi."],
         "industri" in pendekatan and "belum berbasis" in pendekatan),
        (["Pelatihan / belajar mandiri atau otodidak."],
         "mandiri" in pendekatan or "otodidak" in pendekatan),
    ]
    cy = y_as_top
    rh_as = h_as / 5.0
    for idx, (lines, chk) in enumerate(asesi_opts):
        rt, rb = cy, cy + rh_as
        if idx < 4:
            c.line(x_sp, Y(rb), R, Y(rb))
        draw_cb(c, x_sp + 11.0, (rt + rb) / 2.0, chk)
        c.setFont("Lib", 8.4)
        if len(lines) == 1:
            c.drawString(x_sp + 21.0, Y((rt + rb) / 2.0 + 3.0), lines[0])
        else:
            mid = (rt + rb) / 2.0
            c.drawString(x_sp + 21.0, Y(mid - 2.0), lines[0])
            c.drawString(x_sp + 21.0, Y(mid + 9.0), lines[1])
        cy = rb

    # Tujuan Asesmen
    y_tj_top = y_as_bot
    y_tj_bot = y_tj_top + h_tj
    c.line(x_sp, Y(y_tj_top), x_sp, Y(y_tj_bot))
    c.line(L, Y(y_tj_bot), R, Y(y_tj_bot))
    c.setFont("Lib-B", 9.0)
    c.drawString(L + 6.0, Y(y_tj_top + 16.0), "Tujuan")
    c.drawString(L + 6.0, Y(y_tj_top + 28.0), "Asesmen")
    tj_opts = [
        ("Sertifikasi", "sertifikasi" in tujuan),
        ("Pengakuan Kompetensi Terkini (PKT)", "terkini" in tujuan or "pkt" in tujuan),
        ("Rekognisi Pembelajaran Lampau", "rekognisi" in tujuan or "rpl" in tujuan),
        ("Lainnya", "lainnya" in tujuan),
    ]
    cy = y_tj_top
    rh_tj = h_tj / 4.0
    for idx, (txt, chk) in enumerate(tj_opts):
        rt, rb = cy, cy + rh_tj
        if idx < 3:
            c.line(x_sp, Y(rb), R, Y(rb))
        draw_cb(c, x_sp + 11.0, (rt + rb) / 2.0, chk)
        c.setFont("Lib", 8.5)
        c.drawString(x_sp + 21.0, Y((rt + rb) / 2.0 + 3.0), txt)
        cy = rb

    # Konteks Asesmen
    y_kt_top = y_tj_bot
    y_kt_bot = y_kt_top + h_ling + h_pel + h_hub + h_pelak
    x_k1 = L + 72.0
    x_k2 = L + 180.0
    c.line(x_k1, Y(y_kt_top), x_k1, Y(y_kt_bot))
    c.line(x_k2, Y(y_kt_top), x_k2, Y(s1_bot))
    c.setFont("Lib-B", 9.0)
    c.drawString(L + 6.0, Y(y_kt_top + 16.0), "Konteks")
    c.drawString(L + 6.0, Y(y_kt_top + 28.0), "Asesmen:")

    # Lingkungan
    y_ling_bot = y_kt_top + h_ling
    c.line(x_k1, Y(y_ling_bot), R, Y(y_ling_bot))
    mid = (y_kt_top + y_ling_bot) / 2.0
    c.setFont("Lib", 8.5)
    c.drawString(x_k1 + 6.0, Y(mid + 3.0), "Lingkungan")
    draw_cb(c, x_k2 + 11.0, mid, "nyata" in lingkungan)
    c.drawString(x_k2 + 21.0, Y(mid + 3.0), "Tempat kerja nyata")
    draw_cb(c, x_k2 + 150.0, mid, "simulasi" in lingkungan)
    c.drawString(x_k2 + 160.0, Y(mid + 3.0), "Tempat kerja simulasi")

    # Peluang
    y_pel_bot = y_ling_bot + h_pel
    c.line(x_k1, Y(y_pel_bot), R, Y(y_pel_bot))
    mid = (y_ling_bot + y_pel_bot) / 2.0
    c.setFont("Lib", 8.2)
    c.drawString(x_k1 + 6.0, Y(mid - 9.0), "Peluang untuk")
    c.drawString(x_k1 + 6.0, Y(mid + 1.5), "mengumpulkan bukti dalam")
    c.drawString(x_k1 + 6.0, Y(mid + 12.0), "sejumlah situasi")
    c.setFont("Lib", 8.5)
    draw_cb(c, x_k2 + 11.0, mid, "tersedia" in peluang)
    c.drawString(x_k2 + 21.0, Y(mid + 3.0), "Tersedia")
    draw_cb(c, x_k2 + 150.0, mid, "terbatas" in peluang)
    c.drawString(x_k2 + 160.0, Y(mid + 3.0), "Terbatas")

    # Hubungan
    y_hub_bot = y_pel_bot + h_hub
    c.line(x_k1, Y(y_hub_bot), R, Y(y_hub_bot))
    mid = (y_pel_bot + y_hub_bot) / 2.0
    c.setFont("Lib", 8.2)
    c.drawString(x_k1 + 6.0, Y(mid - 9.0), "Hubungan antara")
    c.drawString(x_k1 + 6.0, Y(mid + 1.5), "standar kompetensi")
    c.drawString(x_k1 + 6.0, Y(mid + 12.0), "dan:")
    hub_opts = [
        ("Bukti untuk mendukung asesmen :", "mendukung asesmen" in hubungan or "bukti untuk" in hubungan),
        ("Aktivitas kerja di tempat kerja Asesi:", "aktivitas kerja" in hubungan or "tempat kerja asesi" in hubungan),
        ("Kegiatan Pembelajaran:", "kegiatan pembelajaran" in hubungan),
    ]
    cy = y_pel_bot
    rh_hub = h_hub / 3.0
    c.setFont("Lib", 8.4)
    for idx, (txt, chk) in enumerate(hub_opts):
        rt, rb = cy, cy + rh_hub
        if idx < 2:
            c.line(x_k2, Y(rb), R, Y(rb))
        draw_cb(c, x_k2 + 11.0, (rt + rb) / 2.0, chk)
        c.drawString(x_k2 + 21.0, Y((rt + rb) / 2.0 + 3.0), txt)
        draw_smilies(c, R - 50.0, (rt + rb) / 2.0)
        cy = rb

    # Pelaksana
    y_pelak_bot = y_hub_bot + h_pelak
    c.line(L, Y(y_pelak_bot), R, Y(y_pelak_bot))
    mid = (y_hub_bot + y_pelak_bot) / 2.0
    c.setFont("Lib", 8.2)
    c.drawString(x_k1 + 6.0, Y(mid - 9.0), "Siapa yang")
    c.drawString(x_k1 + 6.0, Y(mid + 1.5), "melakukan asesmen/")
    c.drawString(x_k1 + 6.0, Y(mid + 12.0), "RPL")
    pelak_opts = [
        ("Lembaga Sertifikasi Profesi Wisata Indonesia", "lembaga sertifikasi" in pelaksana or "lsp" in pelaksana),
        ("Organisasi Pelatihan", "organisasi pelatihan" in pelaksana),
        ("Asesor Perusahaan", "asesor perusahaan" in pelaksana),
    ]
    cy = y_hub_bot
    rh_pelak = h_pelak / 3.0
    c.setFont("Lib", 8.4)
    for idx, (txt, chk) in enumerate(pelak_opts):
        rt, rb = cy, cy + rh_pelak
        if idx < 2:
            c.line(x_k2, Y(rb), R, Y(rb))
        draw_cb(c, x_k2 + 11.0, (rt + rb) / 2.0, chk)
        c.drawString(x_k2 + 21.0, Y((rt + rb) / 2.0 + 3.0), txt)
        cy = rb

    # Konfirmasi dengan orang yang relevan
    mid = (y_pelak_bot + s1_bot) / 2.0
    c.setFont("Lib-B", 8.8)
    c.drawString(L + 6.0, Y(mid - 5.0), "Konfirmasi dengan orang")
    c.drawString(L + 6.0, Y(mid + 7.0), "yang relevan")
    relevan_opts = [
        (["Manajer sertifikasi"], any("manajer sertifikasi" in o for o in orang_relevan) or not orang_relevan),
        (["Master Asesor / Master Trainer / Lead Asesor Kompetensi"], any("master asesor" in o or "lead asesor" in o for o in orang_relevan) or not orang_relevan),
        (["Manajer Pelatihan Lembaga Training terakreditasi /", "Lembaga Training terdaftar"], any("manajer pelatihan" in o or "lembaga training" in o for o in orang_relevan)),
        (["Manajer atau supervisor ditempat kerja"], any("supervisor" in o or "ditempat kerja" in o for o in orang_relevan)),
    ]
    weights = [1.0, 1.0, 1.35, 1.0]
    tw = sum(weights)
    cy = y_pelak_bot
    c.setFont("Lib", 8.4)
    for idx, (lines, chk) in enumerate(relevan_opts):
        rh = (h_rel * weights[idx]) / tw
        rt, rb = cy, cy + rh
        if idx < 3:
            c.line(x_k2, Y(rb), R, Y(rb))
        draw_cb(c, x_k2 + 11.0, (rt + rb) / 2.0, chk)
        if len(lines) == 1:
            c.drawString(x_k2 + 21.0, Y((rt + rb) / 2.0 + 3.0), lines[0])
        else:
            m = (rt + rb) / 2.0
            c.drawString(x_k2 + 21.0, Y(m - 2.0), lines[0])
            c.drawString(x_k2 + 21.0, Y(m + 9.0), lines[1])
        cy = rb

    draw_pnum(c, pnum)
    c.showPage()
    pnum += 1
    draw_header(c)

    # ================= PAGE 2+: 1.2 Standar Industri & 2. Mempersiapkan Rencana Asesmen =================
    cur_y = Y_TOP_CONT

    def new_page():
        nonlocal pnum, cur_y
        draw_pnum(c, pnum)
        c.showPage()
        pnum += 1
        draw_header(c)
        cur_y = Y_TOP_CONT

    # 1.2 Standar Industri atau Tempat Kerja
    si_top = cur_y
    si_c1, si_c2 = L + 32.0, L + 116.0
    si_dict = data.get("standar_industri") or {}
    if not isinstance(si_dict, dict):
        si_dict = {}

    def si_opt(key, def_chk=False, def_val=""):
        v = si_dict.get(key, {})
        if isinstance(v, dict):
            return bool(v.get("checked", def_chk)), str(v.get("text", def_val) or "").strip()
        if isinstance(v, bool):
            return v, def_val
        if isinstance(v, str):
            return True, v.strip()
        return def_chk, def_val

    sk_chk, sk_val = si_opt("standar_kompetensi", True, "SKKNI No.125 Tahun 2024")
    kp_chk, kp_val = si_opt("kurikulum_pelatihan", False, "")
    kin_chk, kin_val = si_opt("spesifikasi_kinerja", False, "")
    prod_chk, prod_val = si_opt("spesifikasi_produk", False, "")
    ped_chk, ped_val = si_opt("pedoman_khusus", False, "")

    si_rows = [
        ("Standar Kompetensi: ", sk_val, sk_chk, False),
        ("Kriteria asesmen dari kurikulum pelatihan", kp_val if kp_val and kp_val != "Kriteria asesmen dari kurikulum pelatihan" else "", kp_chk, False),
        ("Spesifikasi kinerja suatu perusahaan atau industri: ", kin_val, kin_chk, True),
        ("Spesifikasi Produk: ", prod_val, prod_chk, True),
        ("Pedoman khusus: ", ped_val, ped_chk, True),
    ]
    si_rh = 21.0
    si_bot = si_top + si_rh * 5
    c.setLineWidth(0.75)
    c.rect(L, Y(si_bot), R - L, si_bot - si_top)
    c.setLineWidth(0.55)
    c.line(si_c1, Y(si_top), si_c1, Y(si_bot))
    c.line(si_c2, Y(si_top), si_c2, Y(si_bot))
    c.setFont("Lib-B", 9.0)
    c.drawString(L + 7.0, Y(si_top + 14.0), "1.2")
    c.setFont("Lib", 8.6)
    c.drawString(si_c1 + 6.0, Y(si_top + 14.0), "Standar Industri")
    c.drawString(si_c1 + 6.0, Y(si_top + 25.0), "atau Tempat")
    c.drawString(si_c1 + 6.0, Y(si_top + 36.0), "Kerja")

    for idx, (pref, val, chk, is_ital) in enumerate(si_rows):
        rt = si_top + idx * si_rh
        rb = rt + si_rh
        if idx < 4:
            c.line(si_c2, Y(rb), R, Y(rb))
        draw_cb(c, si_c2 + 11.0, (rt + rb) / 2.0, chk)
        tx = si_c2 + 21.0
        ty = (rt + rb) / 2.0 + 3.0
        disp_pref = pref if val else pref.rstrip()
        c.setFont("Lib", 8.5)
        c.drawString(tx, Y(ty), disp_pref)
        if val:
            pw = rl_pdfmetrics.stringWidth(pref, "Lib", 8.5)
            c.setFont("Lib-I" if is_ital else "Lib", 8.5)
            c.drawString(tx + pw, Y(ty), val[:55])

    cur_y = si_bot + 14.0

    # Section 2 Banner
    c.setLineWidth(0.75)
    c.rect(L, Y(cur_y + 20.0), R - L, 20.0)
    c.setFont("Lib-B", 10.0)
    c.drawString(L + 8.0, Y(cur_y + 13.5), "2.    Mempersiapkan Rencana Asesmen")
    cur_y += 28.0

    # 2a. Daftar Unit Kompetensi (Kelompok Pekerjaan)
    units = data.get("units") or []
    if units:
        u_c1, u_c2, u_c3 = L + 90.0, L + 125.0, L + 220.0

        def draw_units_list_header(top_y):
            hb = top_y + 24.0
            c.setLineWidth(0.65)
            c.rect(L, Y(hb), R - L, 24.0)
            for ux in (u_c1, u_c2, u_c3):
                c.line(ux, Y(top_y), ux, Y(hb))
            c.setFont("Lib-B", 8.8)
            c.drawCentredString((L + u_c1) / 2.0, Y(top_y + 15.0), "Kelompok")
            c.drawCentredString((u_c1 + u_c2) / 2.0, Y(top_y + 15.0), "No.")
            c.drawCentredString((u_c2 + u_c3) / 2.0, Y(top_y + 15.0), "Kode Unit")
            c.drawCentredString((u_c3 + R) / 2.0, Y(top_y + 15.0), "Judul Unit")
            return hb

        def close_units_list_box(box_top, body_top, bot_y):
            c.setLineWidth(0.65)
            c.rect(L, Y(bot_y), R - L, bot_y - box_top)
            for ux in (u_c1, u_c2, u_c3):
                c.line(ux, Y(box_top), ux, Y(bot_y))
            mid = (body_top + bot_y) / 2.0
            c.setFont("Lib-B", 8.5)
            c.drawCentredString((L + u_c1) / 2.0, Y(mid - 3.0), "Kelompok")
            c.drawCentredString((L + u_c1) / 2.0, Y(mid + 8.0), "Pekerjaan 1")

        box_top = cur_y
        body_top = draw_units_list_header(box_top)
        cur_y = body_top
        has_u_rows = False

        for idx, u in enumerate(units, 1):
            code = str(u.get("code", "") if isinstance(u, dict) else (u[0] if isinstance(u, (list, tuple)) else u))
            title = str(u.get("title", "") if isinstance(u, dict) else (u[1] if isinstance(u, (list, tuple)) and len(u) > 1 else ""))
            t_lines = wrap_w(title, "Lib", 8.5, (R - 6.0) - (u_c3 + 6.0)) or [title]
            rh = max(18.0, 7.0 + len(t_lines) * 10.5)

            if cur_y + rh > Y_MAX and has_u_rows:
                close_units_list_box(box_top, body_top, cur_y)
                new_page()
                box_top = cur_y
                body_top = draw_units_list_header(box_top)
                cur_y = body_top
                has_u_rows = False

            if has_u_rows:
                c.setLineWidth(0.45)
                c.line(u_c1, Y(cur_y), R, Y(cur_y))

            ym = cur_y + rh / 2.0
            c.setFont("Lib", 8.5)
            c.drawCentredString((u_c1 + u_c2) / 2.0, Y(ym + 3.0), f"{idx}.")
            c.drawCentredString((u_c2 + u_c3) / 2.0, Y(ym + 3.0), code)
            for k, tl in enumerate(t_lines):
                c.drawString(u_c3 + 6.0, Y(ym + 3.0 + (k - (len(t_lines) - 1) / 2.0) * 10.5), tl)
            cur_y += rh
            has_u_rows = True

        close_units_list_box(box_top, body_top, cur_y)
        cur_y += 14.0

    # 2b. Rencana Asesmen Berdasarkan Unit Kompetensi (Terhubung langsung ke AK.01)
    # Column boundaries: Unit(50.4..148), Bukti(148..244), L(244..260), TL(260..276), T(276..292),
    # CL(292..338), DIT(338..374), TJ(374..420), CVP(420..464), CRP(464..502), VPK(502..544.8)
    cols = [L, 148.0, 244.0, 260.0, 276.0, 292.0, 338.0, 374.0, 420.0, 464.0, 502.0, R]
    raw_types = data.get("evidence_types", ["L", "T"])
    ev_types = [str(x).strip().upper() for x in (raw_types if isinstance(raw_types, list) else str(raw_types).split(","))]
    raw_met = data.get("metode_codes", ["CL", "DPT"])
    met_codes = [("CVP" if str(x).strip().upper() == "VP" else str(x).strip().upper()) for x in (raw_met if isinstance(raw_met, list) else [])]
    ev_lines = data.get("evidence_lines") or ["Hasil observasi", "langsung dan tanya", "jawab tentang"]
    ev_prefix = " ".join(str(x).strip() for x in ev_lines if str(x).strip())

    def draw_plan_table_header(top_y):
        hdr_h = 216.0
        sub_y = top_y + 84.0
        bot_y = top_y + hdr_h
        c.setLineWidth(0.75)
        c.setStrokeColor(rl_black)
        c.rect(L, Y(bot_y), R - L, hdr_h)
        # Vertical full lines
        c.setLineWidth(0.6)
        for cx in (cols[1], cols[2], cols[5]):
            c.line(cx, Y(top_y), cx, Y(bot_y))
        # Sub-columns from sub_y down to bot_y
        for cx in (cols[3], cols[4], cols[6], cols[7], cols[8], cols[9], cols[10]):
            c.line(cx, Y(sub_y), cx, Y(bot_y))
        c.line(cols[2], Y(sub_y), R, Y(sub_y))

        # Col 0: Unit Kompetensi
        c.setFont("Cal-B", 9.0)
        c.drawCentredString((cols[0] + cols[1]) / 2.0, Y(top_y + 98.0), "Unit")
        c.drawCentredString((cols[0] + cols[1]) / 2.0, Y(top_y + 110.0), "Kompetensi")

        # Col 1: Bukti-Bukti
        c.setFont("Cal-B", 8.8)
        c.drawCentredString((cols[1] + cols[2]) / 2.0, Y(top_y + 32.0), "Bukti-Bukti")
        c.setFont("Cal", 8.0)
        for idx, bl in enumerate(["(Kinerja, Produk,", "Portofolio, dan /", "atau", "Pengetahuan)", "diidentifikasi", "berdasarkan", "Kriteria Unjuk", "Kerja dan", "Pendekatan", "Asesmen."]):
            c.drawCentredString((cols[1] + cols[2]) / 2.0, Y(top_y + 47.0 + idx * 11.2), bl)

        # Col 2..4: Jenis Bukti
        c.setFont("Cal-B", 8.8)
        c.drawCentredString((cols[2] + cols[5]) / 2.0, Y(top_y + 36.0), "Jenis")
        c.drawCentredString((cols[2] + cols[5]) / 2.0, Y(top_y + 48.0), "Bukti")
        for cx0, cx1, lbl in [(cols[2], cols[3], "L"), (cols[3], cols[4], "TL"), (cols[4], cols[5], "T")]:
            c.drawCentredString((cx0 + cx1) / 2.0, Y((sub_y + bot_y) / 2.0 + 3.0), lbl)

        # Col 5..11: Metode dan Perangkat Asesmen (strictly centered within cols[5]..R)
        mcx = (cols[5] + R) / 2.0
        c.setFont("Cal-B", 8.0)
        c.drawCentredString(mcx, Y(top_y + 13.0), "Metode dan Perangkat")
        c.drawCentredString(mcx, Y(top_y + 24.0), "Asesmen")
        c.setFont("Cal-B", 7.2)
        for idx, ml in enumerate([
            "CL (Ceklis Observasi), DIT (Daftar Instruksi",
            "Terstruktur), DPL (Daftar Pertanyaan Lisan), DPT",
            "(Daftar Pertanyaan Tertulis), VPK (Verifikasi Pihak",
            "Ketiga), CVP (Ceklis Verifikasi Portofolio), CRP (Ceklis",
            "Reviu Produk), PW (Pertanyaan Wawancara)",
        ]):
            c.drawCentredString(mcx, Y(top_y + 36.0 + idx * 10.5), ml)

        # Vertical rotated sub-headers (centered both horizontally & vertically inside each cell)
        sub_defs = [
            (cols[5], cols[6], "Observasi langsung", [
                "(kerja nyata/aktivitas waktu nyata",
                "di tempat kerja di lingkungan",
                "tempat kerja yang disimulasikan)",
            ]),
            (cols[6], cols[7], "Kegiatan Terstruktur", [
                "(latihan simulasi dan bermain peran,",
                "proyek, presentasi, lembar kegiatan)",
            ]),
            (cols[7], cols[8], "Tanya Jawab", [
                "(pertanyaan tertulis, wawancara,",
                "asesmen diri, tanya jawab lisan,",
                "angket, ujian lisan atau tertulis)",
            ]),
            (cols[8], cols[9], "Verifikasi Portofolio", [
                "(sampel pekerjaan yang disusun oleh Asesi,",
                "produk dengan dokumentasi pendukung,",
                "bukti sejarah, jurnal atau buku catatan,",
                "informasi tentang pengalaman hidup)",
            ]),
            (cols[9], cols[10], "Reviu Produk", [
                "Produk hasil proyek, contoh hasil",
                "kerja/produk",
            ]),
            (cols[10], cols[11], "Verifikasi Pihak Ketiga", [
                "(testimoni dan laporan dari atasan, bukti",
                "pelatihan, otentikasi pencapaian sebelumnya,",
                "wawancara dengan atasan, atau rekan kerja)",
            ]),
        ]
        mid_rot_y = Y((sub_y + bot_y) / 2.0)
        for cx0, cx1, title_v, sub_lines in sub_defs:
            total_lines = 1 + len(sub_lines)
            line_step = 8.0
            c.saveState()
            c.translate((cx0 + cx1) / 2.0, mid_rot_y)
            c.rotate(90)
            # In rotated coords: x = vertical axis along cell, y = horizontal offset across column
            start_y_off = ((total_lines - 1) * line_step) / 2.0 - 2.0
            c.setFont("Cal-B", 7.6)
            c.drawCentredString(0, start_y_off, title_v)
            c.setFont("Cal", 6.2)
            for s_idx, s_txt in enumerate(sub_lines):
                c.drawCentredString(0, start_y_off - (s_idx + 1) * line_step, s_txt)
            c.restoreState()

        return bot_y

    def close_plan_table(box_top, bot_y):
        sub_y = box_top + 84.0
        c.setLineWidth(0.75)
        c.rect(L, Y(bot_y), R - L, bot_y - box_top)
        c.setLineWidth(0.6)
        for cx in (cols[1], cols[2], cols[5]):
            c.line(cx, Y(box_top), cx, Y(bot_y))
        for cx in (cols[3], cols[4], cols[6], cols[7], cols[8], cols[9], cols[10]):
            c.line(cx, Y(sub_y), cx, Y(bot_y))

    if cur_y + 265.0 > Y_MAX:
        new_page()

    plan_box_top = cur_y
    cur_y = draw_plan_table_header(plan_box_top)
    has_p_rows = False

    for idx, u in enumerate(units, 1):
        title = str(u.get("title", "") if isinstance(u, dict) else (u[1] if isinstance(u, (list, tuple)) and len(u) > 1 else u))
        u_lines = wrap_w(f"{idx}. {title}", "Lib", 8.2, (cols[1] - cols[0]) - 8.0) or [f"{idx}. {title}"]
        b_lines = wrap_w(f"{ev_prefix} {title}", "Lib", 8.0, (cols[2] - cols[1]) - 8.0) or [ev_prefix]
        rh = max(26.0, 10.0 + max(len(u_lines), len(b_lines)) * 10.2)

        if cur_y + rh > Y_MAX and has_p_rows:
            close_plan_table(plan_box_top, cur_y)
            new_page()
            plan_box_top = cur_y
            cur_y = draw_plan_table_header(plan_box_top)
            has_p_rows = False

        if has_p_rows:
            c.setLineWidth(0.45)
            c.line(L, Y(cur_y), R, Y(cur_y))

        ym = cur_y + rh / 2.0
        c.setFont("Lib", 8.2)
        for k, ul in enumerate(u_lines):
            c.drawString(cols[0] + 4.0, Y(cur_y + 11.0 + k * 10.2), ul)
        c.setFont("Lib", 8.0)
        for k, bl in enumerate(b_lines):
            c.drawString(cols[1] + 4.0, Y(cur_y + 11.0 + k * 10.2), bl)

        # Jenis Bukti (L, TL, T)
        c.setFont("Lib-B", 8.5)
        if "L" in ev_types:
            c.drawCentredString((cols[2] + cols[3]) / 2.0, Y(ym + 3.0), "L")
        if "TL" in ev_types:
            c.drawCentredString((cols[3] + cols[4]) / 2.0, Y(ym + 3.0), "TL")
        if "T" in ev_types:
            c.drawCentredString((cols[4] + cols[5]) / 2.0, Y(ym + 3.0), "T")

        # Metode & Perangkat
        if "CL" in met_codes:
            c.drawCentredString((cols[5] + cols[6]) / 2.0, Y(ym + 3.0), "CL")
        if "DIT" in met_codes:
            c.drawCentredString((cols[6] + cols[7]) / 2.0, Y(ym + 3.0), "DIT")
        tj_items = [x for x in ["DPT", "DPL", "PW"] if x in met_codes]
        if tj_items:
            tj_str = ", ".join(tj_items)
            c.setFont("Lib-B", 7.0 if len(tj_items) >= 3 else (7.8 if len(tj_items) == 2 else 8.5))
            c.drawCentredString((cols[7] + cols[8]) / 2.0, Y(ym + 3.0), tj_str)
            c.setFont("Lib-B", 8.5)
        if "CVP" in met_codes:
            c.drawCentredString((cols[8] + cols[9]) / 2.0, Y(ym + 3.0), "CVP")
        if "CRP" in met_codes:
            c.drawCentredString((cols[9] + cols[10]) / 2.0, Y(ym + 3.0), "CRP")
        if "VPK" in met_codes:
            c.drawCentredString((cols[10] + cols[11]) / 2.0, Y(ym + 3.0), "VPK")

        cur_y += rh
        has_p_rows = True

    close_plan_table(plan_box_top, cur_y)
    cur_y += 14.0

    # ================= SECTION 3: Modifikasi & Kontekstualisasi =================
    mod_items = [
        ("3.1. a. Karakteristik Asesi:", "karakteristik khusus Asesi", bool(data.get("has_karakteristik", False)), str(data.get("karakteristik_detail") or "")),
        ("       b. Kebutuhan kontekstualisasi terkait tempat kerja:", "kebutuhan kontekstualisasi", bool(data.get("has_kontekstualisasi", False)), str(data.get("kontekstualisasi_detail") or "")),
        ("3.2. Saran yang diberikan oleh paket pelatihan atau pengembang pelatihan", "saran", bool(data.get("has_saran", False)), str(data.get("saran_detail") or "")),
        ("3.3. Penyesuaian perangkat asesmen terkait kebutuhan kontekstualisasi", "penyesuaian perangkat", bool(data.get("has_penyesuaian", False)), str(data.get("penyesuaian_detail") or "")),
        ("3.4. Peluang untuk kegiatan asesmen terintegrasi dan mencatat setiap perubahan yang diperlukan untuk alat asesmen", "peluang", bool(data.get("has_terintegrasi", False)), str(data.get("terintegrasi_detail") or "")),
    ]
    tasks = data.get("integrated_tasks") or []
    est_s3_h = 22.0 + len(mod_items) * 34.0 + len(tasks) * 12.0
    if cur_y + est_s3_h > Y_MAX:
        new_page()

    s3_top = cur_y
    s3_mid = L + 245.0
    c.setLineWidth(0.75)
    c.line(L, Y(s3_top), R, Y(s3_top))
    c.line(L, Y(s3_top + 20.0), R, Y(s3_top + 20.0))
    c.setFont("Lib-B", 9.5)
    c.drawString(L + 6.0, Y(s3_top + 13.5), "3.  Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi:")
    cur_y = s3_top + 20.0

    for idx, (left_lbl, suffix_lbl, has_it, detail) in enumerate(mod_items):
        l_lines = wrap_w(left_lbl, "Lib", 8.5, (s3_mid - L) - 12.0)
        d_lines = wrap_w(f"Jika Ada, tuliskan: {detail}" if detail else "Jika Ada, tuliskan...........................................", "Lib", 8.4, (R - s3_mid) - 12.0)
        extra_t = tasks if (idx == 4 and has_it and isinstance(tasks, list)) else []
        rh = max(32.0, 12.0 + max(len(l_lines), 1 + len(d_lines) + len(extra_t)) * 11.5)

        if cur_y + rh > Y_MAX:
            c.setLineWidth(0.75)
            c.rect(L, Y(cur_y), R - L, cur_y - s3_top)
            c.line(s3_mid, Y(s3_top + 20.0), s3_mid, Y(cur_y))
            new_page()
            s3_top = cur_y
            c.line(L, Y(s3_top), R, Y(s3_top))

        if idx > 0:
            c.setLineWidth(0.5)
            c.line(L, Y(cur_y), R, Y(cur_y))

        c.setFont("Lib", 8.5)
        for k, ll in enumerate(l_lines):
            c.drawString(L + 6.0, Y(cur_y + 13.0 + k * 11.5), ll)

        # Ada / tidak ada* with strikethrough
        rx = s3_mid + 8.0
        ry = cur_y + 13.0
        w_ada = rl_pdfmetrics.stringWidth("Ada", "Lib", 8.5)
        w_sl = rl_pdfmetrics.stringWidth(" / ", "Lib", 8.5)
        w_tdk = rl_pdfmetrics.stringWidth("tidak ada*", "Lib", 8.5)
        c.drawString(rx, Y(ry), f"Ada / tidak ada* {suffix_lbl}")
        c.setLineWidth(0.9)
        if has_it:
            c.line(rx + w_ada + w_sl, Y(ry - 2.5), rx + w_ada + w_sl + w_tdk, Y(ry - 2.5))
        else:
            c.line(rx, Y(ry - 2.5), rx + w_ada, Y(ry - 2.5))

        dy_off = ry + 13.0
        for dl in d_lines:
            c.drawString(rx, Y(dy_off), dl)
            dy_off += 11.5
        for tk in extra_t:
            c.drawString(rx + 6.0, Y(dy_off), f"- {tk}")
            dy_off += 11.5

        cur_y += rh

    c.setLineWidth(0.75)
    c.rect(L, Y(cur_y), R - L, cur_y - s3_top)
    c.line(s3_mid, Y(s3_top + 20.0), s3_mid, Y(cur_y))
    c.setFont("Lib", 8.2)
    c.drawString(L + 6.0, Y(cur_y + 12.0), "*Coret yang tidak perlu")
    cur_y += 24.0

    # ================= FINAL PAGE: Konfirmasi & Penyusun / Validator =================
    penyusun_list = data.get("penyusun_list") or []
    validators_list = data.get("validators_list") or []
    p_rows = []
    if penyusun_list:
        for idx, item in enumerate(penyusun_list, 1):
            p_rows.append(("PENYUSUN", str(idx), str(item.get("name") or ""), str(item.get("no_met") or ""), item.get("signature"), str(data.get("tanggal") or "")))
    else:
        p_rows.append(("PENYUSUN", "1", str(data.get("asesor_nama") or ""), str(data.get("asesor_met") or ""), data.get("signature_asesor"), str(data.get("tanggal") or "")))

    v_rows = []
    if validators_list:
        for idx, item in enumerate(validators_list, 1):
            v_rows.append(("VALIDATOR", str(idx), str(item.get("name") or ""), str(item.get("no_met") or ""), item.get("signature"), str(data.get("tanggal") or "")))
    else:
        v_rows.append(("VALIDATOR", "1", "", "", None, ""))

    pv_rows = p_rows + v_rows
    pv_rh = 36.0
    needed_final_h = 122.0 + 39.0 + len(pv_rows) * pv_rh + 20.0
    if cur_y + needed_final_h > Y_MAX:
        new_page()

    # Konfirmasi Table
    kc1, kc2 = L + 255.0, L + 375.0
    k_top = cur_y
    k_hdr_bot = k_top + 26.0
    k_rows = [
        ("Manajer sertifikasi LSP", "", ""),
        ("Master Asesor / Master Trainer / Lead Asesor Kompetensi", "", ""),
        ("Manajer Pelatihan Lembaga Training terakreditasi / Lembaga Training terdaftar", "", ""),
        ("Manajer atau supervisor ditempat kerja", "", ""),
    ]
    k_rh = 24.0
    k_bot = k_hdr_bot + len(k_rows) * k_rh
    c.setLineWidth(0.75)
    c.rect(L, Y(k_bot), R - L, k_bot - k_top)
    c.line(L, Y(k_hdr_bot), R, Y(k_hdr_bot))
    c.line(kc1, Y(k_top), kc1, Y(k_bot))
    c.line(kc2, Y(k_top), kc2, Y(k_bot))
    c.setFont("Lib-B", 8.8)
    c.drawCentredString((L + kc1) / 2.0, Y(k_top + 16.0), "Orang yang relevan")
    c.drawCentredString((kc1 + kc2) / 2.0, Y(k_top + 16.0), "Nama")
    c.drawCentredString((kc2 + R) / 2.0, Y(k_top + 11.0), "Tandatangan dan")
    c.drawCentredString((kc2 + R) / 2.0, Y(k_top + 21.0), "Tanggal")
    c.setFont("Lib", 8.4)
    for idx, (lbl, nm, dt) in enumerate(k_rows):
        rt = k_hdr_bot + idx * k_rh
        if idx > 0:
            c.setLineWidth(0.5)
            c.line(L, Y(rt), R, Y(rt))
        lbl_l = wrap_w(lbl, "Lib", 8.3, (kc1 - L) - 10.0)
        for li, ll in enumerate(lbl_l):
            c.drawString(L + 6.0, Y(rt + 10.5 + li * 10.0), ll)

    cur_y = k_bot + 16.0

    # PENYUSUN DAN VALIDATOR
    c.setFont("Lib-B", 9.0)
    c.drawString(L, Y(cur_y + 10.0), "PENYUSUN DAN VALIDATOR")
    pv_top = cur_y + 15.0
    pv_hdr_bot = pv_top + 24.0
    pc1, pc2, pc3, pc4 = L + 72.0, L + 98.0, L + 295.0, L + 392.0
    pv_bot = pv_hdr_bot + len(pv_rows) * pv_rh
    c.setLineWidth(0.75)
    c.rect(L, Y(pv_bot), R - L, pv_bot - pv_top)
    c.line(L, Y(pv_hdr_bot), R, Y(pv_hdr_bot))
    for px in (pc1, pc2, pc3, pc4):
        c.line(px, Y(pv_top), px, Y(pv_bot))
    c.setFont("Lib-B", 8.4)
    c.drawCentredString((L + pc1) / 2.0, Y(pv_top + 15.0), "STATUS")
    c.drawCentredString((pc1 + pc2) / 2.0, Y(pv_top + 15.0), "NO")
    c.drawCentredString((pc2 + pc3) / 2.0, Y(pv_top + 15.0), "NAMA")
    c.drawCentredString((pc3 + pc4) / 2.0, Y(pv_top + 15.0), "NOMOR MET")
    c.drawCentredString((pc4 + R) / 2.0, Y(pv_top + 10.0), "TANDA TANGAN")
    c.drawCentredString((pc4 + R) / 2.0, Y(pv_top + 20.0), "DAN TANGGAL")

    for idx, (st_lbl, no_lbl, nm, met, sig, dt) in enumerate(pv_rows):
        rt = pv_hdr_bot + idx * pv_rh
        if idx > 0:
            prev_st = pv_rows[idx - 1][0]
            if st_lbl != prev_st:
                c.setLineWidth(0.75)
                c.line(L, Y(rt), R, Y(rt))
            else:
                c.setLineWidth(0.5)
                c.line(pc1, Y(rt), R, Y(rt))
        c.setFont("Lib", 8.5)
        c.drawCentredString((pc1 + pc2) / 2.0, Y(rt + 21.0), no_lbl)
        c.drawString(pc2 + 6.0, Y(rt + 21.0), nm)
        c.drawCentredString((pc3 + pc4) / 2.0, Y(rt + 21.0), met)
        if sig:
            draw_sig(c, sig, pc4 + 16.0, rt + 24.0, w=70.0, h=20.0)
        if dt:
            c.setFont("Lib", 7.8)
            c.drawCentredString((pc4 + R) / 2.0, Y(rt + 32.0), dt)

    c.setFont("Lib-B", 8.5)
    p_count = len(p_rows)
    v_count = len(v_rows)
    c.drawCentredString((L + pc1) / 2.0, Y(pv_hdr_bot + (p_count * pv_rh) / 2.0 + 3.0), "PENYUSUN")
    c.drawCentredString((L + pc1) / 2.0, Y(pv_hdr_bot + (p_count * pv_rh) + (v_count * pv_rh) / 2.0 + 3.0), "VALIDATOR")

    draw_pnum(c, pnum)
    c.showPage()
    c.save()
    print(f"OK -> Berhasil generate FR.MAPA.01 Dinamis ({pnum} Halaman): {dest_path}")


def render_mapa_overlay(master_pdf_path, data, dest_path):
    """Overlay assessor's MAPA data onto official clean master template, or generate dynamically when units exist."""
    if isinstance(data.get("units"), list) and len(data["units"]) > 0 and not os.path.isfile(master_pdf_path):
        render_mapa_dynamic(data, dest_path)
        return
    if not os.path.isfile(master_pdf_path):
        render_mapa_dynamic(data, dest_path)
        return

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
    asesi_nama = str(data.get("asesi_nama") or "").strip()
    meta["title"] = f"FR.MAPA.01 ({asesi_nama})" if asesi_nama else f"FR.MAPA.01 - {skema_nama}"
    meta["author"] = "LSP Wisindo"
    meta["subject"] = "FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen"
    meta["creator"] = "LSP Wisindo MUK System"
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
