import pymupdf

doc = pymupdf.open(r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf')
p1 = doc[0]

# Let's inspect exact positions of colons on Page 1:
colons = p1.search_for(':')
for c in colons:
    # Get text near colon
    near_rect = pymupdf.Rect(c.x0 - 150, c.y0 - 2, c.x1 + 250, c.y1 + 4)
    near_text = p1.get_text(clip=near_rect).replace('\n', ' ').strip()
    print(f"Colon at ({c.x0:.1f}, {c.y0:.1f}) -> {near_text}")

