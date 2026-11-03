import os, sys, pymupdf

doc = pymupdf.open('scratch/test_overlay_ia01.pdf')
print("Checking all 9 pages of scratch/test_overlay_ia01.pdf:")
for p in range(len(doc)):
    page = doc[p]
    drawings = len(page.get_drawings())
    images = len(page.get_images())
    text = page.get_text()
    print(f"Page {p+1}: drawings={drawings}, images={images}, text_len={len(text)}")

