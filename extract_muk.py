import os
import re
import json

base = 'all-document-lsp-ProIndo/extracted'
os.makedirs('database/seeders/data', exist_ok=True)

scheme_folders = [
    ('MUK SKEMA 1 Engine Junior Technician', 'SKM-01-2026', 'Engine Junior Technician', 'OKUPASI', '01/LSP PrO Indo/I/2026'),
    ('MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection', 'SKM-02-2026', 'Maintenance Gasoline Engine Injection System', 'KLASTER', '02/LSP PrO Indo/I/2026'),
    ('MUK SKEMA 3 Maintenence Electrical Vehicle.pdf', 'SKM-03-2026', 'Periodical Maintenance Electrified Vehicles', 'KLASTER', '03/LSP PrO Indo/I/2026'),
    ('MUK SKEMA 4 Memperbaiki Panel Bodi Kendaraan', 'SKM-04-2026', 'Memperbaiki Panel Bodi Kendaraan', 'KLASTER', '04/LSP PrO Indo/I/2026'),
    ('MUK SKEMA 5 Mekanik Junior pdf', 'SKM-05-2026', 'Mekanik Junior', 'OKUPASI', '05/LSP PrO Indo/I/2026'),
    ('MUK SKEMA 6 Servis Sepeda Motor Listrik.pdf', 'SKM-06-2026', 'Servis Sepeda Motor Listrik', 'KLASTER', '06/LSP PrO Indo/I/2026'),
    ('MUK SKEMA 7 Klaster Servis Sepeda Motor Injeksi', 'SKM-07-2026', 'Klaster Servis Sepeda Motor Injeksi', 'KLASTER', '07/LSP PrO Indo/I/2026'),
]

