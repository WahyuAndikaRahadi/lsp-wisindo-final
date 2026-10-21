#!/usr/bin/env python3
"""
Skrip Verifikasi Dokumen MUK:
1. Memeriksa kecocokan jumlah halaman (page count)
2. Memeriksa persentase perbedaan piksel visual (pixel difference ratio) per halaman (resolusi 100 dpi)
3. Memeriksa ekstraksi teks esensial (text extraction via pdftotext)
"""
import sys, os, glob, subprocess
from PIL import Image, ImageChops

def run_diff(ref_pdf, gen_pdf):
    print(f"=== VERIFIKASI VISUAL DOKUMEN MUK ===")
    print(f"Referensi: {ref_pdf}")
    print(f"Hasil Gen: {gen_pdf}")

    tmp_dir = "/tmp/muk_verify"
    os.makedirs(tmp_dir, exist_ok=True)
    for f in glob.glob(f"{tmp_dir}/*"):
        try: os.remove(f)
        except: pass

    # 1. Render kedua PDF ke PNG @ 100 dpi
    subprocess.run(["pdftoppm", "-png", "-r", "100", ref_pdf, f"{tmp_dir}/ref"], check=True)
    subprocess.run(["pdftoppm", "-png", "-r", "100", gen_pdf, f"{tmp_dir}/gen"], check=True)

    refs = sorted(glob.glob(f"{tmp_dir}/ref-*.png"))
    gens = sorted(glob.glob(f"{tmp_dir}/gen-*.png"))

    print(f"\n1. Pemeriksaan Jumlah Halaman:")
    print(f"   Referensi : {len(refs)} halaman")
    print(f"   Hasil Gen : {len(gens)} halaman")
    page_match = len(refs) == len(gens)
    print(f"   Status    : {'SESUAI (PASS)' if page_match else 'BEDA (FAIL)'}")

    # 2. Perbedaan Visual Piksel
    def calc_ratio(im_a, im_b):
        # Resize jika ada selisih subpiksel
        if im_a.size != im_b.size:
            im_b = im_b.resize(im_a.size)
        d = ImageChops.difference(im_a.convert("L"), im_b.convert("L")).point(lambda v: 255 if v > 40 else 0)
        hist = d.histogram()
        diff_px = sum(hist[255:])
        total_px = im_a.width * im_a.height
        return diff_px / total_px

    print(f"\n2. Pemeriksaan Rasio Selisih Piksel (Toleransi < 3.5% akibat data dummy):")
    min_pages = min(len(refs), len(gens))
    for i in range(min_pages):
        im_r = Image.open(refs[i])
        im_g = Image.open(gens[i])
        r = calc_ratio(im_r, im_g)
        status = "OK (Lolos)" if r < 0.035 else "PERIKSA (Deviasi Data/Layout)"
        print(f"   Halaman {i+1}: Selisih {r:.2%} -> {status}")

    # 3. Pemeriksaan Keberadaan Teks Kunci via pdftotext
    print(f"\n3. Pemeriksaan Teks Esensial (pdftotext):")
    res = subprocess.run(["pdftotext", gen_pdf, "-"], capture_output=True, text=True)
    gen_text = res.stdout

    gen_lower = gen_pdf.lower()
    if "ia_01_b" in gen_lower or "ia01b" in gen_lower:
        required_keywords = [
            "FR.IA.01B",
            "LEMBAR HASIL PENGUKURAN",
            "REPORT SHEET",
            "G.45OTO01.065.2",
            "Tanda tangan",
        ]
    elif "ia_01" in gen_lower or "ia01" in gen_lower:
        required_keywords = [
            "FR.IA.01.",
            "CL - CEKLIS OBSERVASI AKTIVITAS",
            "PANDUAN BAGI ASESOR",
            "G.45OTO01.001.2",
            "Melaksanakan Keselamatan dan Kesehatan Kerja",
            "Engine",
            "Umpan Balik untuk asesi:",
            "Rekomendasi:",
            "direkomendasikan KOMPETEN",
        ]
    elif "ak_07" in gen_lower or "ak07" in gen_lower:
        required_keywords = [
            "FR.AK.07.",
            "CEKLIS PENYESUAIAN YANG WAJAR",
            "PANDUAN BAGI ASESOR",
            "Hasil kesepakatan antara asesi dengan asesor",
        ]
    elif "apl_02" in gen_lower or "apl02" in gen_lower:
        required_keywords = [
            "FR.APL.02.",
            "ASESMEN MANDIRI",
            "PANDUAN ASESMEN MANDIRI",
            "G.45OTO01.001.2",
            "Ditinjau Oleh Asesor",
        ]
    else:
        required_keywords = [
            "FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI",
            "Rincian Data Pemohon Sertifikasi",
            "G.45OTO01.001.2",
            "SKKNI Nomor 97",
            "Bukti Persyaratan Dasar Pemohon",
            "Bukti Administratif",
            "Rekomendasi (diisi oleh LSP)"
        ]

    for kw in required_keywords:
        found = kw in gen_text
        print(f"   - '{kw}': {'DITEMUKAN' if found else 'TIDAK DITEMUKAN'}")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        ref = "all-document-lsp-ProIndo/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/2. FR.APL.01. Permohonan Sertifikasi Kompetensi.pdf"
        gen = "tests/Fixtures/muk/FR_APL_01_generated.pdf"
    else:
        ref, gen = sys.argv[1], sys.argv[2]
    run_diff(ref, gen)
