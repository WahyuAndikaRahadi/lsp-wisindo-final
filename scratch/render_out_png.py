import pymupdf

doc = pymupdf.open('scratch/test_overlay_ia01.pdf')
doc[0].get_pixmap(dpi=150).save('scratch/out_p1.png')
doc[8].get_pixmap(dpi=150).save('scratch/out_p9.png')
print("Saved scratch/out_p1.png and scratch/out_p9.png")

