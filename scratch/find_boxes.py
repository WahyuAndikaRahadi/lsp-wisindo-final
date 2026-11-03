import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

total_boxes = 0
for p_idx, page in enumerate(doc):
    page_boxes = []
    # Search for square character
    for b in page.get_text('dict')['blocks']:
        if 'lines' not in b: continue
        for line in b['lines']:
            for s in line['spans']:
                txt = s['text'].strip()
                if '☐' in txt or txt == '\u2610':
                    page_boxes.append((s['bbox'], txt))
    print(f"Page {p_idx+1}: {len(page_boxes)} box spans found.")
    total_boxes += len(page_boxes)

print(f"Total box spans found across 9 pages: {total_boxes}")

