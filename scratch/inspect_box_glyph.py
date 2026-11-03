import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)
page = doc[0]

# Box 0 on Page 1: (429.2, 701.6, 438.6, 715.8)
# Let's render this small rectangle at 300 DPI
pix = page.get_pixmap(clip=pymupdf.Rect(425, 698, 442, 718), dpi=300)
pix.save('scratch/ref_box0_zoom.png')
print("Saved scratch/ref_box0_zoom.png, size:", pix.width, pix.height)

