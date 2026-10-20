#!/usr/bin/env python3
"""
Generator FR.APL.01 (Permohonan Sertifikasi Kompetensi) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; semua isi dinamis datang dari dict DATA.
Mendukung skema standar (9 unit) dan skema besar (hingga 18+ unit) dengan paginasi dinamis.
"""
import os, sys, re
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.colors import Color, black

BASE = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(BASE, ".."))
OUT = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/FR_APL_01_generated.pdf")
ASSETS = os.path.join(PROJECT_ROOT, "tests/Fixtures/muk/assets")

# Dimensi A4 pada 100 dpi: 827 x 1169 px -> Point: 595.56 x 842.52 pt
W, H = 595.56, 842.52
S = 0.72  # 1 px @100dpi = 0.72 pt
X = lambda px: px * S
Y = lambda py: H - py * S

# Register fonts dengan fallback deteksi lokasi OS (Arch/Debian/Ubuntu)
def find_font(filename):
    candidates = [
        "/usr/share/fonts/carlito",
        "/usr/share/fonts/liberation",
        "/usr/share/fonts/TTF",
        "/usr/share/fonts/truetype/crosextra",
        "/usr/share/fonts/truetype/liberation",
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
    "Cal": "Carlito-Regular.ttf",
    "Cal-B": "Carlito-Bold.ttf",
    "Cal-I": "Carlito-Italic.ttf",
    "Lib": "LiberationSans-Regular.ttf",
    "Lib-B": "LiberationSans-Bold.ttf",
    "DJ": "DejaVuSans.ttf",
    "DJC": "DejaVuSansCondensed.ttf",
    "DJC-B": "DejaVuSansCondensed-Bold.ttf",
}
for name, ffile in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile)))

sw = pdfmetrics.stringWidth
BLUE = Color(0.27, 0.45, 0.77)

def fit_size(s, font, width_px, ref=10.0):
    return ref * (width_px * S) / sw(s, font, ref)

BODY = fit_size("Pada bagian ini, cantumlan data pribadi, data pendidikan formal serta data pekerjaan anda pada saat", "Cal", 618)
UNIT = fit_size("G.45OTO01.001.2", "Lib", 121)

# DATA dummy representatif (fiktif, sesuai konteks Klaster 2)
DATA = {
    "skema_judul": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/I/2026",
    "standar": ["SKKNI Nomor 97", "Tahun 2018"],
    "units": [
        ("G.45OTO01.001.2", "Melaksanakan Keselamatan dan Kesehatan Kerja"),
        ("G.45OTO01.002.2", "Menggunakan Peralatan dan Perlengkapan Tempat Kerja"),
        ("G.45OTO01.007.2", "Membaca Gambar Teknik"),
        ("G.45OTO01.008.2", "Menggunakan Alat Ukur"),
        ("G.45OTO01.009.2", "Melaksanakan Diagnosis"),
        ("G.45OTO01.010.2", "Melakukan Perawatan Berkala Engine"),
        ("G.45OTO01.065.2", "Memelihara Baterai"),
        ("G.45OTO01.073.2", "Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan"),
        ("G.45OTO01.078.2", "Memperbaiki Sistem Manajemen Engine"),
    ],
    # Data Pribadi Asesi
    "nama_lengkap": "Ahmad Fauzi Ramadhan",
    "nik": "3275010203050004",
    "ttl": "Bekasi, 02 Maret 2005",
    "jenis_kelamin": "L",
    "kebangsaan": "Indonesia",
    "alamat_rumah": "Jl. Melati Raya No. 12, RT 03/RW 05, Kel. Kayuringin Jaya, Kec. Bekasi Selatan, Kota Bekasi",
    "kode_pos": "17144",
    "telp_rumah": "-",
    "telp_kantor_pribadi": "021-5550123",
    "hp": "0812-3456-7890",
    "email": "fauzi.r@example.com",
    "pendidikan": "SMK Teknik Kendaraan Ringan Otomotif",
    "instansi": "CV Maju Jaya Motor (dummy)",
    "jabatan": "Mekanik Junior",
    "alamat_kantor": "Jl. Raya Contoh No. 45, Kel. Contoh Baru, Kota Bekasi",
    "kode_pos_kantor": "17145",
    "telp_kantor": "021-5550199",
    "fax_kantor": "021-5550188",
    "kontak_kantor": "021-5550199 / 021-5550188 / hrd@majujayamotor.example",
    "tujuan": {"sertifikasi": True, "pkt": False, "rpl": False, "lainnya": False},
    "syarat_dasar": ["memenuhi", "tidak_ada", "tidak_ada", "tidak_ada"],
    "syarat_admin": ["memenuhi", "memenuhi"],
    "diterima": True,
    "catatan": "Berkas lengkap dan memenuhi persyaratan dasar.",
    "tgl_pemohon": "20 Oktober 2026",
    "nama_admin": "Rina Kartika",
    "tgl_admin": "20 Oktober 2026",
}

