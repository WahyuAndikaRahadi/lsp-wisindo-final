import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

for i, page in enumerate(doc):
    print(f"Page {i+1}:")
    for b in page.get_text('dict')['blocks']:
        if 'lines' not in b: continue
        for l in b['lines']:
            for s in l['spans']:
                if 'SKEMA' in s['text'] or 'SERTIFIKASI' in s['text'] or s['size'] > 18:
                    print(f"  Watermark text span: size={s['size']}, color={s.get('color')}, font={s['font']}, text={repr(s['text'])}")

