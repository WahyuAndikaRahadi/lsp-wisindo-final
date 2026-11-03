import os
import sys
import json
import io
import base64
from PIL import Image
import pymupdf as fitz

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
REF_PDF = os.path.join(PROJECT_ROOT, "lsp-document-dynamic", "MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection", "8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf")
JSON_PATH = os.path.join(PROJECT_ROOT, "storage", "app", "test_ia01_payload.json")
OUT_PDF = os.path.join(PROJECT_ROOT, "scratch", "test_overlay_ia01.pdf")

CALIBRI_REG = "C:/Windows/Fonts/calibri.ttf"
CALIBRI_BOLD = "C:/Windows/Fonts/calibrib.ttf"
CALIBRI_ITALIC = "C:/Windows/Fonts/calibrii.ttf"

def clean_signature(sig_data):
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
                    pixels[x, y] = (10, 15, 30, int(a * factor))
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
    bx0, by0, bx1, by1 = box_rect
    bw = bx1 - bx0
    bh = by1 - by0
    cx = (bx0 + bx1) / 2.0
    cy = (by0 + by1) / 2.0
    
    p1 = fitz.Point(cx - 0.35 * bw, cy - 0.05 * bh)
    p2 = fitz.Point(cx - 0.08 * bw, cy + 0.32 * bh)
    p3 = fitz.Point(cx + 0.42 * bw, cy - 0.40 * bh)
    
    shape = page.new_shape()
    shape.draw_polyline([p1, p2, p3])
    shape.finish(color=(0, 0, 0), width=1.4, lineCap=1, lineJoin=1)
    shape.commit()

def run_overlay(master_pdf, data, out_pdf):
    doc = fitz.open(master_pdf)
    
    kuk_results = data.get("kuk_results_seq") or data.get("kuk_results_by_id") or {}
    fallback_is_k = bool(data.get("is_kompeten", True))
    global_kuk_idx = 0
    
    total_pages = len(doc)
    for p_idx in range(total_pages):
        page = doc[p_idx]
        
        # 1. Page 1 metadata
        if p_idx == 0:
            asesor_nama = data.get("asesor_nama", "")
            asesi_nama = data.get("asesi_nama", "")
            tanggal = data.get("tanggal", "")
            
            page.insert_text(fitz.Point(266.8, 190.5), asesor_nama, fontfile=CALIBRI_REG, fontsize=11.0, color=(0, 0, 0))
            page.insert_text(fitz.Point(266.8, 208.0), asesi_nama, fontfile=CALIBRI_REG, fontsize=11.0, color=(0, 0, 0))
            page.insert_text(fitz.Point(266.8, 225.3), tanggal, fontfile=CALIBRI_REG, fontsize=11.0, color=(0, 0, 0))
            
        # 2. Checkboxes on pages 1 to 9
        page_boxes = []
        for b in page.get_text('dict')['blocks']:
            if 'lines' not in b: continue
            for line in b['lines']:
                for s in line['spans']:
                    txt = s['text'].strip()
                    if '☐' in txt or txt == '\u2610':
                        page_boxes.append(s['bbox'])
        
        # Sort boxes by Y first, then X
        page_boxes.sort(key=lambda b: (round(b[1], -1), b[0]))
        
        # On Page 9, the last 2 boxes are recommendation boxes
        if p_idx == total_pages - 1:
            kuk_boxes = page_boxes[:-2]
            rec_boxes = page_boxes[-2:]
        else:
            kuk_boxes = page_boxes
            rec_boxes = []
            
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
                page.insert_text(fitz.Point(505.0, box_ya[3] - 2.5), note[:14], fontfile=CALIBRI_REG, fontsize=9.5, color=(0, 0, 0))
                
            global_kuk_idx += 1
            
        # 3. Page 9 Bottom: Umpan Balik, Rekomendasi, Signatures
        if p_idx == total_pages - 1:
            is_kompeten = bool(data.get("is_kompeten", True))
            
            # Recommendation checkmarks
            if len(rec_boxes) >= 2:
                if is_kompeten:
                    draw_vector_checkmark(page, rec_boxes[0])
                else:
                    draw_vector_checkmark(page, rec_boxes[1])
                    
            # Umpan Balik text
            umpan_balik = data.get("umpan_balik") or data.get("assessor_notes") or ""
            if not umpan_balik:
                umpan_balik = "Asesi telah menunjukkan pemenuhan unjuk kerja praktik sesuai standar KUK SKKNI dan SOP industri yang berlaku."
                
            fb_rect = fitz.Rect(78.6, 252.0, 545.0, 305.0)
            page.insert_textbox(fb_rect, umpan_balik, fontfile=CALIBRI_ITALIC, fontsize=10.0, color=(0, 0, 0))
            
            # Asesi section
            asesi_nama = data.get("asesi_nama", "")
            page.insert_text(fitz.Point(416.0, 355.4), asesi_nama, fontfile=CALIBRI_REG, fontsize=11.0, color=(0, 0, 0))
            
            tanggal_asesi = data.get("tanggal_ttd_asesi") or data.get("tanggal", "")
            page.insert_text(fitz.Point(448.0, 401.0), f"Tanggal: {tanggal_asesi}", fontfile=CALIBRI_REG, fontsize=8.5, color=(0, 0, 0))
            
            sig_asesi_data = data.get("signature_asesi") or data.get("asesi_signature")
            if sig_asesi_data:
                sig_bytes = clean_signature(sig_asesi_data)
                if sig_bytes:
                    sig_rect = fitz.Rect(440.0, 363.0, 530.0, 393.0)
                    page.insert_image(sig_rect, stream=sig_bytes)
                    
            # Asesor section
            asesor_nama = data.get("asesor_nama", "")
            asesor_reg = data.get("asesor_no_reg") or data.get("asesor_reg", "")
            page.insert_text(fitz.Point(416.0, 446.3), asesor_nama, fontfile=CALIBRI_REG, fontsize=11.0, color=(0, 0, 0))
            page.insert_text(fitz.Point(416.0, 468.7), asesor_reg, fontfile=CALIBRI_REG, fontsize=11.0, color=(0, 0, 0))
            
            tanggal_asesor = data.get("tanggal_ttd_asesor") or data.get("tanggal", "")
            page.insert_text(fitz.Point(448.0, 509.0), f"Tanggal: {tanggal_asesor}", fontfile=CALIBRI_REG, fontsize=8.5, color=(0, 0, 0))
            
            sig_asesor_data = data.get("signature_asesor") or data.get("assessor_signature")
            if sig_asesor_data:
                sig_bytes = clean_signature(sig_asesor_data)
                if sig_bytes:
                    sig_rect = fitz.Rect(440.0, 471.0, 530.0, 501.0)
                    page.insert_image(sig_rect, stream=sig_bytes)
                    
    doc.save(out_pdf)
    doc.close()
    print(f"SUCCESS: Master overlay generated at {out_pdf}")

if __name__ == "__main__":
    with open(JSON_PATH, "r", encoding="utf-8") as f:
        payload = json.load(f)
    run_overlay(REF_PDF, payload, OUT_PDF)