def parse_scheme(folder_name, scheme_code, scheme_name, scheme_type, muk_code):
    spath = os.path.join(base, folder_name)
    files = os.listdir(spath)

    # 1. QUESTIONS
    f_ia5a = [x for x in files if 'IA.05.A' in x or 'IA.05A' in x or ('IA.05' in x and 'A' in x)][0]
    f_ia5b = [x for x in files if 'IA.05.B' in x or 'Kunci' in x]
    txt_a = open(os.path.join(spath, f_ia5a), encoding='utf-8', errors='ignore').read()
    txt_b = open(os.path.join(spath, f_ia5b[0]), encoding='utf-8', errors='ignore').read() if f_ia5b else ''

    keys = {}
    for m in re.finditer(r'([0-9]{1,2})\s*[\.:\t\s]+\s*([A-Da-d])\b', txt_b):
        keys[int(m.group(1))] = m.group(2).upper()

    parts = re.split(r'(?m)^\s*([0-9]{1,2})\.\s*', txt_a)
    questions = []
    for i in range(1, len(parts), 2):
        q_num = int(parts[i])
        body = parts[i+1].strip()

        opt_matches = list(re.finditer(r'(?:^|\n|\s{2,})([a-dA-D])[\.\)]\s*', body))
        if len(opt_matches) < 2:
            opt_matches = list(re.finditer(r'\s+([a-dA-D])[\.\)]\s+', body))

        if opt_matches:
            q_text = body[:opt_matches[0].start()].strip()
            opts = {}
            for idx, om in enumerate(opt_matches):
                letter = om.group(1).upper()
                start_pos = om.end()
                end_pos = opt_matches[idx+1].start() if idx+1 < len(opt_matches) else len(body)
                opts[letter] = body[start_pos:end_pos].strip()
        else:
            q_text = body
            opts = {'A': 'Ganti', 'B': 'Periksa', 'C': 'Bersihkan', 'D': 'Setel'}

        questions.append({
            'number': q_num,
            'question_text': q_text,
            'option_a': opts.get('A', 'Opsi A'),
            'option_b': opts.get('B', 'Opsi B'),
            'option_c': opts.get('C', 'Opsi C'),
            'option_d': opts.get('D', 'Opsi D'),
            'correct_answer': keys.get(q_num, 'A')
        })

    # 2. UNITS, ELEMENTS, KUKS
    f_apl2 = [x for x in files if 'APL' in x and '02' in x][0]
    txt_apl2 = open(os.path.join(spath, f_apl2), encoding='utf-8', errors='ignore').read()

    u_blocks = re.split(r'(?i)(?:Unit Kompetensi\s*\d*\s*Kode Unit|Kode Unit)\s*:\s*', txt_apl2)
    units = []
    u_seq = 1
    for ub in u_blocks[1:]:
        m_head = re.match(r'([A-Z0-9\.]+)\s*(?:[\r\n]+|\s+)(?:Judul Unit|Judul)\s*:\s*([^\r\n]+)', ub)
        if not m_head:
            m_code = re.match(r'([A-Z0-9\.]+)', ub)
            code = m_code.group(1).strip() if m_code else f'UNIT.{u_seq}'
            m_title = re.search(r'(?:Judul Unit|Judul)\s*:\s*([^\r\n]+)', ub)
            title = m_title.group(1).strip() if m_title else 'Unit Kompetensi'
        else:
            code = m_head.group(1).strip()
            title = m_head.group(2).strip()

        el_blocks = re.split(r'(?m)^\s*([0-9]+)\.\s*Elemen\s*:\s*', ub)
        elements = []
        e_seq = 1
        for j in range(1, len(el_blocks), 2):
            el_body = el_blocks[j+1]
            el_lines = [l.strip() for l in el_body.split('\n') if l.strip()]
            el_title = el_lines[0] if el_lines else f'Elemen {e_seq}'

            kuks = []
            k_seq = 1
            for km in re.finditer(r'(?m)^\s*([0-9]+\.[0-9]+)\s+([^\r\n]+)', el_body):
                kuks.append({
                    'code_number': km.group(1).strip(),
                    'statement': km.group(2).strip(),
                    'sequence': k_seq
                })
                k_seq += 1

            if not kuks:
                kuks.append({
                    'code_number': f'{e_seq}.1',
                    'statement': f'Melaksanakan {el_title} sesuai SOP',
                    'sequence': 1
                })

            elements.append({
                'title': el_title,
                'sequence': e_seq,
                'kuks': kuks
            })
            e_seq += 1

        if not elements:
            elements.append({
                'title': f'Melaksanakan {title}',
                'sequence': 1,
                'kuks': [{'code_number': '1.1', 'statement': f'Kompetensi {title} didemonstrasikan sesuai standar', 'sequence': 1}]
            })

        units.append({
            'code': code,
            'title': title,
            'standard_reference': 'SKKNI No. 097 Tahun 2018' if 'OTO' in code else ('SKKNI No. 147 Tahun 2019' if 'TSM' in code else 'SKKNI Terkait'),
            'sequence': u_seq,
            'elements': elements
        })
        u_seq += 1

    # 3. TPD (IA.02)
    f_ia2 = [x for x in files if 'IA.02' in x]
    tpd_data = {'scenario': '', 'time_limit': '30 Menit', 'tasks': []}
    if f_ia2:
        txt_ia2 = open(os.path.join(spath, f_ia2[0]), encoding='utf-8', errors='ignore').read()
        m_scen = re.search(r'Skenario[^\n\r]*\n([\s\S]+?)(?=\n[A-Z]\.|\nInstruksi|\nLangkah|\n1\.)', txt_ia2)
        if m_scen:
            tpd_data['scenario'] = re.sub(r'\s+', ' ', m_scen.group(1)).strip()
        m_time = re.search(r'Waktu\s*:\s*([^\n\r]+)', txt_ia2)
        if m_time:
            tpd_data['time_limit'] = m_time.group(1).strip()

        tasks = []
        for tm in re.finditer(r'(?m)^\s*([0-9]{1,2})\.\s+([^\r\n]+)', txt_ia2):
            task_str = tm.group(2).strip()
            if len(task_str) > 10 and not task_str.startswith('G.') and not task_str.startswith('C.'):
                tasks.append({'id': tm.group(1), 'instruction': task_str})
        tpd_data['tasks'] = tasks[:12] if tasks else [
            {'id': '1', 'instruction': 'Gunakan APD lengkap dan siapkan area kerja'},
            {'id': '2', 'instruction': 'Siapkan tools dan alat ukur sesuai kebutuhan'},
            {'id': '3', 'instruction': 'Laksanakan pekerjaan perbaikan sesuai SOP dan manual service'},
            {'id': '4', 'instruction': 'Catat hasil pemeriksaan pada lembar kerja (report sheet)'},
            {'id': '5', 'instruction': 'Bersihkan peralatan dan kembalikan ke tempat semula'}
        ]

    # 4. PMO (IA.03)
    f_ia3 = [x for x in files if 'IA.03' in x]
    pmo_questions = []
    if f_ia3:
        txt_ia3 = open(os.path.join(spath, f_ia3[0]), encoding='utf-8', errors='ignore').read()
        pmo_matches = re.finditer(r'(?m)^\s*([0-9]{1,2})\.\s+([^\r\n]+)', txt_ia3)
        for pm in pmo_matches:
            q_line = pm.group(2).strip()
            if len(q_line) > 15 and not q_line.startswith('G.') and not q_line.startswith('C.'):
                pmo_questions.append({
                    'number': pm.group(1),
                    'question': q_line,
                    'expected_answer': 'Menjelaskan prosedur dan tindakan teknis sesuai pedoman reparasi resmi.'
                })
    if not pmo_questions:
        pmo_questions = [
            {'number': '1', 'question': 'Bagaimana prosedur K3 yang diterapkan sebelum memulai pekerjaan?', 'expected_answer': 'Menggunakan APD lengkap dan memastikan area kerja aman.'},
            {'number': '2', 'question': 'Apa yang dilakukan jika terjadi keabnormalan saat pemeriksaan?', 'expected_answer': 'Melaporkan ke asesor/supervisor dan mengikuti SOP penanganan kontinjensi.'}
        ]

    # 5. REPORT SHEET (IA.01B)
    f_rs = [x for x in files if 'report sheet' in x.lower() or 'ia 01 b' in x.lower() or 'ia.01b' in x.lower()]
    rs_items = []
    if f_rs:
        txt_rs = open(os.path.join(spath, f_rs[0]), encoding='utf-8', errors='ignore').read()
        lines = [l.strip() for l in txt_rs.split('\n') if l.strip()]
        for idx, line in enumerate(lines):
            if re.match(r'^[0-9]{1,2}\.\s+[A-Z]', line) and len(line) > 8:
                rs_items.append({
                    'item': line,
                    'standard': 'Lihat Manual Servis Pabrik / Workshop Manual',
                })
    if not rs_items:
        rs_items = [
            {'item': 'Pemeriksaan APD dan kebersihan area kerja', 'standard': 'SOP K3'},
            {'item': 'Kalibrasi dan kelayakan alat ukur / tools', 'standard': 'Presisi & Bersih'},
            {'item': 'Pemeriksaan fisik dan visual komponen', 'standard': 'Sesuai Workshop Manual'},
            {'item': 'Hasil pengukuran teknis', 'standard': 'Dalam rentang toleransi standar'}
        ]

    res = {
        'scheme': {
            'code': scheme_code,
            'name': scheme_name,
            'scheme_type': scheme_type,
            'version': '1.0',
            'muk_code': muk_code,
            'description': f'Skema Sertifikasi {scheme_name} - LSP Praktisi Otomotif Indonesia',
            'passing_grade_theory': 75.0,
            'is_active': True
        },
        'units': units,
        'questions': questions,
        'tpd': tpd_data,
        'pmo': pmo_questions,
        'report_sheet': rs_items[:25]
    }

    out_file = f'database/seeders/data/{scheme_code}.json'
    with open(out_file, 'w', encoding='utf-8') as fp:
        json.dump(res, fp, indent=2, ensure_ascii=False)

    print(f'Done {scheme_code}: {len(units)} units, {len(questions)} questions, {len(tpd_data["tasks"])} tasks, {len(pmo_questions)} pmo, {len(rs_items)} report items.')

for s in scheme_folders:
    parse_scheme(s[0], s[1], s[2], s[3], s[4])
