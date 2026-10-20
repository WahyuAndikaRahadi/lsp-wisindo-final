#!/usr/bin/env python3
"""
Generator 2.2 & 2.3 Berita Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Halaman 1: Portrait (Berita Acara)
Halaman 2: Landscape (Daftar Hadir Peserta)
"""

import os
import sys
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ASSETS = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk", "assets")

def find_font(filename):
    candidates = [
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/liberation",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/dejavu"
    ]
    for d in candidates:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            return p
    for root, _, files in os.walk("/usr/share/fonts"):
        if filename in files:
            return os.path.join(root, filename)
    return filename

font_map = {
    "Lib": "LiberationSans-Regular.ttf",
    "Lib-B": "LiberationSans-Bold.ttf",
    "Lib-I": "LiberationSans-Italic.ttf",
    "Cal": "Carlito-Regular.ttf",
    "Cal-B": "Carlito-Bold.ttf",
}
for name, ffile in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile)))

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
    "peserta": [
        {"no": 1, "nama": "Wahyu Andika Rahadi", "institusi": "SMK Negeri 3 Karawang", "alamat": "Jl. Kondangjaya No. 12, Karawang", "pekerjaan": "Pelajar", "telp": "081234567890", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 2, "nama": "Rizky Ramadhan", "institusi": "SMK Negeri 3 Karawang", "alamat": "Perum Griya Kondang Asri Blok B", "pekerjaan": "Pelajar", "telp": "081234567891", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 3, "nama": "Muhammad Fadhil", "institusi": "SMK Negeri 3 Karawang", "alamat": "Jl. Johar Indah No. 45, Karawang", "pekerjaan": "Pelajar", "telp": "081234567892", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 4, "nama": "Dimas Pratama", "institusi": "SMK Negeri 3 Karawang", "alamat": "Ds. Klari RT 03/02, Karawang", "pekerjaan": "Pelajar", "telp": "081234567893", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 5, "nama": "Bagus Setiawan", "institusi": "SMK Negeri 3 Karawang", "alamat": "Perum Cikampek Baru Blok D", "pekerjaan": "Pelajar", "telp": "081234567894", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 6, "nama": "Aditya Nugraha", "institusi": "SMK Negeri 3 Karawang", "alamat": "Jl. Raya Kosambi No. 88, Karawang", "pekerjaan": "Pelajar", "telp": "081234567895", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 7, "nama": "Farhan Maulana", "institusi": "SMK Negeri 3 Karawang", "alamat": "Kp. Rawagabus RT 01/05, Karawang", "pekerjaan": "Pelajar", "telp": "081234567896", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 8, "nama": "Ilham Hidayat", "institusi": "SMK Negeri 3 Karawang", "alamat": "Jl. Telukjambe Timur No. 19", "pekerjaan": "Pelajar", "telp": "081234567897", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 9, "nama": "Gilang Permana", "institusi": "SMK Negeri 3 Karawang", "alamat": "Perum Telagasari Indah Blok A", "pekerjaan": "Pelajar", "telp": "081234567898", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
        {"no": 10, "nama": "Fajar Sidik", "institusi": "SMK Negeri 3 Karawang", "alamat": "Jl. Tuparev No. 102, Karawang", "pekerjaan": "Pelajar", "telp": "081234567899", "hasil": "K", "rekomendasi": "Diterbitkan Sertifikat", "ket": "Lulus"},
    ]
}

