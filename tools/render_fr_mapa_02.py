#!/usr/bin/env python3
"""
Generator & Master Overlay FR.MAPA.02 (Peta Instrumen Asesmen) - LSP PrO Indo
Layout, tabel, dan font presisi 100% identik dengan master template asli BNSP di lsp-document-fixed:
4. FR.MAPA.02. Peta Instrumen Asesmen.pdf

Fitur Utama:
- Mengambil instrumen terpilih asesor di FR.AK.01 (selected_instruments / getEffectiveInstruments).
- Mengambil Potensi Asesi (1-5) dari FR.MAPA.01 (1.1 Calon Asesi / pendekatan_asesi):
    1: Hasil pelatihan/pendidikan (berbasis kompetensi)
    2: Hasil pelatihan/pendidikan (belum berbasis kompetensi)
    3: Pekerja berpengalaman (tempat kerja berbasis kompetensi)
    4: Pekerja berpengalaman (tempat kerja belum berbasis kompetensi)
    5: Pelatihan/belajar mandiri (otodidak)
- Menghapus 100% watermark background (Word artifact header bezier curves, vector rg 0.722, dan background images).
- Menyesuaikan tanda centang (✓) HANYA pada kolom Potensi Asesi yang aktif dan HANYA untuk instrumen FR.IA yang dipilih di AK.01.
- 4 kolom potensi asesi lainnya dibiarkan kosong total (ballot box tidak dicentang).
- Baris instrumen yang tidak dipilih dibiarkan kosong total.
- Membiarkan tabel Penyusun dan Validator pada halaman terakhir tetap utuh / baku sesuai template master BNSP.
- Set PDF Title metadata agar tab browser tidak menampilkan '(anonymous)'.
"""

import os
import sys
import json
import re

try:
    import pymupdf as fitz
except ImportError:
    import fitz

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
DEFAULT_MASTER_PDF = os.path.join(
    PROJECT_ROOT,
    "lsp-document-fixed",
    "MUK SKEMA 1 Engine Junior Technician",
    "4. FR.MAPA.02. Peta Instrumen Asesmen.pdf"
)
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_MAPA_02_generated.pdf")


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

                # 3. Clean background watermark images if any
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


def draw_vector_checkmark(page, box):
    """Draw crisp, sharp black vector checkmark perfectly proportioned inside ballot box."""
    cx = (box.x0 + box.x1) / 2.0
    cy = (box.y0 + box.y1) / 2.0
    p1 = fitz.Point(cx - 3.2, cy - 0.2)
    p2 = fitz.Point(cx - 0.8, cy + 2.4)
    p3 = fitz.Point(cx + 3.2, cy - 3.0)

    shape = page.new_shape()
    shape.draw_polyline([p1, p2, p3])
    shape.finish(color=(0, 0, 0), width=1.4, lineCap=1, lineJoin=1)
    shape.commit()


def get_potensi_index(pendekatan_raw):
    """
    Resolve Potensi Asesi category (1 to 5) from raw text or numeric value.
    1: Hasil pelatihan/pendidikan (berbasis kompetensi)
    2: Hasil pelatihan/pendidikan (belum berbasis kompetensi)
    3: Pekerja berpengalaman (tempat kerja berbasis kompetensi)
    4: Pekerja berpengalaman (tempat kerja belum berbasis kompetensi)
    5: Pelatihan/belajar mandiri (otodidak)
    """
    if isinstance(pendekatan_raw, int) and 1 <= pendekatan_raw <= 5:
        return pendekatan_raw
    txt = str(pendekatan_raw or "").lower().strip()
    if txt in ["1", "2", "3", "4", "5"]:
        return int(txt)

    if "otodidak" in txt or "mandiri" in txt:
        return 5
    if "pekerja berpengalaman" in txt:
        if "belum" in txt:
            return 4
        return 3
    if "pelatihan" in txt or "pendidikan" in txt:
        if "belum" in txt:
            return 2
        return 1

    return 1


