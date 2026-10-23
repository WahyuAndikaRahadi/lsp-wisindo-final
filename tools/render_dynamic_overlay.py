#!/usr/bin/env python3
"""
Dynamic MUK PDF Overlay Generator - LSP PrO Indo
Populates dynamic candidate and schedule data into BNSP master PDFs.
Works across Schemes 1 through 7 from /lsp-document-dynamic.
"""

import os
import sys
import json
import io
import base64
from PIL import Image
import pymupdf as fitz

def get_clean_sig_bytes(sig_data):
    if not sig_data:
        return None
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            raw = base64.b64decode(sig_data.split(",", 1)[1])
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            with open(sig_data, "rb") as f:
                raw = f.read()
        else:
            return None

        im = Image.open(io.BytesIO(raw)).convert("RGBA")
        w, h = im.size
        if w == 0 or h == 0:
            return None

        corners = []
        for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)):
            px = im.getpixel(pt)
            if isinstance(px, tuple) and len(px) >= 3:
                corners.append((px[0], px[1], px[2]))
        if not corners:
            corners = [(255, 255, 255)]
        bg_r = sum(c[0] for c in corners) / len(corners)
        bg_g = sum(c[1] for c in corners) / len(corners)
        bg_b = sum(c[2] for c in corners) / len(corners)

        pixels = im.load()
        for y in range(h):
            for x in range(w):
                px = pixels[x, y]
                if not isinstance(px, tuple) or len(px) < 4:
                    continue
                r, g, b, a = int(px[0]), int(px[1]), int(px[2]), int(px[3])
                if a == 0:
                    continue
                is_green = (g > 80 and g > r * 1.15 and g > b * 1.15)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5
                if is_green or dist_bg < 45 or (r > 235 and g > 235 and b > 235):
                    pixels[x, y] = (0, 0, 0, 0)
                elif dist_bg < 75 or (g > r and g > b and g > 70):
                    factor = min(1.0, max(0.0, (dist_bg - 45) / 30.0))
                    pixels[x, y] = (r, g, b, int(a * factor))

        bbox = im.getbbox()
        if bbox:
            pad = 2
            im = im.crop((max(0, bbox[0] - pad), max(0, bbox[1] - pad), min(w, bbox[2] + pad), min(h, bbox[3] + pad)))

        out = io.BytesIO()
        im.save(out, format="PNG")
        return out.getvalue()
    except Exception:
        return None

def clean_watermark(doc):
    cleaned = 0
    for page in doc:
        for xref in page.get_contents():
            stream = doc.xref_stream(xref).decode('latin1', errors='ignore')
            wm_start = stream.find('0.722 0.8 0.894 rg')
            if wm_start != -1:
                wm_end = stream.find('h\r\nf*\r\n', wm_start)
                if wm_end != -1:
                    wm_end += len('h\r\nf*\r\n')
                    clean_stream = stream[:wm_start] + stream[wm_end:]
                    doc.update_stream(xref, clean_stream.encode('latin1'))
                    cleaned += 1
    return cleaned

def find_colon_after(page, rect, max_x_dist=260, y_tolerance=6):
    colons = page.search_for(':')
    matching = [
        c for c in colons 
        if c.x0 >= rect.x0 and (c.x0 - rect.x1) < max_x_dist and abs((c.y0 + c.y1)/2 - (rect.y0 + rect.y1)/2) <= y_tolerance
    ]
    if matching:
        matching.sort(key=lambda c: c.x0)
        return matching[0]
    return None

