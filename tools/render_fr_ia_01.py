#!/usr/bin/env python3
"""
Generator & Master Overlay FR.IA.01 (Ceklis Observasi Aktivitas Praktik) - LSP PrO Indo
Layout, tabel, dan font presisi identik dengan contoh master BNSP:
http://127.0.0.1:8000/print-example/ia01?ref=1

Fitur:
- Deteksi dinamis label & kolom per skema (Skema 1, 2, 3, dst.) tanpa pergeseran teks / tabrakan garis.
- Penghapusan 100% watermark (Word header artifact curves, vector stream 0.722, dan background images).
- Pembersihan tanda tangan digital transparan & kontras tinggi, aspect-ratio preserved, tidak nabrak garis.
- Font: Calibri 11pt (Regular, Bold, Italic) sesuai standar BNSP.
- Data dinamis database:
  * Skema sertifikasi (judul & nomor)
  * Asesi (nama & NIK)
  * Asesor (nama & nomor registrasi MET)
  * TUK & tanggal pelaksanaan
  * Checkbox KUK: "Ya" vs "Tidak" sesuai hasil observasi di DB
  * Catatan penilaian lanjut (jika ada)
  * Umpan balik asesor
  * Rekomendasi (KOMPETEN / BELUM KOMPETEN)
  * Tanda tangan digital asesi & asesor
"""

import os
import sys
import json
import io
import base64
import re
from PIL import Image
import pymupdf as fitz

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
DEFAULT_REF_PDF = os.path.join(
    PROJECT_ROOT,
    "lsp-document-dynamic",
    "MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection",
    "8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf"
)
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_IA_01_generated.pdf")

CALIBRI_REG = "C:/Windows/Fonts/calibri.ttf"
CALIBRI_BOLD = "C:/Windows/Fonts/calibrib.ttf"
CALIBRI_ITALIC = "C:/Windows/Fonts/calibrii.ttf"

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tuk_nama": "TUK Otomotif Mandiri",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "asesi_nama": "Wahyu Andika Rahadi",
    "asesi_nik": "3216010000000001",
    "tanggal": "19 Oktober 2026",
    "tanggal_ttd": "19 Oktober 2026",
    "tanggal_ttd_asesor": "19 Oktober 2026",
    "tanggal_ttd_asesi": "19 Oktober 2026",
    "is_kompeten": True,
    "umpan_balik": "Asesi telah menunjukkan pemenuhan unjuk kerja praktik sesuai standar KUK SKKNI dan SOP industri yang berlaku.",
    "signature_asesi": None,
    "signature_asesor": None,
    "units": [],
    "kuk_results_seq": {},
    "kuk_results_by_id": {},
    "unfulfilled_items": [],
}

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

                # 2. Word Artifact Header bezier curves (/Artifact ... /Subtype/Header>> BDC /GS.. gs ... h f* q)
                prefix = "/Artifact <</Attached [/Top]/Type/Pagination/Subtype/Header>> BDC"
                art_pos = stream.find(prefix)
                if art_pos != -1:
                    gs_match = re.search(r"/GS\d+\s+gs\s*", stream[art_pos:art_pos + 200])
                    if gs_match:
                        wm_start = art_pos + gs_match.end()
                        for term in ["h\r\nf*\r\nq", "h\nf*\nq", "h f* q"]:
                            term_idx = stream.find(term, wm_start)
                            if term_idx != -1 and (term_idx - wm_start) > 5000:
                                wm_end = term_idx + len(term) - 1  # preserve the trailing 'q'
                                stream = stream[:wm_start] + stream[wm_end:]
                                modified = True
                                cleaned += 1
                                break

                # 3. Clean background watermark images if any (> 400x400)
                for img in page.get_image_info(xrefs=True):
                    w = img.get("width", 0)
                    h = img.get("height", 0)
                    bbox = img.get("bbox", (0, 0, 0, 0))
                    img_w = bbox[2] - bbox[0]
                    img_h = bbox[3] - bbox[1]
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
    """
    Load signature and ensure strokes are solid, bold, and crisp on transparent background.
    Safely handles both transparent canvas signatures and solid white background scans.
    """
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
        # Check if corners are transparent
        corners_transparent = all(
            pixels[pt][3] < 50 for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1))
        )

        if corners_transparent:
            # Already transparent canvas: darken and strengthen strokes to make them crisp
            for y in range(h):
                for x in range(w):
                    r, g, b, a = pixels[x, y]
                    if a > 20:
                        # Make strokes solid and clearly visible in deep dark navy/black
                        pixels[x, y] = (10, 15, 30, min(255, int(a * 1.5)))
                    else:
                        pixels[x, y] = (0, 0, 0, 0)
        else:
            # Solid background (e.g. paper scan or white canvas):
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
    """Draw a sharp vector checkmark matching FR.APL.02 inside a checkbox bounding box."""
    bx0, by0, bx1, by1 = box_rect
    cx = (bx0 + bx1) / 2.0
    # True vertical center of font glyph inside line box
    cy = (by0 + by1) / 2.0 + 0.8

    # Sharp V-shape checkmark matching APL.02 (not wide/triangle)
    p1 = fitz.Point(cx - 2.5, cy - 0.2)
    p2 = fitz.Point(cx - 0.6, cy + 2.4)
    p3 = fitz.Point(cx + 3.0, cy - 3.0)

    shape = page.new_shape()
    shape.draw_polyline([p1, p2, p3])
    shape.finish(color=(0, 0, 0), width=1.4, lineCap=1, lineJoin=1)
    shape.commit()

