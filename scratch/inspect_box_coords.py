import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

for p_idx in range(len(doc)):
    page = doc[p_idx]
    page_boxes = []
    for b in page.get_text('dict')['blocks']:
        if 'lines' not in b: continue
        for line in b['lines']:
            for s in line['spans']:
                txt = s['text'].strip()
                if '☐' in txt or txt == '\u2610':
                    page_boxes.append(s['bbox'])
    
    # Sort boxes by Y, then by X
    page_boxes.sort(key=lambda b: (round(b[1], -1), b[0]))
    print(f"\n--- Page {p_idx+1} ({len(page_boxes)} boxes) ---")
    for idx, bb in enumerate(page_boxes):
        print(f"  Box {idx}: ({bb[0]:.1f}, {bb[1]:.1f}, {bb[2]:.1f}, {bb[3]:.1f})")

