import pymupdf

ref_doc = pymupdf.open(r'lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf')
gen_doc = pymupdf.open('storage/app/muk-cache/dyn_2_3_ia01.pdf')

print(f"Ref pages: {len(ref_doc)}, Gen pages: {len(gen_doc)}")
assert len(ref_doc) == len(gen_doc) == 9, "Both must have 9 pages!"

for p in range(9):
    ref_p = ref_doc[p]
    gen_p = gen_doc[p]
    assert ref_p.rect == gen_p.rect, f"Page {p+1} rect mismatch!"

p1_text = gen_doc[0].get_text()
for item in ['Maintenance Gasoline Engine', 'Ari Susanto', 'Asesi Ani Rahayu', '02 November 2026']:
    assert item in p1_text, f"Page 1 missing: {item}"
print("Page 1 dynamic data verified: OK!")

p9_text = gen_doc[8].get_text()
for item in ['Bagus', 'KOMPETEN', 'Ari Susanto', 'MET.000.004916 2018', 'Asesi Ani Rahayu']:
    assert item in p9_text, f"Page 9 missing: {item}"
print("Page 9 dynamic data verified: OK!")

for p in range(9):
    drawings = gen_doc[p].get_drawings()
    print(f"Page {p+1}: {len(drawings)} vector drawings (tables & checkmarks)")

imgs = gen_doc[8].get_images()
print(f"Page 9 images: {len(imgs)} (Logo + Asesi Sig + Asesor Sig)")
assert len(imgs) >= 3, "Page 9 must have logo and signatures!"

print("ALL VERIFICATIONS PASSED!")

