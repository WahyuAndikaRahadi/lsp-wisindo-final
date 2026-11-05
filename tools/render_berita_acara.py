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

def register_fonts():
    font_candidates = {
        "Lib": [
            "C:/Windows/Fonts/arial.ttf",
            "C:/Windows/Fonts/calibri.ttf",
            "/usr/share/fonts/liberation/LiberationSans-Regular.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            "LiberationSans-Regular.ttf",
        ],
        "Lib-B": [
            "C:/Windows/Fonts/arialbd.ttf",
            "C:/Windows/Fonts/calibrib.ttf",
            "/usr/share/fonts/liberation/LiberationSans-Bold.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
            "LiberationSans-Bold.ttf",
        ],
        "Lib-I": [
            "C:/Windows/Fonts/ariali.ttf",
            "C:/Windows/Fonts/calibrii.ttf",
            "/usr/share/fonts/liberation/LiberationSans-Italic.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Italic.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Oblique.ttf",
            "LiberationSans-Italic.ttf",
        ],
    }
    for name, cands in font_candidates.items():
        registered = False
        for p in cands:
            if os.path.isfile(p):
                try:
                    pdfmetrics.registerFont(TTFont(name, p))
                    registered = True
                    break
                except Exception:
                    pass
        if not registered:
            try:
                pdfmetrics.registerFont(TTFont(name, cands[-1]))
            except Exception:
                pass

register_fonts()

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