def find_colon_for_label(page, label_text, max_x_dist=200, y_tol=8):
    """Find the colon ':' matching a given label on the page."""
    rects = page.search_for(label_text)
    if not rects:
        return None
    r = rects[0]
    colons = [c for c in page.search_for(':') if c.x0 >= r.x0 and (c.x0 - r.x1) < max_x_dist and abs(c.y0 - r.y0) < y_tol]
    if colons:
        colons.sort(key=lambda c: c.x0)
        return colons[0]
    return None

def render_master_overlay(master_pdf_path, data, dest_path):
    """Render dynamic data on top of BNSP master PDF with full scheme resilience."""
    if not os.path.isfile(master_pdf_path):
        raise FileNotFoundError(f"Master PDF not found: {master_pdf_path}")

    doc = fitz.open(master_pdf_path)
    total_pages = len(doc)

    # 1. Strip all watermarks from all pages
    clean_watermark(doc)

    kuk_results = data.get("kuk_results_seq") or data.get("kuk_results_by_id") or {}
    fallback_is_k = bool(data.get("is_kompeten", True))
    global_kuk_idx = 0

    # Locate recommendation & signature page dynamically
    rec_page_idx = total_pages - 1
    for p_i in range(total_pages - 1, -1, -1):
        if doc[p_i].search_for("Rekomendasi"):
            rec_page_idx = p_i
            break

    # Register Calibri fonts
    has_calibri = os.path.isfile(CALIBRI_REG)
    reg_font = CALIBRI_REG if has_calibri else None
    bold_font = CALIBRI_BOLD if os.path.isfile(CALIBRI_BOLD) else None
    italic_font = CALIBRI_ITALIC if os.path.isfile(CALIBRI_ITALIC) else None

    for p_idx in range(total_pages):
        page = doc[p_idx]

        # ----------------------------------------------------
        # Page 1: Metadata Table Overlay (Dynamic Discovery)
        # ----------------------------------------------------
        if p_idx == 0:
            skema_nama = data.get("skema_nama") or data.get("skema_judul") or "Maintenance Gasoline Engine Injection System"
            skema_nomor = data.get("skema_nomor") or "02/LSP PrO Indo/I/2026"
            tuk_tipe = data.get("tuk_tipe") or "Sewaktu/Tempat Kerja/Mandiri*"
            asesor_nama = data.get("asesor_nama") or "Drs. Budi Santoso, M.T."
            asesi_nama = data.get("asesi_nama") or "Wahyu Andika Rahadi"
            tanggal = data.get("tanggal") or "19 Oktober 2026"

            meta_fields = [
                ("Judul", skema_nama, italic_font),
                ("Nomor", skema_nomor, reg_font),
                ("TUK", tuk_tipe, reg_font),
                ("Nama Asesor", asesor_nama, reg_font),
                ("Nama Asesi", asesi_nama, reg_font),
                ("Tanggal", tanggal, reg_font),
            ]

            # Detect horizontal lines in header area to safely constrain whiteout within cells
            h_lines = []
            for d in page.get_drawings():
                if 110 < d['rect'].y1 < 265:
                    for it in d['items']:
                        if it[0] == 're':
                            r = it[1]
                            if (r.y1 - r.y0) < 3.0 and (r.x1 - r.x0) > 50.0:
                                h_lines.append(r)

            # Detect vertical divider between colon and value column
            v_lines = []
            for d in page.get_drawings():
                if 110 < d['rect'].y1 < 265:
                    for it in d['items']:
                        if it[0] == 're':
                            r = it[1]
                            if (r.y1 - r.y0) < 3.0 and (r.y1 - r.y0) > 10.0 and 260 < r.x0 < 270:
                                v_lines.append(r.x1)

            v_line_x = max(v_lines) if v_lines else 264.3
            # Ensure white-out is strictly to the right of the vertical line
            whiteout_x0 = max(266.0, v_line_x + 1.6)
            # Indent text neatly inside the value cell so vertical line is clearly visible
            text_x = max(270.0, v_line_x + 5.8)

            for lbl, val, font in meta_fields:
                colon = find_colon_for_label(page, lbl)
                if colon:
                    above = [h.y1 for h in h_lines if h.y1 <= colon.y0 + 2.0]
                    top_y = max(above) if above else colon.y0 - 4.0
                    below = [h.y0 for h in h_lines if h.y0 >= colon.y1 - 2.0]
                    bot_y = min(below) if below else colon.y1 + 4.0

                    # White out the cell right of colon to eliminate any existing template text,
                    # with safe margins keeping horizontal and vertical table borders completely intact
                    shape = page.new_shape()
                    shape.draw_rect(fitz.Rect(whiteout_x0, top_y + 1.2, 554.5, bot_y - 1.2))
                    shape.finish(color=(1, 1, 1), fill=(1, 1, 1))
                    shape.commit()

                    # Insert dynamic text aligned with baseline
                    baseline_y = bot_y - 4.0
                    page.insert_text(
                        fitz.Point(text_x, baseline_y),
                        str(val),
                        fontfile=font,
                        fontsize=11.0,
                        color=(0, 0, 0)
                    )

        # ----------------------------------------------------
        # Checkboxes on All Pages
        # ----------------------------------------------------
        page_boxes = []
        for b in page.get_text("dict")["blocks"]:
            if "lines" not in b:
                continue
            for line in b["lines"]:
                for s in line["spans"]:
                    txt = s["text"].strip()
                    if "☐" in txt or txt == "\u2610":
                        page_boxes.append(s["bbox"])

        # Sort boxes top-to-bottom, then left-to-right
        page_boxes.sort(key=lambda b: (round(b[1], -1), b[0]))

        if p_idx == rec_page_idx:
            # On recommendation page, recommendation boxes are x < 150 below 'Rekomendasi'
            rec_rects = page.search_for("Rekomendasi")
            rec_y = rec_rects[0].y0 - 5.0 if rec_rects else 200.0
            rec_boxes = [b for b in page_boxes if b[0] < 150.0 and b[1] >= rec_y]
            kuk_boxes = [b for b in page_boxes if b not in rec_boxes]
        else:
            rec_boxes = []
            kuk_boxes = page_boxes

        # Pairs of (Ya, Tidak)
        for i in range(0, len(kuk_boxes), 2):
            if i + 1 >= len(kuk_boxes):
                break
            box_ya = kuk_boxes[i]
            box_tdk = kuk_boxes[i + 1]

            if isinstance(kuk_results, list):
                k_info = kuk_results[global_kuk_idx] if 0 <= global_kuk_idx < len(kuk_results) else None
            elif isinstance(kuk_results, dict):
                k_info = kuk_results.get(str(global_kuk_idx)) or kuk_results.get(global_kuk_idx)
            else:
                k_info = None

            if k_info:
                row_is_k = bool(k_info.get("is_k", True if k_info.get("result") == "K" else False))
                note = k_info.get("note", "")
            else:
                row_is_k = fallback_is_k
                note = ""

            if row_is_k:
                draw_vector_checkmark(page, box_ya)
            else:
                draw_vector_checkmark(page, box_tdk)

            if note:
                page.insert_text(fitz.Point(505.0, box_ya[3] - 2.5), note[:14], fontfile=reg_font, fontsize=9.5, color=(0, 0, 0))

            global_kuk_idx += 1

        # ----------------------------------------------------
        # Recommendation & Signatures (Dynamic Discovery)
        # ----------------------------------------------------
        if p_idx == rec_page_idx:
            is_kompeten = bool(data.get("is_kompeten", True))

            # 1. Recommendation checkbox
            if len(rec_boxes) >= 2:
                if is_kompeten:
                    draw_vector_checkmark(page, rec_boxes[0])
                else:
                    draw_vector_checkmark(page, rec_boxes[1])

            # 2. Umpan Balik untuk asesi
            umpan_balik = data.get("umpan_balik") or data.get("assessor_notes") or ""
            if not umpan_balik:
                umpan_balik = "Asesi telah menunjukkan pemenuhan unjuk kerja praktik sesuai standar KUK SKKNI dan SOP industri yang berlaku."

            ub_rects = page.search_for("Umpan Balik")
            if ub_rects:
                ub = ub_rects[0]
                fb_rect = fitz.Rect(78.6, ub.y1 + 4.0, 545.0, ub.y1 + 55.0)
            else:
                fb_rect = fitz.Rect(78.6, 252.0, 545.0, 305.0)

            page.insert_textbox(fb_rect, umpan_balik, fontfile=italic_font or reg_font, fontsize=10.0, color=(0, 0, 0))

            # 3. Dynamic Signatures & Name placement via colons
            # Find the 5 colons on the right half of the table (x > 250)
            right_colons = [c for c in page.search_for(':') if c.x0 > 250.0]
            right_colons.sort(key=lambda c: c.y0)

            # Find vertical line dividing colon column and value column (x in 415..430)
            sig_v_lines = []
            for d in page.get_drawings():
                for it in d['items']:
                    if it[0] == 're':
                        r = it[1]
                        if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 10.0 and 415 < r.x0 < 430:
                            sig_v_lines.append(r.x1)

            sig_v_line_x = max(sig_v_lines) if sig_v_lines else 421.4
            # Place text at least 6.6 pt to the right of the vertical line (minimum 428.0)
            sig_text_x = max(428.0, sig_v_line_x + 6.6)

            asesi_nama = data.get("asesi_nama", "")
            tanggal_asesi = data.get("tanggal_ttd_asesi") or data.get("tanggal", "")
            asesor_nama = data.get("asesor_nama", "")
            asesor_reg = data.get("asesor_no_reg") or data.get("asesor_reg", "")
            tanggal_asesor = data.get("tanggal_ttd_asesor") or data.get("tanggal", "")

            sig_asesi_data = data.get("signature_asesi") or data.get("asesi_signature")
            sig_asesor_data = data.get("signature_asesor") or data.get("assessor_signature")

            if len(right_colons) >= 5:
                c_asesi_nama = right_colons[0]
                c_asesi_ttd = right_colons[1]
                c_asesor_nama = right_colons[2]
                c_asesor_reg = right_colons[3]
                c_asesor_ttd = right_colons[4]

                # Asesi Nama (vertically centered in cell, generous padding from vertical border)
                page.insert_text(
                    fitz.Point(sig_text_x, c_asesi_nama.y1 - 2.5),
                    asesi_nama,
                    fontfile=reg_font,
                    fontsize=10.5,
                    color=(0, 0, 0)
                )

                # Asesi Signature & Date (well-spaced, crisp, non-colliding)
                if sig_asesi_data:
                    sig_bytes = clean_signature_image(sig_asesi_data)
                    if sig_bytes:
                        sig_rect = fitz.Rect(max(430.0, sig_v_line_x + 8.5), c_asesi_ttd.y0 - 18.0, 535.0, c_asesi_ttd.y0 + 9.0)
                        page.insert_image(sig_rect, stream=sig_bytes, keep_proportion=True)

                page.insert_text(
                    fitz.Point(max(440.0, sig_v_line_x + 18.0), c_asesi_ttd.y0 + 18.5),
                    f"Tanggal: {tanggal_asesi}",
                    fontfile=reg_font,
                    fontsize=8.5,
                    color=(0, 0, 0)
                )

                # Asesor Nama & No. Reg (vertically centered in cells, safe padding from vertical border)
                page.insert_text(
                    fitz.Point(sig_text_x, c_asesor_nama.y1 - 2.5),
                    asesor_nama,
                    fontfile=reg_font,
                    fontsize=10.5,
                    color=(0, 0, 0)
                )
                page.insert_text(
                    fitz.Point(sig_text_x, c_asesor_reg.y1 - 2.5),
                    asesor_reg,
                    fontfile=reg_font,
                    fontsize=10.5,
                    color=(0, 0, 0)
                )

                # Asesor Signature & Date (well-spaced, never touching bottom table line)
                if sig_asesor_data:
                    sig_bytes = clean_signature_image(sig_asesor_data)
                    if sig_bytes:
                        sig_rect = fitz.Rect(max(430.0, sig_v_line_x + 8.5), c_asesor_ttd.y0 - 18.0, 535.0, c_asesor_ttd.y0 + 9.0)
                        page.insert_image(sig_rect, stream=sig_bytes, keep_proportion=True)

                page.insert_text(
                    fitz.Point(max(440.0, sig_v_line_x + 18.0), c_asesor_ttd.y0 + 18.0),
                    f"Tanggal: {tanggal_asesor}",
                    fontfile=reg_font,
                    fontsize=8.5,
                    color=(0, 0, 0)
                )
            else:
                # Fallback to standard proportions
                page.insert_text(fitz.Point(sig_text_x, 355.4), asesi_nama, fontfile=reg_font, fontsize=10.5, color=(0, 0, 0))
                page.insert_text(fitz.Point(max(440.0, sig_v_line_x + 18.0), 401.0), f"Tanggal: {tanggal_asesi}", fontfile=reg_font, fontsize=8.5, color=(0, 0, 0))
                if sig_asesi_data:
                    sig_bytes = clean_signature_image(sig_asesi_data)
                    if sig_bytes:
                        page.insert_image(fitz.Rect(max(430.0, sig_v_line_x + 8.5), 363.0, 535.0, 393.0), stream=sig_bytes, keep_proportion=True)

                page.insert_text(fitz.Point(sig_text_x, 446.3), asesor_nama, fontfile=reg_font, fontsize=10.5, color=(0, 0, 0))
                page.insert_text(fitz.Point(sig_text_x, 468.7), asesor_reg, fontfile=reg_font, fontsize=10.5, color=(0, 0, 0))
                page.insert_text(fitz.Point(max(440.0, sig_v_line_x + 18.0), 509.0), f"Tanggal: {tanggal_asesor}", fontfile=reg_font, fontsize=8.5, color=(0, 0, 0))
                if sig_asesor_data:
                    sig_bytes = clean_signature_image(sig_asesor_data)
                    if sig_bytes:
                        page.insert_image(fitz.Rect(max(430.0, sig_v_line_x + 8.5), 471.0, 535.0, 501.0), stream=sig_bytes, keep_proportion=True)

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    doc.save(dest_path)
    doc.close()
    print(f"OK -> Sukses generate FR.IA.01 (Multi-Scheme Master Overlay {total_pages} Halaman): {dest_path}")

