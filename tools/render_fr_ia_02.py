#!/usr/bin/env python3
"""
Generator & Master Overlay FR.IA.02 (Tugas Praktik Demonstrasi - TPD) - LSP PrO Indo

Fitur:
- Deteksi dinamis master template PDF per skema (Skema 1 s/d Skema 7).
- Penghapusan 100% watermark background (Word artifact header bezier curves).
- Mengisi data Asesi dan Asesor serta tanda tangannya sesuai database.
- Menjaga data Penyusun dan Validator tetap utuh / tidak diubah.
- Font: Calibri (Regular, Bold) atau Helvetica fallback.
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

CALIBRI_REG = find_font("Carlito-Regular.ttf", fallback="LiberationSans-Regular.ttf")
CALIBRI_BOLD = find_font("Carlito-Bold.ttf", fallback="LiberationSans-Bold.ttf")

def clean_watermark(doc):
    """Strip Word header artifact vector curves from all pages."""
    cleaned = 0
    prefix = "/Artifact <</Attached [/Top]/Type/Pagination/Subtype/Header>> BDC"
    for page in doc:
        for xref in page.get_contents():
            try:
                stream = doc.xref_stream(xref).decode("latin1", errors="ignore")
                art_pos = stream.find(prefix)
                if art_pos != -1:
                    emc_pos = stream.find("EMC", art_pos)
                    if emc_pos != -1:
                        new_stream = stream[:art_pos] + stream[emc_pos + 3:]
                        doc.update_stream(xref, new_stream.encode("latin1"))
                        cleaned += 1
            except Exception:
                pass
    return cleaned

def clean_signature_image(raw_bytes_or_path):
    """
    Clean signature image to ensure transparent background and crisp dark ink.
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

def find_colon_for_label(page, label_words, max_x_dist=200, y_tol=10):
    """Find colon ':' associated with a given label on the page."""
    words = page.get_text("words")
    # match label words sequence
    label_w0 = label_words[0]
    matched_candidates = [w for w in words if w[4].lower() == label_w0.lower()]
    for cand in matched_candidates:
        cand_y = cand[1]
        colons = [c for c in page.search_for(':') if c.x0 >= cand[0] and (c.x0 - cand[2]) < max_x_dist and abs(c.y0 - cand_y) < y_tol]
        if colons:
            colons.sort(key=lambda c: c.x0)
            return colons[0], cand
    return None, None