# --- Primitif Gambar ---
def hline(c, x1, x2, y, w=0.7):
    c.setLineWidth(w); c.line(X(x1), Y(y), X(x2), Y(y))

def vline(c, x, y1, y2, w=0.7):
    c.setLineWidth(w); c.line(X(x), Y(y1), X(x), Y(y2))

def text(c, s, x, yc, font="Cal", size=None, color=black, strike=False):
    size = size or BODY
    base = Y(yc) - size * 0.32
    c.setFillColor(color); c.setFont(font, size); c.drawString(X(x), base, s)
    wdt = sw(s, font, size)
    if strike:
        c.setStrokeColor(color); c.setLineWidth(0.6)
        c.line(X(x), base + size * 0.3, X(x) + wdt, base + size * 0.3)
    return X(x) + wdt

def ctext(c, s, xc, yc, font="Cal", size=None, color=black):
    size = size or BODY
    c.setFillColor(color); c.setFont(font, size)
    c.drawString(X(xc) - sw(s, font, size) / 2, Y(yc) - size * 0.32, s)

def heading(c, s, x, yc, width_px, font="Cal-B"):
    text(c, s, x, yc, font, fit_size(s, font, width_px))

def runs(c, parts, x, yc, size=None):
    size = size or BODY
    xp = X(x)
    for s, font, strike in parts:
        base = Y(yc) - size * 0.32
        c.setFont(font, size); c.setFillColor(black); c.drawString(xp, base, s)
        wdt = sw(s, font, size)
        if strike:
            c.setLineWidth(0.6); c.line(xp, base + size * 0.3, xp + wdt, base + size * 0.3)
        xp += wdt
    return xp

def justified(c, line, x, yc, width_px):
    words = line.split(); size = BODY
    gaps = len(words) - 1
    total = sum(sw(w, "Cal", size) for w in words)
    gap = (X(width_px) - total) / gaps if gaps > 0 else 0
    xp = X(x); base = Y(yc) - size * 0.32
    c.setFont("Cal", size); c.setFillColor(black)
    for w in words:
        c.drawString(xp, base, w); xp += sw(w, "Cal", size) + gap

def checkbox(c, checked, xc, yc, size=13):
    g = "\u2611" if checked else "\u2610"
    c.setFillColor(black); c.setFont("DJ", size)
    c.drawString(X(xc) - sw(g, "DJ", size) / 2, Y(yc) - size * 0.34, g)

def wrap_plain(s, font, size, maxw):
    lines, cur = [], ""
    for w in s.split():
        t = (cur + " " + w).strip()
        if sw(t, font, size) <= maxw: cur = t
        else: lines.append(cur); cur = w
    if cur: lines.append(cur)
    return lines

STYLE_FONT = {"n": "Cal", "i": "Cal-I", "a": "Lib"}
def rich_lines(template, maxw_px, judul):
    toks = []
    for part in re.split(r"(\{judul\}|\{atau\})", template):
        if part == "{judul}":
            toks += [(w, "i") for w in judul.split()]
        elif part == "{atau}":
            toks += [("atau", "a")]
        else:
            toks += [(w, "n") for w in part.split()]
    maxw, lines, cur, curw = X(maxw_px), [], [], 0
    space = sw(" ", "Cal", BODY)
    for w, st in toks:
        ww = sw(w, STYLE_FONT[st], BODY)
        add = ww if not cur else space + ww
        if cur and curw + add > maxw:
            lines.append(cur); cur, curw = [(w, st)], ww
        else:
            cur.append((w, st)); curw += add
    if cur: lines.append(cur)
    return lines

def draw_rich(c, lines, x, yc_first, step_px):
    for i, ln in enumerate(lines):
        xp = X(x); yc = yc_first + i * step_px; base = Y(yc) - BODY * 0.32
        for j, (w, st) in enumerate(ln):
            if j: xp += sw(" ", "Cal", BODY)
            c.setFont(STYLE_FONT[st], BODY); c.setFillColor(black); c.drawString(xp, base, w)
            xp += sw(w, STYLE_FONT[st], BODY)

