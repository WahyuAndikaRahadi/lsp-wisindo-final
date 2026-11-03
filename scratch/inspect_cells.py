import json

with open('tests/Fixtures/muk/ia01_master_cells.json', encoding='utf-8') as f:
    d = json.load(f)
print('Pages in json:', len(d))
p1 = d[0]
print('Page 1 keys:', p1.keys())
print('Page 1 tables count:', len(p1['tables']))
for idx, tbl in enumerate(p1['tables']):
    print(f"Table {idx}: bbox={tbl.get('bbox')}, rows={len(tbl.get('rows', []))}")