def render_master_overlay(master_pdf_path, data, dest_path):
    """Render dynamic data on top of master IA.02 PDF."""
    if not os.path.isfile(master_pdf_path):
        raise FileNotFoundError(f"Master PDF not found: {master_pdf_path}")

    doc = fitz.open(master_pdf_path)

    # 1. Clean watermark from all pages
    clean_watermark(doc)

    # Register fonts if available
    font_reg = "calibri" if os.path.isfile(CALIBRI_REG) else "helv"
    font_bold = "calibri-bold" if os.path.isfile(CALIBRI_BOLD) else "helv"

    # ================= PAGE 1 METADATA OVERLAY =================
    page1 = doc[0]
    if font_reg == "calibri":
        page1.insert_font(fontname="calibri", fontfile=CALIBRI_REG)
    if font_bold == "calibri-bold":
        page1.insert_font(fontname="calibri-bold", fontfile=CALIBRI_BOLD)

    # Extract dynamic data fields
    asesi_nama = str(data.get("asesi_nama") or "")
    asesor_nama = str(data.get("asesor_nama") or "")
    tanggal = str(data.get("tanggal") or "")
    tuk_display = str(data.get("tuk_nama") or data.get("tuk_tipe") or "Sewaktu")
    if "*" not in tuk_display and not data.get("tuk_nama"):
        tuk_display += "*"

    # Search colons on page 1
    colons_p1 = sorted(page1.search_for(':'), key=lambda c: c.y0)
    words_p1 = page1.get_text("words")

    def get_colon_after_word(word_match, min_y=120, max_y=280):
        cands = [w for w in words_p1 if w[4].lower() == word_match.lower() and min_y <= w[1] <= max_y]
        if not cands:
            return None
        w = cands[0]
        matching_colons = [c for c in colons_p1 if c.x0 > w[0] and abs(c.y0 - w[1]) < 12]
        return matching_colons[0] if matching_colons else None

    # Nama Asesor Page 1
    c_asesor = get_colon_after_word("asesor")
    if c_asesor and asesor_nama:
        page1.insert_text((c_asesor.x1 + 6, c_asesor.y1 - 1.5), asesor_nama, fontname=font_bold, fontsize=10, color=(0, 0, 0))

    # Nama Asesi Page 1
    c_asesi = get_colon_after_word("asesi")
    if c_asesi and asesi_nama:
        page1.insert_text((c_asesi.x1 + 6, c_asesi.y1 - 1.5), asesi_nama, fontname=font_bold, fontsize=10, color=(0, 0, 0))

    # Tanggal Page 1
    c_tgl = get_colon_after_word("tanggal")
    if c_tgl and tanggal:
        page1.insert_text((c_tgl.x1 + 6, c_tgl.y1 - 1.5), tanggal, fontname=font_reg, fontsize=10, color=(0, 0, 0))

    # ================= SIGNATURE & VALIDATION OVERLAY =================
    # Asesi and Asesor signature table is on the last page or second-to-last page (e.g. Skema 3)
    # It always precedes PENYUSUN DAN VALIDATOR
    sig_asesi_bytes = clean_signature_image(data.get("signature_asesi"))
    sig_asesor_bytes = clean_signature_image(data.get("signature_asesor"))
    tgl_ttd_asesi = str(data.get("tanggal_ttd_asesi") or tanggal or "")
    tgl_ttd_asesor = str(data.get("tanggal_ttd_asesor") or tanggal or "")
    asesor_no_reg = str(data.get("asesor_no_reg") or "")

    for p_idx in range(len(doc)):
        page = doc[p_idx]
        txt = page.get_text()
        has_asesi = "ASESI" in txt and ("Nama" in txt or "Tanda" in txt)
        has_asesor = "ASESOR" in txt and ("Nama" in txt or "Tanda" in txt)

        if not (has_asesi or has_asesor):
            continue

        if font_reg == "calibri":
            try:
                page.insert_font(fontname="calibri", fontfile=CALIBRI_REG)
            except Exception:
                pass
        if font_bold == "calibri-bold":
            try:
                page.insert_font(fontname="calibri-bold", fontfile=CALIBRI_BOLD)
            except Exception:
                pass

        colons = sorted(page.search_for(':'), key=lambda c: c.y0)
        words = page.get_text("words")

        # Find PENYUSUN boundary if on same page, so we never touch Penyusun/Validator table
        penyusun_words = [w for w in words if "penyusun" in w[4].lower()]
        penyusun_y = min([w[1] for w in penyusun_words]) if penyusun_words else 9999.0

        # Filter colons that are strictly above PENYUSUN
        valid_colons = [c for c in colons if c.y1 < (penyusun_y - 10)]

        # Find section markers
        asesi_marker = [w for w in words if w[4] == "ASESI" and w[1] < penyusun_y]
        asesor_marker = [w for w in words if w[4] == "ASESOR" and w[1] < penyusun_y]

        y_asesi_start = asesi_marker[0][1] if asesi_marker else 0.0
        y_asesor_start = asesor_marker[0][1] if asesor_marker else 9999.0

        # --- Fill ASESI on this page ---
        if has_asesi:
            # 1. Nama Asesi
            nama_words = [w for w in words if w[4].lower() == "nama" and y_asesi_start <= w[1] < y_asesor_start and w[1] < penyusun_y]
            if nama_words:
                w_nama = nama_words[0]
                matching_c = [c for c in valid_colons if c.x0 > w_nama[0] and abs(c.y0 - w_nama[1]) < 14]
                if matching_c and asesi_nama:
                    c = matching_c[0]
                    page.insert_text((c.x1 + 6, c.y1 - 1.5), asesi_nama, fontname=font_bold, fontsize=10, color=(0, 0, 0))

            # 2. Tanda tangan / Tanggal Asesi
            tgl_words = [w for w in words if w[4].lower() in ["tanggal", "tanda"] and y_asesi_start <= w[1] < y_asesor_start and w[1] < penyusun_y]
            if tgl_words:
                w_tgl = tgl_words[-1] # use the lowest word (e.g. Tanggal)
                matching_c = [c for c in valid_colons if c.x0 > 150 and abs(c.y0 - w_tgl[1]) < 20]
                if matching_c:
                    c = matching_c[0]
                    # Insert tanggal asesi text
                    if tgl_ttd_asesi:
                        page.insert_text((c.x1 + 6, c.y1 + 4.0), tgl_ttd_asesi, fontname=font_reg, fontsize=9, color=(0, 0, 0))

                    # Insert signature image if available
                    if sig_asesi_bytes:
                        sig_rect = fitz.Rect(c.x1 + 130, c.y0 - 15, c.x1 + 250, c.y1 + 20)
                        page.insert_image(sig_rect, stream=sig_asesi_bytes, keep_proportion=True)

        # --- Fill ASESOR on this page ---
        if has_asesor:
            # 1. Nama Asesor
            nama_words = [w for w in words if w[4].lower() == "nama" and w[1] >= y_asesor_start and w[1] < penyusun_y]
            if nama_words:
                w_nama = nama_words[0]
                matching_c = [c for c in valid_colons if c.x0 > w_nama[0] and abs(c.y0 - w_nama[1]) < 14]
                if matching_c and asesor_nama:
                    c = matching_c[0]
                    page.insert_text((c.x1 + 6, c.y1 - 1.5), asesor_nama, fontname=font_bold, fontsize=10, color=(0, 0, 0))

            # 2. No. Reg Asesor
            reg_words = [w for w in words if w[4].lower() in ["reg", "no."] and w[1] >= y_asesor_start and w[1] < penyusun_y]
            if reg_words:
                w_reg = reg_words[0]
                matching_c = [c for c in valid_colons if c.x0 > w_reg[0] and abs(c.y0 - w_reg[1]) < 14]
                if matching_c and asesor_no_reg:
                    c = matching_c[0]
                    page.insert_text((c.x1 + 6, c.y1 - 1.5), asesor_no_reg, fontname=font_reg, fontsize=9.5, color=(0, 0, 0))

            # 3. Tanda tangan / Tanggal Asesor
            tgl_words = [w for w in words if w[4].lower() in ["tanggal", "tanda"] and w[1] >= y_asesor_start and w[1] < penyusun_y]
            if tgl_words:
                w_tgl = tgl_words[-1]
                matching_c = [c for c in valid_colons if c.x0 > 150 and abs(c.y0 - w_tgl[1]) < 20]
                if matching_c:
                    c = matching_c[0]
                    # Insert tanggal asesor text
                    if tgl_ttd_asesor:
                        page.insert_text((c.x1 + 6, c.y1 + 4.0), tgl_ttd_asesor, fontname=font_reg, fontsize=9, color=(0, 0, 0))

                    # Insert signature image if available
                    if sig_asesor_bytes:
                        sig_rect = fitz.Rect(c.x1 + 130, c.y0 - 15, c.x1 + 250, c.y1 + 20)
                        page.insert_image(sig_rect, stream=sig_asesor_bytes, keep_proportion=True)

    # Save output PDF
    dest_dir = os.path.dirname(dest_path)
    if dest_dir:
        os.makedirs(dest_dir, exist_ok=True)
    doc.save(dest_path, garbage=4, deflate=True)
    doc.close()
    return dest_path

def main():
    if len(sys.argv) < 3:
        print("Usage: python render_fr_ia_02.py <json_payload_or_path> <output_pdf> [master_pdf]")
        sys.exit(1)

    json_arg = sys.argv[1]
    out_pdf = sys.argv[2]
    master_pdf = sys.argv[3] if len(sys.argv) > 3 else None

    if os.path.isfile(json_arg):
        with open(json_arg, "r", encoding="utf-8") as f:
            data = json.load(f)
    else:
        data = json.loads(json_arg)

    if not master_pdf or not os.path.isfile(master_pdf):
        print(f"Error: master PDF not provided or not found: {master_pdf}", file=sys.stderr)
        sys.exit(1)

    render_master_overlay(master_pdf, data, out_pdf)
    print(f"Generated successfully: {out_pdf}")

if __name__ == "__main__":
    main()