def header(c, dy):
    top, bot = 18 + dy, 108 + dy
    c.setLineWidth(0.7); c.setStrokeColor(black)
    c.rect(X(126), Y(bot), X(762 - 126), (bot - top) * S)
    vline(c, 254, top, bot)
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, X(148.5), Y(20 + dy + 85.6), X(96.7), X(85.6), mask="auto")
    segs = [("L", 1), ("embaga ", 0), ("S", 1), ("ertifikasi ", 0), ("P", 1), ("rofesi ", 0),
            ("P", 1), ("raktisi ", 0), ("O", 1), ("tomotif  ", 0), ("LSP-PrO Indo", 1)]
    f = lambda b: "DJC-B" if b else "DJC"
    w10 = sum(sw(s, f(b), 10) for s, b in segs)
    size = 10 * (417 * S) / w10
    xp = X(293)
    for s, b in segs:
        c.setFont(f(b), size); c.setFillColor(black)
        c.drawString(xp, Y(70 + dy) - size * 0.32, s); xp += sw(s, f(b), size)

def watermark(c):
    # Dihapus sesuai permintaan user: tidak menggunakan watermark LSP
    pass

def pageno(c, n):
    ctext(c, str(n), 452, 1093, "Cal", 10, BLUE)

def colon(c, x, yc):
    text(c, ":", x - 2, yc, "Cal", BODY)

def table_header(c, top, yc, no_x, bukti_x, bukti_label):
    ctext(c, "No.", no_x, yc, "Cal-B"); ctext(c, bukti_label, bukti_x, yc, "Cal-B")
    ctext(c, "Ada", 575, top + 14, "Cal-B")
    ctext(c, "Memenuhi", 525, yc - 2, "Cal-B"); ctext(c, "Syarat", 525, yc + 18, "Cal-B")
    ctext(c, "Tidak", 618, yc - 9, "Cal-B"); ctext(c, "Memenuhi", 618, yc + 10, "Cal-B"); ctext(c, "Syarat", 618, yc + 29, "Cal-B")
    text(c, "Tidak Ada", 672, yc, "Cal-B")

def mark_cells(c, status, yc):
    checkbox(c, status == "memenuhi", 525, yc, 13)
    checkbox(c, status == "tidak_memenuhi", 618, yc, 13)
    if status == "tidak_ada":
        ctext(c, "\u2713", 717, yc, "DJ", 12)

