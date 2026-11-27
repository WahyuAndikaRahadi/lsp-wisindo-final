#!/usr/bin/env python3
"""
Generator FR.IA.08 (CVP - Ceklis Verifikasi Portofolio) - LSP PrO Indo
Menghasilkan dokumen PDF FR.IA.08 resmi BNSP sesuai template master:
- Kop Surat Resmi LSP PrO Indo di setiap halaman (atas).
- Tanpa footer (footer dihilangkan sesuai permintaan).
- Font size proporsional, besar dan jelas sesuai template (Calibri 9.5 - 10pt).
- Header Judul: FR.IA.08. | CVP - CEKLIS VERIFIKASI PORTOFOLIO (12pt Bold).
- Metadata Skema, TUK, Asesor, Asesi, dan Tanggal (latar transparan/putih).
- Panduan Bagi Asesor (5 poin baku, latar transparan).
- Tabel Bukti Portofolio & Aturan Bukti (VATM: Valid, Asli, Terkini, Memadai).
- Tabel Tindak Lanjut Klarifikasi Wawancara (dinamis per Unit, Elemen, dan KUK).
- Tabel Bukti Tambahan & Rekomendasi Asesor (2 kolom persis sesuai template).
- Tabel Tanda Tangan Asesi & Asesor bertingkat (stacked 3 kolom persis template).
- Tanda tangan digital Asesi dan Asesor yang telah dibersihkan transparan.
"""

import os
import sys
import json
import io
import base64
from PIL import Image

from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, HexColor, white
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image as RLImage, KeepTogether
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.pdfbase import pdfmetrics

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
ASSETS_DIR = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets")
LOGO_PATH = os.path.join(ASSETS_DIR, "logo.png")
if not os.path.isfile(LOGO_PATH):
    LOGO_PATH = os.path.join(PROJECT_ROOT, "public", "images", "logo-lsp-wisindo.png")

# Setup fonts
try:
    from font_helper import register_reportlab_fonts
except ImportError:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    from font_helper import register_reportlab_fonts

register_reportlab_fonts(["Cal", "Cal-B", "Cal-I", "DJ"])
try:
    pdfmetrics.registerFontFamily('Cal', normal='Cal', bold='Cal-B', italic='Cal-I', boldItalic='Cal-B')
except Exception:
    pass

PAGE_W, PAGE_H = 595.27, 841.89  # A4 Portrait
MARGIN = 36.0
PRINTABLE_W = PAGE_W - (MARGIN * 2)  # 523.27 pt


def draw_header_box(canvas_obj):
    """Draw Kop Surat header box at the top of every page with true aspect ratio."""
    canvas_obj.saveState()

    x0 = MARGIN  # 36.0
    x1 = PAGE_W - MARGIN  # 559.27
    box_w = x1 - x0  # 523.27
    box_h = 56.0
    top_y = PAGE_H - 24.0  # 817.89
    bot_y = top_y - box_h  # 761.89

    # Outer border
    canvas_obj.setLineWidth(0.65)
    canvas_obj.setStrokeColor(black)
    canvas_obj.rect(x0, bot_y, box_w, box_h, fill=0, stroke=1)

    # Vertical divider line
    div_w = 88.0
    x_div = x0 + div_w
    canvas_obj.line(x_div, bot_y, x_div, top_y)

    # Logo in left compartment (preserving natural aspect ratio ~1.13, never squashed)
    if os.path.isfile(LOGO_PATH):
        try:
            im = Image.open(LOGO_PATH)
            im_w, im_h = im.size
            aspect = float(im_w) / float(im_h) if im_h > 0 else 1.13
            logo_h = 44.0
            logo_w = logo_h * aspect  # ~49.7 pt
            logo_x = x0 + (div_w - logo_w) / 2.0
            logo_y = bot_y + (box_h - logo_h) / 2.0
            canvas_obj.drawImage(LOGO_PATH, logo_x, logo_y, width=logo_w, height=logo_h, mask='auto')
        except Exception:
            pass

    # Title in right compartment
    header_html = '<font size="11.5" name="Cal"><b>L</b>embaga <b>S</b>ertifikasi <b>P</b>rofesi <b>P</b>raktisi <b>O</b>tomotif &nbsp;&nbsp;<b>LSP-PrO Indo</b></font>'
    s_kop = ParagraphStyle('HeaderKopBox', fontName='Cal', fontSize=11.5, leading=15, alignment=1)
    p = Paragraph(header_html, s_kop)
    avail_w = x1 - x_div - 8.0
    w, h = p.wrap(avail_w, box_h)
    p.drawOn(canvas_obj, x_div + 4.0, bot_y + (box_h - h) / 2.0)

    canvas_obj.restoreState()


