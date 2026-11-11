#!/usr/bin/env python3
"""
Generator & Master Overlay FR.AK.03 (Umpan Balik dan Catatan Asesmen) - LSP PrO Indo
Layout, tabel, dan font presisi identik dengan master template asli BNSP di lsp-document-dynamic:
http://127.0.0.1:8000/asesi/assessment/3/ak03

Fitur Arsitektur Master Overlay:
- Deteksi dinamis master template PDF per skema (Skema 1 s/d Skema 7).
- Penghapusan 100% watermark (Word header artifact curves, vector stream 0.722, dan background images).
- Font: Calibri 10pt & 9.5pt sesuai standar BNSP.
- Garis tabel vertikal dan horizontal 100% utuh, rapih, solid, dan tidak terhapus.
- Header Table bebas dari tabrakan garis dan teks (aman titik dua dan border).
- Data dinamis database terhubung riil dari evaluasi umpan balik asesi (t_assessment_ak03_items):
  * Skema sertifikasi (judul & nomor)
  * Asesi (nama) & Asesor (nama)
  * TUK & tanggal pelaksanaan (Mulai & Selesai)
  * 10 Butir Pernyataan Evaluasi (centang Ya / Tidak menggunakan vektor checkmark APL.02).
  * Catatan / Komentar per butir pertanyaan.
  * Catatan / komentar lainnya (apabila ada) dari general_notes asesi.
- Default Kosong Total (saat belum diisi asesi):
  * Seluruh kotak pilihan Ya dan Tidak tetap kosong (tidak ada centang bawaan).
  * Catatan per butir kosong.
  * Catatan umum lainnya kosong.
"""

import os
import sys
import json
import io
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
    "14. FR.AK.03. Umpan Balik dan Catatan Asesmen.pdf"
)
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_AK_03_generated.pdf")

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

