import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

pix = doc[0].get_pixmap(dpi=150)
pix.save('scratch/ref_page_1.png')

pix2 = doc[1].get_pixmap(dpi=150)
pix2.save('scratch/ref_page_2.png')

print("Saved scratch/ref_page_1.png and scratch/ref_page_2.png")