class HeaderEveryPageCanvas(canvas.Canvas):
    """Two-pass canvas that draws Kop Surat header on every page, with no footer."""
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        for state in self._saved_page_states:
            self.__dict__.update(state)
            draw_header_box(self)
            # Footer completely omitted as requested
            super().showPage()
        super().save()


def clean_signature_image(raw_bytes_or_path):
    """Load signature and ensure strokes are solid, bold, and crisp on transparent background."""
    if not raw_bytes_or_path:
        return None
    try:
        im = None
        if isinstance(raw_bytes_or_path, str):
            raw_str = raw_bytes_or_path.strip()
            if not raw_str:
                return None
            if "base64," in raw_str:
                base64_data = raw_str.split("base64,", 1)[1].strip()
                img_data = base64.b64decode(base64_data)
                im = Image.open(io.BytesIO(img_data)).convert("RGBA")
            elif os.path.isfile(raw_str):
                im = Image.open(raw_str).convert("RGBA")
            elif os.path.isfile(os.path.join(PROJECT_ROOT, raw_str)):
                im = Image.open(os.path.join(PROJECT_ROOT, raw_str)).convert("RGBA")
            elif os.path.isfile(os.path.join(PROJECT_ROOT, "storage", "app", "public", raw_str)):
                im = Image.open(os.path.join(PROJECT_ROOT, "storage", "app", "public", raw_str)).convert("RGBA")
            elif os.path.isfile(os.path.join(PROJECT_ROOT, "storage", "app", "private", raw_str)):
                im = Image.open(os.path.join(PROJECT_ROOT, "storage", "app", "private", raw_str)).convert("RGBA")
            else:
                try:
                    img_data = base64.b64decode(raw_str)
                    im = Image.open(io.BytesIO(img_data)).convert("RGBA")
                except Exception:
                    return None
        elif isinstance(raw_bytes_or_path, (bytes, bytearray)):
            im = Image.open(io.BytesIO(raw_bytes_or_path)).convert("RGBA")
        else:
            return None

        if im is None:
            return None

        w, h = im.size
        if w == 0 or h == 0:
            return None

        pixels = im.load()
        corners_transparent = all(
            pixels[pt][3] < 50 for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1))
        )

        if corners_transparent:
            for y in range(h):
                for x in range(w):
                    r, g, b, a = pixels[x, y]
                    if a > 20:
                        pixels[x, y] = (10, 15, 30, min(255, int(a * 1.5)))
                    else:
                        pixels[x, y] = (0, 0, 0, 0)
        else:
            corners = [pixels[pt][:3] for pt in ((0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1))]
            bg_r = sum(c[0] for c in corners) / len(corners)
            bg_g = sum(c[1] for c in corners) / len(corners)
            bg_b = sum(c[2] for c in corners) / len(corners)

            for y in range(h):
                for x in range(w):
                    r, g, b, a = pixels[x, y]
                    dist_bg = ((r - bg_r)**2 + (g - bg_g)**2 + (b - bg_b)**2)**0.5
                    if dist_bg < 45 or (r > 215 and g > 215 and b > 215):
                        pixels[x, y] = (0, 0, 0, 0)
                    else:
                        pixels[x, y] = (10, 15, 30, min(255, int(a * 1.5)))

        bbox = im.getbbox()
        if bbox:
            pad = 2
            im = im.crop((max(0, bbox[0] - pad), max(0, bbox[1] - pad), min(w, bbox[2] + pad), min(h, bbox[3] + pad)))

        out = io.BytesIO()
        im.save(out, format="PNG")
        return out.getvalue()
    except Exception:
        return None