def overlay_data_on_page(page, data, page_num, total_pages):
    inserted_keys = set()
    
    # 1. Header & identity mappings: label text -> data key
    header_fields = [
        ('Nama Asesi', 'asesi_nama', 9.5),
        ('Nama Peserta', 'asesi_nama', 9.5),
        ('Nama Lengkap', 'asesi_nama', 9.5),
        ('Nama Asesor', 'asesor_nama', 9.5),
        ('No. Reg. Asesor', 'asesor_no_reg', 9.0),
        ('No. Reg', 'asesor_no_reg', 9.0),
        ('Nama TUK', 'tuk_nama', 9.0),
        ('Tempat Uji', 'tuk_nama', 9.0),
        ('TUK', 'tuk_nama', 9.0),
        ('Hari / Tanggal', 'hari_tanggal', 9.0),
        ('Hari/Tanggal', 'hari_tanggal', 9.0),
        ('Waktu', 'waktu', 9.0),
        ('No. KTP/NIK', 'asesi_nik', 9.0),
        ('NIK', 'asesi_nik', 9.0),
        ('Tempat / tgl. lahir', 'asesi_ttl', 8.5),
        ('Jenis kelamin', 'asesi_gender', 8.5),
        ('Kebangsaan', 'asesi_kebangsaan', 8.5),
        ('Alamat rumah', 'asesi_alamat', 8.5),
        ('No. Telepon', 'asesi_telp', 8.5),
        ('No. HP', 'asesi_telp', 8.5),
        ('E-mail', 'asesi_email', 8.5),
        ('Pendidikan Terakhir', 'asesi_pendidikan', 8.5),
        ('Nama Institusi / Perusahaan', 'asesi_institusi', 8.5),
        ('Nama Institusi', 'asesi_institusi', 8.5),
        ('Jabatan', 'asesi_jabatan', 8.5),
    ]

    for label, key, fsize in header_fields:
        val = data.get(key)
        if not val or key in inserted_keys:
            continue
        
        rects = page.search_for(label)
        for r in rects:
            colon = find_colon_after(page, r)
            if colon:
                ins_x = colon.x1 + 6
                ins_y = colon.y1 - 1.5
            else:
                ins_x = r.x1 + 10
                ins_y = r.y1 - 1.5

            if ins_x < page.rect.width - 30:
                page.insert_text(fitz.Point(ins_x, ins_y), str(val), fontsize=fsize, fontname="helv", color=(0, 0, 0))
                inserted_keys.add(key)
                break

    # 2. Date in table/body if not caught by header
    if 'tanggal' in data:
        tgl_rects = page.search_for('Tanggal :')
        for tr in tgl_rects:
            # Check if there is already text after colon
            colon = find_colon_after(page, tr, max_x_dist=50)
            target_x = colon.x1 + 6 if colon else tr.x1 + 6
            if target_x < page.rect.width - 40:
                page.insert_text(fitz.Point(target_x, tr.y1 - 1.5), str(data['tanggal']), fontsize=9.0, fontname="helv", color=(0, 0, 0))

    # 3. Special Signoff Block with 'ASESI :' and 'ASESOR :' (e.g. FR.IA.02)
    asesi_hdrs = page.search_for('ASESI :')
    asesor_hdrs = page.search_for('ASESOR :')
    if asesi_hdrs and asesor_hdrs:
        y_asesi = asesi_hdrs[0].y1
        y_asesor = asesor_hdrs[0].y1
        penyusun = page.search_for('PENYUSUN DAN VALIDATOR')
        y_end = penyusun[0].y0 if penyusun else page.rect.height

        # 3a. ASESI items
        colons_asesi = [c for c in page.search_for(':') if y_asesi <= c.y0 <= y_asesor]
        if len(colons_asesi) >= 1 and 'asesi_nama' in data:
            c0 = colons_asesi[0]
            page.insert_text(fitz.Point(c0.x1 + 6, c0.y1 - 1.5), str(data['asesi_nama']), fontsize=9.5, fontname="hebo", color=(0, 0, 0))
        if len(colons_asesi) >= 2 and 'tanggal' in data:
            c1 = colons_asesi[1]
            page.insert_text(fitz.Point(c1.x1 + 6, c1.y1 - 1.5), str(data['tanggal']), fontsize=9.0, fontname="helv", color=(0, 0, 0))
            sig_asesi_data = data.get('signature_asesi') or data.get('asesi_signature')
            if sig_asesi_data:
                sig_bytes = get_clean_sig_bytes(sig_asesi_data)
                if sig_bytes:
                    sig_rect = fitz.Rect(c1.x1 + 95, c1.y0 - 18, c1.x1 + 175, c1.y1 + 12)
                    page.insert_image(sig_rect, stream=sig_bytes)

        # 3b. ASESOR items
        colons_asesor = [c for c in page.search_for(':') if y_asesor <= c.y0 <= y_end]
        if len(colons_asesor) >= 1 and 'asesor_nama' in data:
            c0 = colons_asesor[0]
            page.insert_text(fitz.Point(c0.x1 + 6, c0.y1 - 1.5), str(data['asesor_nama']), fontsize=9.5, fontname="hebo", color=(0, 0, 0))
        if len(colons_asesor) >= 2 and 'asesor_no_reg' in data:
            c1 = colons_asesor[1]
            page.insert_text(fitz.Point(c1.x1 + 6, c1.y1 - 1.5), str(data['asesor_no_reg']), fontsize=9.0, fontname="helv", color=(0, 0, 0))
        if len(colons_asesor) >= 3 and 'tanggal' in data:
            c2 = colons_asesor[2]
            page.insert_text(fitz.Point(c2.x1 + 6, c2.y1 - 1.5), str(data['tanggal']), fontsize=9.0, fontname="helv", color=(0, 0, 0))
            sig_asesor_data = data.get('signature_asesor') or data.get('assessor_signature')
            if sig_asesor_data:
                sig_bytes = get_clean_sig_bytes(sig_asesor_data)
                if sig_bytes:
                    sig_rect = fitz.Rect(c2.x1 + 95, c2.y0 - 18, c2.x1 + 175, c2.y1 + 12)
                    page.insert_image(sig_rect, stream=sig_bytes)

    # 4. Standard Signature names in parentheses and digital signatures ( ... )
    sig_asesi_labels = page.search_for('Tanda tangan Asesi') or page.search_for('Tanda Tangan Asesi') or page.search_for('Tanda Tangan Peserta')
    for sr in sig_asesi_labels:
        # Check area below signature for name
        if 'asesi_nama' in data:
            # Find open parenthesis below
            parens = [p for p in page.search_for('(') if p.y0 > sr.y1 and (p.y0 - sr.y1) < 70 and abs(p.x0 - sr.x0) < 150]
            if parens:
                p_rect = parens[0]
                page.insert_text(fitz.Point(p_rect.x1 + 4, p_rect.y1 - 1.5), str(data['asesi_nama']), fontsize=9.5, fontname="hebo", color=(0, 0, 0))
            else:
                page.insert_text(fitz.Point(sr.x0 + 40, sr.y1 + 42), f"( {data['asesi_nama']} )", fontsize=9.5, fontname="hebo", color=(0, 0, 0))

        sig_asesi_data = data.get('signature_asesi') or data.get('asesi_signature')
        if sig_asesi_data:
            sig_bytes = get_clean_sig_bytes(sig_asesi_data)
            if sig_bytes:
                sig_rect = fitz.Rect(sr.x0 + 85, sr.y0 - 18, sr.x0 + 165, sr.y1 + 12)
                page.insert_image(sig_rect, stream=sig_bytes)

    sig_asesor_labels = page.search_for('Tanda tangan Asesor') or page.search_for('Tanda Tangan Asesor')
    for sr in sig_asesor_labels:
        if 'asesor_nama' in data:
            parens = [p for p in page.search_for('(') if p.y0 > sr.y1 and (p.y0 - sr.y1) < 70 and abs(p.x0 - sr.x0) < 150]
            if parens:
                p_rect = parens[0]
                page.insert_text(fitz.Point(p_rect.x1 + 4, p_rect.y1 - 1.5), str(data['asesor_nama']), fontsize=9.5, fontname="hebo", color=(0, 0, 0))
            else:
                page.insert_text(fitz.Point(sr.x0 + 40, sr.y1 + 42), f"( {data['asesor_nama']} )", fontsize=9.5, fontname="hebo", color=(0, 0, 0))

        sig_asesor_data = data.get('signature_asesor') or data.get('assessor_signature')
        if sig_asesor_data:
            sig_bytes = get_clean_sig_bytes(sig_asesor_data)
            if sig_bytes:
                sig_rect = fitz.Rect(sr.x0 + 85, sr.y0 - 18, sr.x0 + 165, sr.y1 + 12)
                page.insert_image(sig_rect, stream=sig_bytes)

    # 5. Recommendation checkmarks (for AK02 / Rekaman Asesmen)
    rekomendasi = str(data.get('rekomendasi', '')).upper()
    if rekomendasi in ['K', 'KOMPETEN']:
        k_rects = page.search_for('Kompeten')
        for kr in k_rects:
            # Avoid matching 'Belum Kompeten'
            if not page.search_for('Belum ' + kr.rect if hasattr(kr, 'rect') else 'Belum'):
                # Check for checkbox box around
                page.insert_text(fitz.Point(kr.x0 - 15, kr.y1 - 1.5), "V", fontsize=11, fontname="hebo", color=(0, 0, 0))
                break

def render_dynamic(master_path, data, output_path):
    if not os.path.isfile(master_path):
        raise FileNotFoundError(f"Master file not found: {master_path}")

    doc = fitz.open(master_path)
    clean_watermark(doc)
    
    total_pages = len(doc)
    for idx, page in enumerate(doc):
        overlay_data_on_page(page, data, idx + 1, total_pages)

    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    doc.save(output_path)
    doc.close()
    print(f"OK -> Dynamic overlay saved to {output_path} ({total_pages} pages)")

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: render_dynamic_overlay.py <master_pdf> <data_json_path> <output_pdf>")
        sys.exit(1)

    master_pdf = sys.argv[1]
    data_json_path = sys.argv[2]
    out_pdf = sys.argv[3]

    with open(data_json_path, 'r', encoding='utf-8') as f:
        payload = json.load(f)

    render_dynamic(master_pdf, payload, out_pdf)
