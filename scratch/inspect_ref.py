import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

def inspect_page(p_idx):
    page = doc[p_idx]
    print(f'=== PAGE {p_idx+1} ===')
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
                print(f"({bb[0]:.1f}, {bb[1]:.1f}) [{s['font']}, {s['size']:.1f}pt, {weight}] -> {repr(txt)}")

inspect_page(0)