# ================= HALAMAN 1 =================
def page1(c, d, pnum=1):
    watermark(c); header(c, 0)
    heading(c, "FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI", 127, 139, 363)
    heading(c, "Bagian 1 :  Rincian Data Pemohon Sertifikasi", 127, 180, 303)
    text(c, "Pada bagian ini, cantumlan data pribadi, data pendidikan formal serta data pekerjaan anda pada saat", 127, 207)
    text(c, "ini.", 127, 227)
    heading(c, "a.  Data Pribadi", 127, 252, 98)

    # --- a. Data Pribadi ---
    L, R, C1, C2 = 136, 776, 311, 350
    rows = [274, 303, 340, 378, 416, 453, 524, 556, 594, 629]
    hline(c, L, R, rows[0]); hline(c, L, R, rows[-1])
    for y in (303, 340, 378, 416, 453, 524, 594): hline(c, L, R, y)
    hline(c, C1, R, 556)
    for x in (L, C1, C2, R): vline(c, x, rows[0], rows[-1])

    lab = [("Nama lengkap", 290), ("No. KTP/NIK/Paspor", 326), ("Tempat / tgl. Lahir", 364),
           ("Jenis kelamin", 401), ("Kebangsaan", 438), ("Alamat rumah", 489),
           ("No. Telepon/E-mail", 547), ("Kualifikasi Pendidikan", 617)]
    for s, yc in lab: text(c, s, 149, yc)
    for yc in (284, 321, 357, 395, 432, 536, 573, 611): colon(c, 331, yc)

    text(c, d["nama_lengkap"], 356, 290)
    text(c, d["nik"], 356, 326)
    text(c, d["ttl"], 356, 364)
    jk = d["jenis_kelamin"]
    runs(c, [("Laki-laki", "Cal", jk != "L"), (" / ", "Cal", False), ("Wanita", "Cal", jk != "P"), (" *)", "Cal", False)], 356, 401)
    text(c, d["kebangsaan"], 356, 438)
    for i, ln in enumerate(wrap_plain(d["alamat_rumah"], "Cal", BODY, X(410))):
        text(c, ln, 356, 475 + i * 17)
    xe = text(c, "Kode pos :", 549, 514); text(c, " " + d["kode_pos"], 549 + (xe - X(549)) / S, 514)
    for lbl, val, x, yc in (("Rumah :", d["telp_rumah"], 351, 539), ("Kantor :", d["telp_kantor_pribadi"], 549, 539),
                            ("HP :", d["hp"], 351, 574), ("E-mail :", d["email"], 555, 574)):
        xe = text(c, lbl, x, yc); text(c, " " + val, x + (xe - X(x)) / S, yc)
    text(c, d["pendidikan"], 356, 617)
    text(c, "*Coret yang tidak perlu", 138, 647)

    # --- b. Data Pekerjaan ---
    heading(c, "b.  Data Pekerjaan Sekarang", 127, 684, 178)
    L, R, C1, C2 = 136, 776, 304, 330
    top, bottom = 711, 926
    hline(c, L, R, top); hline(c, L, R, bottom)
    for y in (746, 781, 857, 900): hline(c, L, R, y)
    for x in (L, C1, C2, R): vline(c, x, top, bottom)
    text(c, "Nama Institusi /", 151, 721); text(c, "Perusahaan", 151, 736)
    text(c, "Jabatan", 151, 761); text(c, "Alamat Kantor", 151, 797); text(c, "No. Telp/Fax/E-mail", 151, 913)
    for yc in (720, 761, 797, 913): colon(c, 317, yc)
    text(c, d["instansi"], 336, 728)
    text(c, d["jabatan"], 336, 761)
    for i, ln in enumerate(wrap_plain(d["alamat_kantor"], "Cal", BODY, X(430))):
        text(c, ln, 336, 797 + i * 17)
    xe = text(c, "Kode pos :", 561, 847); text(c, " " + d["kode_pos_kantor"], 561 + (xe - X(561)) / S, 847)
    xe = text(c, "Telp   :", 563, 869); text(c, " " + d["telp_kantor"], 563 + (xe - X(563)) / S, 869)
    xe = text(c, "Fax    :", 569, 889); text(c, " " + d["fax_kantor"], 569 + (xe - X(569)) / S, 889)
    text(c, d["kontak_kantor"], 336, 913)
    pageno(c, pnum)

