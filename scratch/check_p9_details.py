import pymupdf, sys
sys.stdout.reconfigure(encoding='utf-8')

doc = pymupdf.open('scratch/test_overlay_ia01.pdf')
p9 = doc[8]
for b in p9.get_text('dict')['blocks']:
    if 'lines' not in b: continue
    for l in b['lines']:
        for s in l['spans']:
            bb = s['bbox']
            if bb[1] > 230:
                print(f"({bb[0]:.1f}, {bb[1]:.1f}) [{s['font']}, {s['size']:.1f}pt] -> {repr(s['text'])}")

for img in p9.get_images():
    print("Image:", img)
