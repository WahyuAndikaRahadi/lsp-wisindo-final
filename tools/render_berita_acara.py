#!/usr/bin/env python3
"""
Generator 2.2 & 2.3 Berita Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta - LSP PrO Indo
Mendukung multi-batch otomatis (maksimal 10 asesi per Berita Acara dan Daftar Hadir).
Jika peserta > 10, paket BA (Portrait) + Daftar Hadir (Landscape) baru dibuat berurutan dalam file yang sama.
Mendukung rendering tanda tangan digital Asesor, Penanggung Jawab, dan Asesi.
"""

import os
import sys
import io
import json
import base64
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

try:
    from font_helper import register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import register_reportlab_fonts

register_reportlab_fonts(["Lib", "Lib-B", "Lib-I"])

def clean_signature_image(raw_bytes_or_path):
    try:
        from PIL import ImageFilter
        if isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path))
        else:
            im = Image.open(raw_bytes_or_path)
        im = im.convert("RGBA")
        w, h = im.size
        if w == 0 or h == 0:
            return None

        corners = []
        for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)):
            px = im.getpixel(pt)
            if isinstance(px, tuple) and len(px) >= 4 and px[3] > 200:
                corners.append((px[0], px[1], px[2]))

        has_solid_bg = len(corners) >= 3
        bg_r = sum(c[0] for c in corners) / len(corners) if has_solid_bg else 255
        bg_g = sum(c[1] for c in corners) / len(corners) if has_solid_bg else 255
        bg_b = sum(c[2] for c in corners) / len(corners) if has_solid_bg else 255

        out = Image.new("RGBA", (w, h), (0, 0, 0, 0))
        for y in range(h):
            for x in range(w):
                px = im.getpixel((x, y))
                if not isinstance(px, tuple) or len(px) < 4:
                    continue
                r, g, b, a = int(px[0]), int(px[1]), int(px[2]), int(px[3])
                if a < 12:
                    continue
                is_white = (r > 220 and g > 220 and b > 220)
                is_green = (g > 80 and g > r * 1.15 and g > b * 1.15)
                dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5 if has_solid_bg else 999
                if is_white or is_green or (has_solid_bg and dist_bg < 45):
                    continue
                boosted_a = min(255, int(a * 2.8))
                if boosted_a > 20:
                    out.putpixel((x, y), (10, 15, 30, min(255, int(boosted_a * 1.25))))

        bbox = out.getbbox()
        if not bbox:
            return None
        pad = 4
        crop_box = (
            max(0, bbox[0] - pad),
            max(0, bbox[1] - pad),
            min(w, bbox[2] + pad),
            min(h, bbox[3] + pad)
        )
        out = out.crop(crop_box)
        r_ch, g_ch, b_ch, a_ch = out.split()
        a_dilated = a_ch.filter(ImageFilter.MaxFilter(3))
        out_thick = Image.merge("RGBA", (r_ch, g_ch, b_ch, a_dilated))

        out_io = io.BytesIO()
        out_thick.save(out_io, format="PNG")
        return out_io.getvalue()
    except Exception:
        return None

def draw_signature(c, sig_data, x_left, y_bottom, max_w=120.0, max_h=35.0):
    if not sig_data:
        return False
    try:
        if isinstance(sig_data, str) and sig_data.startswith("data:image"):
            sig_data = sig_data.split(",", 1)[1]
            raw = base64.b64decode(sig_data)
        elif isinstance(sig_data, str) and os.path.isfile(sig_data):
            with open(sig_data, "rb") as f:
                raw = f.read()
        elif isinstance(sig_data, (bytes, bytearray)):
            raw = sig_data
        else:
            return False

        cleaned = clean_signature_image(raw)
        if not cleaned:
            return False
        img = ImageReader(io.BytesIO(cleaned))
        iw, ih = img.getSize()
        if iw <= 0 or ih <= 0:
            return False
        scale = min(max_w / iw, max_h / ih)
        w = iw * scale
        h = ih * scale
        c.drawImage(img, x_left, y_bottom, w, h, mask="auto")
        return True
    except Exception:
        return False

