#!/usr/bin/env python3
"""
Generator & Master Overlay FR.AK.02 (Rekaman Asesmen Kompetensi) - LSP PrO Indo
Layout, tabel, dan font presisi identik dengan master template asli BNSP di lsp-document-dynamic:
http://127.0.0.1:8000/asesor/assessment/3/ak02

Fitur Arsitektur Master Overlay:
- Deteksi dinamis master template PDF per skema (Skema 1 s/d Skema 7).
- Penghapusan 100% watermark (Word header artifact curves, vector stream 0.722, dan background images).
- Pembersihan tanda tangan digital transparan & kontras tinggi, aspect-ratio preserved, margin aman tidak nabrak garis.
- Font: Calibri 10.5pt & 9.5pt sesuai standar BNSP.
- Garis tabel vertikal dan horizontal 100% utuh, rapih, solid, dan tidak terhapus.
- Data dinamis database terhubung riil dari penilaian asesor (t_assessments & t_assessment_ak02_items):
  * Skema sertifikasi (judul & nomor)
  * Asesi (nama) & Asesor (nama & nomor registrasi MET)
  * TUK & tanggal pelaksanaan (Mulai & Selesai)
  * Checklist metode bukti per unit kompetensi (Observasi, Portofolio, Pihak Ketiga, Lisan, Tulis, Proyek, Lainnya)
    menggunakan vektor checkmark APL.02.
  * Rekomendasi hasil asesmen: dicentang Kompeten jika final_result KOMPETEN, Belum kompeten jika BELUM_KOMPETEN.
  * Tindak lanjut yang dibutuhkan: diambil dari follow_up_action asesor.
  * Komentar / observasi asesor: diambil dari final_notes asesor.
  * Tanda tangan digital & tanggal asesi dan asesor.
- Default Kosong Total (saat belum dinilai asesor):
  * Semua checklist metode per unit kompetensi kosong.
  * Rekomendasi (Kompeten & Belum kompeten) tetap kosong (ballot box tidak dicentang).
  * Tindak lanjut dan Komentar asesor kosong (tidak ada teks dummy/mock).
  * Tanda tangan dan tanggal kosong jika belum ditandatangani.
"""

import os
import sys
import json
import io
import base64
import re
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
DEFAULT_REF_PDF = os.path.join(
    PROJECT_ROOT,
    "lsp-document-dynamic",
    "MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection",
    "13. FR.AK.02. Rekaman Asesmen Kompetensi.pdf"
)
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_AK_02_generated.pdf")

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

                # 3. Clean background watermark images if any (> 400x400 or > 250x250)
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

def draw_checkmark_at_point(page, cx, cy):
    """Draw a sharp vector checkmark matching FR.APL.02 centered at (cx, cy)."""
    p1 = fitz.Point(cx - 2.5, cy - 0.2)
    p2 = fitz.Point(cx - 0.6, cy + 2.4)
    p3 = fitz.Point(cx + 3.0, cy - 3.0)

    shape = page.new_shape()
    shape.draw_polyline([p1, p2, p3])
    shape.finish(color=(0, 0, 0), width=1.4, lineCap=1, lineJoin=1)
    shape.commit()

def get_page_unit_rows(page):
    """Detect horizontal row boundaries (y0, y1) for competency units on a given page."""
    h_ys = []
    for p in page.get_drawings():
        r = p['rect']
        if (r.y1 - r.y0) < 1.5 and abs(r.x0 - 56.6) < 4.0 and abs(r.x1 - 298.0) < 4.0:
            h_ys.append(round(r.y0, 1))
    h_ys = sorted(list(set(h_ys)))

    rekom = page.search_for('Rekomendasi')
    if rekom:
        lines_above = [y for y in h_ys if y <= rekom[0].y0]
        max_y = lines_above[-1] if lines_above else rekom[0].y0
    else:
        max_y = 9999

    head = page.search_for('Tanggal Asesmen') or page.search_for('Nama Asesi')
    min_y = head[0].y1 if head else 0
    unit_hdr = page.search_for('Unit Kompetensi')
    if unit_hdr:
        lines_below = [y for y in h_ys if y >= unit_hdr[0].y1]
        if lines_below:
            min_y = max(min_y, lines_below[0])

    unit_rows = []
    for i in range(len(h_ys) - 1):
        y0, y1 = h_ys[i], h_ys[i + 1]
        if y0 >= (min_y - 1.0) and y1 <= (max_y + 1.0) and 15 < (y1 - y0) < 60:
            unit_rows.append((y0, y1))
    return unit_rows

