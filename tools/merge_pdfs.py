#!/usr/bin/env python3
"""
Merge Multiple PDF and Image Files into One Unified PDF - LSP PrO Indo
Digunakan untuk menggabungkan seluruh dokumen MUK per asesi:
FR.APL.01, FR.APL.02, FR.MAPA, FR.AK.01, FR.AK.07, FR.IA (01, 01B, 02, 03, 05A, 05C),
FR.AK.02, FR.AK.03, FR.AK.04, dan Surat Pernyataan.
"""

import sys
import os
import json

try:
    import pymupdf as fitz
except ImportError:
    import fitz

def merge_files(file_list, output_path):
    doc = fitz.open()
    inserted_count = 0
    total_pages = 0

    for file_path in file_list:
        if not file_path or not os.path.isfile(file_path):
            continue

        ext = os.path.splitext(file_path)[1].lower()

        if ext == '.pdf':
            try:
                sub = fitz.open(file_path)
                doc.insert_pdf(sub)
                total_pages += len(sub)
                sub.close()
                inserted_count += 1
            except Exception as e:
                print(f"Warning: gagal memasukkan PDF '{file_path}': {e}", file=sys.stderr)
        elif ext in ['.jpg', '.jpeg', '.png', '.webp', '.bmp']:
            try:
                img = fitz.open(file_path)
                rect = img[0].rect
                pdfbytes = img.convert_to_pdf()
                img.close()
                imgpdf = fitz.open("pdf", pdfbytes)
                page = doc.new_page(width=rect.width, height=rect.height)
                page.show_pdf_page(rect, imgpdf, 0)
                imgpdf.close()
                total_pages += 1
                inserted_count += 1
            except Exception as e:
                print(f"Warning: gagal mengonversi gambar '{file_path}': {e}", file=sys.stderr)

    if inserted_count == 0:
        print("Error: Tidak ada file valid yang berhasil digabungkan.", file=sys.stderr)
        sys.exit(1)

    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    doc.save(output_path, deflate=True)
    doc.close()
    print(f"OK -> Berhasil menggabungkan {inserted_count} file ({total_pages} halaman) ke {output_path}")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: merge_pdfs.py <input_json_or_file_list> <output_pdf>", file=sys.stderr)
        sys.exit(1)

    input_arg = sys.argv[1]
    output_pdf = sys.argv[2]

    files = []
    if os.path.isfile(input_arg):
        with open(input_arg, "r", encoding="utf-8") as f:
            files = json.load(f)
    else:
        try:
            files = json.loads(input_arg)
        except Exception:
            files = [x.strip() for x in input_arg.split(",") if x.strip()]

    if not isinstance(files, list):
        print("Error: Input files harus berupa JSON array.", file=sys.stderr)
        sys.exit(1)

    merge_files(files, output_pdf)