def is_instrument_selected(row_code, selected_instruments):
    """
    Check if the instrument code for the row is included in the assessor's selected instruments.
    Supports row codes: '01', '02', '03', '04A', '04B', '05', '06', '07', '08', '09', '10', '11'
    """
    if not selected_instruments:
        return False

    norm_sel = set()
    for item in selected_instruments:
        c = re.sub(r'[^A-Z0-9]', '', str(item).upper())
        norm_sel.add(c)
        if c.startswith('FRIA'):
            norm_sel.add(c.replace('FRIA', 'IA'))
        elif c.startswith('FR'):
            norm_sel.add(c.replace('FR', ''))

    target_ia = f"IA{row_code}"
    if target_ia in norm_sel:
        return True

    # Multi-variant matching
    if row_code == '04A' and ('IA04' in norm_sel or 'IA04A' in norm_sel):
        return True
    if row_code == '04B' and ('IA04' in norm_sel or 'IA04B' in norm_sel):
        return True
    if row_code == '05' and any(k in norm_sel for k in ['IA05', 'IA05A', 'IA05B', 'IA05C']):
        return True
    if row_code in norm_sel:
        return True

    return False


def find_scheme_master_pdf(scheme_hint=None):
    """Find master FR.MAPA.02 template PDF per scheme with fallback to default master."""
    if scheme_hint:
        hint = str(scheme_hint).lower()
        base_dir = os.path.join(PROJECT_ROOT, "lsp-document-fixed")
        if os.path.isdir(base_dir):
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
            target_str = name_map.get(num, hint)
            for folder in os.listdir(base_dir):
                if target_str.lower() in folder.lower():
                    folder_path = os.path.join(base_dir, folder)
                    if os.path.isdir(folder_path):
                        for f in os.listdir(folder_path):
                            if "mapa.02" in f.lower() or "mapa 02" in f.lower():
                                return os.path.join(folder_path, f)

    return DEFAULT_MASTER_PDF if os.path.isfile(DEFAULT_MASTER_PDF) else None


