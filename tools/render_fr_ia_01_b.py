#!/usr/bin/env python3
"""
Generator FR.IA.01.B (Report Sheet Praktik) - LSP PrO Indo
Layout meniru template aslinya secara presisi piksel; tanpa watermark sesuai instruksi.
Paginasi persis 3 halaman sesuai master dokumen BNSP.
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
    "DJ": "DejaVuSans.ttf",
}
for name, ffile in font_map.items():
    pdfmetrics.registerFont(TTFont(name, find_font(ffile)))

PAGE_W, PAGE_H = 595.3, 841.9
Y = lambda top: PAGE_H - top

DATA = {
    "skema_nama": "Maintenance Gasoline Engine Injection System",
    "skema_nomor": "02/LSP PrO Indo/XI/2025",
    "tuk_tipe": "Sewaktu/Tempat Kerja/Mandiri*",
    "asesor_nama": "Drs. Budi Santoso, M.T.",
    "asesi_nama": "Wahyu Andika Rahadi",
    "tanggal": "19 Oktober 2026",
    "durasi": "120 Menit",
    "catatan": "Asesi telah melakukan seluruh rangkaian pemeriksaan umum, perawatan berkala sistem EFI, pemeliharaan baterai, dan diagnosis scan tool sesuai SOP keselamatan kerja dan standar workshop manual.",
    "tanggal_ttd": "19 Oktober 2026",
}

def draw_header_box(c, x0, x1, top, bot):
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(x0, Y(bot), x1 - x0, bot - top)
    x_div = 147.7
    c.line(x_div, Y(top), x_div, Y(bot))
    logo_file = os.path.join(ASSETS, "logo.png")
    if os.path.exists(logo_file):
        c.drawImage(logo_file, x0 + 16.0, Y(bot) + 4.0, 50.0, 36.0, mask="auto")
    c.setFont("Lib-B", 11.5)
    c.setFillColor(black)
    c.drawString(x_div + 18.0, Y(bot) + 18.0, "Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo")

def draw_cb(c, cx, cy, checked):
    sz = 8.5
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(cx - sz / 2, cy - sz / 2, sz, sz)
    if checked:
        c.setFont("DJ", 8.5)
        c.setFillColor(black)
        c.drawCentredString(cx, cy - sz / 2 + 1.0, "✓")

def render(dest_path):
    c = canvas.Canvas(dest_path, pagesize=(PAGE_W, PAGE_H))
    
    # ================= PAGE 1 =================
    # Title
    c.setFont("Lib-B", 12)
    c.drawString(72.1, Y(55.0), "FR.IA.01B. REPORT SHEET")
    
    # Table 1: Metadata (top=88.2 to 259.5)
    L, R = 72.1, 560.9
    c1, c2, c3 = 191.7, 244.8, 259.2
    
    # Rows: 88.2, 111.9, 135.1, 173.8, 195.3, 216.4, 237.7, 259.5
    r_y = [88.2, 111.9, 135.1, 173.8, 195.3, 216.4, 237.7, 259.5]
    c.setLineWidth(0.7)
    c.setStrokeColor(black)
    c.rect(L, Y(r_y[-1]), R - L, r_y[-1] - r_y[0])
    
    for y in r_y[1:-1]:
        c.line(L, Y(y), R, Y(y))
        
    c.line(c1, Y(r_y[2]), c1, Y(r_y[0]))
    c.line(c2, Y(r_y[-1]), c2, Y(r_y[0]))
    c.line(c3, Y(r_y[-1]), c3, Y(r_y[0]))
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(105.0), "Skema Sertifikasi")
    c.drawString(L + 6.0, Y(118.0), "(KKNI/Okupasi/Klaster)")
    c.drawString(c1 + 6.0, Y(103.0), "Judul")
    c.drawString(c2 + 4.0, Y(103.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(103.0), DATA["skema_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(c1 + 6.0, Y(126.0), "Nomor")
    c.drawString(c2 + 4.0, Y(126.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(126.0), DATA["skema_nomor"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(158.0), "TUK")
    c.drawString(c2 + 4.0, Y(158.0), ":")
    c.drawString(c3 + 6.0, Y(158.0), DATA["tuk_tipe"])
    
    c.drawString(L + 6.0, Y(187.0), "Nama Asesor")
    c.drawString(c2 + 4.0, Y(187.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(187.0), DATA["asesor_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(208.0), "Nama Asesi")
    c.drawString(c2 + 4.0, Y(208.0), ":")
    c.setFont("Lib-B", 8.5)
    c.drawString(c3 + 6.0, Y(208.0), DATA["asesi_nama"])
    
    c.setFont("Lib", 8.5)
    c.drawString(L + 6.0, Y(229.0), "Tanggal")
    c.drawString(c2 + 4.0, Y(229.0), ":")
    c.drawString(c3 + 6.0, Y(229.0), DATA["tanggal"])
    
    c.drawString(L + 6.0, Y(251.0), "Durasi Waktu")
    c.drawString(c2 + 4.0, Y(251.0), ":")
    c.drawString(c3 + 6.0, Y(251.0), DATA["durasi"])
    
    # Table 2: Report Sheet Items (top=273.3 to 774.6)
    L2, R2 = 72.1, 566.4
    c_no = 92.0
    c_unit = 240.0
    c_item = 485.0
    c_mid_hasil = (c_item + R2) / 2
    
    r_items = [273.3, 286.7, 312.7, 376.5, 440.4, 504.3, 542.9, 632.0, 683.2, 774.6]
    c.rect(L2, Y(r_items[-1]), R2 - L2, r_items[-1] - r_items[0])
    
    for y in r_items[1:-1]:
        c.line(L2, Y(y), R2, Y(y))
        
    c.line(c_no, Y(r_items[-1]), c_no, Y(r_items[0]))
    c.line(c_unit, Y(r_items[-1]), c_unit, Y(r_items[0]))
    c.line(c_item, Y(r_items[-1]), c_item, Y(r_items[0]))
    c.line(c_mid_hasil, Y(r_items[-1]), c_mid_hasil, Y(r_items[1]))
    
    # Header
    c.setFont("Lib-B", 8.5)
    c.drawCentredString((L2 + c_no) / 2, Y(295.0), "No")
    c.drawCentredString((c_no + c_unit) / 2, Y(295.0), "Kode & Judul Unit")
    c.drawCentredString((c_unit + c_item) / 2, Y(295.0), "Item dan Hasil Pemeriksaan")
    c.drawCentredString((c_item + R2) / 2, Y(282.0), "Hasil")
    c.setFont("Lib", 8)
    c.drawCentredString((c_item + c_mid_hasil) / 2, Y(302.0), "Baik")
    c.drawCentredString((c_mid_hasil + R2) / 2, Y(302.0), "Tdk Baik")
    
    # Unit 1
    c.setFont("Lib", 8)
    c.drawCentredString((L2 + c_no) / 2, Y(345.0), "1.")
    c.drawString(c_no + 5.0, Y(330.0), "G.45OTO01.001.2")
    c.drawString(c_no + 5.0, Y(342.0), "Melaksanakan Keselamatan")
    c.drawString(c_no + 5.0, Y(354.0), "dan Kesehatan Kerja")
    c.drawString(c_unit + 6.0, Y(330.0), "Persiapan :")
    c.drawString(c_unit + 12.0, Y(342.0), "• APD Sesuai Prosedur")
    c.drawString(c_unit + 12.0, Y(354.0), "• Area Kerja & Pelindung Kendaraan")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(345.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(345.0), False)
    
    # Unit 2
    c.drawCentredString((L2 + c_no) / 2, Y(408.0), "2.")
    c.drawString(c_no + 5.0, Y(395.0), "G.45OTO01.002.2")
    c.drawString(c_no + 5.0, Y(407.0), "Menggunakan Peralatan dan")
    c.drawString(c_no + 5.0, Y(419.0), "Perlengkapan Tempat Kerja")
    c.drawString(c_unit + 6.0, Y(395.0), "Persiapan :")
    c.drawString(c_unit + 12.0, Y(407.0), "• Pengecekan Tools & Equipment")
    c.drawString(c_unit + 12.0, Y(419.0), "• Kalibrasi Peralatan & Kebersihan")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(408.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(408.0), False)
    
    # Unit 3
    c.drawCentredString((L2 + c_no) / 2, Y(472.0), "3.")
    c.drawString(c_no + 5.0, Y(460.0), "G.45OTO01.007.2")
    c.drawString(c_no + 5.0, Y(472.0), "Membaca Gambar Teknik")
    c.drawString(c_unit + 6.0, Y(455.0), "Lihat Buku Manual (Workshop Manual):")
    c.drawString(c_unit + 12.0, Y(468.0), "• Tahanan Injektor: 12.8 ohm")
    c.drawString(c_unit + 12.0, Y(481.0), "• Baca Wiring Harness Pompa Bensin")
    c.drawString(c_unit + 12.0, Y(494.0), "• Letak Relay Fuel Pump: Terpasang Baik")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(472.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(472.0), False)
    
    # Unit 4
    c.drawCentredString((L2 + c_no) / 2, Y(523.0), "4.")
    c.drawString(c_no + 5.0, Y(517.0), "G.45OTO01.008.2")
    c.drawString(c_no + 5.0, Y(529.0), "Menggunakan Alat Ukur")
    c.drawString(c_unit + 6.0, Y(517.0), "• Penempatan Tools & Equipment Benar")
    c.drawString(c_unit + 6.0, Y(529.0), "• Gunakan Alat Ukur Sesuai SOP")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(523.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(523.0), False)
    
    # Unit 5
    c.drawCentredString((L2 + c_no) / 2, Y(587.0), "5.")
    c.drawString(c_no + 5.0, Y(575.0), "G.45OTO01.065.2")
    c.drawString(c_no + 5.0, Y(587.0), "Memelihara Baterai")
    c.drawString(c_unit + 6.0, Y(560.0), "Periksa Kondisi Baterai:")
    c.drawString(c_unit + 12.0, Y(573.0), "• Tegangan: 12.6 Volt | Kesehatan: 98%")
    c.drawString(c_unit + 12.0, Y(586.0), "• Kondisi Terminal & Bodi: Bersih/Baik")
    c.drawString(c_unit + 12.0, Y(599.0), "• Berat Jenis: 1.26 | Level Air: Cukup")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(587.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(587.0), False)
    
    # Unit 6
    c.drawCentredString((L2 + c_no) / 2, Y(657.0), "6.")
    c.drawString(c_no + 5.0, Y(648.0), "G.45OTO01.073.2")
    c.drawString(c_no + 5.0, Y(660.0), "Memperbaiki Sistem Pengaman")
    c.drawString(c_unit + 6.0, Y(648.0), "• Periksa Fuse EFI: Baik (Kontinu)")
    c.drawString(c_unit + 6.0, Y(661.0), "• Lampu Indikator Dashboard: Normal")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(657.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(657.0), False)
    
    # Unit 7
    c.drawCentredString((L2 + c_no) / 2, Y(728.0), "7.")
    c.drawString(c_no + 5.0, Y(718.0), "G.45OTO01.009.2")
    c.drawString(c_no + 5.0, Y(730.0), "Melaksanakan Diagnosis")
    c.drawString(c_unit + 6.0, Y(700.0), "Menggunakan Scan Tool:")
    c.drawString(c_unit + 12.0, Y(713.0), "• Pasang Konektor OBD-II: Terkoneksi")
    c.drawString(c_unit + 12.0, Y(726.0), "• DTC: No Fault Code Detected (Clean)")
    c.drawString(c_unit + 12.0, Y(739.0), "• Water Temp: 85°C | Intake Temp: 32°C")
    c.drawString(c_unit + 12.0, Y(752.0), "• Ignition Timing: 10° BTDC")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(728.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(728.0), False)
    
    c.showPage()
    
    # ================= PAGE 2 =================
    draw_header_box(c, 72.2, 558.0, 14.4, 59.9)
    
    # Page 2 Table (top=71.9 to 776.0)
    p2_r = [71.9, 125.4, 649.0, 776.0]
    c.rect(L2, Y(p2_r[-1]), R2 - L2, p2_r[-1] - p2_r[0])
    for y in p2_r[1:-1]:
        c.line(L2, Y(y), R2, Y(y))
        
    c.line(c_no, Y(p2_r[-1]), c_no, Y(p2_r[0]))
    c.line(c_unit, Y(p2_r[-1]), c_unit, Y(p2_r[0]))
    c.line(c_item, Y(p2_r[-1]), c_item, Y(p2_r[0]))
    c.line(c_mid_hasil, Y(p2_r[-1]), c_mid_hasil, Y(p2_r[0]))
    
    # Unit 7 tail (71.9 to 125.4)
    c.setFont("Lib", 8)
    c.drawString(c_unit + 6.0, Y(90.0), "• Putaran Mesin / Idle Speed: 750 RPM")
    c.drawString(c_unit + 6.0, Y(103.0), "• Actuation Test (Cooling Fan): ON / Berfungsi")
    c.drawString(c_unit + 6.0, Y(116.0), "• Cylinder Fuel Cut: Berfungsi Sesuai SOP")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(98.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(98.0), False)
    
    # Unit 8 (125.4 to 649.0)
    c.drawCentredString((L2 + c_no) / 2, Y(387.0), "8.")
    c.drawString(c_no + 5.0, Y(360.0), "G.45OTO01.010.2")
    c.drawString(c_no + 5.0, Y(372.0), "Melakukan Perawatan Berkala")
    c.drawString(c_no + 5.0, Y(384.0), "Engine")
    
    # Items inside Unit 8
    u8_y = 142.0
    c.drawString(c_unit + 6.0, Y(u8_y), "Sistem Pelumasan:")
    c.drawString(c_unit + 12.0, Y(u8_y + 13.0), "• Oil Level: Antara F dan L | Kualitas: Baik | Kebocoran: Tidak Ada")
    
    u8_y += 35.0
    c.drawString(c_unit + 6.0, Y(u8_y), "Sistem Pengapian:")
    c.drawString(c_unit + 12.0, Y(u8_y + 13.0), "• Celah Busi Silinder No.1-4: 1.1 mm (Kondisi Baik)")
    c.drawString(c_unit + 12.0, Y(u8_y + 26.0), "• Tahanan Primer Coil: 0.7 Ω | Sekunder: 12.4 KΩ")
    
    u8_y += 48.0
    c.drawString(c_unit + 6.0, Y(u8_y), "Sistem Bahan Bakar:")
    c.drawString(c_unit + 12.0, Y(u8_y + 13.0), "• Saringan Udara: Dibersihkan | Saluran Bensin: Aman")
    
    u8_y += 35.0
    c.drawString(c_unit + 6.0, Y(u8_y), "Sistem Pendingin:")
    c.drawString(c_unit + 12.0, Y(u8_y + 13.0), "• Air Radiator: Full | Radiator & Hose: Tidak Bocor")
    c.drawString(c_unit + 12.0, Y(u8_y + 26.0), "• Tekanan Tutup Radiator: 0.9 bar | Fan Belt: 10 mm Defleksi")
    
    u8_y += 48.0
    c.drawString(c_unit + 6.0, Y(u8_y), "Sistem Pengisian:")
    c.drawString(c_unit + 12.0, Y(u8_y + 13.0), "• Lampu Indikator Baterai: Mati Saat Mesin Hidup (Normal)")
    c.drawString(c_unit + 12.0, Y(u8_y + 26.0), "• Tegangan Pengisian: 14.2 Volt | Voltage Drop: 0.1 Volt")
    
    u8_y += 48.0
    c.drawString(c_unit + 6.0, Y(u8_y), "Exhaust Emission Test (Idling Speed):")
    c.drawString(c_unit + 12.0, Y(u8_y + 13.0), "• CO: 0.05% | CO2: 14.8% | Lambda: 1.002 | HC: 45 ppm")
    
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(387.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(387.0), False)
    
    # Unit 9 (649.0 to 776.0)
    c.drawCentredString((L2 + c_no) / 2, Y(712.0), "9.")
    c.drawString(c_no + 5.0, Y(700.0), "G.45OTO01.078.2")
    c.drawString(c_no + 5.0, Y(712.0), "Memperbaiki Sistem")
    c.drawString(c_no + 5.0, Y(724.0), "Manajemen Engine")
    
    c.drawString(c_unit + 6.0, Y(670.0), "Mencari Gangguan / Jumper:")
    c.drawString(c_unit + 12.0, Y(683.0), "• Terminal Jumper: TE1 dan E1 (Normal)")
    c.drawString(c_unit + 12.0, Y(696.0), "• Problem Terjadi: Sensor IAT Socket Longgar")
    c.drawString(c_unit + 12.0, Y(709.0), "• Perbaikan: Pengencangan Konektor & Reset ECU")
    c.drawString(c_unit + 12.0, Y(722.0), "• Hapus Memori Problem: Berhasil / Check Engine Mati")
    draw_cb(c, (c_item + c_mid_hasil) / 2, Y(712.0), True)
    draw_cb(c, (c_mid_hasil + R2) / 2, Y(712.0), False)
    
    c.showPage()
    
    # ================= PAGE 3 =================
    draw_header_box(c, 72.2, 558.0, 14.4, 59.9)
    
    # Page 3 Table: Catatan & Tanda Tangan (top=84.4 to 304.6)
    L3, R3 = 72.2, 566.7
    p3_r = [84.4, 205.9, 255.2, 304.6]
    c_split_sig = 320.0
    
    c.rect(L3, Y(p3_r[-1]), R3 - L3, p3_r[-1] - p3_r[0])
    for y in p3_r[1:-1]:
        c.line(L3, Y(y), R3, Y(y))
        
    c.line(c_split_sig, Y(p3_r[-1]), c_split_sig, Y(p3_r[1]))
    
    # r0: Catatan
    c.setFont("Lib-B", 8.5)
    c.drawString(L3 + 8.0, Y(102.0), "Catatan Hasil Pemeriksaan :")
    c.setFont("Lib", 8.5)
    c.drawString(L3 + 8.0, Y(122.0), DATA["catatan"][:85])
    c.drawString(L3 + 8.0, Y(136.0), DATA["catatan"][85:175])
    c.drawString(L3 + 8.0, Y(150.0), DATA["catatan"][175:])
    
    # r1: Asesi
    c.drawString(L3 + 8.0, Y(230.0), f"Nama Asesi : {DATA['asesi_nama']}")
    c.drawString(c_split_sig + 8.0, Y(230.0), f"Ttd & Tanggal : [Ttd Digital] - {DATA['tanggal_ttd']}")
    
    # r2: Asesor
    c.drawString(L3 + 8.0, Y(280.0), f"Nama Asesor : {DATA['asesor_nama']}")
    c.drawString(c_split_sig + 8.0, Y(280.0), f"Ttd & Tanggal : [Ttd Digital] - {DATA['tanggal_ttd']}")
    
    c.showPage()
    c.save()
    print(f"OK -> Sukses generate FR.IA.01.B: {dest_path}")

if __name__ == "__main__":
    out_dir = os.path.join(os.path.dirname(__file__), "..", "tests", "Fixtures", "muk")
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "FR_IA_01_B_generated.pdf")
    render(out_file)