def render_master_overlay(master_pdf_path, data, dest_path):
    """Render dynamic data on top of BNSP master AK.02 PDF with multi-scheme resilience."""
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

    is_filled = bool(data.get("is_filled", False))
    units_data = data.get("units") or []
    rekom_kompeten = data.get("rekomendasi_kompeten")  # True, False, or None
    tindak_lanjut_text = str(data.get("tindak_lanjut") or "").strip()
    komentar_asesor_text = str(data.get("komentar_asesor") or "").strip()

    # Track unit index across pages
    current_unit_idx = 0

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

        # ----------------------------------------------------
        # 1. HEADER METADATA OVERLAY (PAGE 1)
        # ----------------------------------------------------
        if p_idx == 0:
            skema_rect = page.search_for("Skema Sertifikasi")
            if skema_rect:
                sy = skema_rect[0].y0
                raw_h = []
                raw_v = []
                for d in page.get_drawings():
                    r = d['rect']
                    if (r.y1 - r.y0) < 2.5 and r.x1 > 500 and (sy - 20) <= r.y0 <= (sy + 150):
                        raw_h.append(round(r.y0, 1))
                    if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 10 and (sy - 20) <= r.y0 <= (sy + 150):
                        raw_v.append(round(r.x0, 1))

                dedup_h = []
                for y in sorted(list(set(raw_h))):
                    if not dedup_h or abs(y - dedup_h[-1]) > 1.0:
                        dedup_h.append(y)

                dedup_v = []
                for x in sorted(list(set(raw_v))):
                    if not dedup_v or abs(x - dedup_v[-1]) > 2.0:
                        dedup_v.append(x)

                if len(dedup_h) >= 8 and len(dedup_v) >= 5:
                    v_left = dedup_v[0]
                    v_sub = dedup_v[1]
                    v_colon = dedup_v[2]
                    v_mid = dedup_v[3]
                    v_right = dedup_v[4]
                else:
                    v_left = 56.0
                    v_sub = 177.0
                    v_colon = 230.3
                    v_mid = 244.5
                    v_right = 574.0
                    if not dedup_h:
                        dedup_h = [111.8, 130.3, 147.8, 165.3, 182.8, 200.1, 217.1, 235.1]

                # Safe cell interior redaction across all 7 rows
                # Leaves colons and all border lines 100% untouched!
                for idx in range(min(7, len(dedup_h) - 1)):
                    y_top = dedup_h[idx]
                    y_bot = dedup_h[idx + 1]
                    safe_rect = fitz.Rect(v_mid + 1.2, y_top + 1.0, v_right - 1.2, y_bot - 1.0)
                    page.add_redact_annot(safe_rect, fill=(1, 1, 1))
                page.apply_redactions()

                header_values = [
                    str(data.get("skema_nama") or "").strip(),
                    str(data.get("skema_nomor") or "").strip(),
                    str(data.get("tuk_tipe") or "Sewaktu/Tempat Kerja/Mandiri*").strip(),
                    str(data.get("asesor_nama") or "").strip(),
                    str(data.get("asesi_nama") or "").strip(),
                    str(data.get("tanggal_mulai") or "").strip(),
                    str(data.get("tanggal_selesai") or "").strip(),
                ]

                for idx in range(min(7, len(dedup_h) - 1)):
                    val_text = header_values[idx]
                    y_top = dedup_h[idx]
                    y_bot = dedup_h[idx + 1]
                    y_base = y_bot - 4.5
                    if val_text:
                        f_size = 9.5 if len(val_text) > 42 else 10.0
                        page.insert_text(
                            fitz.Point(v_mid + 6.0, y_base),
                            val_text,
                            fontfile=reg_font,
                            fontsize=f_size,
                            color=(0, 0, 0)
                        )

                # Re-stroke table borders to guarantee 100% crisp visibility
                shape_hdr = page.new_shape()
                # 1. Outer box
                shape_hdr.draw_rect(fitz.Rect(v_left, dedup_h[0], v_right, dedup_h[-1]))
                shape_hdr.finish(color=(0, 0, 0), width=1.2)
                # 2. Right vertical border
                shape_hdr.draw_line(fitz.Point(v_right, dedup_h[0]), fitz.Point(v_right, dedup_h[-1]))
                shape_hdr.finish(color=(0, 0, 0), width=1.2)
                # 3. Vertical line to the right of colons (v_mid)
                shape_hdr.draw_line(fitz.Point(v_mid, dedup_h[0]), fitz.Point(v_mid, dedup_h[-1]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                # 4. Vertical line to the left of colons (v_colon)
                shape_hdr.draw_line(fitz.Point(v_colon, dedup_h[0]), fitz.Point(v_colon, dedup_h[-1]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                # 5. Vertical line dividing left labels and sub-labels (v_sub)
                shape_hdr.draw_line(fitz.Point(v_sub, dedup_h[0]), fitz.Point(v_sub, dedup_h[2]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.draw_line(fitz.Point(v_sub, dedup_h[5]), fitz.Point(v_sub, dedup_h[7]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                # 6. Horizontal lines matching the exact merged rows:
                shape_hdr.draw_line(fitz.Point(v_sub, dedup_h[1]), fitz.Point(v_right, dedup_h[1]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.draw_line(fitz.Point(v_left, dedup_h[2]), fitz.Point(v_right, dedup_h[2]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.draw_line(fitz.Point(v_left, dedup_h[3]), fitz.Point(v_right, dedup_h[3]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.draw_line(fitz.Point(v_left, dedup_h[4]), fitz.Point(v_right, dedup_h[4]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.draw_line(fitz.Point(v_left, dedup_h[5]), fitz.Point(v_right, dedup_h[5]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.draw_line(fitz.Point(v_sub, dedup_h[6]), fitz.Point(v_right, dedup_h[6]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                shape_hdr.commit()

        # ----------------------------------------------------
        # 2. COMPETENCY UNITS TABLE OVERLAY
        # ----------------------------------------------------
        page_unit_rows = get_page_unit_rows(page)

        if page_unit_rows:
            # Redact pre-printed checkmarks or characters in method columns (x: 298..574)
            min_row_y = page_unit_rows[0][0]
            max_row_y = page_unit_rows[-1][1]

            for b in page.get_text("dict")["blocks"]:
                if "lines" in b:
                    for l in b["lines"]:
                        for s in l["spans"]:
                            s_bbox = s["bbox"]
                            if 298.0 < s_bbox[0] < 574.0 and (min_row_y - 2.0) <= s_bbox[1] and s_bbox[3] <= (max_row_y + 2.0):
                                page.add_redact_annot(fitz.Rect(s_bbox), fill=(1, 1, 1))
            page.apply_redactions()

            # Determine column center X coordinates dynamically
            v_xs = []
            for d in page.get_drawings():
                r = d['rect']
                if (r.x1 - r.x0) < 1.5 and (r.y1 - r.y0) > 40 and r.x0 > 290:
                    v_xs.append(round(r.x0, 1))
            v_xs = sorted(list(set(v_xs)))

            col_cxs = [316.0, 351.5, 401.2, 450.9, 486.3, 521.8, 557.1]
            if len(v_xs) >= 8:
                col_cxs = [(v_xs[i] + v_xs[i + 1]) / 2.0 for i in range(7)]

            # Draw checkmarks for each row if assessment has been filled by assessor
            for (y0, y1) in page_unit_rows:
                cy = (y0 + y1) / 2.0 - 0.5

                if is_filled and current_unit_idx < len(units_data):
                    u = units_data[current_unit_idx]
                    method_flags = [
                        bool(u.get("obs")),
                        bool(u.get("port")),
                        bool(u.get("pihak3")),
                        bool(u.get("lisan")),
                        bool(u.get("tulis")),
                        bool(u.get("proyek")),
                        bool(u.get("lain")),
                    ]
                    for col_idx, is_checked in enumerate(method_flags):
                        if is_checked and col_idx < len(col_cxs):
                            draw_checkmark_at_point(page, col_cxs[col_idx], cy)

                current_unit_idx += 1

        # ----------------------------------------------------
        # 3. REKOMENDASI HASIL ASESMEN OVERLAY
        # ----------------------------------------------------
        rekom_rects = page.search_for("Rekomendasi")
        if rekom_rects:
            rekom_y = rekom_rects[0].y0

            kompeten_rects = [r for r in page.search_for("Kompeten") if abs(r.y0 - rekom_y) < 30]
            belum_rects = [r for r in page.search_for("Belum kompeten") if abs(r.y0 - rekom_y) < 30]

            kompeten_box = None
            belum_box = None

            if kompeten_rects:
                kr = kompeten_rects[0]
                kompeten_box = fitz.Rect(kr.x0 - 16.0, kr.y0 - 2.0, kr.x0 - 2.0, kr.y1 + 2.0)
            if belum_rects:
                br = belum_rects[0]
                belum_box = fitz.Rect(br.x0 - 16.0, br.y0 - 2.0, br.x0 - 2.0, br.y1 + 2.0)

            for b in page.get_text("dict")["blocks"]:
                if "lines" in b:
                    for l in b["lines"]:
                        for s in l["spans"]:
                            if abs(s["bbox"][1] - rekom_y) < 30 and "\u2610" in s["text"]:
                                s_rect = fitz.Rect(s["bbox"])
                                if s_rect.x0 < 350:
                                    kompeten_box = s_rect
                                else:
                                    belum_box = s_rect

            if is_filled and rekom_kompeten is True and kompeten_box:
                draw_vector_checkmark(page, kompeten_box)
            elif is_filled and rekom_kompeten is False and belum_box:
                draw_vector_checkmark(page, belum_box)

        # ----------------------------------------------------
        # 4. TINDAK LANJUT YANG DIBUTUHKAN OVERLAY
        # ----------------------------------------------------
        tl_rects = page.search_for("Tindak lanjut")
        if tl_rects:
            tl_y = tl_rects[0].y0
            h_lines = []
            for d in page.get_drawings():
                r = d['rect']
                if (r.y1 - r.y0) < 1.5 and abs(r.x0 - 56.6) < 5.0:
                    h_lines.append(round(r.y0, 1))
            h_lines = sorted(list(set(h_lines)))

            lines_above = [y for y in h_lines if y <= (tl_y + 1.0)]
            lines_below = [y for y in h_lines if y > (tl_y + 1.0)]

            row_top = lines_above[-1] if lines_above else tl_y - 5.0
            row_bottom = lines_below[0] if lines_below else tl_y + 40.0

            # Redact text inside value cell cleanly without touching borders
            val_rect = fitz.Rect(302.0, row_top + 1.5, 570.0, row_bottom - 1.5)
            page.add_redact_annot(val_rect, fill=(1, 1, 1))
            page.apply_redactions()

            if is_filled and tindak_lanjut_text:
                text_rect = fitz.Rect(304.0, row_top + 4.0, 568.0, row_bottom - 2.0)
                page.insert_textbox(
                    text_rect,
                    tindak_lanjut_text,
                    fontfile=reg_font,
                    fontsize=9.5,
                    color=(0, 0, 0)
                )

        # ----------------------------------------------------
        # 5. KOMENTAR / OBSERVASI ASESOR OVERLAY
        # ----------------------------------------------------
        kom_rects = page.search_for("Komentar/ Observasi") or page.search_for("Komentar")
        if kom_rects:
            valid_kom = [r for r in kom_rects if r.x0 < 250]
            if valid_kom:
                kom_y = valid_kom[0].y0
                h_lines = []
                for d in page.get_drawings():
                    r = d['rect']
                    if (r.y1 - r.y0) < 1.5 and abs(r.x0 - 56.6) < 5.0:
                        h_lines.append(round(r.y0, 1))
                h_lines = sorted(list(set(h_lines)))

                lines_above = [y for y in h_lines if y <= (kom_y + 1.0)]
                lines_below = [y for y in h_lines if y > (kom_y + 1.0)]

                row_top = lines_above[-1] if lines_above else kom_y - 5.0
                row_bottom = lines_below[0] if lines_below else kom_y + 55.0

                val_rect = fitz.Rect(302.0, row_top + 1.5, 570.0, row_bottom - 1.5)
                page.add_redact_annot(val_rect, fill=(1, 1, 1))
                page.apply_redactions()

                if is_filled and komentar_asesor_text:
                    text_rect = fitz.Rect(304.0, row_top + 4.0, 568.0, row_bottom - 2.0)
                    page.insert_textbox(
                        text_rect,
                        komentar_asesor_text,
                        fontfile=reg_font,
                        fontsize=9.5,
                        color=(0, 0, 0)
                    )

        # ----------------------------------------------------
        # 6. SIGNATURE TABLE OVERLAY (TANDA TANGAN ASESI & ASESOR)
        # ----------------------------------------------------
        asesi_labels = [r for r in (page.search_for("Asesi :") or page.search_for("Asesi:")) if r.x0 < 100]
        asesor_labels = [r for r in (page.search_for("Asesor :") or page.search_for("Asesor:")) if r.x0 < 100]

        if asesi_labels and asesor_labels:
            sy = asesi_labels[0].y0
            raw_sig_h = []
            raw_sig_v = []
            for d in page.get_drawings():
                r = d['rect']
                if (r.y1 - r.y0) < 2.5 and r.x1 > 500 and (sy - 15) <= r.y0 <= (sy + 225):
                    raw_sig_h.append(round(r.y0, 1))
                if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 10 and (sy - 15) <= r.y0 <= (sy + 225):
                    raw_sig_v.append(round(r.x0, 1))

            sig_h_lines = []
            for y in sorted(list(set(raw_sig_h))):
                if not sig_h_lines or abs(y - sig_h_lines[-1]) > 1.0:
                    sig_h_lines.append(y)

            sig_v_lines = []
            for x in sorted(list(set(raw_sig_v))):
                if not sig_v_lines or abs(x - sig_v_lines[-1]) > 2.0:
                    sig_v_lines.append(x)

            if len(sig_v_lines) >= 4:
                v_left_sig = sig_v_lines[0]
                v_div_colon = sig_v_lines[1]
                v_div_val = sig_v_lines[2]
                v_right_sig = sig_v_lines[3]
            else:
                v_left_sig = 56.3
                v_div_colon = 225.2
                v_div_val = 232.3
                v_right_sig = 571.3

            # Redact value cells safely without touching borders or colons
            if len(sig_h_lines) >= 8:
                val_row_indices = [1, 2, 4, 5, 6]
                for r_idx in val_row_indices:
                    y_top = sig_h_lines[r_idx]
                    y_bot = sig_h_lines[r_idx + 1]
                    safe_rect = fitz.Rect(v_div_val + 1.2, y_top + 1.0, v_right_sig - 1.2, y_bot - 1.0)
                    page.add_redact_annot(safe_rect, fill=(1, 1, 1))
                page.apply_redactions()

                sig_text_x = v_div_val + 6.0
                asesi_nama = str(data.get("asesi_nama") or "").strip()
                asesor_nama = str(data.get("asesor_nama") or "").strip()
                asesor_no_reg = str(data.get("asesor_no_reg") or "").strip()
                tanggal_asesi = str(data.get("tanggal_tanda_tangan_asesi") or data.get("tanggal_tanda_tangan") or "").strip()
                tanggal_asesor = str(data.get("tanggal_tanda_tangan_asesor") or data.get("tanggal_tanda_tangan") or "").strip()
                sig_asesi_data = data.get("signature_asesi")
                sig_asesor_data = data.get("signature_asesor")

                # Insert Nama Asesi (Row 1)
                if asesi_nama:
                    page.insert_text(
                        fitz.Point(sig_text_x, sig_h_lines[2] - 4.5),
                        asesi_nama,
                        fontfile=reg_font,
                        fontsize=10.0,
                        color=(0, 0, 0)
                    )

                # Insert Asesi Signature & Date (Row 2)
                if is_filled and sig_asesi_data:
                    sig_bytes = clean_signature_image(sig_asesi_data)
                    if sig_bytes:
                        sig_rect = fitz.Rect(sig_text_x + 10.0, sig_h_lines[2] + 3.0, sig_text_x + 95.0, sig_h_lines[3] - 13.0)
                        page.insert_image(sig_rect, stream=sig_bytes, keep_proportion=True)

                if is_filled and tanggal_asesi:
                    page.insert_text(
                        fitz.Point(sig_text_x + 10.0, sig_h_lines[3] - 3.5),
                        f"Tanggal: {tanggal_asesi}",
                        fontfile=reg_font,
                        fontsize=8.5,
                        color=(0, 0, 0)
                    )

                # Insert Nama Asesor (Row 4)
                if asesor_nama:
                    page.insert_text(
                        fitz.Point(sig_text_x, sig_h_lines[5] - 4.5),
                        asesor_nama,
                        fontfile=reg_font,
                        fontsize=10.0,
                        color=(0, 0, 0)
                    )

                # Insert No. Reg Asesor (Row 5)
                if asesor_no_reg:
                    page.insert_text(
                        fitz.Point(sig_text_x, sig_h_lines[6] - 4.5),
                        asesor_no_reg,
                        fontfile=reg_font,
                        fontsize=10.0,
                        color=(0, 0, 0)
                    )

                # Insert Asesor Signature & Date (Row 6)
                if is_filled and sig_asesor_data:
                    sig_bytes = clean_signature_image(sig_asesor_data)
                    if sig_bytes:
                        sig_rect = fitz.Rect(sig_text_x + 10.0, sig_h_lines[6] + 3.0, sig_text_x + 95.0, sig_h_lines[7] - 13.0)
                        page.insert_image(sig_rect, stream=sig_bytes, keep_proportion=True)

                if is_filled and tanggal_asesor:
                    page.insert_text(
                        fitz.Point(sig_text_x + 10.0, sig_h_lines[7] - 3.5),
                        f"Tanggal: {tanggal_asesor}",
                        fontfile=reg_font,
                        fontsize=8.5,
                        color=(0, 0, 0)
                    )

                # Re-stroke signature table borders to guarantee 100% crisp visibility
                shape_sig = page.new_shape()
                # 1. Outer box
                shape_sig.draw_rect(fitz.Rect(v_left_sig, sig_h_lines[0], v_right_sig, sig_h_lines[-1]))
                shape_sig.finish(color=(0, 0, 0), width=1.2)

                # 2. Right vertical border
                shape_sig.draw_line(fitz.Point(v_right_sig, sig_h_lines[0]), fitz.Point(v_right_sig, sig_h_lines[-1]))
                shape_sig.finish(color=(0, 0, 0), width=1.2)

                # 3. Vertical lines dividing colon column and value column (inside Asesi & Asesor rows)
                shape_sig.draw_line(fitz.Point(v_div_colon, sig_h_lines[1]), fitz.Point(v_div_colon, sig_h_lines[3]))
                shape_sig.finish(color=(0, 0, 0), width=0.6)
                shape_sig.draw_line(fitz.Point(v_div_val, sig_h_lines[1]), fitz.Point(v_div_val, sig_h_lines[3]))
                shape_sig.finish(color=(0, 0, 0), width=0.6)

                shape_sig.draw_line(fitz.Point(v_div_colon, sig_h_lines[4]), fitz.Point(v_div_colon, sig_h_lines[7]))
                shape_sig.finish(color=(0, 0, 0), width=0.6)
                shape_sig.draw_line(fitz.Point(v_div_val, sig_h_lines[4]), fitz.Point(v_div_val, sig_h_lines[7]))
                shape_sig.finish(color=(0, 0, 0), width=0.6)

                # 4. Horizontal lines for rows 1 to 6
                for y in sig_h_lines[1:-1]:
                    shape_sig.draw_line(fitz.Point(v_left_sig, y), fitz.Point(v_right_sig, y))
                    shape_sig.finish(color=(0, 0, 0), width=0.6)
                shape_sig.commit()

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    meta = doc.metadata or {}
    asesi_nama = str(data.get("asesi_nama") or "").strip()
    meta["title"] = f"FR.AK.02 ({asesi_nama})" if asesi_nama else "FR.AK.02 Rekaman Asesmen Kompetensi"
    meta["author"] = "LSP Wisindo"
    meta["subject"] = "FR.AK.02 Rekaman Asesmen Kompetensi"
    meta["creator"] = "LSP Wisindo MUK System"
    doc.set_metadata(meta)
    doc.save(dest_path)
    doc.close()
    print(f"OK -> Sukses generate FR.AK.02 (Multi-Scheme Master Overlay {total_pages} Halaman): {dest_path}")

def find_scheme_master_pdf(scheme_code_or_name=None):
    """Find matching AK.02 master template PDF in lsp-document-dynamic for the given scheme."""
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
                    if lf.endswith(".pdf") and ("ak.02" in lf or "ak02" in lf or "ak 02" in lf):
                        return os.path.join(folder_path, f)

    if os.path.isfile(DEFAULT_REF_PDF):
        return DEFAULT_REF_PDF

    for root, _, files in os.walk(base_dir):
        for f in files:
            lf = f.lower()
            if lf.endswith(".pdf") and ("ak.02" in lf or "ak02" in lf or "ak 02" in lf):
                return os.path.join(root, f)

    return None

if __name__ == "__main__":
    out_dir = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)

    out_file = OUT_FILE
    master_pdf = None

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
        master_pdf = DEFAULT_REF_PDF

    render_master_overlay(master_pdf, DATA, out_file)
