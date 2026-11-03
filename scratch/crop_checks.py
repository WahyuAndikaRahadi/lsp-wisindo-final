from PIL import Image

p1_im = Image.open('scratch/out_p1.png')
# DPI is 150 (scale = 150/72 ≈ 2.083)
scale = 150.0 / 72.0

# Box 0 on Page 1: (429.2, 701.6, 438.6, 715.8)
# Box 1 on Page 1: (470.6, 701.6, 480.0, 715.8)
crop_p1 = p1_im.crop((int(420 * scale), int(695 * scale), int(490 * scale), int(765 * scale)))
crop_p1.save('scratch/crop_checks_p1.png')

p9_im = Image.open('scratch/out_p9.png')
# Rec box 4 on Page 9: (83.8, 326.5, 93.3, 340.7)
crop_p9 = p9_im.crop((int(75 * scale), int(320 * scale), int(150 * scale), int(370 * scale)))
crop_p9.save('scratch/crop_checks_p9.png')

print("Cropped checkmarks to scratch/crop_checks_p1.png and scratch/crop_checks_p9.png")

