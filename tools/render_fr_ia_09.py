#!/usr/bin/env python3
"""
Generator FR.IA.09 (PW - Pertanyaan Wawancara) - LSP PrO Indo
Menghasilkan dokumen PDF FR.IA.09 resmi BNSP sesuai template master:
- Kop Surat Resmi LSP PrO Indo di setiap halaman (atas) dengan logo berukuran proporsional.
- Tanpa footer dan tanpa watermark.
- Font size proporsional, besar dan jelas sesuai template (Calibri 9.5 - 10pt, judul 12pt).
- Header Judul: FR.IA.09. | PW - PERTANYAAN WAWANCARA (12pt Bold).
- Metadata Skema, TUK, Asesor, Asesi, dan Tanggal (latar transparan/putih).
- Panduan Bagi Asesor (6 poin baku sesuai template).
- Tabel Bukti Portofolio (No. dan Dokumen Bukti Portofolio).
- Tabel Pertanyaan Wawancara (No., Pertanyaan, Kesimpulan Jawaban Asesi, Pencapaian Ya/Tidak).
- Tabel Tanda Tangan Asesi & Asesor bertingkat (stacked 3 kolom).
- Tabel Penyusun dan Validator dengan data tetap dan tanda tangan asli dari template docx.
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
    LOGO_PATH = os.path.join(PROJECT_ROOT, "public", "images", "logo-lsp-proindo.png")

IA09_IMAGES_DIR = os.path.join(PROJECT_ROOT, "public", "images", "muk", "ia09")
if not os.path.isdir(IA09_IMAGES_DIR):
    IA09_IMAGES_DIR = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets", "ia09")

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
    box_h = 58.0
    top_y = PAGE_H - 24.0  # 817.89
    bot_y = top_y - box_h  # 759.89

    # Outer border
    canvas_obj.setLineWidth(0.65)
    canvas_obj.setStrokeColor(black)
    canvas_obj.rect(x0, bot_y, box_w, box_h, fill=0, stroke=1)

    # Vertical divider line
    div_w = 78.0
    x_div = x0 + div_w
    canvas_obj.line(x_div, bot_y, x_div, top_y)

    # Logo in left compartment (preserving natural aspect ratio ~1.13, never squashed)
    if os.path.isfile(LOGO_PATH):
        try:
            im = Image.open(LOGO_PATH)
            im_w, im_h = im.size
            aspect = float(im_w) / float(im_h) if im_h > 0 else 1.13
            logo_h = 48.0
            logo_w = logo_h * aspect  # ~54.2 pt
            logo_x = x0 + (div_w - logo_w) / 2.0
            logo_y = bot_y + (box_h - logo_h) / 2.0
            canvas_obj.drawImage(LOGO_PATH, logo_x, logo_y, width=logo_w, height=logo_h, mask='auto')
        except Exception:
            pass

    # Title in right compartment
    header_html = '<font size="12" name="Cal"><b>L</b>embaga <b>S</b>ertifikasi <b>P</b>rofesi <b>P</b>raktisi <b>O</b>tomotif &nbsp;<b>LSP-PrO Indo</b></font>'
    s_kop = ParagraphStyle('HeaderKopBox', fontName='Cal', fontSize=12, leading=15, alignment=1)
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


def get_static_signature_flowable(img_filename, target_h=30.0, max_w=85.0):
    """Load a static signature image from the template assets and return an RLImage flowable."""
    search_paths = [
        os.path.join(IA09_IMAGES_DIR, img_filename),
        os.path.join(PROJECT_ROOT, "public", "images", "muk", "ia09", img_filename),
        os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "assets", "ia09", img_filename),
    ]
    img_path = next((p for p in search_paths if os.path.isfile(p)), None)
    if not img_path:
        return Spacer(1, target_h)

    try:
        im = Image.open(img_path)
        w, h = im.size
        if h <= 0:
            return Spacer(1, target_h)
        aspect = float(w) / float(h)
        render_w = target_h * aspect
        render_h = target_h
        if render_w > max_w:
            render_w = max_w
            render_h = render_w / aspect
        return RLImage(img_path, width=render_w, height=render_h)
    except Exception:
        return Spacer(1, target_h)


def generate_pdf(data, output_path):
    """Build the FR.IA.09 PDF document with Kop on every page, larger font size, and no footer."""
    doc = SimpleDocTemplate(
        output_path,
        pagesize=(PAGE_W, PAGE_H),
        leftMargin=MARGIN,
        rightMargin=MARGIN,
        topMargin=92.0,
        bottomMargin=30.0,
    )

    base_styles = getSampleStyleSheet()

    # Typography matching Calibri with clean, readable font sizes
    s_normal = ParagraphStyle('DocNormal', parent=base_styles['Normal'], fontName='Cal', fontSize=10, leading=13)
    s_normal_bold = ParagraphStyle('DocNormalBold', parent=s_normal, fontName='Cal-B')
    s_center = ParagraphStyle('DocCenter', parent=s_normal, alignment=1)
    s_center_bold = ParagraphStyle('DocCenterBold', parent=s_normal_bold, alignment=1)
    s_italic_small = ParagraphStyle('DocItalicSmall', parent=s_normal, fontName='Cal-I', fontSize=8, leading=10, textColor=HexColor('#333333'))

    s_table_header = ParagraphStyle('DocTH', parent=s_normal_bold, fontSize=9.5, leading=12, alignment=1)
    s_table_cell = ParagraphStyle('DocCell', parent=s_normal, fontSize=9.5, leading=12.5)
    s_table_cell_bold = ParagraphStyle('DocCellBold', parent=s_normal_bold, fontSize=9.5, leading=12.5)
    s_table_cell_center = ParagraphStyle('DocCellCenter', parent=s_normal, fontSize=9.5, leading=12.5, alignment=1)
    s_table_cell_italic = ParagraphStyle('DocCellItalic', parent=s_normal, fontName='Cal-I', fontSize=9.5, leading=12.5)

    story = []

    # =========================================================================
    # TABLE 1: HEADER TITLE (FR.IA.09. | PW - PERTANYAAN WAWANCARA)
    # Borderless table, 12pt bold
    # =========================================================================
    t1_data = [
        [
            Paragraph("<font size='12' name='Cal-B'><b>FR.IA.09.</b></font>", s_normal_bold),
            Paragraph("<font size='12' name='Cal-B'><b>PW - PERTANYAAN WAWANCARA</b></font>", s_normal_bold)
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
        colWidths=[142, 61, 15, PRINTABLE_W - 218]
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
    1. Pertanyaan wawancara dapat dilakukan untuk keseluruhan unit kompetensi dalam skema sertifikasi atau dilakukan untuk masing-masing kelompok pekerjaan dalam satu skema sertifikasi.<br/>
    2. Isilah bukti portofolio sesuai dengan bukti yang diminta pada skema sertifikasi sebagaimana yang telah dibuat pada FR-IA.08.<br/>
    3. Ajukan pertanyaan verifikasi portofolio untuk semua unit/elemen kompetensi yang di-checklist pada FR-IA.08.<br/>
    4. Ajukan pertanyaan kepada asesi sebagai tindak lanjut hasil verifikasi portofolio.<br/>
    5. Jika hasil verifikasi portofolio telah memenuhi aturan bukti maka pertanyaan wawancara tidak perlu dilakukan terhadap bukti tersebut.<br/>
    6. Tuliskan pencapaian atas setiap kesimpulan pertanyaan wawancara dengan cara mencentang (√) “Ya” atau “Tidak”.
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
    # TABLE 4: BUKTI PORTOFOLIO (Transparan, font 9.5pt)
    # =========================================================================
    portfolios = data.get("portfolios", [])
    if not portfolios:
        portfolios = [
            {"order": "1.", "title": "Surat pengalaman kerja di bagian Engine Junior technician minimal 2 tahun"},
            {"order": "2.", "title": "Sertifikat Kompetensi Engine Junior technician"},
            {"order": "3.", "title": "Work Order  ( surat perintah kerja )"},
            {"order": "4.", "title": "Log Book ( Buku catatan pekerjaan )"},
        ]

    t4_data = [
        [
            Paragraph("<b>No.</b>", s_table_header),
            Paragraph("<b>Bukti Portofolio</b>", s_table_header)
        ]
    ]

    for idx, p in enumerate(portfolios):
        p_order = p.get("order") or f"{idx + 1}."
        p_title = p.get("title") or p.get("name") or p.get("type", f"Bukti {idx + 1}")
        t4_data.append([
            Paragraph(f"<b>{p_order}</b>", s_table_cell_center),
            Paragraph(p_title, s_table_cell)
        ])

    t4_table = Table(t4_data, colWidths=[32, PRINTABLE_W - 32])
    t4_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
        ('RIGHTPADDING', (0, 0), (-1, -1), 5),
    ]))
    story.append(t4_table)
    story.append(Spacer(1, 6))

    # =========================================================================
    # TABLE 5: DAFTAR PERTANYAAN WAWANCARA (Transparan, font 9.5pt)
    # Repeat header on every page break
    # =========================================================================
    questions = data.get("questions", [])

    w_col0 = 30.0
    w_col1 = 212.0
    w_col2 = 191.27
    w_col3 = 45.0
    w_col4 = 45.0
    col_widths_q = [w_col0, w_col1, w_col2, w_col3, w_col4]

    t5_rows = [
        [
            Paragraph("<b>Daftar Pertanyaan Wawancara</b>", s_table_header),
            "",
            Paragraph("<b>Kesimpulan Jawaban Asesi</b>", s_table_header),
            Paragraph("<b>Pencapaian</b>", s_table_header),
            ""
        ],
        [
            "", "", "",
            Paragraph("<b>Ya</b>", s_table_header),
            Paragraph("<b>Tidak</b>", s_table_header)
        ]
    ]

    chk = '<font name="Cal-B" size="12"><b>\u221a</b></font>'

    for idx, q in enumerate(questions):
        q_order = str(q.get("order") or (idx + 1))
        evidence_ctx = q.get("evidence_context") or "Sesuai dengan bukti no. …… yang Anda ajukan, …….."
        q_text = q.get("question_text", "")
        q_desc = f"{evidence_ctx} {q_text}" if q_text else evidence_ctx

        ans_summary = q.get("candidate_answer_summary")
        if not ans_summary:
            ans_summary = "Asesi menjelaskan dengan benar dan sesuai SOP manual perbaikan."

        is_achieved_val = str(q.get("is_achieved", "YA")).upper()
        is_ya = is_achieved_val in ["YA", "1", "TRUE", "K"]
        is_tidak = is_achieved_val in ["TIDAK", "TDK", "0", "FALSE", "BK"]

        ya_icon = chk if is_ya else ""
        tidak_icon = chk if is_tidak else ""

        t5_rows.append([
            Paragraph(f"<b>{q_order}.</b>", s_table_cell_center),
            Paragraph(q_desc, s_table_cell),
            Paragraph(ans_summary, s_table_cell_italic),
            Paragraph(ya_icon, s_table_cell_center),
            Paragraph(tidak_icon, s_table_cell_center)
        ])

    t5_table = Table(t5_rows, colWidths=col_widths_q, repeatRows=2)
    t5_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 0), (1, 1)),  # Daftar Pertanyaan spans Col 0-1, Row 0-1
        ('SPAN', (2, 0), (2, 1)),  # Kesimpulan Jawaban spans Row 0-1
        ('SPAN', (3, 0), (4, 0)),  # Pencapaian spans Col 3-4, Row 0
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
    ]))
    story.append(t5_table)
    story.append(Spacer(1, 6))

    # =========================================================================
    # TABLE 6 & TABLE 7: TANDA TANGAN ASESI/ASESOR & PENYUSUN/VALIDATOR
    # Latar transparan, font 9.5-10pt
    # =========================================================================
    asesi_sig_data = data.get("signature_asesi")
    asesor_sig_data = data.get("signature_asesor")
    tgl_asesi = data.get("tanggal_ttd_asesi") or tanggal
    tgl_asesor = data.get("tanggal_ttd_asesor") or tanggal
    asesor_no_reg = data.get("asesor_no_reg") or "-"

    asesi_sig_clean = clean_signature_image(asesi_sig_data)
    if asesi_sig_clean:
        try:
            asesi_sig_flowable = RLImage(io.BytesIO(asesi_sig_clean), width=105, height=36)
        except Exception:
            asesi_sig_flowable = Spacer(1, 35)
    else:
        asesi_sig_flowable = Spacer(1, 35)

    asesor_sig_clean = clean_signature_image(asesor_sig_data)
    if asesor_sig_clean:
        try:
            asesor_sig_flowable = RLImage(io.BytesIO(asesor_sig_clean), width=105, height=36)
        except Exception:
            asesor_sig_flowable = Spacer(1, 35)
    else:
        asesor_sig_flowable = Spacer(1, 35)

    t6_col0_w = 125.0
    t6_col1_w = 12.0
    t6_col2_w = PRINTABLE_W - (t6_col0_w + t6_col1_w)

    t6_data = [
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

    t6_table = Table(t6_data, colWidths=[t6_col0_w, t6_col1_w, t6_col2_w])
    t6_table.setStyle(TableStyle([
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

    # =========================================================================
    # TABLE 7: PENYUSUN DAN VALIDATOR (Static from template docx)
    # =========================================================================
    sig1 = get_static_signature_flowable("image1.png", target_h=28.0)
    sig2 = get_static_signature_flowable("image2.png", target_h=28.0)
    sig3 = get_static_signature_flowable("image3.png", target_h=28.0)
    sig4 = get_static_signature_flowable("image4.png", target_h=28.0)
    sig5 = get_static_signature_flowable("image5.png", target_h=28.0)

    t7_col0_w = 88.0
    t7_col1_w = 27.0
    t7_col2_w = 199.27
    t7_col3_w = 113.0
    t7_col4_w = 96.0

    t7_data = [
        # Row 0: Header
        [
            Paragraph("<b>STATUS</b>", s_table_header),
            Paragraph("<b>NO</b>", s_table_header),
            Paragraph("<b>NAMA</b>", s_table_header),
            Paragraph("<b>NOMOR MET</b>", s_table_header),
            Paragraph("<b>TANDA TANGAN DAN TANGGAL</b>", s_table_header)
        ],
        # Row 1: Penyusun 1 - Ridwan
        [
            Paragraph("<b>PENYUSUN</b>", s_table_cell_center),
            Paragraph("1", s_table_cell_center),
            Paragraph("<b>RIDWAN</b>", s_table_cell),
            Paragraph("000.008265 2025", s_table_cell_center),
            [sig1, Paragraph("<font size='8' color='#333333'>05 Januari 2026</font>", s_table_cell_center)]
        ],
        # Row 2: Penyusun 2 - Randi Ardiansyah
        [
            "",
            Paragraph("2", s_table_cell_center),
            Paragraph("<b>RANDI ARDIANSYAH</b>", s_table_cell),
            Paragraph("000.002498 2013", s_table_cell_center),
            [sig2, Paragraph("<font size='8' color='#333333'>05 Januari 2026</font>", s_table_cell_center)]
        ],
        # Row 3: Validator 1 - Saptono
        [
            Paragraph("<b>VALIDATOR</b>", s_table_cell_center),
            Paragraph("1", s_table_cell_center),
            Paragraph("<b>SAPTONO</b>", s_table_cell),
            Paragraph("000.003672 2016", s_table_cell_center),
            [sig3, Paragraph("<font size='8' color='#333333'>05 Januari 2026</font>", s_table_cell_center)]
        ],
        # Row 4: Validator 2 - Karyanto
        [
            "",
            Paragraph("2", s_table_cell_center),
            Paragraph("<b>KARYANTO</b>", s_table_cell),
            Paragraph("000.008260 2025", s_table_cell_center),
            [sig4, Paragraph("<font size='8' color='#333333'>05 Januari 2026</font>", s_table_cell_center)]
        ],
        # Row 5: Validator 3 - Gunadi
        [
            "",
            Paragraph("3", s_table_cell_center),
            Paragraph("<b>GUNADI</b>", s_table_cell),
            Paragraph("000.000459 2007", s_table_cell_center),
            [sig5, Paragraph("<font size='8' color='#333333'>05 Januari 2026</font>", s_table_cell_center)]
        ],
    ]

    t7_table = Table(t7_data, colWidths=[t7_col0_w, t7_col1_w, t7_col2_w, t7_col3_w, t7_col4_w])
    t7_table.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.5, black),
        ('INNERGRID', (0, 0), (-1, -1), 0.5, black),
        ('SPAN', (0, 1), (0, 2)),  # PENYUSUN spans Row 1-2
        ('SPAN', (0, 3), (0, 5)),  # VALIDATOR spans Row 3-5
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 2.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 2.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
    ]))

    footer_flowables = [
        t6_table,
        Spacer(1, 2),
        Paragraph("*Coret yang tidak perlu", s_italic_small),
        Spacer(1, 6),
        t7_table
    ]

    story.append(KeepTogether(footer_flowables))

    doc.build(story, canvasmaker=HeaderEveryPageCanvas)
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
        sample_out = os.path.join(PROJECT_ROOT, "tests", "Fixtures", "muk", "FR_IA_09_generated.pdf")
        os.makedirs(os.path.dirname(sample_out), exist_ok=True)
        sample_data = {
            "skema_nama": "Engine Junior Technician",
            "skema_nomor": "01/LSP PrO Indo/I/2026",
            "tuk_nama": "SMKS Pasundan Subang",
            "tuk_type": "Sewaktu/Tempat Kerja/Mandiri*",
            "asesor_nama": "Alfian",
            "asesor_no_reg": "MET.000.001234 2022",
            "asesi_nama": "Asesi Ani Rahayu",
            "tanggal": "24 November 2026",
            "tanggal_ttd_asesi": "24 November 2026",
            "tanggal_ttd_asesor": "24 November 2026",
            "portfolios": [
                {"order": "1.", "title": "Surat pengalaman kerja di bagian Engine Junior technician minimal 2 tahun"},
                {"order": "2.", "title": "Sertifikat Kompetensi Engine Junior technician"},
                {"order": "3.", "title": "Work Order  ( surat perintah kerja )"},
                {"order": "4.", "title": "Log Book ( Buku catatan pekerjaan )"},
            ],
            "questions": [
                {
                    "order": 1,
                    "evidence_context": "Sesuai dengan bukti no. …… yang Anda ajukan, ……...",
                    "question_text": "Sesuai dengan pengalaman saudara , ceritakan kecelakaan kerja akibat tidak menggunakan APD saat bekerja",
                    "candidate_answer_summary": "Asesi menjelaskan perlunya kacamata dan sepatu safety saat menangani mesin panas guna mencegah luka bakar dan benturan.",
                    "is_achieved": "YA"
                },
                {
                    "order": 2,
                    "evidence_context": "Sesuai dengan bukti no. …… yang Anda ajukan, ……...",
                    "question_text": "Jelaskan akibat yang terjadi Jika Peralatan dan perlengkapan digunakan tidak sesuai dengan manual perbaikan.",
                    "candidate_answer_summary": "Bisa merusak baut silinder, torsi tidak presisi, dan membahayakan keselamatan mekanik.",
                    "is_achieved": "YA"
                },
                {
                    "order": 3,
                    "evidence_context": "Sesuai dengan bukti no. …… yang Anda ajukan, ……...",
                    "question_text": "Jelaskan SOP menyimpan Data informasi dan bukti komunikasi.",
                    "candidate_answer_summary": "Data dicatat dalam formulir service record dan diinput pada sistem bengkel terpusat.",
                    "is_achieved": "YA"
                }
            ],
            "signature_asesi": None,
            "signature_asesor": None,
        }
        generate_pdf(sample_data, sample_out)
        print(f"Self-test sample generated: {sample_out}")


if __name__ == "__main__":
    main()
