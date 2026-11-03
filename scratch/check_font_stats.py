import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

stats = {}
for p_idx, page in enumerate(doc):
    blocks = page.get_text('dict')['blocks']
    for b in blocks:
        if 'lines' not in b: continue
        for line in b['lines']:
            for s in line['spans']:
                txt = s['text'].strip()
                if not txt: continue
                fn = s['font']
                sz = round(s['size'], 1)
                is_bold = bool(s['flags'] & 4) or ('Bold' in fn) or ('bold' in fn)
                is_italic = bool(s['flags'] & 2) or ('Italic' in fn) or ('italic' in fn)
                weight = 'BOLD' if is_bold else ('ITALIC' if is_italic else 'REG')
                key = (fn, sz, weight)
                stats[key] = stats.get(key, 0) + 1

for k, count in sorted(stats.items(), key=lambda x: -x[1]):
    print(f"{k[0]:20s} {k[1]:4.1f}pt {k[2]:6s} -> {count} spans")