def render_page_1(c):
    # Page 1: Portrait (596 x 842)
    PW, PH = 596.0, 842.0
    Y1 = lambda top: PH - top
    
    # 1. Header image (Kop Surat)
    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        c.drawImage(kop_file, 33.0, Y1(29.8 + 89.2), 528.0, 89.2, mask="auto")
        
    # 2. Title
    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PW / 2, Y1(135.0), "BERITA ACARA PELAKSANAAN UJI KOMPETENSI")
    
    # 3. Intro Paragraph
    c.setFont("Lib", 9.5)
    intro_p1 = f"Pada hari ini, Hari/Tanggal : {DATA['hari_tanggal']}  Waktu : {DATA['waktu']} bertempat di {DATA['tuk_nama']},"
    intro_p2 = f"telah dilaksanakan proses asesmen Skema Sertifikasi {DATA['skema_nama']} dengan perincian sebagai berikut :"
    c.drawString(45.0, Y1(165.0), intro_p1)
    c.drawString(45.0, Y1(180.0), intro_p2)
    
    # Candidate counts
    jml_ikut = len(DATA["peserta"])
    jml_k = sum(1 for p in DATA["peserta"] if p.get("hasil") == "K")
    jml_bk = sum(1 for p in DATA["peserta"] if p.get("hasil") == "BK")
    
    def to_words(n):
        kata = ["Nol", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh"]
        return kata[n] if 0 <= n < len(kata) else str(n)
        
    c.drawString(45.0, Y1(215.0), f"Jumlah kandidat yang mengikuti : {jml_ikut}  ( {to_words(jml_ikut)} ) orang")
    c.drawString(45.0, Y1(235.0), f"Jumlah kandidat yang dinyatakan Kompeten : {jml_k}  ( {to_words(jml_k)} ) orang")
    c.drawString(45.0, Y1(255.0), f"Jumlah kandidat yang dinyatakan Belum Kompeten : {jml_bk}  ( {to_words(jml_bk)} ) orang")
    
    # 4. Table Berita Acara
    cols = [44.1, 84.7, 269.3, 354.7, 476.3, 561.9]
    top_tbl = 285.0
    hdr_h = 28.0
    row_h = 16.0
    n_rows = max(10, len(DATA["peserta"]))
    bot_tbl = top_tbl + hdr_h + n_rows * row_h
    
    c.setLineWidth(0.6)
    c.setStrokeColor(black)
    c.rect(cols[0], Y1(bot_tbl), cols[-1] - cols[0], bot_tbl - top_tbl)
    
    # Horizontal line below header
    c.line(cols[0], Y1(top_tbl + hdr_h), cols[-1], Y1(top_tbl + hdr_h))
    
    # Row lines
    for r in range(1, n_rows):
        y_r = top_tbl + hdr_h + r * row_h
        c.line(cols[0], Y1(y_r), cols[-1], Y1(y_r))
        
    # Vertical lines
    for x in cols[1:-1]:
        c.line(x, Y1(top_tbl), x, Y1(bot_tbl))
        
    # Table Header text
    c.setFont("Lib-B", 9.0)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y1(top_tbl + 18.0), "No.")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y1(top_tbl + 18.0), "Nama Peserta")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y1(top_tbl + 12.0), "Hasil")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y1(top_tbl + 22.0), "Asesmen*)")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y1(top_tbl + 12.0), "Rekomendasi/")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y1(top_tbl + 22.0), "Tindak Lanjut")
    c.drawCentredString((cols[4] + cols[5]) / 2, Y1(top_tbl + 18.0), "Keterangan")
    
    # Table Rows
    c.setFont("Lib", 8.5)
    for idx in range(n_rows):
        y_mid = top_tbl + hdr_h + idx * row_h + row_h / 2
        p_info = DATA["peserta"][idx] if idx < len(DATA["peserta"]) else None
        
        c.drawCentredString((cols[0] + cols[1]) / 2, Y1(y_mid + 3.0), str(idx + 1))
        if p_info:
            c.drawString(cols[1] + 6.0, Y1(y_mid + 3.0), p_info["nama"])
            c.drawCentredString((cols[2] + cols[3]) / 2, Y1(y_mid + 3.0), p_info.get("hasil", "K"))
            c.drawString(cols[3] + 6.0, Y1(y_mid + 3.0), p_info.get("rekomendasi", "Diterbitkan Sertifikat"))
            c.drawCentredString((cols[4] + cols[5]) / 2, Y1(y_mid + 3.0), p_info.get("ket", "Lulus"))
            
    # 5. Note & Closing text
    c.setFont("Lib-I", 8.0)
    c.drawString(cols[0], Y1(bot_tbl + 16.0), "Keterangan : *) Diisi dengan K (Kompeten) atau BK (Belum Kompeten)")
    c.setFont("Lib", 9.0)
    c.drawString(cols[0], Y1(bot_tbl + 32.0), "Demikian berita acara ini dibuat dengan sebenarnya, untuk digunakan sebagaimana mestinya.")
    
    # 6. Signatures
    y_sig = bot_tbl + 52.0
    c.drawString(330.0, Y1(y_sig), f"{DATA['tuk_kota']}, {DATA['tanggal_dokumen']}")
    
    c.setFont("Lib-B", 9.0)
    c.drawString(65.0, Y1(y_sig + 25.0), "Asesor")
    c.drawString(330.0, Y1(y_sig + 25.0), "Penanggungjawab kegiatan")
    
    c.setFont("Lib", 8.5)
    c.drawString(65.0, Y1(y_sig + 45.0), "Nama")
    c.drawString(135.0, Y1(y_sig + 45.0), f": {DATA['asesor_nama']}")
    c.drawString(65.0, Y1(y_sig + 60.0), "No. Reg")
    c.drawString(135.0, Y1(y_sig + 60.0), f": {DATA['asesor_no_reg']}")
    c.drawString(65.0, Y1(y_sig + 75.0), "Tanda tangan")
    c.drawString(135.0, Y1(y_sig + 75.0), f": [Ttd Digital]")
    c.drawString(65.0, Y1(y_sig + 115.0), "………………………………….")
    
    c.drawString(330.0, Y1(y_sig + 45.0), "Nama")
    c.drawString(405.0, Y1(y_sig + 45.0), f": {DATA['penanggungjawab_nama']}")
    c.drawString(330.0, Y1(y_sig + 60.0), "Jabatan")
    c.drawString(405.0, Y1(y_sig + 60.0), f": {DATA['penanggungjawab_jabatan']}")
    c.drawString(330.0, Y1(y_sig + 75.0), "Tanda tangan")
    c.drawString(405.0, Y1(y_sig + 75.0), f": [Ttd Digital]")
    c.drawString(330.0, Y1(y_sig + 115.0), "………………………………….")