def generate_pdf(data, output_path):
    """Build the FR.IA.08 PDF document with Kop on every page, larger font size, and no footer."""
    asesi_nama = data.get("asesi_nama", "Asesi")
    doc_title = f"FR.IA.08 ({asesi_nama})"

    # topMargin leaves clean space for repeating kop surat box (height 44pt starting at top 26pt, bot 70pt)
    doc = SimpleDocTemplate(
        output_path,
        pagesize=(PAGE_W, PAGE_H),
        leftMargin=MARGIN,
        rightMargin=MARGIN,
        topMargin=92.0,
        bottomMargin=30.0,
        title=doc_title,
        author="LSP PrO Indo",
    )

    base_styles = getSampleStyleSheet()

    # Typography matching Calibri with larger, highly readable font size
    s_normal = ParagraphStyle('DocNormal', parent=base_styles['Normal'], fontName='Cal', fontSize=10, leading=13)
    s_normal_bold = ParagraphStyle('DocNormalBold', parent=s_normal, fontName='Cal-B')
    s_center = ParagraphStyle('DocCenter', parent=s_normal, alignment=1)
    s_center_bold = ParagraphStyle('DocCenterBold', parent=s_normal_bold, alignment=1)
    s_italic_small = ParagraphStyle('DocItalicSmall', parent=s_normal, fontName='Cal-I', fontSize=8, leading=10, textColor=HexColor('#333333'))

    s_table_header = ParagraphStyle('DocTH', parent=s_normal_bold, fontSize=9.5, leading=12, alignment=1)
    s_table_cell = ParagraphStyle('DocCell', parent=s_normal, fontSize=9.5, leading=12.5)
    s_table_cell_bold = ParagraphStyle('DocCellBold', parent=s_normal_bold, fontSize=9.5, leading=12.5)
    s_table_cell_center = ParagraphStyle('DocCellCenter', parent=s_normal, fontSize=9.5, leading=12.5, alignment=1)

    s_kuk_item = ParagraphStyle('DocKukItem', parent=s_normal, fontSize=8.5, leading=11)

    story = []

    # =========================================================================
    # TABLE 1: HEADER TITLE (FR.IA.08. | CVP - CEKLIS VERIFIKASI PORTOFOLIO)
    # Borderless table, 12pt bold
    # =========================================================================
    t1_data = [
        [
            Paragraph("<font size='12' name='Cal-B'><b>FR.IA.08.</b></font>", s_normal_bold),
            Paragraph("<font size='12' name='Cal-B'><b>CVP - CEKLIS VERIFIKASI PORTOFOLIO</b></font>", s_normal_bold)
        ]
    ]
    t1_table = Table(t1_data, colWidths=[65, PRINTABLE_W - 65])
    t1_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ('LEFTPADDING', (0, 0), (-1, -1), 0),
        ('RIGHTPADDING', (0, 0), (-1, -1), 0),
    ]))
    story.append(t1_table)
    story.append(Spacer(1, 2))

    # =========================================================================
    # TABLE 2: SKEMA METADATA TABLE (Transparan, font 9.5-10pt)
    # =========================================================================
    skema_nama = data.get("skema_nama", "-")
    skema_nomor = data.get("skema_nomor", "-")
    tuk_nama = data.get("tuk_nama", "")
    tuk_type = data.get("tuk_type", "Sewaktu/Tempat Kerja/Mandiri*")
    tuk_display = f"{tuk_type} - {tuk_nama}" if tuk_nama and tuk_nama not in tuk_type else tuk_type
    asesor_nama = data.get("asesor_nama", "-")
    asesi_nama = data.get("asesi_nama", "-")
    tanggal = data.get("tanggal", "-")

    meta_data = [
        [
            Paragraph("Skema Sertifikasi<br/>(KKNI/Okupasi/Klaster)", s_table_cell),
            Paragraph("Judul", s_table_cell),
            Paragraph(":", s_center),
            Paragraph(f"<b>{skema_nama}</b>", s_table_cell),
        ],
        [
            "",
            Paragraph("Nomor", s_table_cell),
            Paragraph(":", s_center),
            Paragraph(f"<b>{skema_nomor}</b>", s_table_cell),
        ],
        [
            Paragraph("TUK", s_table_cell),
            "",
            Paragraph(":", s_center),
            Paragraph(tuk_display, s_table_cell),
        ],
        [
            Paragraph("Nama Asesor", s_table_cell),
            "",
            Paragraph(":", s_center),
            Paragraph(asesor_nama, s_table_cell),
        ],
        [
            Paragraph("Nama Asesi", s_table_cell),
            "",
            Paragraph(":", s_center),
            Paragraph(f"<b>{asesi_nama}</b>", s_table_cell),
        ],
        [
            Paragraph("Tanggal", s_table_cell),
            "",
            Paragraph(":", s_center),
            Paragraph(tanggal, s_table_cell),
        ],
    ]

    meta_table = Table(
        meta_data,
        colWidths=[150, 52, 12, PRINTABLE_W - 214]
    )
    meta_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 0), (0, 1)),  # Skema label spans row 0-1
        ('SPAN', (0, 2), (1, 2)),  # TUK spans cols 0-1
        ('SPAN', (0, 3), (1, 3)),  # Asesor spans cols 0-1
        ('SPAN', (0, 4), (1, 4)),  # Asesi spans cols 0-1
        ('SPAN', (0, 5), (1, 5)),  # Tanggal spans cols 0-1
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 2.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 2.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
        ('RIGHTPADDING', (0, 0), (-1, -1), 5),
    ]))
    story.append(meta_table)
    story.append(Spacer(1, 2))
    story.append(Paragraph("*Coret yang tidak perlu", s_italic_small))
    story.append(Spacer(1, 4))

    # =========================================================================
    # TABLE 3: PANDUAN BAGI ASESOR (Transparan, font 9.5-10.5pt)
    # =========================================================================
    panduan_text = """
    1. Verifikasi portofolio dapat dilakukan untuk keseluruhan unit kompetensi dalam skema sertifikasi atau dilakukan untuk masing-masing kelompok pekerjaan dalam satu skema sertifikasi.<br/>
    2. Isilah bukti portofolio sesuai ketentuan bukti berkualitas dan relevan dengan standar kompetensi kerja sebagaimana yang telah disepakati pada rekaman asesmen mandiri.<br/>
    3. Lakukan verifikasi portofolio berdasarkan aturan bukti.<br/>
    4. Berikan hasil verifikasi portofolio dengan memberi centang (√) pada kolom yang sesuai.<br/>
    5. Jika hasil verifikasi dokumen portofolio belum memenuhi aturan bukti maka asesor melanjutkan dengan metode tanya jawab pertanyaan wawancara dan/atau verifikasi pihak ketiga.
    """
    panduan_table = Table(
        [
            [Paragraph("<font size='10.5' name='Cal-B'><b>PANDUAN BAGI ASESOR</b></font>", s_normal_bold)],
            [Paragraph(panduan_text.strip(), s_table_cell)]
        ],
        colWidths=[PRINTABLE_W]
    )
    panduan_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 6),
        ('RIGHTPADDING', (0, 0), (-1, -1), 6),
    ]))
    story.append(panduan_table)
    story.append(Spacer(1, 6))

    # =========================================================================
    # TABLE 4: BUKTI PORTOFOLIO & ATURAN BUKTI (VATM - Transparan)
    # =========================================================================
    portfolios = data.get("portfolios", [])
    if not portfolios:
        portfolios = [
            {"id": "p1", "type": "Surat Pengalaman Kerja", "title": "Surat Pengalaman Kerja minimal 2 tahun di bidang otomotif"},
            {"id": "p2", "type": "Sertifikat Kompetensi", "title": "Sertifikat Pelatihan / Kompetensi Teknis Otomotif"},
            {"id": "p3", "type": "Work Order", "title": "Work Order (Surat Perintah Kerja) perbaikan kendaraan"},
        ]

    is_assessed = data.get("is_assessed", True)
    vatm_data = data.get("vatm")
    if not isinstance(vatm_data, dict):
        vatm_data = {}

    w_col0 = 195.27
    w_vatm = 41.0
    col_widths_vatm = [w_col0] + [w_vatm] * 8

    vatm_rows = [
        [
            Paragraph("<b>Bukti Portofolio:</b>", s_table_header),
            Paragraph("<b>Aturan Bukti</b>", s_table_header),
            "", "", "", "", "", "", ""
        ],
        [
            "",
            Paragraph("<b>Valid</b>", s_table_header), "",
            Paragraph("<b>Asli</b>", s_table_header), "",
            Paragraph("<b>Terkini</b>", s_table_header), "",
            Paragraph("<b>Memadai</b>", s_table_header), ""
        ],
        [
            "",
            Paragraph("Ya", s_table_header), Paragraph("Tidak", s_table_header),
            Paragraph("Ya", s_table_header), Paragraph("Tidak", s_table_header),
            Paragraph("Ya", s_table_header), Paragraph("Tidak", s_table_header),
            Paragraph("Ya", s_table_header), Paragraph("Tidak", s_table_header)
        ]
    ]

    for idx, p in enumerate(portfolios):
        p_id = str(p.get("id", idx))
        p_eval = vatm_data.get(p_id, vatm_data.get(str(idx), {})) if isinstance(vatm_data, dict) else {}

        chk = '<font name="Cal-B" size="11"><b>\u221a</b></font>'

        if is_assessed and isinstance(p_eval, dict):
            v_val = str(p_eval.get("v", "")).upper()
            a_val = str(p_eval.get("a", "")).upper()
            t_val = str(p_eval.get("t", "")).upper()
            m_val = str(p_eval.get("m", "")).upper()

            v_ya = chk if v_val == "YA" else ""
            v_tdk = chk if v_val in ["TIDAK", "TDK"] else ""

            a_ya = chk if a_val == "YA" else ""
            a_tdk = chk if a_val in ["TIDAK", "TDK"] else ""

            t_ya = chk if t_val == "YA" else ""
            t_tdk = chk if t_val in ["TIDAK", "TDK"] else ""

            m_ya = chk if m_val == "YA" else ""
            m_tdk = chk if m_val in ["TIDAK", "TDK"] else ""
        else:
            v_ya = ""
            v_tdk = ""
            a_ya = ""
            a_tdk = ""
            t_ya = ""
            t_tdk = ""
            m_ya = ""
            m_tdk = ""

        p_type = p.get("type", "Portofolio")
        p_title = p.get("title") or p.get("original_name") or f"Dokumen Bukti {idx + 1}"

        p_desc = f"<b>{p_type}:</b><br/>{p_title}"

        vatm_rows.append([
            Paragraph(p_desc, s_table_cell),
            Paragraph(v_ya, s_table_cell_center), Paragraph(v_tdk, s_table_cell_center),
            Paragraph(a_ya, s_table_cell_center), Paragraph(a_tdk, s_table_cell_center),
            Paragraph(t_ya, s_table_cell_center), Paragraph(t_tdk, s_table_cell_center),
            Paragraph(m_ya, s_table_cell_center), Paragraph(m_tdk, s_table_cell_center),
        ])

    vatm_table = Table(vatm_rows, colWidths=col_widths_vatm, repeatRows=3)
    vatm_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 0), (0, 2)),  # Bukti Portofolio spans 3 header rows
        ('SPAN', (1, 0), (8, 0)),  # Aturan Bukti spans all 8 columns
        ('SPAN', (1, 1), (2, 1)),  # Valid
        ('SPAN', (3, 1), (4, 1)),  # Asli
        ('SPAN', (5, 1), (6, 1)),  # Terkini
        ('SPAN', (7, 1), (8, 1)),  # Memadai
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
    ]))
    story.append(vatm_table)
    story.append(Spacer(1, 6))

    # =========================================================================
    # TABLE 5: TINDAK LANJUT KLARIFIKASI WAWANCARA (Transparan, font 9.5pt)
    # =========================================================================
    clarif_raw = data.get("clarification_elements") or []
    if isinstance(clarif_raw, (list, tuple, set)):
        clarification_elements = set(str(e) for e in clarif_raw)
    else:
        clarification_elements = set()
    units = data.get("units", [])

    w_chk = 38.0
    w_unit = 120.0
    w_elem = 68.0
    w_kuk = PRINTABLE_W - (w_chk + w_unit + w_elem)  # ~297.27 pt
    col_widths_clarif = [w_chk, w_unit, w_elem, w_kuk]

    clarif_rows = [
        [
            Paragraph("<b>Sebagai tindak lanjut dari hasil verifikasi bukti, substansi materi di bawah ini (no elemen yg di cek list) harus diklarifikasi selama wawancara:</b>", s_table_cell),
            "", "", ""
        ],
        [
            Paragraph("<b>Cek List</b>", s_table_header),
            Paragraph("<b>No. Unit Kompetensi</b>", s_table_header),
            Paragraph("<b>No. Elemen</b>", s_table_header),
            Paragraph("<b>Materi / substansi wawancara / KUK</b>", s_table_header)
        ]
    ]

    for u in units:
        u_code = u.get("code", "")
        u_title = u.get("title", "")
        u_desc = f"<b>{u_code}</b><br/><font size='8' color='#333333'>{u_title}</font>"

        elements = u.get("elements", [])
        if not elements:
            clarif_rows.append([
                Paragraph("-", s_table_cell_center),
                Paragraph(u_desc, s_table_cell),
                Paragraph("-", s_table_cell_center),
                Paragraph("<i>Tidak ada elemen</i>", s_table_cell)
            ])
            continue

        for elem in elements:
            elem_id = str(elem.get("id", ""))
            elem_num = elem.get("number", "")
            elem_name = elem.get("name") or elem.get("title") or f"Elemen {elem_num}"

            is_checked = is_assessed and ((elem_id in clarification_elements) or elem.get("is_checked", False))
            chk_icon = '<font name="Cal-B" size="11"><b>\u221a</b></font>' if is_checked else ''

            kuks = elem.get("kuks", [])
            kuk_lines = []
            if kuks:
                for k in kuks:
                    k_code = k.get("code", "")
                    k_stmt = k.get("statement", "")
                    kuk_lines.append(f"&bull; <b>{k_code}</b> {k_stmt}")
                kuk_content = f"<b>{elem_name}</b><br/>" + "<br/>".join(kuk_lines)
            else:
                kuk_content = f"<b>{elem_name}</b>"

            clarif_rows.append([
                Paragraph(chk_icon, s_table_cell_center),
                Paragraph(u_desc, s_table_cell),
                Paragraph(f"Elemen {elem_num}", s_table_cell_center),
                Paragraph(kuk_content, s_kuk_item)
            ])

    clarif_table = Table(clarif_rows, colWidths=col_widths_clarif, repeatRows=2)
    clarif_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 0), (3, 0)),  # Header span
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
    ]))
    story.append(clarif_table)
    story.append(Spacer(1, 6))

    # =========================================================================
    # TABLE 6 & TABLE 7: BUKTI TAMBAHAN, REKOMENDASI & TANDA TANGAN (STACKED)
    # Latar transparan, font 9.5-10pt
    # =========================================================================
    bottom_flowables = []

    # Table 6: Bukti tambahan & Rekomendasi Asesor (2 kolom persis docx)
    additional_evidence = (data.get("additional_evidence") if is_assessed else None) or ".................................................................."
    rec_val = str(data.get("recommendation", "")).upper() if is_assessed else ""
    is_k = is_assessed and (rec_val in ["K", "KOMPETEN"])
    is_bk = is_assessed and (rec_val in ["BK", "BELUM_KOMPETEN", "BELUM KOMPETEN"])

    box_k = '<font name="Cal-B" size="11"><b>[\u221a]</b></font>' if is_k else '<font name="Cal" size="9.5"><b>[&nbsp;&nbsp;]</b></font>'
    box_bk = '<font name="Cal-B" size="11"><b>[\u221a]</b></font>' if is_bk else '<font name="Cal" size="9.5"><b>[&nbsp;&nbsp;]</b></font>'

    rem_unit = data.get("remediation_unit") or "....."
    rem_elem = data.get("remediation_element") or "....."
    rem_kuk = data.get("remediation_kuk") or "......."

    t6_col0_w = 145.0
    t6_col1_w = PRINTABLE_W - t6_col0_w

    t6_data = [
        [
            Paragraph(f"<b>Bukti tambahan diperlukan pada unit / elemen kompetensi sebagai berikut:</b><br/>{additional_evidence}", s_table_cell),
            ""
        ],
        [
            Paragraph("<b>Rekomendasi Asesor:</b>", s_table_cell_bold),
            Paragraph(f"{box_k} Asesi telah memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <b>KOMPETEN</b>", s_table_cell)
        ],
        [
            "",
            Paragraph(
                f"{box_bk} Asesi belum memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <b>OBSERVASI LANGSUNG/KEGIATAN TERSTRUKTUR*)</b> pada:<br/>"
                f"&nbsp;&nbsp;&nbsp;&nbsp;Kelompok Pekerjaan Unit : {rem_unit}<br/>"
                f"&nbsp;&nbsp;&nbsp;&nbsp;Elemen: {rem_elem}<br/>"
                f"&nbsp;&nbsp;&nbsp;&nbsp;KUK : {rem_kuk}",
                s_table_cell
            )
        ]
    ]

    t6_table = Table(t6_data, colWidths=[t6_col0_w, t6_col1_w])
    t6_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 0), (1, 0)),  # Row 0 spans both columns
        ('SPAN', (0, 1), (0, 2)),  # Col 0 spans Row 1 and Row 2
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
        ('RIGHTPADDING', (0, 0), (-1, -1), 5),
    ]))
    bottom_flowables.append(t6_table)
    bottom_flowables.append(Spacer(1, 6))

    # Table 7: Tanda Tangan Stacked (3 kolom persis docx)
    asesi_sig_data = data.get("signature_asesi")
    asesor_sig_data = data.get("signature_asesor")
    tgl_asesi = data.get("tanggal_ttd_asesi") or tanggal
    tgl_asesor = data.get("tanggal_ttd_asesor") or (tanggal if data.get("is_assessed", True) else "........................")
    asesor_no_reg = data.get("asesor_no_reg") or "-"

    asesi_sig_clean = clean_signature_image(asesi_sig_data)
    if asesi_sig_clean:
        try:
            asesi_sig_flowable = RLImage(io.BytesIO(asesi_sig_clean), width=105, height=38)
        except Exception:
            asesi_sig_flowable = Spacer(1, 35)
    else:
        asesi_sig_flowable = Spacer(1, 35)

    asesor_sig_clean = clean_signature_image(asesor_sig_data) if is_assessed else None
    if asesor_sig_clean:
        try:
            asesor_sig_flowable = RLImage(io.BytesIO(asesor_sig_clean), width=105, height=38)
        except Exception:
            asesor_sig_flowable = Spacer(1, 35)
    else:
        asesor_sig_flowable = Spacer(1, 35)

    t7_col0_w = 125.0
    t7_col1_w = 12.0
    t7_col2_w = PRINTABLE_W - (t7_col0_w + t7_col1_w)

    t7_data = [
        # Row 0: Asesi :
        [Paragraph("<b>Asesi :</b>", s_normal_bold), "", ""],
        # Row 1: Nama
        [Paragraph("Nama", s_table_cell), Paragraph(":", s_center), Paragraph(f"<b>{asesi_nama}</b>", s_table_cell)],
        # Row 2: Tanda tangan dan Tanggal
        [
            Paragraph("Tanda tangan dan<br/>Tanggal", s_table_cell),
            Paragraph(":", s_center),
            [asesi_sig_flowable, Paragraph(f"<font size='8' color='#333333'>{tgl_asesi}</font>", s_normal)]
        ],
        # Row 3: Asesor :
        [Paragraph("<b>Asesor :</b>", s_normal_bold), "", ""],
        # Row 4: Nama
        [Paragraph("Nama", s_table_cell), Paragraph(":", s_center), Paragraph(f"<b>{asesor_nama}</b>", s_table_cell)],
        # Row 5: No. Reg
        [Paragraph("No. Reg", s_table_cell), Paragraph(":", s_center), Paragraph(asesor_no_reg, s_table_cell)],
        # Row 6: Tanda tangan dan Tanggal
        [
            Paragraph("Tanda tangan dan<br/>Tanggal", s_table_cell),
            Paragraph(":", s_center),
            [asesor_sig_flowable, Paragraph(f"<font size='8' color='#333333'>{tgl_asesor}</font>", s_normal)]
        ],
    ]

    t7_table = Table(t7_data, colWidths=[t7_col0_w, t7_col1_w, t7_col2_w])
    t7_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 0), (2, 0)),  # Asesi : spans row 0 cols 0-2
        ('SPAN', (0, 3), (2, 3)),  # Asesor : spans row 3 cols 0-2
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 2.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 2.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
        ('RIGHTPADDING', (0, 0), (-1, -1), 5),
    ]))
    bottom_flowables.append(t7_table)
    bottom_flowables.append(Spacer(1, 2))
    bottom_flowables.append(Paragraph("*Coret yang tidak perlu", s_italic_small))

    story.append(KeepTogether(bottom_flowables))

    doc.build(story, canvasmaker=HeaderEveryPageCanvas)

    try:
        import pymupdf as fitz
        fdoc = fitz.open(output_path)
        meta = fdoc.metadata or {}
        meta["title"] = doc_title
        meta["author"] = "LSP PrO Indo"
        fdoc.set_metadata(meta)
        fdoc.save(output_path, incremental=True, encryption=fitz.PDF_ENCRYPT_KEEP)
        fdoc.close()
    except Exception:
        pass

    return output_path


