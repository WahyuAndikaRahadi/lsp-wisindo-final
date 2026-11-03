from PIL import Image
import numpy as np

im = Image.open('scratch/ref_page_1.png').convert('RGB')
arr = np.array(im)

# Pure white is (255, 255, 255)
# Text is dark (< 100)
# Gray table headers is around (217, 217, 217)
# Watermark is usually faint blue/gray around (200-240, 200-240, 230-255)

h, w, _ = arr.shape
print(f"Image size: {w}x{h}")

# Check center region (x from 100 to w-100, y from 200 to h-200)
center = arr[200:h-200, 100:w-100]
# Count non-white, non-black, non-gray pixels
r = center[:, :, 0].astype(int)
g = center[:, :, 1].astype(int)
b = center[:, :, 2].astype(int)

# Watermarks typically have color saturation |r-g| > 10 or |r-b| > 10 or faint tint
diff_rg = np.abs(r - g)
diff_rb = np.abs(r - b)
tinted = (diff_rb > 8) & (r > 150) & (b > 150)
print(f"Faint tinted watermark pixels in center: {np.sum(tinted)}")

# Let's inspect unique colors in faint range
faint = (r > 180) & (r < 250) & (g > 180) & (g < 250) & (b > 180) & (b < 250)
faint_pixels = arr[200:h-200, 100:w-100][faint]
print(f"Total faint pixels in center: {len(faint_pixels)}")
if len(faint_pixels) > 0:
    unique_faint = np.unique(faint_pixels, axis=0)
    print(f"Unique faint colors: {len(unique_faint)}")
    # Print sample colors
    print("Sample faint colors:", unique_faint[:10])

