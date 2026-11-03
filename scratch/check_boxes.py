import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

for i in range(len(doc)):
    page = doc[i]
    print(f"Page {i+1}: rect={page.rect}, mediabox={page.mediabox}")