def main():
    if len(sys.argv) >= 3:
        json_path = sys.argv[1]
        out_pdf = sys.argv[2]
        with open(json_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        generate_pdf(data, out_pdf)
        print(f"Generated PDF successfully: {out_pdf}")
    else:
        # Standalone self-test
        sample_out = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_IA_08_generated.pdf")
        os.makedirs(os.path.dirname(sample_out), exist_ok=True)
        sample_data = {
            "skema_nama": "Maintenance Gasoline Engine Injection System",
            "skema_nomor": "02/LSP PrO Indo/I/2026",
            "tuk_nama": "TUK Otomotif Mandiri",
            "tuk_type": "Sewaktu/Tempat Kerja/Mandiri*",
            "asesor_nama": "Drs. Budi Santoso, M.T.",
            "asesor_no_reg": "MET.000.001234 2022",
            "asesi_nama": "Wahyu Andika Rahadi",
            "tanggal": "19 Oktober 2026",
            "tanggal_ttd_asesi": "19 Oktober 2026",
            "tanggal_ttd_asesor": "19 Oktober 2026",
            "portfolios": [
                {"id": "p1", "type": "Surat Pengalaman Kerja", "title": "Surat Pengalaman Kerja 2 Tahun Bengkel Pasundan"},
                {"id": "p2", "type": "Sertifikat Pelatihan", "title": "Sertifikat Tune-up EFI Engine"},
                {"id": "p3", "type": "Work Order", "title": "Kumpulan SPK Servis Berkala Mesin"},
            ],
            "vatm": {
                "p1": {"v": "YA", "a": "YA", "t": "YA", "m": "YA"},
                "p2": {"v": "YA", "a": "YA", "t": "YA", "m": "YA"},
                "p3": {"v": "YA", "a": "YA", "t": "YA", "m": "YA"},
            },
            "clarification_elements": ["1", "2"],
            "units": [
                {
                    "code": "G.45OTO01.001.2",
                    "title": "Melaksanakan K3 di Tempat Kerja",
                    "elements": [
                        {"id": "1", "number": 1, "name": "Mengikuti prosedur keselamatan kerja", "kuks": [{"code": "1.1", "statement": "APD digunakan sesuai standar."}]},
                        {"id": "2", "number": 2, "name": "Menghadapi situasi darurat", "kuks": [{"code": "2.1", "statement": "Prosedur tanggap darurat diikuti."}]}
                    ]
                }
            ],
            "additional_evidence": "Seluruh bukti portofolio memenuhi aturan VATM.",
            "recommendation": "K",
            "signature_asesi": None,
            "signature_asesor": None,
        }
        generate_pdf(sample_data, sample_out)
        print(f"Self-test sample generated: {sample_out}")


if __name__ == "__main__":
    main()
