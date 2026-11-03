import pymupdf, sys

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

with open('scratch/ref_pages_dump.txt', 'w', encoding='utf-8') as out:
    for p_idx in range(len(doc)):
        page = doc[p_idx]
        out.write(f"\n================================ PAGE {p_idx+1} ================================\n")
        # Extract tables / vector drawings
        drawings = page.get_drawings()
        out.write(f"Total drawings/paths: {len(drawings)}\n")
        
        # Extract text blocks
        blocks = page.get_text('dict')['blocks']
        for b in blocks:
            if 'lines' not in b: continue
            for line in b['lines']:
                for s in line['spans']:
                    txt = s['text'].strip()
                    if not txt: continue
                    # Skip watermark text
                    if 'SKEMA' in txt and 'SERTIFIKASI' in txt and s['size'] > 18:
                        continue
                    is_bold = bool(s['flags'] & 4) or ('Bold' in s['font']) or ('bold' in s['font'])
                    is_italic = bool(s['flags'] & 2) or ('Italic' in s['font']) or ('italic' in s['font'])
                    weight = 'BOLD' if is_bold else ('ITALIC' if is_italic else 'REG')
                    bb = s['bbox']
                    out.write(f"({bb[0]:.1f}, {bb[1]:.1f}, {bb[2]:.1f}, {bb[3]:.1f}) [{s['font']}, {s['size']:.1f}pt, {weight}] -> {repr(txt)}\n")

print("Dumped all 9 pages to scratch/ref_pages_dump.txt")

