import pymupdf

doc = pymupdf.open(r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf')
p9 = doc[8]

colons = p9.search_for(':')
for c in colons:
    near_rect = pymupdf.Rect(c.x0 - 150, c.y0 - 2, c.x1 + 250, c.y1 + 4)
    near_text = p9.get_text(clip=near_rect).replace('\n', ' ').strip()
    print(f"Colon at ({c.x0:.1f}, {c.y0:.1f}) -> {near_text}")