def render_page_1(c, chunk, chunk_offset, batch_idx, total_batches, assessor_info=None):
    PW, PH = 596.0, 842.0
    Y1 = lambda top: PH - top

    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        c.drawImage(kop_file, 33.0, Y1(29.8 + 89.2), 528.0, 89.2, mask="auto")

    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PW / 2, Y1(133.0), "BERITA ACARA PELAKSANAAN UJI KOMPETENSI")

    if total_batches > 1:
        c.setFont("Lib", 8.5)
        c.drawCentredString(PW / 2, Y1(147.0), f"(Bagian {batch_idx + 1} dari {total_batches})")

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
    row_h = 16.0
    n_rows = 10
    bot_tbl = top_tbl + hdr_h + n_rows * row_h

    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y1(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    c.line(cols[0], Y1(top_tbl + hdr_h), cols[-1], Y1(top_tbl + hdr_h))

    for r in range(1, n_rows):
        y_r = top_tbl + hdr_h + r * row_h
        c.line(cols[0], Y1(y_r), cols[-1], Y1(y_r))

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

    c.setFont("Lib", 8.5)
    for idx in range(n_rows):
        y_mid = top_tbl + hdr_h + idx * row_h + row_h / 2
        p_info = chunk[idx] if idx < len(chunk) else None
        cand_no = (p_info.get("no") if p_info and p_info.get("no") is not None else (chunk_offset + idx + 1))

        c.drawCentredString((cols[0] + cols[1]) / 2, Y1(y_mid + 3.0), str(cand_no))
        if p_info:
            c.drawString(cols[1] + 6.0, Y1(y_mid + 3.0), p_info.get("nama", ""))
            res = "K" if p_info.get("hasil") in ("K", "KOMPETEN") else ("BK" if p_info.get("hasil") in ("BK", "BELUM_KOMPETEN") else p_info.get("hasil", ""))
            c.drawCentredString((cols[2] + cols[3]) / 2, Y1(y_mid + 3.0), res)
            c.drawString(cols[3] + 6.0, Y1(y_mid + 3.0), p_info.get("rekomendasi", "Diterbitkan Sertifikat"))
            c.drawCentredString((cols[4] + cols[5]) / 2, Y1(y_mid + 3.0), p_info.get("ket", "Lulus"))

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

    sig_pic = DATA.get("signature_penanggungjawab")
    drawn_pic = draw_signature(c, sig_pic, x_left=405.0, y_bottom=Y1(y_sig + 106.0), max_w=120.0, max_h=34.0)
    if not drawn_pic:
        c.drawString(405.0, Y1(y_sig + 75.0), ": [Ttd Digital]")
    c.drawString(330.0, Y1(y_sig + 115.0), "………………………………….")

def render_page_2(c, chunk, chunk_offset, batch_idx, total_batches, assessor_info=None):
    PW, PH = 842.0, 596.0
    Y2 = lambda top: PH - top

    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        c.drawImage(kop_file, 73.5, Y2(22.0 + 68.4), 480.0, 68.4, mask="auto")

    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PW / 2, Y2(105.0), "DAFTAR HADIR")
    if total_batches > 1:
        c.setFont("Lib", 8.5)
        c.drawCentredString(PW / 2, Y2(118.0), f"(Bagian {batch_idx + 1} dari {total_batches})")

    c.setFont("Lib", 9.0)
    c.drawString(62.1, Y2(128.0), f"Hari / tanggal :  {DATA.get('hari_tanggal', '')}")
    c.drawString(62.1, Y2(144.0), f"Tempat           :  {DATA.get('tuk_nama', '')}")

    cols = [62.1, 93.7, 225.2, 327.7, 525.7, 593.3, 674.3, 782.1]
    top_tbl = 160.0
    hdr_h = 24.0
    row_h = 18.0
    n_rows = 10
    bot_tbl = top_tbl + hdr_h + n_rows * row_h

    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y2(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    c.line(cols[0], Y2(top_tbl + hdr_h), cols[-1], Y2(top_tbl + hdr_h))

    for r in range(1, n_rows):
        y_r = top_tbl + hdr_h + r * row_h
        c.line(cols[0], Y2(y_r), cols[-1], Y2(y_r))

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

    c.setFont("Lib", 8.0)
    for idx in range(n_rows):
        y_mid = top_tbl + hdr_h + idx * row_h + row_h / 2
        p_info = chunk[idx] if idx < len(chunk) else None
        cand_no = (p_info.get("no") if p_info and p_info.get("no") is not None else (chunk_offset + idx + 1))

        c.drawCentredString((cols[0] + cols[1]) / 2, Y2(y_mid + 3.0), str(cand_no))
        if p_info:
            c.drawString(cols[1] + 5.0, Y2(y_mid + 3.0), p_info.get("nama", "")[:24])
            c.drawString(cols[2] + 5.0, Y2(y_mid + 3.0), p_info.get("institusi", "")[:18])
            c.drawString(cols[3] + 5.0, Y2(y_mid + 3.0), p_info.get("alamat", "")[:36])
            c.drawString(cols[4] + 5.0, Y2(y_mid + 3.0), p_info.get("pekerjaan", "")[:12])
            c.drawString(cols[5] + 5.0, Y2(y_mid + 3.0), p_info.get("telp", ""))

        sig_num = str(cand_no)
        is_odd = ((idx + 1) % 2 != 0)
        slot_left = cols[6] + 4.0 if is_odd else (cols[6] + cols[7]) / 2 + 4.0

        if p_info and p_info.get("signature"):
            drawn_cand = draw_signature(c, p_info.get("signature"), x_left=slot_left + 14.0, y_bottom=Y2(y_mid + 7.0), max_w=34.0, max_h=15.0)
            if drawn_cand:
                c.drawString(slot_left, Y2(y_mid + 3.0), f"{sig_num}.")
            else:
                c.drawString(slot_left, Y2(y_mid + 3.0), f"{sig_num}. [Ttd]")
        elif p_info:
            c.drawString(slot_left, Y2(y_mid + 3.0), f"{sig_num}. [Ttd]")

    y_sig2 = bot_tbl + 25.0
    c.setFont("Lib", 8.5)
    c.drawString(cols[0] + 30.0, Y2(y_sig2), f"{DATA.get('tuk_kota', 'Karawang')}, {DATA.get('tanggal_dokumen', '')}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 16.0), "Asesor :")

    asesor_nama = (assessor_info.get("nama") if assessor_info else None) or DATA.get('asesor_nama', '')
    asesor_no_reg = (assessor_info.get("no_met") or assessor_info.get("no_reg") if assessor_info else None) or DATA.get('asesor_no_reg', '')
    sig_asesor = (assessor_info.get("signature") if assessor_info else None) or DATA.get("signature_asesor")

    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 32.0), f"Nama             :  {asesor_nama}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 48.0), f"No. Reg          :  {asesor_no_reg}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 64.0), "Tanda tangan :")

    drawn_asesor_p2 = draw_signature(c, sig_asesor, x_left=cols[0] + 95.0, y_bottom=Y2(y_sig2 + 95.0), max_w=120.0, max_h=34.0)
    if not drawn_asesor_p2:
        c.drawString(cols[0] + 95.0, Y2(y_sig2 + 64.0), "[Ttd Digital]")

    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 104.0), "………………………………….")

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