# ================= HALAMAN 2 =================
def page2(c, d, pnum=2):
    watermark(c); header(c, 21)
    heading(c, "Bagian  2 :  Data Sertifikasi", 128, 160, 181)
    justified(c, "Tuliskan Judul dan Nomor Skema Sertifikasi yang anda ajukan berikut Daftar Unit Kompetensi sesuai", 128, 188, 622)
    justified(c, "kemasan pada skema sertifikasi untuk mendapatkan pengakuan sesuai dengan latar belakang", 128, 207, 622)
    text(c, "pendidikan, pelatihan serta pengalaman kerja yang anda miliki.", 128, 226)

    # --- tabel skema ---
    L, R = 128, 768; THICK = 1.5
    hline(c, L, R, 236, THICK); hline(c, L, R, 428, THICK)
    vline(c, L, 236, 428, THICK); vline(c, R, 236, 428, THICK)
    hline(c, 313, R, 264); hline(c, L, R, 292)
    vline(c, 313, 236, 292); vline(c, 383, 236, 428); vline(c, 411, 236, 428)
    for y in (325, 359, 392): hline(c, 383, R, y)
    text(c, "Skema Sertifikasi", 151, 254)
    runs(c, [("(", "Cal", False), ("KKNI/Okupasi", "Cal", True), ("/Klaster)", "Cal", False)], 143, 275)
    text(c, "Judul", 327, 247); text(c, "Nomor", 327, 278)
    colon(c, 396, 247); colon(c, 396, 278)
    text(c, d["skema_judul"], 418, 247, "Cal-I"); text(c, d["skema_nomor"], 418, 278)
    text(c, "Tujuan Asesmen", 151, 303); colon(c, 397, 297)
    t = d["tujuan"]
    for key, lbl, bx, yc in (("sertifikasi", "Sertifikasi", 424, 312),
                             ("pkt", "Pengakuan Kompetensi Terkini (PKT)", 426, 345),
                             ("rpl", "Rekognisi Pembelajaran Lampau (RPL)", 426, 376),
                             ("lainnya", "Lainnya", 426, 411)):
        checkbox(c, t[key], bx, yc, 14); text(c, lbl, 446, yc + 1)

    # --- tabel unit ---
    heading(c, "Daftar Unit Kompetensi sesuai kemasan:", 128, 453, 254)
    L, R = 128, 771
    hline(c, L, R, 471, THICK); hline(c, L, R, 523)
    for x in (176, 313, 640): vline(c, x, 471, 760)
    ctext(c, "No.", 152, 497, "Cal-B"); ctext(c, "Kode Unit", 245, 497, "Cal-B"); ctext(c, "Judul Unit", 477, 497, "Cal-B")
    ctext(c, "Standar", 706, 481, "Cal-B"); ctext(c, "Kompetensi Kerja", 706, 499, "Cal-B")
    y = 523
    maxw = X(316)
    for i, (kode, judul) in enumerate(d["units"], 1):
        ln = wrap_plain(judul, "Lib", UNIT, maxw)
        h = 20.6 if len(ln) == 1 else 37.5
        yc = y + h / 2
        hline(c, L, 640, y + h, 0.7)
        ctext(c, f"{i}.", 152, yc)
        ctext(c, kode, 245, yc, "Lib", UNIT)
        for k, l in enumerate(ln):
            text(c, l, 318, yc + (k - (len(ln) - 1) / 2) * 17, "Lib", UNIT)
        y += h
    
    hline(c, L, R, y, THICK)
    vline(c, L, 471, y, THICK); vline(c, R, 471, y, THICK)
    mid = (523 + y) / 2
    ctext(c, d["standar"][0], 705, mid - 9); ctext(c, d["standar"][1], 705, mid + 9)

    # --- 3.1 Bukti Persyaratan Dasar (Item 1 & 2) ---
    heading(c, "Bagian  3 :  Bukti Kelengkapan Pemohon", 128, 790, 279)
    heading(c, "3.1 Bukti Persyaratan Dasar Pemohon", 128, 810, 260)
    L, R = 130, 767
    hline(c, L, R, 842, THICK)
    for x in (L, R): vline(c, x, 842, 1098, THICK)
    for x in (171, 482, 668): vline(c, x, 842, 1098)
    vline(c, 569, 869, 1098); hline(c, 482, 668, 869); hline(c, L, R, 928, 1.2); hline(c, L, R, 966, 0.9)
    hline(c, L, R, 1098, THICK)
    table_header(c, 842, 890, 158, 334, "Bukti Persyaratan Dasar")
    ctext(c, "1.", 158, 953)
    xe = text(c, "Fotocopy Ijazah SMK otomotif;", 176, 952)
    text(c, " atau", 176 + (xe - X(176)) / S, 952, "Lib", UNIT)
    mark_cells(c, d["syarat_dasar"][0], 951)
    tmpl2 = "Surat Keterangan Peserta didik SMK Jurusan Otomotif yang telah menyelesaikan mata pelajaran bidang {judul} sesuai unit kompetensi skema dengan dibuktikan nilai raport semester 1-4 dan fotocopy Surat Keterangan/Sertifikat mengikuti prakerin; {atau}"
    ln = rich_lines(tmpl2, 298, d["skema_judul"])
    draw_rich(c, ln, 176, 975.5, 18.6)
    ctext(c, "2.", 158, 1013); mark_cells(c, d["syarat_dasar"][1], 1013)
    pageno(c, pnum)