def render_master_overlay(master_pdf_path, data, dest_path):
    """Render dynamic data on top of BNSP master AK.03 PDF with multi-scheme resilience."""
    if not os.path.isfile(master_pdf_path):
        raise FileNotFoundError(f"Master PDF not found: {master_pdf_path}")

    doc = fitz.open(master_pdf_path)

    # If scheme has an empty trailing page (like Scheme 3), delete it
    if len(doc) > 1 and len(doc[1].get_text("text").strip()) < 100:
        doc.delete_page(1)

    total_pages = len(doc)

    # 1. Strip all watermarks from all pages
    clean_watermark(doc)

    # Register fonts
    has_calibri = os.path.isfile(CALIBRI_REG)
    reg_font = CALIBRI_REG if has_calibri else None
    bold_font = CALIBRI_BOLD if os.path.isfile(CALIBRI_BOLD) else None
    italic_font = CALIBRI_ITALIC if os.path.isfile(CALIBRI_ITALIC) else None

    is_filled = bool(data.get("is_filled", False))
    ratings = data.get("ratings") or {}
    general_notes = str(data.get("general_notes") or "").strip()

    for p_idx in range(total_pages):
        page = doc[p_idx]

        # Strip Australian VET footnote template text if present
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
            komponen_rect = page.search_for("KOMPONEN")

            if skema_rect and komponen_rect:
                sy = skema_rect[0].y0
                ey = komponen_rect[0].y0

                raw_h = []
                raw_v = []
                for d in page.get_drawings():
                    r = d['rect']
                    if (r.y1 - r.y0) < 2.5 and r.x1 > 450 and (sy - 20) <= r.y0 <= (ey - 20):
                        raw_h.append(round(r.y0, 1))
                    if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 10 and (sy - 20) <= r.y0 <= (ey - 20):
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
                    dedup_h = dedup_h[:8]
                    v_left = dedup_v[0]
                    v_sub = dedup_v[1]
                    v_colon = dedup_v[2]
                    v_mid = dedup_v[3]
                    v_right = dedup_v[-1]
                else:
                    v_left = 70.2
                    v_sub = 191.2
                    v_colon = 244.4
                    v_mid = 258.8
                    v_right = 559.3
                    dedup_h = [98.3, 116.8, 134.3, 151.6, 169.1, 186.6, 203.6, 221.5]

                # Safe interior cell redaction across all 7 rows
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
                            fitz.Point(v_mid + 5.0, y_base),
                            val_text,
                            fontfile=reg_font,
                            fontsize=f_size,
                            color=(0, 0, 0)
                        )

                # Re-stroke Header Table borders to guarantee 100% crisp visibility
                shape_hdr = page.new_shape()
                # 1. Outer box
                shape_hdr.draw_rect(fitz.Rect(v_left, dedup_h[0], v_right, dedup_h[7]))
                shape_hdr.finish(color=(0, 0, 0), width=1.2)
                # 2. Right vertical border
                shape_hdr.draw_line(fitz.Point(v_right, dedup_h[0]), fitz.Point(v_right, dedup_h[7]))
                shape_hdr.finish(color=(0, 0, 0), width=1.2)
                # 3. Vertical line to the right of colons (v_mid)
                shape_hdr.draw_line(fitz.Point(v_mid, dedup_h[0]), fitz.Point(v_mid, dedup_h[7]))
                shape_hdr.finish(color=(0, 0, 0), width=0.6)
                # 4. Vertical line to the left of colons (v_colon)
                shape_hdr.draw_line(fitz.Point(v_colon, dedup_h[0]), fitz.Point(v_colon, dedup_h[7]))
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
        # 2. FEEDBACK EVALUATION TABLE (10 QUESTIONS + NOTES)
        # ----------------------------------------------------
        komp_matches = page.search_for("KOMPONEN")
        if komp_matches:
            k_y = komp_matches[0].y0

            raw_t2_h = []
            for d in page.get_drawings():
                r = d['rect']
                if (r.y1 - r.y0) < 2.5 and r.x1 > 450 and r.y0 >= (k_y - 20):
                    raw_t2_h.append(round(r.y0, 1))
            t2_h_lines = []
            for y in sorted(list(set(raw_t2_h))):
                if not t2_h_lines or abs(y - t2_h_lines[-1]) > 1.0:
                    t2_h_lines.append(y)

            raw_t2_v = []
            for d in page.get_drawings():
                r = d['rect']
                if (r.x1 - r.x0) < 3.0 and (r.y1 - r.y0) > 30 and r.y0 >= (k_y - 20):
                    raw_t2_v.append(round(r.x0, 1))
            t2_v_lines = []
            for x in sorted(list(set(raw_t2_v))):
                if not t2_v_lines or abs(x - t2_v_lines[-1]) > 2.0:
                    t2_v_lines.append(x)

            if len(t2_v_lines) >= 5:
                v_t2_left = t2_v_lines[0]
                v_c1 = t2_v_lines[1]
                v_c2 = t2_v_lines[2]
                v_c3 = t2_v_lines[3]
                v_t2_right = t2_v_lines[4]
            else:
                v_t2_left = 70.9
                v_c1 = 374.8
                v_c2 = 401.5
                v_c3 = 433.6
                v_t2_right = 559.4

            # Find pre-printed ballot boxes (20 boxes: 2 per question)
            ballot_boxes = page.search_for("\u2610")
            # Sort boxes primarily by y, then by x
            ballot_boxes.sort(key=lambda r: (round(r.y0 / 15.0) * 15.0, r.x0))

            if is_filled and len(ballot_boxes) >= 20:
                for q_idx in range(10):
                    q_num = q_idx + 1
                    q_key = str(q_num)
                    item_data = ratings.get(q_key) or ratings.get(q_num) or {}
                    choice = str(item_data.get("value") or "").upper().strip()
                    comment = str(item_data.get("comment") or "").strip()

                    ya_box = ballot_boxes[2 * q_idx]
                    tidak_box = ballot_boxes[2 * q_idx + 1]

                    if choice == "YA":
                        draw_vector_checkmark(page, ya_box)
                    elif choice == "TIDAK":
                        draw_vector_checkmark(page, tidak_box)

                    if comment and (2 + q_idx + 1) < len(t2_h_lines):
                        c_top = t2_h_lines[2 + q_idx]
                        c_bot = t2_h_lines[3 + q_idx]
                        c_rect = fitz.Rect(v_c3 + 4.0, c_top + 3.0, v_t2_right - 4.0, c_bot - 2.0)
                        page.insert_textbox(
                            c_rect,
                            comment,
                            fontfile=reg_font,
                            fontsize=8.5,
                            color=(0, 0, 0)
                        )

                if general_notes and len(t2_h_lines) >= 14:
                    gen_rect = fitz.Rect(v_t2_left + 8.0, t2_h_lines[12] + 20.0, v_t2_right - 8.0, t2_h_lines[13] - 4.0)
                    page.insert_textbox(
                        gen_rect,
                        general_notes,
                        fontfile=reg_font,
                        fontsize=9.0,
                        color=(0, 0, 0)
                    )

            # Re-stroke Table 2 borders to guarantee 100% crisp visibility
            if len(t2_h_lines) >= 14:
                shape_t2 = page.new_shape()
                # 1. Outer box
                shape_t2.draw_rect(fitz.Rect(v_t2_left, t2_h_lines[0], v_t2_right, t2_h_lines[-1]))
                shape_t2.finish(color=(0, 0, 0), width=1.2)
                # 2. Right vertical border
                shape_t2.draw_line(fitz.Point(v_t2_right, t2_h_lines[0]), fitz.Point(v_t2_right, t2_h_lines[-1]))
                shape_t2.finish(color=(0, 0, 0), width=1.2)
                # 3. Left vertical border
                shape_t2.draw_line(fitz.Point(v_t2_left, t2_h_lines[0]), fitz.Point(v_t2_left, t2_h_lines[-1]))
                shape_t2.finish(color=(0, 0, 0), width=1.2)
                # 4. Vertical dividers (Komponen / Ya and Tidak / Catatan)
                shape_t2.draw_line(fitz.Point(v_c1, t2_h_lines[0]), fitz.Point(v_c1, t2_h_lines[12]))
                shape_t2.finish(color=(0, 0, 0), width=0.6)
                shape_t2.draw_line(fitz.Point(v_c3, t2_h_lines[0]), fitz.Point(v_c3, t2_h_lines[12]))
                shape_t2.finish(color=(0, 0, 0), width=0.6)
                # 5. Vertical divider (Ya / Tidak inside Hasil)
                shape_t2.draw_line(fitz.Point(v_c2, t2_h_lines[1]), fitz.Point(v_c2, t2_h_lines[12]))
                shape_t2.finish(color=(0, 0, 0), width=0.6)
                # 6. Horizontal sub-divider below Hasil header
                shape_t2.draw_line(fitz.Point(v_c1, t2_h_lines[1]), fitz.Point(v_c3, t2_h_lines[1]))
                shape_t2.finish(color=(0, 0, 0), width=0.6)
                # 7. Horizontal lines dividing rows
                for y in t2_h_lines[2:-1]:
                    shape_t2.draw_line(fitz.Point(v_t2_left, y), fitz.Point(v_t2_right, y))
                    shape_t2.finish(color=(0, 0, 0), width=0.6)
                # Bottom border line
                shape_t2.draw_line(fitz.Point(v_t2_left, t2_h_lines[-1]), fitz.Point(v_t2_right, t2_h_lines[-1]))
                shape_t2.finish(color=(0, 0, 0), width=1.2)
                shape_t2.commit()

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    doc.save(dest_path)
    doc.close()
    print(f"OK -> Sukses generate FR.AK.03 (Multi-Scheme Master Overlay {total_pages} Halaman): {dest_path}")

def find_scheme_master_pdf(scheme_code_or_name=None):
    """Find matching AK.03 master template PDF in lsp-document-dynamic for the given scheme."""
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
                    if lf.endswith(".pdf") and ("ak.03" in lf or "ak03" in lf or "ak 03" in lf):
                        return os.path.join(folder_path, f)

    if os.path.isfile(DEFAULT_REF_PDF):
        return DEFAULT_REF_PDF

    for root, _, files in os.walk(base_dir):
        for f in files:
            lf = f.lower()
            if lf.endswith(".pdf") and ("ak.03" in lf or "ak03" in lf or "ak 03" in lf):
                return os.path.join(root, f)

    return None

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
        master_pdf = DEFAULT_REF_PDF

    render_master_overlay(master_pdf, DATA, out_file)
