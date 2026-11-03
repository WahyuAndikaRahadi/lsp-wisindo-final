import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

p1 = doc[0]
print("Images on Page 1:", len(p1.get_images()))
for img in p1.get_images():
    print(" ", img)

print("Drawings on Page 1:", len(p1.get_drawings()))
# Check drawings for light grey or light blue color
watermark_drawings = []
for d in p1.get_drawings():
    color = d.get('color')
    fill = d.get('fill')
    # if color or fill is light grey or light blue
    if fill and any(c > 0.7 for c in fill if isinstance(c, (int, float))):
        watermark_drawings.append(d)
print("Light fill drawings:", len(watermark_drawings))

