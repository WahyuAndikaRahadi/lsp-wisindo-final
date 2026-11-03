import pymupdf

ref_path = r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf'
doc = pymupdf.open(ref_path)

cleaned = 0
for idx, page in enumerate(doc):
    for xref in page.get_contents():
        stream = doc.xref_stream(xref).decode('latin1', errors='ignore')
        wm_start = stream.find('0.722 0.8 0.894 rg')
        if wm_start != -1:
            print(f"Page {idx+1}: Found watermark stream starting at byte {wm_start}!")
            cleaned += 1

print(f"Total pages with watermark stream: {cleaned}")