def render_page_2(c):
    # Page 2: Landscape (842 x 596)
    PW, PH = 842.0, 596.0
    Y2 = lambda top: PH - top
    
    # 1. Header image (Kop Surat Landscape)
    kop_file = os.path.join(ASSETS, "kop_surat.png")
    if os.path.exists(kop_file):
        c.drawImage(kop_file, 73.5, Y2(22.0 + 68.4), 480.0, 68.4, mask="auto")
        
    # 2. Title
    c.setFont("Lib-B", 12.0)
    c.setFillColor(black)
    c.drawCentredString(PW / 2, Y2(105.0), "DAFTAR HADIR")
    
    # 3. Subheader
    c.setFont("Lib", 9.0)
    c.drawString(62.1, Y2(125.0), f"Hari / tanggal :  {DATA['hari_tanggal']}")
    c.drawString(62.1, Y2(142.0), f"Tempat           :  {DATA['tuk_nama']}")
    
    # 4. Table Daftar Hadir Peserta
    cols = [62.1, 93.7, 225.2, 327.7, 525.7, 593.3, 674.3, 782.1]
    top_tbl = 160.0
    hdr_h = 24.0
    row_h = 18.0
    n_rows = max(10, len(DATA["peserta"]))
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
        
    # Headers
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((cols[0] + cols[1]) / 2, Y2(top_tbl + 16.0), "No")
    c.drawCentredString((cols[1] + cols[2]) / 2, Y2(top_tbl + 16.0), "Nama Peserta")
    c.drawCentredString((cols[2] + cols[3]) / 2, Y2(top_tbl + 16.0), "Institusi")
    c.drawCentredString((cols[3] + cols[4]) / 2, Y2(top_tbl + 16.0), "Alamat")
    c.drawCentredString((cols[4] + cols[5]) / 2, Y2(top_tbl + 16.0), "Pekerjaan")
    c.drawCentredString((cols[5] + cols[6]) / 2, Y2(top_tbl + 16.0), "No. Telp/HP")
    c.drawCentredString((cols[6] + cols[7]) / 2, Y2(top_tbl + 16.0), "Tanda Tangan")
    
    # Rows
    c.setFont("Lib", 8.0)
    for idx in range(n_rows):
        y_mid = top_tbl + hdr_h + idx * row_h + row_h / 2
        p_info = DATA["peserta"][idx] if idx < len(DATA["peserta"]) else None
        
        c.drawCentredString((cols[0] + cols[1]) / 2, Y2(y_mid + 3.0), str(idx + 1))
        if p_info:
            c.drawString(cols[1] + 5.0, Y2(y_mid + 3.0), p_info["nama"][:22])
            c.drawString(cols[2] + 5.0, Y2(y_mid + 3.0), p_info["institusi"][:18])
            c.drawString(cols[3] + 5.0, Y2(y_mid + 3.0), p_info["alamat"][:36])
            c.drawString(cols[4] + 5.0, Y2(y_mid + 3.0), p_info["pekerjaan"][:12])
            c.drawString(cols[5] + 5.0, Y2(y_mid + 3.0), p_info["telp"])
            
        # Tanda Tangan zigzag
        sig_num = str(idx + 1)
        if (idx + 1) % 2 != 0:
            c.drawString(cols[6] + 8.0, Y2(y_mid + 3.0), f"{sig_num}. [Ttd]")
        else:
            c.drawString((cols[6] + cols[7]) / 2 + 5.0, Y2(y_mid + 3.0), f"{sig_num}. [Ttd]")
            
    # 5. Signatures below
    y_sig2 = bot_tbl + 25.0
    c.setFont("Lib", 8.5)
    c.drawString(cols[0] + 30.0, Y2(y_sig2), f"{DATA['tuk_kota']}, {DATA['tanggal_dokumen']}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 16.0), "Asesor :")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 32.0), f"Nama             :  {DATA['asesor_nama']}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 48.0), f"No. Reg          :  {DATA['asesor_no_reg']}")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 64.0), f"Tanda tangan :  [Ttd Digital]")
    c.drawString(cols[0] + 30.0, Y2(y_sig2 + 104.0), "………………………………….")

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(596.0, 842.0))
    
    # Page 1: Portrait
    render_page_1(c)
    c.showPage()
    
    # Page 2: Landscape
    c.setPageSize((842.0, 596.0))
    render_page_2(c)
    c.showPage()
    
    c.save()
    print(f"OK -> Sukses generate Berita Acara & Daftar Hadir Peserta: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "Berita_Acara_Peserta_generated.pdf")
    render(out_file)
