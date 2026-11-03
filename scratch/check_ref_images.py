import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

# Let's check if there are any watermark words or background images or anything
for i, page in enumerate(doc):
    imgs = page.get_images()
    print(f"Page {i+1} images: {len(imgs)}")
    for img in imgs:
        xref = img[0]
        base_img = doc.extract_image(xref)
        print(f"   xref={xref}, format={base_img['ext']}, size={base_img['width']}x{base_img['height']}")