def find_scheme_master_pdf(scheme_code_or_name=None):
    """Find matching IA.01 master template PDF in lsp-document-dynamic for the given scheme."""
    base_dir = os.path.join(PROJECT_ROOT, "lsp-document-dynamic")
    if not os.path.isdir(base_dir):
        return DEFAULT_REF_PDF if os.path.isfile(DEFAULT_REF_PDF) else None

    # Check if a scheme identifier was passed
    if scheme_code_or_name:
        s_clean = str(scheme_code_or_name).lower()
        # Look for scheme number
        m = re.search(r'(?:skm|skema)[-_ ]?0?([1-7])', s_clean)
        num = m.group(1) if m else None

        for folder in os.listdir(base_dir):
            folder_lower = folder.lower()
            folder_path = os.path.join(base_dir, folder)
            if not os.path.isdir(folder_path):
                continue

            match = False
            if num and f"skema {num}" in folder_lower:
                match = True
            elif any(part in folder_lower for part in s_clean.split() if len(part) > 4):
                match = True

            if match:
                for f in os.listdir(folder_path):
                    lf = f.lower()
                    if lf.endswith(".pdf") and ("ia.01.ceklis" in lf or ("ia.01" in lf and "01b" not in lf and "report" not in lf)):
                        return os.path.join(folder_path, f)

    # Fallback to default
    if os.path.isfile(DEFAULT_REF_PDF):
        return DEFAULT_REF_PDF

    # Generic search
    for root, _, files in os.walk(base_dir):
        for f in files:
            lf = f.lower()
            if lf.endswith(".pdf") and ("ia.01.ceklis" in lf or ("ia.01" in lf and "01b" not in lf and "report" not in lf)):
                return os.path.join(root, f)

    return None

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)

    out_file = OUT_FILE
    master_pdf = None

    if len(sys.argv) > 1:
        arg1 = sys.argv[1]
        if arg1.endswith(".json") and os.path.isfile(arg1):
            with open(arg1, "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
            if len(sys.argv) > 3 and os.path.isfile(sys.argv[3]):
                master_pdf = sys.argv[3]
        elif len(sys.argv) > 2 and sys.argv[2].endswith(".json") and os.path.isfile(sys.argv[2]):
            out_file = arg1
            with open(sys.argv[2], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 3 and os.path.isfile(sys.argv[3]):
                master_pdf = sys.argv[3]
        else:
            out_file = arg1
            if len(sys.argv) > 2 and os.path.isfile(sys.argv[2]):
                master_pdf = sys.argv[2]

    if not master_pdf:
        master_pdf = find_scheme_master_pdf(DATA.get("skema_nomor") or DATA.get("skema_nama"))

    if master_pdf and os.path.isfile(master_pdf):
        render_master_overlay(master_pdf, DATA, out_file)
    else:
        print("ERROR: Master template PDF FR.IA.01 tidak ditemukan.")
        sys.exit(1)
