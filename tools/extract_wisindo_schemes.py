#!/usr/bin/env python3
import os
import re
import json
import zipfile
import xml.etree.ElementTree as ET

BASE_DIR = "/home/wahyuandika/Dokumen/coding-project/lsp-wisindo-final/all-document-lsp-Wisindo"
OUT_DIR = "/home/wahyuandika/Dokumen/coding-project/lsp-wisindo-final/database/seeders/data"

SCHEMES_CONFIG = [
    {
        "folder": "Skema Sertifikasi Okupasi Chef de Partie Hotel",
        "code": "SKM-CDP-01",
        "nomor": "459/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Chef de Partie Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-01.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Commis Chef Hotel",
        "code": "SKM-CC-02",
        "nomor": "458/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Commis Chef Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-02.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Receptionist",
        "code": "SKM-FO-03",
        "nomor": "448/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Receptionist Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-03.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Room Attendant",
        "code": "SKM-HK-04",
        "nomor": "451/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Room Attendant Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-04.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Waiter Server",
        "code": "SKM-FB-05",
        "nomor": "455/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Waiter Server Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-05.json",
        "passing_grade": 75.0,
    },
]

NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}


def read_docx_tables(docx_path):
    if not os.path.exists(docx_path):
        return []
    with zipfile.ZipFile(docx_path) as z:
        tree = ET.fromstring(z.read("word/document.xml"))
        tables = []
        for tbl in tree.findall(".//w:tbl", NS):
            rows = []
            for tr in tbl.findall(".//w:tr", NS):
                row = [" ".join("".join(tc.itertext()).split()) for tc in tr.findall(".//w:tc", NS)]
                rows.append(row)
            tables.append(rows)
        return tables


def clean_unit_title(title):
    t = " ".join(str(title or "").split())
    if t.endswith("Operasional Dasa"):
        t = t + "r"
    return t


def extract_apl01_unit_order(apl01_path):
    """Extract canonical unit sequence (1..N) from FR.APL.01 (matches FR.APL.02)."""
    tables = read_docx_tables(apl01_path)
    ordered = []
    default_std = "SKKNI No.125 Tahun 2024"
    for tbl in tables:
        flat = " ".join(" ".join(r) for r in tbl)
        if "Kode Unit" in flat and "Judul Unit" in flat and len(tbl) > 3:
            for row in tbl[1:]:
                if len(row) >= 3 and re.match(r"^[A-Z]\.\d+", row[1]):
                    std = row[3].strip() if len(row) > 3 and row[3].strip() else default_std
                    if std and len(ordered) == 0:
                        default_std = std
                    ordered.append({
                        "sequence": len(ordered) + 1,
                        "code": row[1].strip(),
                        "title": clean_unit_title(row[2]),
                        "standard_reference": std,
                    })
    return ordered


def extract_mapa01_job_groups(mapa01_path):
    """Extract ordered Kelompok Pekerjaan and intra-group unit ordering from FR.MAPA.01."""
    if not os.path.exists(mapa01_path):
        return []
    with zipfile.ZipFile(mapa01_path) as z:
        tree = ET.fromstring(z.read("word/document.xml"))

    job_groups = []
    for tbl in tree.findall(".//w:tbl", NS):
        flat = " ".join("".join(tbl.itertext()).split())
        if "Kelompok Pekerjaan" not in flat:
            continue

        trs = tbl.findall(".//w:tr", NS)
        if not trs:
            continue

        first_cell = trs[0].find(".//w:tc", NS)
        paras = [
            " ".join("".join(p.itertext()).split())
            for p in (first_cell.findall(".//w:p", NS) if first_cell is not None else [])
        ]
        paras = [p for p in paras if p]
        raw_header = " ".join(paras)

        m = re.match(r"^Kelompok\s+Pekerjaan\s+(\d+)\s*(.*)$", raw_header, re.IGNORECASE)
        if m:
            group_seq = int(m.group(1))
            group_name = m.group(2).strip()
        else:
            group_seq = len(job_groups) + 1
            group_name = raw_header

        if not group_name:
            group_name = f"Kelompok Pekerjaan {group_seq}"

        group_units = []
        for tr in trs[1:]:
            row = [" ".join("".join(tc.itertext()).split()) for tc in tr.findall(".//w:tc", NS)]
            if len(row) >= 3 and re.match(r"^[A-Z]\.\d+", row[2]):
                group_units.append({
                    "code": row[2].strip(),
                    "title": clean_unit_title(row[3] if len(row) > 3 else ""),
                    "sequence": len(group_units) + 1,
                })

        if group_units:
            job_groups.append({
                "sequence": group_seq,
                "name": group_name,
                "units": group_units,
            })

    job_groups.sort(key=lambda g: g["sequence"])
    return job_groups