DATA = {
    "hari_tanggal": "Senin / 19 Oktober 2026",
    "waktu": "Pkl. 08.00 s/d 17.00",
    "tuk_nama": "SMK Negeri 3 Karawang",
    "tuk_kota": "Karawang",
    "skema_nama": "Mekanik Junior",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "tanggal_dokumen": "19 Oktober 2026",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesor_no_reg": "MET.000.001234 2022",
    "penanggungjawab_nama": "H. Ahmad Fauzi, S.Pd., M.M.",
    "penanggungjawab_jabatan": "Kepala TUK Sewaktu",
    "signature_asesor": None,
    "signature_penanggungjawab": None,
    "peserta": [],
}

def to_words(n):
    kata = ["Nol", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh"]
    if 0 <= n < len(kata):
        return kata[n]
    if n < 20:
        return f"{kata[n - 10]} Belas"
    if n < 100:
        rem = n % 10
        return f"{kata[n // 10]} Puluh" + (f" {kata[rem]}" if rem else "")
    return str(n)

def wrap_text(text, font_name, font_size, max_w):
    """Break text into lines that fit within max_w points without truncating."""
    text = str(text or "").strip()
    if not text:
        return []
    words = text.split()
    if not words:
        return []
    lines = []
    cur_line = []
    for w in words:
        test_line = " ".join(cur_line + [w])
        if pdfmetrics.stringWidth(test_line, font_name, font_size) <= max_w:
            cur_line.append(w)
        else:
            if cur_line:
                lines.append(" ".join(cur_line))
                cur_line = []
            if pdfmetrics.stringWidth(w, font_name, font_size) <= max_w:
                cur_line.append(w)
            else:
                sub = ""
                for ch in w:
                    if pdfmetrics.stringWidth(sub + ch, font_name, font_size) <= max_w:
                        sub += ch
                    else:
                        if sub:
                            lines.append(sub)
                        sub = ch
                if sub:
                    cur_line.append(sub)
    if cur_line:
        lines.append(" ".join(cur_line))
    return lines

def draw_multiline(c, lines, x, y_mid, leading, font_name, font_size, y_func, align="left", col_right=None):
    """Draw vertically centered multi-line text inside a table cell."""
    if not lines:
        return
    c.setFont(font_name, font_size)
    n = len(lines)
    for i, line in enumerate(lines):
        line_y_from_top = y_mid - ((n - 1) * leading) / 2.0 + i * leading
        baseline = y_func(line_y_from_top + font_size * 0.35)
        if align == "center" and col_right is not None:
            c.drawCentredString((x + col_right) / 2.0, baseline, line)
        elif align == "center":
            c.drawCentredString(x, baseline, line)
        elif align == "right" and col_right is not None:
            c.drawRightString(col_right, baseline, line)
        else:
            c.drawString(x, baseline, line)

def render_page_1(c, chunk, chunk_offset, batch_idx, total_batches, assessor_info=None):
    PW, PH = 596.0, 842.0
    Y1 = lambda top: PH - top

    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        kop_w = 528.0
        kop_h = 89.2
        kop_x = (PW - kop_w) / 2.0
        c.drawImage(kop_file, kop_x, Y1(29.8 + kop_h), kop_w, kop_h, mask="auto")

    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PW / 2, Y1(133.0), "BERITA ACARA PELAKSANAAN UJI KOMPETENSI")

    c.setFont("Lib", 9.5)
    intro_p1 = f"Pada hari ini, Hari/Tanggal : {DATA.get('hari_tanggal', '')}  Waktu : {DATA.get('waktu', '')} bertempat di {DATA.get('tuk_nama', '')},"
    intro_p2 = f"telah dilaksanakan proses asesmen Skema Sertifikasi {DATA.get('skema_nama', '')} dengan perincian sebagai berikut :"
    c.drawString(45.0, Y1(165.0), intro_p1)
    c.drawString(45.0, Y1(180.0), intro_p2)

    jml_ikut = len(chunk)
    jml_k = sum(1 for p in chunk if p.get("hasil") in ("K", "KOMPETEN"))
    jml_bk = sum(1 for p in chunk if p.get("hasil") in ("BK", "BELUM_KOMPETEN"))

    c.drawString(45.0, Y1(215.0), f"Jumlah kandidat yang mengikuti : {jml_ikut}  ( {to_words(jml_ikut)} ) orang")
    c.drawString(45.0, Y1(235.0), f"Jumlah kandidat yang dinyatakan Kompeten : {jml_k}  ( {to_words(jml_k)} ) orang")
    c.drawString(45.0, Y1(255.0), f"Jumlah kandidat yang dinyatakan Belum Kompeten : {jml_bk}  ( {to_words(jml_bk)} ) orang")

    cols = [44.1, 84.7, 269.3, 354.7, 476.3, 561.9]
    top_tbl = 285.0
    hdr_h = 28.0
    n_rows = 10

    # Calculate row data and dynamic row heights to wrap text
    row_data_p1 = []
    row_heights_p1 = []
    for idx in range(n_rows):
        p_info = chunk[idx] if idx < len(chunk) else None
        cand_no = str(idx + 1)
        if p_info:
            lines_nama = wrap_text(p_info.get("nama", ""), "Lib", 8.5, (cols[2] - cols[1]) - 10.0)
            res = "K" if p_info.get("hasil") in ("K", "KOMPETEN") else ("BK" if p_info.get("hasil") in ("BK", "BELUM_KOMPETEN") else p_info.get("hasil", ""))
            if res == "K":
                rek_text = "direkomendasikan"
            elif res == "BK":
                rek_text = "belum kompeten"
            else:
                raw_rek = p_info.get("rekomendasi", "")
                if raw_rek in ("Diterbitkan Sertifikat", "Direkomendasikan", "direkomendasikan"):
                    rek_text = "direkomendasikan"
                elif raw_rek in ("Tidak Diterbitkan", "Belum Kompeten", "belum kompeten"):
                    rek_text = "belum kompeten"
                else:
                    rek_text = raw_rek
            lines_rekomendasi = wrap_text(rek_text, "Lib", 8.0, (cols[4] - cols[3]) - 10.0)
            lines_ket = wrap_text(p_info.get("ket", "Lulus" if res == "K" else "Belum Lulus"), "Lib", 8.0, (cols[5] - cols[4]) - 8.0)
            max_l = max(1, len(lines_nama), len(lines_rekomendasi), len(lines_ket))
            h = max(16.0, max_l * 9.5 + 4.0)
            row_data_p1.append({
                "p_info": p_info,
                "cand_no": cand_no,
                "lines_nama": lines_nama,
                "res": res,
                "lines_rekomendasi": lines_rekomendasi,
                "lines_ket": lines_ket,
            })
            row_heights_p1.append(h)
        else:
            row_data_p1.append({
                "p_info": None,
                "cand_no": cand_no,
                "lines_nama": [],
                "res": "",
                "lines_rekomendasi": [],
                "lines_ket": [],
            })
            row_heights_p1.append(16.0)

    cur_y = top_tbl + hdr_h
    row_tops_p1 = []
    row_mids_p1 = []
    for h in row_heights_p1:
        row_tops_p1.append(cur_y)
        row_mids_p1.append(cur_y + h / 2.0)
        cur_y += h
    bot_tbl = cur_y

    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y1(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    c.line(cols[0], Y1(top_tbl + hdr_h), cols[-1], Y1(top_tbl + hdr_h))

    for r in range(1, n_rows):
        c.line(cols[0], Y1(row_tops_p1[r]), cols[-1], Y1(row_tops_p1[r]))

    for x in cols[1:-1]:
        c.line(x, Y1(top_tbl), x, Y1(bot_tbl))

    c.setFont("Lib-B", 9.0)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y1(top_tbl + 18.0), "No.")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y1(top_tbl + 18.0), "Nama Peserta")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y1(top_tbl + 12.0), "Hasil")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y1(top_tbl + 22.0), "Asesmen*)")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y1(top_tbl + 12.0), "Rekomendasi/")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y1(top_tbl + 22.0), "Tindak Lanjut")
    c.drawCentredString((cols[4] + cols[5]) / 2, Y1(top_tbl + 18.0), "Keterangan")

    for idx in range(n_rows):
        rd = row_data_p1[idx]
        y_mid = row_mids_p1[idx]
        c.setFont("Lib", 8.5)
        c.drawCentredString((cols[0] + cols[1]) / 2, Y1(y_mid + 2.5), rd["cand_no"])
        if rd["p_info"]:
            draw_multiline(c, rd["lines_nama"], cols[1] + 5.0, y_mid, 9.5, "Lib", 8.5, Y1, align="left")
            c.setFont("Lib", 8.5)
            c.drawCentredString((cols[2] + cols[3]) / 2, Y1(y_mid + 2.5), rd["res"])
            draw_multiline(c, rd["lines_rekomendasi"], cols[3] + 5.0, y_mid, 9.0, "Lib", 8.0, Y1, align="left")
            draw_multiline(c, rd["lines_ket"], (cols[4] + cols[5]) / 2, y_mid, 9.0, "Lib", 8.0, Y1, align="center")

    c.setFont("Lib-I", 8.0)
    c.drawString(cols[0], Y1(bot_tbl + 16.0), "Keterangan : *) Diisi dengan K (Kompeten) atau BK (Belum Kompeten)")
    c.setFont("Lib", 9.0)
    c.drawString(cols[0], Y1(bot_tbl + 32.0), "Demikian berita acara ini dibuat dengan sebenarnya, untuk digunakan sebagaimana mestinya.")

    y_sig = bot_tbl + 52.0
    c.drawString(330.0, Y1(y_sig), f"{DATA.get('tuk_kota', 'Karawang')}, {DATA.get('tanggal_dokumen', '')}")

    asesor_nama = (assessor_info.get("nama") if assessor_info else None) or DATA.get('asesor_nama', '')
    asesor_no_reg = (assessor_info.get("no_met") or assessor_info.get("no_reg") if assessor_info else None) or DATA.get('asesor_no_reg', '')
    sig_asesor = (assessor_info.get("signature") if assessor_info else None) or DATA.get("signature_asesor")

    c.setFont("Lib-B", 9.0)
    c.drawString(65.0, Y1(y_sig + 25.0), "Asesor")
    c.drawString(330.0, Y1(y_sig + 25.0), "Penanggungjawab kegiatan")

    c.setFont("Lib", 8.5)
    c.drawString(65.0, Y1(y_sig + 45.0), "Nama")
    c.drawString(135.0, Y1(y_sig + 45.0), f": {asesor_nama}")
    c.drawString(65.0, Y1(y_sig + 60.0), "No. Reg")
    c.drawString(135.0, Y1(y_sig + 60.0), f": {asesor_no_reg}")
    c.drawString(65.0, Y1(y_sig + 75.0), "Tanda tangan")

    drawn_asesor = draw_signature(c, sig_asesor, x_left=135.0, y_bottom=Y1(y_sig + 106.0), max_w=120.0, max_h=34.0)
    if not drawn_asesor:
        c.drawString(135.0, Y1(y_sig + 75.0), ": [Ttd Digital]")
    c.drawString(65.0, Y1(y_sig + 115.0), "………………………………….")

    c.drawString(330.0, Y1(y_sig + 45.0), "Nama")
    c.drawString(405.0, Y1(y_sig + 45.0), f": {DATA.get('penanggungjawab_nama', '')}")
    c.drawString(330.0, Y1(y_sig + 60.0), "Jabatan")
    c.drawString(405.0, Y1(y_sig + 60.0), f": {DATA.get('penanggungjawab_jabatan', '')}")
    c.drawString(330.0, Y1(y_sig + 75.0), "Tanda tangan")
    c.drawString(405.0, Y1(y_sig + 75.0), ":")

    sig_pic = DATA.get("signature_penanggungjawab")
    drawn_pic = draw_signature(c, sig_pic, x_left=415.0, y_bottom=Y1(y_sig + 106.0), max_w=120.0, max_h=34.0)
    c.drawString(330.0, Y1(y_sig + 115.0), "………………………………….")