# ================= HALAMAN 3 =================
def page3(c, d, pnum=3):
    watermark(c); header(c, 21)
    THICK = 1.5; L, R = 130, 767
    # Lanjutan 3.1 (Item 3 & 4)
    hline(c, L, R, 148, THICK); hline(c, L, R, 303, THICK); hline(c, L, R, 247, 0.9)
    for x in (L, R): vline(c, x, 148, 303, THICK)
    for x in (171, 481, 569, 668): vline(c, x, 148, 303)
    t3 = "Fotocopy Ijazah SMA sederajat dan fotocopy sertifikat pelatihan berbasis kompetensi {judul} dari lembaga pelatihan kerja yang Terakreditasi/Kredibel; {atau}"
    draw_rich(c, rich_lines(t3, 298, d["skema_judul"]), 176, 159.5, 18.2)
    ctext(c, "3.", 152, 188); mark_cells(c, d["syarat_dasar"][2], 188)
    t4 = "Surat keterangan pengalaman kerja sesuai bidang {judul} minimal 1 tahun"
    draw_rich(c, rich_lines(t4, 298, d["skema_judul"]), 176, 255, 18.4)
    ctext(c, "4.", 152, 268); mark_cells(c, d["syarat_dasar"][3], 268)

    # 3.2 Bukti Administratif
    heading(c, "3.2 Bukti Administratif", 128, 334, 148)
    hline(c, L, R, 366, THICK); hline(c, L, R, 547, THICK); hline(c, 482, 668, 392)
    hline(c, L, R, 452, 1.2); hline(c, L, R, 499, 0.9)
    for x in (L, R): vline(c, x, 366, 547, THICK)
    for x in (172, 482, 668): vline(c, x, 366, 547)
    vline(c, 569, 392, 547)
    table_header(c, 366, 413, 151, 327, "Bukti Administratif")
    ctext(c, "1.", 151, 476); text(c, "Fotocopy Kartu Tanda Penduduk (KTP),", 180, 476, "Lib", UNIT + 0.5)
    mark_cells(c, d["syarat_admin"][0], 474)
    ctext(c, "2.", 151, 523)
    text(c, "Pas photo ukuran 3x4 sebanyak 2 lembar", 180, 515, "Lib", UNIT + 0.5)
    text(c, "dengan background warna merah.", 180, 532, "Lib", UNIT + 0.5)
    mark_cells(c, d["syarat_admin"][1], 523)

    # Rekomendasi (diisi oleh LSP)
    L, R = 126, 767
    hline(c, L, R, 572, THICK); hline(c, L, R, 833, THICK)
    vline(c, L, 572, 833, THICK); vline(c, R, 572, 833, THICK); vline(c, 475, 572, 833, THICK)
    hline(c, L, R, 705, THICK)
    for y in (592, 628, 722, 760): hline(c, 475, R, y)
    vline(c, 593, 592, 705); vline(c, 593, 722, 833)
    heading(c, "Rekomendasi (diisi oleh LSP):", 136, 581, 199)
    text(c, "Berdasarkan ketentuan persyaratan dasar,", 136, 601)
    text(c, "makapemohon:", 136, 620)
    ok = d["diterima"]
    runs(c, [("Diterima", "Cal-B", not ok), ("/ ", "Cal-B", False), ("Tidak diterima", "Cal-B", ok),
             (" *)", "Cal", False), (" sebagai peserta sertifikasi", "Cal", False)], 136, 640)
    text(c, "* coret yang tidak sesuai", 136, 693)
    text(c, "Catatan :", 136, 713, "Cal-B")
    for i, l in enumerate(wrap_plain(d["catatan"], "Cal", BODY, X(325))):
        text(c, l, 136, 740 + i * 18)
    text(c, "Pemohon/ Kandidat :", 484, 581, "Cal-B")
    text(c, "Nama", 484, 607); text(c, d["nama_lengkap"], 600, 607, "Cal", BODY - 0.5)
    text(c, "Tanda tangan/", 484, 650); text(c, "Tanggal", 484, 669); text(c, d["tgl_pemohon"], 600, 669)
    text(c, "Admin LSP  :", 484, 713, "Cal-B")
    text(c, "Nama :", 484, 741); text(c, d["nama_admin"], 600, 741)
    text(c, "Tanda tangan/", 484, 779); text(c, "Tanggal", 484, 798); text(c, d["tgl_admin"], 600, 798)
    pageno(c, pnum)

# --- Generator Utama dengan Flow Kontrol ---
def build(d=DATA, out=OUT):
    os.makedirs(os.path.dirname(out), exist_ok=True)
    c = canvas.Canvas(out, pagesize=(W, H))
    c.setTitle("FR.APL.01 Permohonan Sertifikasi Kompetensi (data dummy)")
    c.setAuthor("LSP PrO Indo - ReportLab Generator")

    page1(c, d, pnum=1)
    c.showPage()
    page2(c, d, pnum=2)
    c.showPage()
    page3(c, d, pnum=3)
    c.showPage()

    c.save()
    print(f"OK -> Sukses generate: {out} | BODY={BODY:.2f}pt UNIT={UNIT:.2f}pt")

if __name__ == "__main__":
    build()
