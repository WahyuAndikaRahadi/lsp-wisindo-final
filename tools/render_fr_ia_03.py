#!/usr/bin/env python3
"""
Generator & Master Overlay FR.IA.03 (Pertanyaan Mendukung Observasi - PMO) - LSP PrO Indo
Layout, tabel, dan font presisi identik dengan master BNSP:
http://127.0.0.1:8000/print-example/ia03?ref=1

Fitur:
- Deteksi dinamis master template PDF per skema (Skema 1 s/d Skema 7).
- Penghapusan 100% watermark (Word header artifact curves, vector stream 0.722, dan background images).
- Pembersihan tanda tangan digital transparan & kontras tinggi, aspect-ratio preserved, tidak nabrak garis.
- Font: Calibri 11pt (Regular, Bold, Italic) sesuai standar BNSP.
- Data dinamis database:
  * Skema sertifikasi (judul & nomor)
  * Asesi (nama & NIK)
  * Asesor (nama & nomor registrasi MET)
  * TUK & tanggal pelaksanaan
  * Checkbox Pencapaian: "Ya" vs "Tidak" sesuai hasil observasi / tanya jawab di DB dengan centang APL.02
  * Tanggapan asesi (auto wrapped di sel tanggapan)
  * Umpan balik asesor
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
    "10. FR.IA.03. Pertanyaan Mendukung Observasi (PMO) docx.pdf"
)
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_IA_03_generated.pdf")

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
    "umpan_balik": "",
    "signature_asesi": None,
    "signature_asesor": None,
    "questions": [],
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
                                wm_end = term_idx + len(term) - 1
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
    """Draw a sharp vector checkmark matching FR.APL.02 inside a checkbox bounding box."""
    bx0, by0, bx1, by1 = box_rect
    cx = (bx0 + bx1) / 2.0
    cy = (by0 + by1) / 2.0 + 0.8

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
    """Render dynamic data on top of BNSP master IA.03 PDF with multi-scheme resilience."""
    if not os.path.isfile(master_pdf_path):
        raise FileNotFoundError(f"Master PDF not found: {master_pdf_path}")

    doc = fitz.open(master_pdf_path)
    total_pages = len(doc)

    # 1. Strip all watermarks from all pages
    clean_watermark(doc)

    # Register fonts
    has_calibri = os.path.isfile(CALIBRI_REG)
    reg_font = CALIBRI_REG if has_calibri else None
    bold_font = CALIBRI_BOLD if os.path.isfile(CALIBRI_BOLD) else None
    italic_font = CALIBRI_ITALIC if os.path.isfile(CALIBRI_ITALIC) else None

    # Global tracking for question responses
    q_data = data.get("questions") or []
    q_map = data.get("questions_map") or {}
    has_db_response = bool(data.get("has_db_response", False))
    global_q_idx = 0

    for p_idx in range(total_pages):
        page = doc[p_idx]

        # ----------------------------------------------------
        # Strip Australian VET footnote template text if present
        # ----------------------------------------------------
        for b in page.get_text("dict")["blocks"]:
            if "lines" in b:
                b_txt = "".join(s["text"] for l in b["lines"] for s in l["spans"])
                if "Diadaptasi" in b_txt or "Departemen Pendidikan" in b_txt or "Australia" in b_txt:
                    r = b["bbox"]
                    clean_rect = fitz.Rect(50.0, r[1] - 3.0, 560.0, r[3] + 8.0)
                    page.add_redact_annot(clean_rect, fill=(1, 1, 1))
                    page.apply_redactions()

        for r in page.search_for("Diadaptasi"):
            page.add_redact_annot(fitz.Rect(50.0, r.y0 - 4.0, 560.0, r.y1 + 35.0), fill=(1, 1, 1))
            page.apply_redactions()

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
                if 90 < d['rect'].y1 < 265:
                    for it in d['items']:
                        if it[0] == 're':
                            r = it[1]
                            if (r.y1 - r.y0) < 3.0 and (r.x1 - r.x0) > 50.0:
                                h_lines.append(r)

            # Detect vertical divider between colon and value column
            v_lines = []
            for d in page.get_drawings():
                if 90 < d['rect'].y1 < 265:
                    for it in d['items']:
                        if it[0] == 're':
                            r = it[1]
                            if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 10.0 and 255 < r.x0 < 275:
                                v_lines.append(r.x1)

            v_line_x = max(v_lines) if v_lines else 264.0
            whiteout_x0 = max(266.0, v_line_x + 1.6)
            text_x = max(270.0, v_line_x + 5.8)

            for lbl, val, font in meta_fields:
                colon = find_colon_for_label(page, lbl)
                if colon:
                    above = [h.y1 for h in h_lines if h.y1 <= colon.y0 + 2.0]
                    top_y = max(above) if above else colon.y0 - 4.0
                    below = [h.y0 for h in h_lines if h.y0 >= colon.y1 - 2.0]
                    bot_y = min(below) if below else colon.y1 + 4.0

                    # White out the cell right of colon to eliminate any existing template text
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
        # Intermediate / Question Pages: Checkboxes & Responses
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

        if page_boxes:
            page_boxes.sort(key=lambda b: (round(b[1], -1), b[0]))
            tanggapans = page.search_for("Tanggapan:")
            tanggapans.sort(key=lambda t: t.y0)

            # Discover horizontal lines to bound text cells accurately
            h_lines = []
            for d in page.get_drawings():
                for it in d['items']:
                    if it[0] == 're':
                        r = it[1]
                        if (r.y1 - r.y0) < 3.0 and (r.x1 - r.x0) > 80.0:
                            h_lines.append(r)

            for i in range(0, len(page_boxes), 2):
                if i + 1 >= len(page_boxes):
                    break
                box_ya = page_boxes[i]
                box_tdk = page_boxes[i + 1]

                # Find closest Tanggapan: label on this page
                matching_t = [t for t in tanggapans if abs(t.y0 - box_ya[1]) < 45]
                t_lbl = matching_t[0] if matching_t else None

                # 1. Match question with database question by text if possible
                q_eval = None
                if t_lbl and isinstance(q_data, list):
                    clip_rect = fitz.Rect(70.0, max(0.0, t_lbl.y0 - 55.0), 465.0, t_lbl.y0)
                    pdf_q_text = page.get_text("text", clip=clip_rect).strip()
                    pdf_toks = set(re.findall(r'\w{4,}', pdf_q_text.lower()))
                    best_score = 0
                    best_item = None
                    for item in q_data:
                        if isinstance(item, dict) and item.get("question"):
                            item_toks = set(re.findall(r'\w{4,}', item["question"].lower()))
                            score = len(pdf_toks & item_toks)
                            if score > best_score:
                                best_score = score
                                best_item = item
                    if best_score >= 2:
                        q_eval = best_item

                # 2. Sequential fallback if text match was not found
                if q_eval is None:
                    if isinstance(q_data, list) and global_q_idx < len(q_data):
                        q_eval = q_data[global_q_idx]
                    elif isinstance(q_data, dict):
                        q_eval = (
                            q_data.get(str(global_q_idx + 1))
                            or q_data.get(global_q_idx + 1)
                            or q_data.get(str(global_q_idx))
                            or q_data.get(global_q_idx)
                        )
                        if q_eval is None and global_q_idx < len(q_data):
                            q_eval = list(q_data.values())[global_q_idx]
                    elif isinstance(q_map, dict):
                        q_eval = (
                            q_map.get(str(global_q_idx + 1))
                            or q_map.get(global_q_idx + 1)
                            or q_map.get(str(global_q_idx))
                        )

                # Resolve evaluation result & candidate response
                res_code = None
                resp_text = None
                if isinstance(q_eval, dict):
                    res_code = q_eval.get("result") or q_eval.get("hasil") or q_eval.get("status")
                    resp_text = q_eval.get("response") or q_eval.get("tanggapan") or q_eval.get("catatan")
                elif isinstance(q_eval, str):
                    res_code = q_eval

                # Determine checkmark: Ya (M) vs Tidak (TM)
                # Jika belum dinilai oleh asesor (res_code None/kosong), DEFAULT KOSONG: JANGAN DICENTANG APAPUN!
                if res_code is not None and str(res_code).strip():
                    code_upper = str(res_code).strip().upper()
                    if code_upper in ["M", "YA", "Y", "K", "KOMPETEN", "MEMUASKAN", "TRUE", "1"]:
                        draw_vector_checkmark(page, box_ya)
                    elif code_upper in ["TM", "TIDAK", "T", "BK", "BELUM KOMPETEN", "TIDAK MEMUASKAN", "FALSE", "0"]:
                        draw_vector_checkmark(page, box_tdk)

                # Insert candidate response into the response cell
                # Jika belum diisi oleh asesor, DEFAULT KOSONG: JANGAN DIISI APAPUN!
                if t_lbl and resp_text and str(resp_text).strip():
                    below_lines = [h.y0 for h in h_lines if h.y0 > t_lbl.y1 + 4.0]
                    bot_y = min(below_lines) if below_lines else t_lbl.y0 + 38.0
                    cell_rect = fitz.Rect(135.0, t_lbl.y0 - 2.0, 465.0, bot_y - 2.0)
                    page.insert_textbox(
                        cell_rect,
                        str(resp_text).strip(),
                        fontfile=italic_font or reg_font,
                        fontsize=9.0,
                        color=(0, 0, 0)
                    )

                global_q_idx += 1

        # ----------------------------------------------------
        # Umpan Balik Asesor (Can be on page 3, 4, etc.)
        # ----------------------------------------------------
        ub_rects = page.search_for("Umpan balik") or page.search_for("Umpan Balik")
        if ub_rects:
            ub = ub_rects[0]
            umpan_balik = data.get("umpan_balik")
            if umpan_balik is None:
                umpan_balik = data.get("assessor_notes")

            # Jika belum diisi oleh asesor, DEFAULT KOSONG: JANGAN DIISI APAPUN!
            if umpan_balik and str(umpan_balik).strip():
                # Find bottom border of umpan balik box
                ub_h_lines = [
                    it[1].y0 for d in page.get_drawings()
                    for it in d['items']
                    if it[0] == 're' and (it[1].y1 - it[1].y0) < 3.0 and (it[1].x1 - it[1].x0) > 80.0 and it[1].y0 > ub.y1 + 8.0
                ]
                bot_ub = min(ub_h_lines) if ub_h_lines else ub.y1 + 65.0
                fb_rect = fitz.Rect(72.0, ub.y1 + 4.0, 545.0, bot_ub - 3.0)
                page.insert_textbox(
                    fb_rect,
                    str(umpan_balik).strip(),
                    fontfile=italic_font or reg_font,
                    fontsize=9.5,
                    color=(0, 0, 0)
                )

        # ----------------------------------------------------
        # Last Page: Signature Table Overlay
        # ----------------------------------------------------
        if p_idx == total_pages - 1:
            colons = [c for c in page.search_for(':') if 155.0 < c.x0 < 170.0]
            colons.sort(key=lambda c: c.y0)

            asesi_nama = data.get("asesi_nama", "")
            tanggal_asesi = data.get("tanggal_ttd_asesi") or data.get("tanggal", "")
            asesor_nama = data.get("asesor_nama", "")
            asesor_reg = data.get("asesor_no_reg") or data.get("asesor_reg", "")
            tanggal_asesor = data.get("tanggal_ttd_asesor") or data.get("tanggal", "")

            sig_asesi_data = data.get("signature_asesi") or data.get("asesi_signature")
            sig_asesor_data = data.get("signature_asesor") or data.get("assessor_signature")

            # Find vertical line dividing colon from value cell (x in 160..175)
            sig_v_lines = []
            for d in page.get_drawings():
                for it in d['items']:
                    if it[0] == 're':
                        r = it[1]
                        if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 10.0 and 160 < r.x0 < 175:
                            sig_v_lines.append(r.x1)

            sig_v_line_x = max(sig_v_lines) if sig_v_lines else 171.1
            sig_text_x = max(178.0, sig_v_line_x + 6.6)

            # Detect horizontal lines on last page for row bounds
            last_h_lines = []
            for d in page.get_drawings():
                for it in d['items']:
                    if it[0] == 're':
                        r = it[1]
                        if (r.y1 - r.y0) < 3.0 and (r.x1 - r.x0) > 50.0:
                            last_h_lines.append(r)

            if len(colons) >= 5:
                c_asesi_nama = colons[0]
                c_asesi_ttd = colons[1]
                c_asesor_nama = colons[2]
                c_asesor_reg = colons[3]
                c_asesor_ttd = colons[4]

                # Row bounds helper
                def get_row_bounds(c):
                    above = [h.y1 for h in last_h_lines if h.y1 <= c.y0 + 2.0]
                    top_y = max(above) if above else c.y0 - 5.0
                    below = [h.y0 for h in last_h_lines if h.y0 >= c.y1 - 2.0]
                    bot_y = min(below) if below else c.y1 + 15.0
                    return top_y, bot_y

                # 1. Asesi Nama
                _, bot_nama_asesi = get_row_bounds(c_asesi_nama)
                page.insert_text(
                    fitz.Point(sig_text_x, bot_nama_asesi - 4.2),
                    asesi_nama,
                    fontfile=reg_font,
                    fontsize=10.5,
                    color=(0, 0, 0)
                )

                # 2. Asesi Signature & Date
                top_ttd_asesi, bot_ttd_asesi = get_row_bounds(c_asesi_ttd)
                if sig_asesi_data:
                    sig_bytes = clean_signature_image(sig_asesi_data)
                    if sig_bytes:
                        sig_rect = fitz.Rect(sig_text_x + 5.0, top_ttd_asesi + 2.0, sig_text_x + 105.0, top_ttd_asesi + 27.0)
                        page.insert_image(sig_rect, stream=sig_bytes, keep_proportion=True)

                page.insert_text(
                    fitz.Point(sig_text_x + 10.0, bot_ttd_asesi - 4.5),
                    f"Tanggal: {tanggal_asesi}",
                    fontfile=reg_font,
                    fontsize=8.5,
                    color=(0, 0, 0)
                )

                # 3. Asesor Nama
                _, bot_nama_asesor = get_row_bounds(c_asesor_nama)
                page.insert_text(
                    fitz.Point(sig_text_x, bot_nama_asesor - 4.2),
                    asesor_nama,
                    fontfile=reg_font,
                    fontsize=10.5,
                    color=(0, 0, 0)
                )

                # 4. Asesor No. Reg
                _, bot_reg_asesor = get_row_bounds(c_asesor_reg)
                page.insert_text(
                    fitz.Point(sig_text_x, bot_reg_asesor - 4.2),
                    asesor_reg,
                    fontfile=reg_font,
                    fontsize=10.5,
                    color=(0, 0, 0)
                )

                # 5. Asesor Signature & Date
                top_ttd_asesor, bot_ttd_asesor = get_row_bounds(c_asesor_ttd)
                if sig_asesor_data:
                    sig_bytes = clean_signature_image(sig_asesor_data)
                    if sig_bytes:
                        sig_rect = fitz.Rect(sig_text_x + 5.0, top_ttd_asesor + 2.0, sig_text_x + 105.0, top_ttd_asesor + 25.0)
                        page.insert_image(sig_rect, stream=sig_bytes, keep_proportion=True)

                page.insert_text(
                    fitz.Point(sig_text_x + 10.0, bot_ttd_asesor - 4.5),
                    f"Tanggal: {tanggal_asesor}",
                    fontfile=reg_font,
                    fontsize=8.5,
                    color=(0, 0, 0)
                )
            else:
                # Fallback to standard proportions
                page.insert_text(fitz.Point(sig_text_x, 116.3), asesi_nama, fontfile=reg_font, fontsize=10.5, color=(0, 0, 0))
                page.insert_text(fitz.Point(sig_text_x + 10.0, 158.5), f"Tanggal: {tanggal_asesi}", fontfile=reg_font, fontsize=8.5, color=(0, 0, 0))
                if sig_asesi_data:
                    sig_bytes = clean_signature_image(sig_asesi_data)
                    if sig_bytes:
                        page.insert_image(fitz.Rect(sig_text_x + 5.0, 123.0, sig_text_x + 105.0, 148.0), stream=sig_bytes, keep_proportion=True)

                page.insert_text(fitz.Point(sig_text_x, 192.6), asesor_nama, fontfile=reg_font, fontsize=10.5, color=(0, 0, 0))
                page.insert_text(fitz.Point(sig_text_x, 211.4), asesor_reg, fontfile=reg_font, fontsize=10.5, color=(0, 0, 0))
                page.insert_text(fitz.Point(sig_text_x + 10.0, 250.7), f"Tanggal: {tanggal_asesor}", fontfile=reg_font, fontsize=8.5, color=(0, 0, 0))
                if sig_asesor_data:
                    sig_bytes = clean_signature_image(sig_asesor_data)
                    if sig_bytes:
                        page.insert_image(fitz.Rect(sig_text_x + 5.0, 218.0, sig_text_x + 105.0, 241.0), stream=sig_bytes, keep_proportion=True)

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    doc.save(dest_path)
    doc.close()
    print(f"OK -> Sukses generate FR.IA.03 (Multi-Scheme Master Overlay {total_pages} Halaman): {dest_path}")

def find_scheme_master_pdf(scheme_code_or_name=None):
    """Find matching IA.03 master template PDF in lsp-document-dynamic for the given scheme."""
    base_dir = os.path.join(PROJECT_ROOT, "lsp-document-dynamic")
    if not os.path.isdir(base_dir):
        return DEFAULT_REF_PDF if os.path.isfile(DEFAULT_REF_PDF) else None

    if scheme_code_or_name:
        s_clean = str(scheme_code_or_name).lower()
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
                    if lf.endswith(".pdf") and ("ia.03" in lf or "ia03" in lf):
                        return os.path.join(folder_path, f)

    if os.path.isfile(DEFAULT_REF_PDF):
        return DEFAULT_REF_PDF

    for root, _, files in os.walk(base_dir):
        for f in files:
            lf = f.lower()
            if lf.endswith(".pdf") and ("ia.03" in lf or "ia03" in lf):
                return os.path.join(root, f)

    return None

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)

    out_file = OUT_FILE
    master_pdf = None

    if len(sys.argv) > 1 and sys.argv[1] != "":
        arg1 = sys.argv[1]
        if os.path.isfile(arg1) and arg1.endswith(".json"):
            with open(arg1, "r", encoding="utf-8") as f:
                DATA = json.load(f)
        else:
            out_file = arg1

    if len(sys.argv) > 2 and sys.argv[2] != "":
        out_file = sys.argv[2]

    if len(sys.argv) > 3 and sys.argv[3] != "":
        if os.path.isfile(sys.argv[3]):
            master_pdf = sys.argv[3]

    if not master_pdf:
        scheme_id = DATA.get("skema_nomor") or DATA.get("skema_nama")
        master_pdf = find_scheme_master_pdf(scheme_id)

    if not master_pdf or not os.path.isfile(master_pdf):
        print(f"Error: Master PDF template FR.IA.03 not found for scheme: {DATA.get('skema_nomor')}")
        sys.exit(1)

    render_master_overlay(master_pdf, DATA, out_file)