def extract_units_and_kuks(ia01_path):
    tables = read_docx_tables(ia01_path)
    units = []

    i = 0
    while i < len(tables):
        tbl = tables[i]
        flat_text = " ".join(" ".join(r) for r in tbl)

        if "Kode Unit" in flat_text and "Judul Unit" in flat_text and len(tbl) <= 3:
            unit_code = ""
            unit_title = ""
            for r in tbl:
                for idx, c in enumerate(r):
                    if "Kode Unit" in c:
                        unit_code = r[idx + 2] if idx + 2 < len(r) else (r[idx + 1] if idx + 1 < len(r) else "")
                    if "Judul Unit" in c:
                        unit_title = r[idx + 2] if idx + 2 < len(r) else (r[idx + 1] if idx + 1 < len(r) else "")

            unit_code = unit_code.strip()
            unit_title = clean_unit_title(unit_title)

            if unit_code and unit_title and not unit_code.lower().startswith("judul"):
                elements = []
                standard_ref = "SKKNI No.125 Tahun 2024"
                if i + 1 < len(tables):
                    detail_tbl = tables[i + 1]
                    curr_element = None
                    for row in detail_tbl[2:]:
                        elem_col = row[1] if len(row) > 1 else ""
                        kuk_col = row[2] if len(row) > 2 else ""
                        ref_col = row[3] if len(row) > 3 else ""

                        if ref_col and len(ref_col) > 3:
                            standard_ref = ref_col

                        if elem_col and (not curr_element or curr_element["title"] != elem_col):
                            curr_element = {
                                "title": elem_col,
                                "sequence": len(elements) + 1,
                                "kuks": []
                            }
                            elements.append(curr_element)

                        if kuk_col:
                            m = re.match(r"^(\d+\.\d+)\s*(.*)", kuk_col, re.DOTALL)
                            if m:
                                code_num = m.group(1)
                                statement = m.group(2).strip()
                            else:
                                code_num = f"{len(elements)}.{len(curr_element['kuks']) + 1 if curr_element else 1}"
                                statement = kuk_col.strip()

                            if curr_element is None:
                                curr_element = {
                                    "title": "Elemen Kompetensi Umum",
                                    "sequence": 1,
                                    "kuks": []
                                }
                                elements.append(curr_element)

                            curr_element["kuks"].append({
                                "code_number": code_num,
                                "statement": statement,
                                "sequence": len(curr_element["kuks"]) + 1
                            })

                units.append({
                    "code": unit_code,
                    "title": unit_title,
                    "standard_reference": standard_ref,
                    "sequence": len(units) + 1,
                    "elements": elements
                })
        i += 1

    return units


def extract_questions(ia05_path):
    tables = read_docx_tables(ia05_path)
    questions = []
    if len(tables) < 2:
        return questions

    q_table = tables[1]
    for i in range(0, len(q_table), 2):
        q_row = q_table[i] if i < len(q_table) else []
        opt_row = q_table[i + 1] if i + 1 < len(q_table) else []

        q_text = q_row[0] if len(q_row) > 0 else ""
        opt_raw = opt_row[0] if len(opt_row) > 0 else ""

        if not q_text:
            continue

        parts = re.split(r"(?=[A-E]\.)", opt_raw)
        opts = {}
        for p in parts:
            p = p.strip()
            if p and len(p) > 2 and p[0] in "ABCDE" and p[1] == ".":
                key = "option_" + p[0].lower()
                opts[key] = p[2:].strip()

        questions.append({
            "question_text": q_text,
            "option_a": opts.get("option_a", "Pilihan A"),
            "option_b": opts.get("option_b", "Pilihan B"),
            "option_c": opts.get("option_c", "Pilihan C"),
            "option_d": opts.get("option_d", "Pilihan D"),
            "option_e": opts.get("option_e", "Pilihan E"),
            "correct_answer": "A",
            "is_active": True
        })
    return questions


