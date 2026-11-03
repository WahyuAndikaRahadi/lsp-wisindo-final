from PIL import Image

im = Image.open('scratch/ref_page_1.png').convert('RGB')
w, h = im.size

colors = im.getcolors(maxcolors=100000)
# Sort by count
colors.sort(key=lambda x: -x[0])
print("Top 15 colors on Page 1:")
for count, (r, g, b) in colors[:15]:
    print(f"  Color ({r:3d}, {g:3d}, {b:3d}): {count:7d} pixels")