def render_fr_mapa_02(data, dest_path, master_pdf_override=None):
    """
    Main generator for FR.MAPA.02 PDF.
    Blanks existing checkboxes and places clean vector checkmarks according to selected_instruments and potensi_asesi.
    """
    master_pdf = master_pdf_override
    if not master_pdf or not os.path.isfile(master_pdf):
        master_pdf = find_scheme_master_pdf(data.get("skema_nama") or data.get("skema_nomor"))

    if not master_pdf or not os.path.isfile(master_pdf):
        raise FileNotFoundError(f"Master template FR.MAPA.02 tidak ditemukan: {master_pdf}")

    doc = fitz.open(master_pdf)
    clean_watermark(doc)

    skema_nama = str(data.get("skema_nama") or "Engine Junior Technician").strip()
    skema_nomor = str(data.get("skema_nomor") or "01/LSP PrO Indo/I/2026").strip()
    selected_instruments = data.get("selected_instruments") or []
    potensi_raw = data.get("potensi_asesi") or data.get("pendekatan_asesi") or 1
    potensi_idx = get_potensi_index(potensi_raw)

    total_pages = len(doc)
    # The first 3 pages contain the instrument mapping tables
    grid_pages = min(3, total_pages)

    # 1. Overlay Scheme metadata on Page 1 if needed
    if grid_pages > 0:
        p0 = doc[0]
        # Check if skema_nama differs from template default
        if skema_nama and "Engine Junior Technician" not in skema_nama:
            judul_rects = p0.search_for("Engine Junior Technician")
            for r in judul_rects:
                p0.draw_rect(fitz.Rect(r.x0 - 2, r.y0 - 2, 560.0, r.y1 + 2), fill=(1, 1, 1), color=(1, 1, 1))
                p0.insert_text(fitz.Point(r.x0, r.y1 - 1.5), skema_nama, fontname="helv", fontsize=10.5)

        if skema_nomor and "01/LSP PrO Indo/I/2026" not in skema_nomor:
            nomor_rects = p0.search_for("01/LSP PrO Indo/I/2026")
            for r in nomor_rects:
                p0.draw_rect(fitz.Rect(r.x0 - 2, r.y0 - 2, 560.0, r.y1 + 2), fill=(1, 1, 1), color=(1, 1, 1))
                p0.insert_text(fitz.Point(r.x0, r.y1 - 1.5), skema_nomor, fontname="helv", fontsize=10.5)

    # 2. Iterate through mapping grid pages and update checkboxes
    for pno in range(grid_pages):
        page = doc[pno]
        d = page.get_text("dict")

        # Collect IA labels on page
        ia_labels = []
        cbs = []
        for b in d.get("blocks", []):
            if "lines" in b:
                for l in b["lines"]:
                    for s in l.get("spans", []):
                        txt = s.get("text", "").strip()
                        m = re.search(r'FR\.IA\.(0[1-9]|1[0-1]|0A|04[AB]?)', txt, re.IGNORECASE)
                        if m:
                            code = m.group(1).upper()
                            if code == '0A':
                                code = '04A'
                            elif code == '04' and 'B' in txt:
                                code = '04B'
                            elif code == '04':
                                code = '04A'
                            ia_labels.append((s['bbox'], code, txt))
                        elif txt in ['\uf052', '\uf0a3', '\u2611', '\u2610', '\u25a1', 'ï ’', 'â˜ ', '☐', '☑']:
                            cbs.append((s['bbox'], txt))

        ia_labels.sort(key=lambda x: x[0][1])
        cbs.sort(key=lambda x: (round(x[0][1], -1), x[0][0]))

        # Group checkboxes by row (Y coordinate)
        cb_rows = []
        for cb in cbs:
            if not cb_rows or abs(cb_rows[-1][0][0][1] - cb[0][1]) > 6:
                cb_rows.append([cb])
            else:
                cb_rows[-1].append(cb)

        for row in cb_rows:
            # Sort the checkboxes on the row from left to right (Col 1 to Col 5)
            row.sort(key=lambda x: x[0][0])
            row_y = (row[0][0][1] + row[0][0][3]) / 2.0

            # Match with closest IA label
            matched_label = None
            min_dist = 9999
            for lbl in ia_labels:
                lbl_y = (lbl[0][1] + lbl[0][3]) / 2.0
                dist = abs(lbl_y - row_y)
                if dist < min_dist and dist < 35:
                    min_dist = dist
                    matched_label = lbl

            code = matched_label[1] if matched_label else ""
            is_inst_sel = is_instrument_selected(code, selected_instruments)

            for col_idx, (cb_rect, cb_txt) in enumerate(row):
                # Only check the column corresponding to the selected Potensi Asesi (1 to 5)
                # and only if this instrument row was selected by the assessor
                is_checked = (col_idx == (potensi_idx - 1)) and is_inst_sel

                r = fitz.Rect(cb_rect)
                # Blank out old checkbox character
                page.draw_rect(r, fill=(1, 1, 1), color=(1, 1, 1))

                # Draw ballot box
                bx0 = r.x0 + 0.5
                by0 = r.y0 + 1.0
                bx1 = bx0 + 9.5
                by1 = by0 + 9.5
                box = fitz.Rect(bx0, by0, bx1, by1)
                page.draw_rect(box, color=(0, 0, 0), width=0.8)

                if is_checked:
                    draw_vector_checkmark(page, box)

    # 3. Set standard document metadata
    meta = doc.metadata or {}
    meta["title"] = f"FR.MAPA.02 Peta Instrumen Asesmen - {skema_nama}"
    meta["author"] = "LSP PrO Indo"
    meta["subject"] = "FR.MAPA.02 Peta Instrumen Asesmen"
    meta["creator"] = "LSP PrO Indo MUK System"
    doc.set_metadata(meta)

    os.makedirs(os.path.dirname(os.path.abspath(dest_path)), exist_ok=True)
    doc.save(dest_path, deflate=True)
    doc.close()
    print(f"OK -> Sukses generate FR.MAPA.02 (Master Overlay {total_pages} Halaman): {dest_path}")
    return dest_path


if __name__ == "__main__":
    payload_input = sys.argv[1] if len(sys.argv) > 1 else None
    out_pdf = sys.argv[2] if len(sys.argv) > 2 else OUT_FILE
    master_in = sys.argv[3] if len(sys.argv) > 3 else None

    data_payload = {}
    if payload_input:
        if os.path.isfile(payload_input):
            with open(payload_input, "r", encoding="utf-8") as f:
                data_payload = json.load(f)
        else:
            try:
                data_payload = json.loads(payload_input)
            except Exception:
                data_payload = {}

    if not data_payload:
        data_payload = {
            "skema_nama": "Engine Junior Technician",
            "skema_nomor": "SKM-01-2026",
            "selected_instruments": ["AK01", "IA08", "IA09"],
            "potensi_asesi": 1,
        }

    render_fr_mapa_02(data_payload, out_pdf, master_in)