def main():
    os.makedirs(OUT_DIR, exist_ok=True)

    for f in os.listdir(OUT_DIR):
        if f.startswith("SKM-0") and f.endswith(".json"):
            old_path = os.path.join(OUT_DIR, f)
            print(f"Removing old ProIndo file: {f}")
            os.remove(old_path)

    for cfg in SCHEMES_CONFIG:
        folder_path = os.path.join(BASE_DIR, cfg["folder"])
        apl01_path = os.path.join(folder_path, "1. FR.APL 01. Permohonan Sertifikasi Kompetensi.docx")
        mapa01_path = os.path.join(folder_path, "4. FR.MAPA 01. Merencanakan Aktivitas dan Proses.docx")
        ia01_path = os.path.join(folder_path, "10. FR.IA.01.Ceklis Observasi Aktivitas Praktik.docx")
        ia05_path = os.path.join(folder_path, "14. FR.IA.05A. Pertanyaan Tertulis Pilihan Ganda.docx")

        print(f"\nProcessing scheme: {cfg['name']}")

        apl_order = extract_apl01_unit_order(apl01_path)
        job_groups = extract_mapa01_job_groups(mapa01_path)
        ia_units = extract_units_and_kuks(ia01_path)
        ia_by_code = {u["code"]: u for u in ia_units}

        # Map each unit code to its Kelompok Pekerjaan & intra-group sequence
        kp_map = {}
        for jg in job_groups:
            for u in jg["units"]:
                kp_map[u["code"]] = (jg["sequence"], u["sequence"])

        # Order units strictly according to APL.01 / APL.02 canonical sequence
        ordered_units = []
        for item in apl_order:
            code = item["code"]
            ia_data = ia_by_code.get(code, {})
            kp_seq, kp_unit_seq = kp_map.get(code, (None, None))
            title = ia_data.get("title") or item["title"]
            ordered_units.append({
                "code": code,
                "title": clean_unit_title(title),
                "standard_reference": ia_data.get("standard_reference") or item["standard_reference"],
                "sequence": item["sequence"],
                "job_group_sequence": kp_seq,
                "job_group_unit_sequence": kp_unit_seq,
                "elements": ia_data.get("elements", []),
            })

        print(f"  Extracted {len(ordered_units)} units (APL.01 canonical order), {len(job_groups)} job groups (MAPA.01 order)")
        for jg in job_groups:
            print(f"    KP {jg['sequence']}: {jg['name']} ({len(jg['units'])} unit)")
        total_kuks = sum(len(e["kuks"]) for u in ordered_units for e in u["elements"])
        print(f"  Total KUKs: {total_kuks}")

        questions = extract_questions(ia05_path)
        print(f"  Extracted {len(questions)} CBT questions")

        first_unit_code = ordered_units[0]["code"] if ordered_units else None
        for q in questions:
            q["unit_code"] = first_unit_code

        data = {
            "scheme": {
                "code": cfg["code"],
                "name": cfg["name"],
                "scheme_type": cfg["type"],
                "version": "1.0",
                "description": f"{cfg['name']} (Nomor Skema: {cfg['nomor']})",
                "passing_grade_theory": cfg["passing_grade"]
            },
            "job_groups": job_groups,
            "units": ordered_units,
            "questions": questions
        }

        out_path = os.path.join(OUT_DIR, cfg["out_file"])
        with open(out_path, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"  Saved -> {out_path}")


if __name__ == "__main__":
    main()
