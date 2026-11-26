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
        "folder": "Skema Sertifikasi  Okupasi Chef de Partie Hotel",
        "code": "SKM-CDP-01",
        "nomor": "459/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Chef de Partie Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-01.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi  Okupasi Commis Chef Hotel",
        "code": "SKM-CC-02",
        "nomor": "458/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Commis Chef Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-02.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Receptionist  atau Front Desk Agent  atau Guest Service Agent  atau Guest Service Associate  atau Guest Service Attendant  atau Guest Service Assistant  atau Resort Host Hotel",
        "code": "SKM-FO-03",
        "nomor": "448/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Receptionist Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-03.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Room Attendant  atau Room Boy  atau Room Maid",
        "code": "SKM-HK-04",
        "nomor": "451/PAR/2026",
        "name": "Skema Sertifikasi Okupasi Room Attendant Hotel",
        "type": "OKUPASI",
        "out_file": "SKM-WISINDO-04.json",
        "passing_grade": 75.0,
    },
    {
        "folder": "Skema Sertifikasi Okupasi Waiter Server atau Wait Person Hotel",
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
                row = ["".join(tc.itertext()).strip() for tc in tr.findall(".//w:tc", NS)]
                rows.append(row)
            tables.append(rows)
        return tables

def extract_units_and_kuks(ia01_path):
    tables = read_docx_tables(ia01_path)
    units = []
    
    i = 0
    while i < len(tables):
        tbl = tables[i]
        flat_text = " ".join(" ".join(r) for r in tbl)
        
        # Check if table defines a single Unit Kompetensi (e.g. Table with Kode Unit & Judul Unit)
        # Avoid the summary table (which lists multiple units in rows)
        if "Kode Unit" in flat_text and "Judul Unit" in flat_text and len(tbl) <= 3:
            unit_code = ""
            unit_title = ""
            for r in tbl:
                for idx, c in enumerate(r):
                    if "Kode Unit" in c:
                        unit_code = r[idx+2] if idx+2 < len(r) else (r[idx+1] if idx+1 < len(r) else "")
                    if "Judul Unit" in c:
                        unit_title = r[idx+2] if idx+2 < len(r) else (r[idx+1] if idx+1 < len(r) else "")
            
            unit_code = unit_code.strip()
            unit_title = unit_title.strip()
            
            if unit_code and unit_title and not unit_code.lower().startswith("judul"):
                # Table i+1 should contain elements and KUKs
                elements = []
                standard_ref = "SKKNI Terkait"
                if i + 1 < len(tables):
                    detail_tbl = tables[i + 1]
                    curr_element = None
                    for row in detail_tbl[2:]: # skip headers
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
                            # Match KUK code e.g. "1.1 statement"
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
        opt_row = q_table[i+1] if i+1 < len(q_table) else []
        
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
    
    # Remove old proindo files if present
    for f in os.listdir(OUT_DIR):
        if f.startswith("SKM-0") and f.endswith(".json"):
            old_path = os.path.join(OUT_DIR, f)
            print(f"Removing old ProIndo file: {f}")
            os.remove(old_path)
            
    for cfg in SCHEMES_CONFIG:
        folder_path = os.path.join(BASE_DIR, cfg["folder"])
        ia01_path = os.path.join(folder_path, "10. FR.IA.01.Ceklis Observasi Aktivitas Praktik.docx")
        ia05_path = os.path.join(folder_path, "14. FR.IA.05A. Pertanyaan Tertulis Pilihan Ganda.docx")
        
        print(f"\nProcessing scheme: {cfg['name']}")
        units = extract_units_and_kuks(ia01_path)
        print(f"  Extracted {len(units)} units")
        total_kuks = sum(len(e["kuks"]) for u in units for e in u["elements"])
        print(f"  Total KUKs: {total_kuks}")
        
        questions = extract_questions(ia05_path)
        print(f"  Extracted {len(questions)} CBT questions")
        
        # Link questions to first unit
        first_unit_code = units[0]["code"] if units else None
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
            "units": units,
            "questions": questions
        }
        
        out_path = os.path.join(OUT_DIR, cfg["out_file"])
        with open(out_path, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"  Saved -> {out_path}")

if __name__ == "__main__":
    main()
