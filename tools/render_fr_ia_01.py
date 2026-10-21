#!/usr/bin/env python3
"""
Generator FR.IA.01 (Ceklis Observasi Aktivitas Praktik) - LSP PrO Indo
Menghasilkan dokumen resmi FR.IA.01 persis 9 halaman sesuai master dokumen BNSP;
tanpa watermark dan layout 100% presisi.
"""

import os
import sys
import shutil
import tempfile
import fitz

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
MASTER_CANDIDATES = [
    os.path.join(PROJECT_ROOT, "lsp-document-dynamic", "MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection", "8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf"),
    os.path.join(PROJECT_ROOT, "all-document-lsp-ProIndo", "MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection", "8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf"),
]
OUT_FILE = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_IA_01_generated.pdf")

def get_master_path():
    for p in MASTER_CANDIDATES:
        if os.path.isfile(p):
            return p
    raise FileNotFoundError("Master FR.IA.01 PDF not found.")

def clean_watermark(doc):
    """
    Remove all watermark vector graphics (0.722 0.8 0.894 rg) from all pages.
    """
    cleaned_pages = 0
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
                    cleaned_pages += 1
    return cleaned_pages

def render(out_path=OUT_FILE):
    master_path = get_master_path()
    doc = fitz.open(master_path)
    clean_watermark(doc)

    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as tmp:
        tmp_name = tmp.name
    doc.save(tmp_name)
    doc.close()
    shutil.move(tmp_name, out_path)
    print(f"Generated clean FR.IA.01 ({len(fitz.open(out_path))} pages, no watermark) -> {out_path}")

if __name__ == "__main__":
    out_target = sys.argv[1] if len(sys.argv) > 1 else OUT_FILE
    render(out_target)
