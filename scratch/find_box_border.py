from PIL import Image

im = Image.open('scratch/ref_box0_zoom.png').convert('L')
w, h = im.size
# Find non-white bounding box
bbox = im.point(lambda p: 255 if p < 200 else 0).getbbox()
print("Box border bbox in 300 DPI zoom (x0, y0, x1, y1):", bbox)
# Since clip was (425, 698, 442, 718):
scale = 300.0 / 72.0
orig_x0 = 425 + bbox[0] / scale
orig_y0 = 698 + bbox[1] / scale
orig_x1 = 425 + bbox[2] / scale
orig_y1 = 698 + bbox[3] / scale
print(f"Exact square box glyph in PDF points: ({orig_x0:.2f}, {orig_y0:.2f}, {orig_x1:.2f}, {orig_y1:.2f})")
print(f"Square size: {orig_x1 - orig_x0:.2f} x {orig_y1 - orig_y0:.2f} pt")