def render_page_2(c, chunk, chunk_offset, batch_idx, total_batches, assessor_info=None):
    PW, PH = 842.0, 596.0
    Y2 = lambda top: PH - top

    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        kop_w = 480.0
        kop_h = 68.4
        kop_x = (PW - kop_w) / 2.0
        c.drawImage(kop_file, kop_x, Y2(22.0 + kop_h), kop_w, kop_h, mask="auto")

    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PW / 2, Y2(105.0), "DAFTAR HADIR")

    c.setFont("Lib", 9.0)
    c.drawString(62.1, Y2(128.0), f"Hari / tanggal :  {DATA.get('hari_tanggal', '')}")
    c.drawString(62.1, Y2(144.0), f"Tempat           :  {DATA.get('tuk_nama', '')}")

    cols = [62.1, 93.7, 225.2, 327.7, 525.7, 593.3, 674.3, 782.1]
    top_tbl = 160.0
    hdr_h = 24.0
    n_rows = 10

    # Calculate row data and dynamic row heights to wrap text for all columns
    row_data_p2 = []
    row_heights_p2 = []
    for idx in range(n_rows):
        p_info = chunk[idx] if idx < len(chunk) else None
        cand_no = str(idx + 1)
        if p_info:
            lines_nama = wrap_text(p_info.get("nama", ""), "Lib", 8.0, (cols[2] - cols[1]) - 8.0)
            lines_institusi = wrap_text(p_info.get("institusi", ""), "Lib", 7.5, (cols[3] - cols[2]) - 8.0)
            lines_alamat = wrap_text(p_info.get("alamat", ""), "Lib", 7.5, (cols[4] - cols[3]) - 8.0)
            lines_pekerjaan = wrap_text(p_info.get("pekerjaan", "Pelajar"), "Lib", 7.5, (cols[5] - cols[4]) - 6.0)
            lines_telp = wrap_text(str(p_info.get("telp", "-") or "-"), "Lib", 7.5, (cols[6] - cols[5]) - 6.0)
            max_l = max(1, len(lines_nama), len(lines_institusi), len(lines_alamat), len(lines_pekerjaan), len(lines_telp))
            h = max(18.0, max_l * 9.0 + 4.0)
            row_data_p2.append({
                "p_info": p_info,
                "cand_no": cand_no,
                "lines_nama": lines_nama,
                "lines_institusi": lines_institusi,
                "lines_alamat": lines_alamat,
                "lines_pekerjaan": lines_pekerjaan,
                "lines_telp": lines_telp,
            })
            row_heights_p2.append(h)
        else:
            row_data_p2.append({
                "p_info": None,
                "cand_no": cand_no,
                "lines_nama": [],
                "lines_institusi": [],
                "lines_alamat": [],
                "lines_pekerjaan": [],
                "lines_telp": [],
            })
            row_heights_p2.append(18.0)

    cur_y = top_tbl + hdr_h
    row_tops_p2 = []
    row_mids_p2 = []
    for h in row_heights_p2:
        row_tops_p2.append(cur_y)
        row_mids_p2.append(cur_y + h / 2.0)
        cur_y += h
    bot_tbl = cur_y

    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y2(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    c.line(cols[0], Y2(top_tbl + hdr_h), cols[-1], Y2(top_tbl + hdr_h))

    for r in range(1, n_rows):
        c.line(cols[0], Y2(row_tops_p2[r]), cols[-1], Y2(row_tops_p2[r]))

    for x in cols[1:-1]:
        c.line(x, Y2(top_tbl), x, Y2(bot_tbl))

    c.setFont("Lib-B", 8.5)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y2(top_tbl + 16.0), "No")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y2(top_tbl + 16.0), "Nama Peserta")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y2(top_tbl + 16.0), "Institusi")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y2(top_tbl + 16.0), "Alamat")
    c.drawCentredString((cols[4] + cols[5]) / 2, Y2(top_tbl + 16.0), "Pekerjaan")
    c.drawCentredString((cols[5] + cols[6]) / 2, Y2(top_tbl + 16.0), "No. Telp/HP")
    c.drawCentredString((cols[6] + cols[7]) / 2, Y2(top_tbl + 16.0), "Tanda Tangan")

    for idx in range(n_rows):
        rd = row_data_p2[idx]
        y_mid = row_mids_p2[idx]
        this_row_h = row_heights_p2[idx]
        c.setFont("Lib", 8.0)
        c.drawCentredString((cols[0] + cols[1]) / 2, Y2(y_mid + 2.5), rd["cand_no"])

        if rd["p_info"]:
            p_info = rd["p_info"]
            draw_multiline(c, rd["lines_nama"], cols[1] + 4.0, y_mid, 9.0, "Lib", 8.0, Y2, align="left")
            draw_multiline(c, rd["lines_institusi"], cols[2] + 4.0, y_mid, 8.5, "Lib", 7.5, Y2, align="left")
            draw_multiline(c, rd["lines_alamat"], cols[3] + 4.0, y_mid, 8.5, "Lib", 7.5, Y2, align="left")
            draw_multiline(c, rd["lines_pekerjaan"], cols[4] + 4.0, y_mid, 8.5, "Lib", 7.5, Y2, align="left")
            draw_multiline(c, rd["lines_telp"], cols[5] + 4.0, y_mid, 8.5, "Lib", 7.5, Y2, align="left")

            sig_num = rd["cand_no"]
            is_odd = ((idx + 1) % 2 != 0)
            slot_left = cols[6] + 4.0 if is_odd else (cols[6] + cols[7]) / 2 + 4.0
            sig_max_h = min(18.0, this_row_h - 4.0)

            if p_info.get("signature"):
                drawn_cand = draw_signature(c, p_info.get("signature"), x_left=slot_left + 14.0, y_bottom=Y2(y_mid + sig_max_h / 2.0), max_w=34.0, max_h=sig_max_h)
                c.setFont("Lib", 8.0)
                if drawn_cand:
                    c.drawString(slot_left, Y2(y_mid + 2.5), f"{sig_num}.")
                else:
                    c.drawString(slot_left, Y2(y_mid + 2.5), f"{sig_num}. [Ttd]")
            else:
                c.setFont("Lib", 8.0)
                c.drawString(slot_left, Y2(y_mid + 2.5), f"{sig_num}. [Ttd]")
        else:
            sig_num = rd["cand_no"]
            is_odd = ((idx + 1) % 2 != 0)
            slot_left = cols[6] + 4.0 if is_odd else (cols[6] + cols[7]) / 2 + 4.0
            c.setFont("Lib", 8.0)
            c.drawString(slot_left, Y2(y_mid + 2.5), f"{sig_num}. ………")

    sig_block_h = 94.0
    gap = 20.0
    if bot_tbl + gap + sig_block_h > 585.0:
        gap = max(6.0, 585.0 - (bot_tbl + sig_block_h))
    y_sig2 = bot_tbl + gap

    c.setFont("Lib", 8.5)
    c.drawString(cols[0] + 30.0, Y2(y_sig2), f"{DATA.get('tuk_kota', 'Karawang')}, {DATA.get('tanggal_dokumen', '')}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 14.0), "Asesor :")

    asesor_nama = (assessor_info.get("nama") if assessor_info else None) or DATA.get('asesor_nama', '')
    asesor_no_reg = (assessor_info.get("no_met") or assessor_info.get("no_reg") if assessor_info else None) or DATA.get('asesor_no_reg', '')
    sig_asesor = (assessor_info.get("signature") if assessor_info else None) or DATA.get("signature_asesor")

    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 28.0), f"Nama             :  {asesor_nama}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 42.0), f"No. Reg          :  {asesor_no_reg}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 56.0), "Tanda tangan :")

    drawn_asesor_p2 = draw_signature(c, sig_asesor, x_left=cols[0] + 95.0, y_bottom=Y2(y_sig2 + 86.0), max_w=110.0, max_h=30.0)
    if not drawn_asesor_p2:
        c.drawString(cols[0] + 95.0, Y2(y_sig2 + 56.0), "[Ttd Digital]")

    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 94.0), "………………………………….")

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(596.0, 842.0))
    chunk_size = 10
    batches = []

    if "assessor_groups" in DATA and DATA["assessor_groups"]:
        for grp in DATA["assessor_groups"]:
            assessor_info = grp.get("assessor") or {}
            grp_peserta = grp.get("peserta") or []
            chunks = [grp_peserta[i:i + chunk_size] for i in range(0, len(grp_peserta), chunk_size)]
            if not chunks:
                chunks = [[]]
            for ch_idx, chunk in enumerate(chunks):
                batches.append({
                    "assessor": assessor_info,
                    "chunk": chunk,
                    "chunk_offset": ch_idx * chunk_size,
                })
    else:
        peserta_all = DATA.get("peserta", [])
        has_assessor_in_peserta = any("asesor_nama" in p or "assessor_id" in p for p in peserta_all)
        if has_assessor_in_peserta:
            grouped = {}
            for p in peserta_all:
                key = p.get("assessor_id") or p.get("asesor_nama") or "default"
                if key not in grouped:
                    grouped[key] = {
                        "assessor": {
                            "nama": p.get("asesor_nama") or DATA.get("asesor_nama"),
                            "no_met": p.get("asesor_no_reg") or DATA.get("asesor_no_reg"),
                            "signature": p.get("signature_asesor") or DATA.get("signature_asesor"),
                        },
                        "peserta": [],
                    }
                grouped[key]["peserta"].append(p)
            for grp in grouped.values():
                assessor_info = grp["assessor"]
                grp_peserta = grp["peserta"]
                chunks = [grp_peserta[i:i + chunk_size] for i in range(0, len(grp_peserta), chunk_size)]
                if not chunks:
                    chunks = [[]]
                for ch_idx, chunk in enumerate(chunks):
                    batches.append({
                        "assessor": assessor_info,
                        "chunk": chunk,
                        "chunk_offset": ch_idx * chunk_size,
                    })
        else:
            chunks = [peserta_all[i:i + chunk_size] for i in range(0, len(peserta_all), chunk_size)]
            if not chunks:
                chunks = [[]]
            for ch_idx, chunk in enumerate(chunks):
                batches.append({
                    "assessor": {
                        "nama": DATA.get("asesor_nama"),
                        "no_met": DATA.get("asesor_no_reg"),
                        "signature": DATA.get("signature_asesor"),
                    },
                    "chunk": chunk,
                    "chunk_offset": ch_idx * chunk_size,
                })

    total_batches = len(batches)
    for idx, b in enumerate(batches):
        c.setPageSize((596.0, 842.0))
        render_page_1(c, b["chunk"], b["chunk_offset"], idx, total_batches, b.get("assessor"))
        c.showPage()

        c.setPageSize((842.0, 596.0))
        render_page_2(c, b["chunk"], b["chunk_offset"], idx, total_batches, b.get("assessor"))
        c.showPage()

    c.save()
    print(f"OK -> Sukses generate Berita Acara & Daftar Hadir ({total_batches} paket): {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "Berita_Acara_Peserta_generated.pdf")
    if len(sys.argv) > 1:
        if sys.argv[1].endswith(".json") and os.path.isfile(sys.argv[1]):
            with open(sys.argv[1], "r", encoding="utf-8") as f:
                DATA.update(json.load(f))
            if len(sys.argv) > 2:
                out_file = sys.argv[2]
        else:
            out_file = sys.argv[1]
    render(out_file)
