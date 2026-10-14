# LAPORAN PEMAHAMAN PROYEK: Digitalisasi Asesmen LSP PrO Indo

## 1. APA ITU LSP PrO INDO

LSP PrO Indo (Lembaga Sertifikasi Profesi Praktisi Otomotif Indonesia) = LSP Pihak Ketiga (P3) berlisensi BNSP. Menyelenggarakan uji kompetensi profesi teknisi otomotif, utamanya siswa SMK dan teknisi bengkel.

Kantor: Ruko Blessing Center, Jl. Siput Raya No. 38, Bekasi Selatan.
Website: https://www.lspproindo.id/

7 skema sertifikasi:
1. Engine Junior Technician (Okupasi) - roda 4
2. Klaster Maintenance Gasoline Engine Injection - roda 4
3. Periodical Maintenance Electrified Vehicles (EV) - roda 4
4. Memperbaiki Panel Bodi Kendaraan (Klaster) - roda 4
5. Mekanik Junior (Okupasi) - roda 2
6. Servis Sepeda Motor Listrik (Klaster) - roda 2
7. Klaster Servis Sepeda Motor Injeksi - roda 2

## 2. TEMUAN DARI DOKUMEN MUK

### Konfirmasi: APL01 & APL02 SAMA strukturnya di semua skema
- FR.APL.01 = format identik (field data diri, pendidikan, pekerjaan). Hanya nama skema & nomor berbeda.
- FR.APL.02 = format identik (tabel K/BK per KUK + bukti relevan). KONTEN BERBEDA per skema karena unit kompetensi & KUK berbeda.
- Kesimpulan: Template APL01 & APL02 = satu template universal, data KUK diisi dari database per skema.

### Instrumen yang PERLU dedicated pages (per permintaan user):
- FR.IA.01 (Ceklis Observasi Aktivitas Praktik) - PER SKEMA, konten KUK berbeda
- FR.IA.02 (Tugas Praktik Demonstrasi) - PER SKEMA
- FR.IA.03 (Pertanyaan Mendukung Observasi / PMO) - PER SKEMA
- FR.IA.05 (A: Soal PG, B: Kunci, C: Lembar Jawaban) - PER SKEMA (CBT auto-grading)
- FR.AK.01 (Persetujuan Asesmen dan Kerahasiaan) - PER SKEMA
- FR.AK.07 (Ceklis Penyesuaian Wajar dan Beralasan) - format sama, applied per asesi

### Instrumen yang TIDAK perlu dedicated pages (user bilang skip):
- FR.MAPA.01, FR.MAPA.02, FR.AK.02, FR.AK.03, FR.AK.04, FR.AK.05, FR.AK.06
- Cover MUK, Daftar Periksa, Surat Pernyataan
- Berita Acara, Daftar Hadir (ini export PDF saja)

## 3. PEMAHAMAN FLOW PER ROLE

### ASESI FLOW:
```
Register/Login
    -> Data Diri page (biodata lengkap + drawable signature canvas)
    -> Dashboard
    -> Menu Jadwal
        -> Input kode jadwal (text field) -> auto join jadwal
        -> Jadwal list (cards with details + "Masuk Jadwal Uji" button)
    -> Klik masuk jadwal uji
        -> Page 1: APL01 (auto-fetch data diri) + Upload Bukti section
            - Bukti Persyaratan Dasar: min 1 dari 4 opsi (Ijazah SMK/Surat Siswa Aktif/Ijazah SMA+Sertifikat/Surat Pengalaman)
            - Bukti Administratif: KTP + Pas Foto (wajib keduanya)
          + APL02 (tabel asesmen mandiri, K/BK radio, dropdown bukti per KUK)
            - "Semua K" / "Semua BK" button di atas tabel
            - Default bukti selector beside the buttons
          + Signature (auto-fetched)
          + "Simpan & Lanjutkan" button
        -> GATE: Menunggu approval asesor (cannot proceed until APL approved)
        -> Page 2 (after approved): Ruang Uji
            - List of buttons for selected FR.IA instruments
            - FR.IA.05: Asesi MENGERJAKAN soal CBT (auto-graded)
            - FR.IA.01/02/03: Asesi VIEW ONLY + feedback field + sign
            - Progress indicator component (stepper based on selected instruments)
        -> After all FR.IAs done -> complete assessment -> back to jadwal page
        -> STATE RESTORATION: if exit mid-assessment, resume to last page
```

### ASESOR FLOW:
```
Register/Login
    -> Signature setup
    -> Jadwal page (jadwals assigned to asesor)
    -> Enter jadwal uji
        -> List of asesi + button to review each
        -> Review APL01 & APL02 per asesi
            - 3 actions: Approve / Minta Revisi (with note) / Tolak
            - GATE: asesi can't continue until approved
        -> FR.AK.01 (sesuai skema, from this point progress indicator active)
        -> FR.AK.07 (with asesi selector, can apply batch to multiple asesi)
        -> Enter Ruang Uji (same FR.IA buttons as asesi view)
            - FR.IA.01: ASESOR fills ceklis observasi
            - FR.IA.02: ASESOR fills tugas praktik
            - FR.IA.03: ASESOR fills pertanyaan PMO
            - FR.IA.05: ASESOR views auto-graded results only
            - Each instrument needs asesi sign + feedback
        -> After ALL instruments of ALL asesi signed -> finish assessment
        -> Back to jadwal page, status = FINISHED
```

### ADMIN FLOW:
```
Register/Login
    -> Signature setup
    -> Menu Jadwal
        -> List jadwal
        -> Buat jadwal baru:
            - Nama jadwal, Skema (dropdown), TUK (text field), Asesor (multi-select)
            - Tanggal, Jam mulai/selesai
            - Instrumen dropdown (multi-select FR.IAs, FR.AKs)
            - Kode jadwal (text + generate button)
            - Catatan
    -> Menu Dokumen MUK
        -> List jadwal (each with export buttons):
            - Sertifikat, BA, Daftar Hadir Asesor, Daftar Hadir Asesi, Excel data diri
        -> Jadwal dropdown -> list asesi
        -> Asesi dropdown -> export buttons per PDF (APL01, APL02, MAPA, etc)
        -> PDF = template for now, controller later
    -> Switch Role menu
        -> List active asesor & asesi
        -> Proxy into their account
```

### SUPERADMIN FLOW (implied, left to my discretion):
```
Everything Admin can do +
    -> User Management (CRUD all users, activate/deactivate)
    -> Scheme Management (CRUD 7 schemes, units, elements, KUK)
    -> Question Bank Management (CRUD soal CBT per scheme)
    -> TUK Management (optional, since TUK is text field)
    -> System settings
```

## 4. ARSITEKTUR DATABASE YANG DIRENCANAKAN

Berdasarkan spec existing + user requirements + seeder data, berikut revisi:

### Perubahan dari spec lama:
1. **Roles diperluas**: SUPER_ADMIN, ADMIN_LSP, ASESOR, ASESI, KOMITE_TEKNIS, TUK_ADMIN
   (User explicitly provides these roles in seeder data)
2. **m_users perlu fields tambahan**: assessor_registration_number, is_committee_head, tuk_id
3. **m_tuks table needed**: karena ada TUK_ADMIN role dengan tuk_id reference
4. **status field di users**: ACTIVE (dari seeder data)

### Tabel Master (prefix m_):

| Tabel | Fungsi |
|-------|--------|
| m_users | Semua user (6 roles) |
| m_user_profiles | Biodata lengkap + signature |
| m_tuks | Tempat Uji Kompetensi |
| m_schemes | 7 skema sertifikasi |
| m_competency_units | Unit kompetensi per skema |
| m_unit_elements | Elemen per unit kompetensi |
| m_kuks | Kriteria Unjuk Kerja per elemen |
| m_question_banks | Bank soal CBT FR.IA.05 per skema |

### Tabel Transaksi (prefix t_):

| Tabel | Fungsi |
|-------|--------|
| t_schedules | Jadwal uji kompetensi |
| t_schedule_assessors | Pivot asesor-jadwal |
| t_assessments | Record asesmen per asesi per jadwal |
| t_assessment_proofs | Upload bukti APL01 |
| t_assessment_apl02_items | Jawaban asesmen mandiri per KUK |
| t_assessment_ak07_items | Penyesuaian wajar per asesi |
| t_assessment_ia_responses | Jawaban/observasi/skor per instrumen |

### Detail m_users (revised):

```
id                          BIGINT PK
full_name                   VARCHAR(255)
email                       VARCHAR(255) UNIQUE
phone                       VARCHAR(30) NULLABLE
password                    VARCHAR(255)
role                        ENUM: SUPER_ADMIN, ADMIN_LSP, ASESOR, ASESI, KOMITE_TEKNIS, TUK_ADMIN
status                      ENUM: ACTIVE, INACTIVE, SUSPENDED (default ACTIVE)
assessor_registration_number VARCHAR(50) NULLABLE (only for ASESOR)
is_committee_head           BOOLEAN DEFAULT false (only for KOMITE_TEKNIS)
tuk_id                      BIGINT FK -> m_tuks.id NULLABLE (only for TUK_ADMIN)
remember_token              VARCHAR(100) NULLABLE
timestamps
```

### Detail m_tuks:

```
id          BIGINT PK
name        VARCHAR(255)
address     TEXT NULLABLE
phone       VARCHAR(30) NULLABLE
is_active   BOOLEAN DEFAULT true
timestamps
```

## 5. USER SEEDER YANG DIMINTA

User memberikan 24 akun seeder:
- 1 SUPER_ADMIN: lsp.proindo@gmail.com
- 1 ADMIN_LSP: adminlsp@gmail.com
- 1 KOMITE_TEKNIS (head): komite@gmail.com
- 14 ASESOR: asesor.randi@gmail.com s.d. asesor.alfian@gmail.com (with registration numbers)
- 1 TUK_ADMIN: tukadmin@gmail.com (linked to TUK Cikarang)
- 2 KOMITE_TEKNIS (members): komite2@gmail.com, komite3@gmail.com
- 3 ASESI (dummy): asesi1@gmail.com s.d. asesi3@gmail.com
- Password semua: @Kompeten86

Seeder perlu create TUK "Cikarang" dulu sebelum assign ke TUK_ADMIN.

## 6. KOMPONEN UI KHUSUS YANG DIMINTA

1. **Drawable Signature Component** - HTML5 Canvas, save as base64 PNG, reusable across pages
2. **Progress Indicator / Stepper** - Vertical stepper showing assessment instruments, highlights current step, based on selected instruments from jadwal
3. **APL02 Table** - Simple table with K/BK radio columns, dropdown bukti per KUK, "Semua K"/"Semua BK" bulk buttons

## 7. TECH STACK

- Laravel (latest stable, presumably 11 or 12)
- Blade templates (frontend)
- Tailwind CSS
- Alpine.js / Vanilla JS (minimal, zero bloat)
- MySQL
- Clean code principles, no over-engineering

## 8. PERTANYAAN KONFIRMASI SEBELUM IMPLEMENTASI

1. APL02 kontennya BERBEDA per skema (unit kompetensi & KUK berbeda) tapi TEMPLATE sama - confirmed dari dokumen. System will render APL02 dynamically from database KUK data per scheme.

2. FR.IA.01, FR.IA.02, FR.IA.03 kontennya BERBEDA per skema - confirmed. Each scheme has different unit kompetensi, elemen, and KUK. The dedicated pages will render from database content.

3. FR.IA.05 soalnya BERBEDA per skema - confirmed. Each scheme has its own question bank. CBT auto-grading.

4. FR.AK.01 structure sama tapi nama skema berbeda - confirmed. Template same, scheme name dynamic.

5. FR.AK.07 structure sama across schemes - confirmed. Applied per asesi with batch capability.

6. KOMITE_TEKNIS dan TUK_ADMIN roles: User's seeder includes these but original spec said to eliminate them. Since user explicitly provides these accounts, I will include the roles in the system but keep them minimal (no complex workflows for now).

## 9. FILE STRUCTURE PLAN (LARAVEL)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthController.php
│   │   ├── Asesi/
│   │   │   ├── ProfileController.php
│   │   │   ├── JadwalController.php
│   │   │   ├── AplController.php
│   │   │   └── RuangUjiController.php
│   │   ├── Asesor/
│   │   │   ├── JadwalController.php
│   │   │   ├── AplReviewController.php
│   │   │   ├── Ak07Controller.php
│   │   │   ├── InstrumentController.php
│   │   │   └── FinalizationController.php
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── JadwalController.php
│   │       ├── DokumenMukController.php
│   │       ├── ProxyController.php
│   │       ├── UserController.php
│   │       ├── SchemeController.php
│   │       └── QuestionBankController.php
│   └── Middleware/
│       ├── RoleMiddleware.php
│       ├── CheckAplApproved.php
│       └── TrackLastStep.php
├── Models/
│   ├── User.php
│   ├── UserProfile.php
│   ├── Tuk.php
│   ├── Scheme.php
│   ├── CompetencyUnit.php
│   ├── UnitElement.php
│   ├── Kuk.php
│   ├── QuestionBank.php
│   ├── Schedule.php
│   ├── ScheduleAssessor.php
│   ├── Assessment.php
│   ├── AssessmentProof.php
│   ├── AssessmentApl02Item.php
│   ├── AssessmentAk07Item.php
│   └── AssessmentIaResponse.php
├── Services/
│   ├── AutoGradeService.php
│   └── MukExportService.php
database/
├── migrations/
│   ├── create_m_tuks_table.php
│   ├── create_m_users_table.php (override default)
│   ├── create_m_user_profiles_table.php
│   ├── create_m_schemes_table.php
│   ├── create_m_competency_units_table.php
│   ├── create_m_unit_elements_table.php
│   ├── create_m_kuks_table.php
│   ├── create_m_question_banks_table.php
│   ├── create_t_schedules_table.php
│   ├── create_t_schedule_assessors_table.php
│   ├── create_t_assessments_table.php
│   ├── create_t_assessment_proofs_table.php
│   ├── create_t_assessment_apl02_items_table.php
│   ├── create_t_assessment_ak07_items_table.php
│   └── create_t_assessment_ia_responses_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── TukSeeder.php
    └── UserSeeder.php
resources/views/
├── layouts/app.blade.php
├── components/
│   ├── signature-pad.blade.php
│   ├── progress-stepper.blade.php
│   └── ...
├── auth/
├── asesi/
│   ├── profile.blade.php
│   ├── jadwal/
│   ├── apl.blade.php
│   └── ruang-uji/
├── asesor/
│   ├── jadwal/
│   ├── review-apl.blade.php
│   ├── ak07.blade.php
│   └── instruments/
└── admin/
    ├── dashboard.blade.php
    ├── jadwal/
    ├── dokumen-muk.blade.php
    ├── proxy.blade.php
    ├── users/
    ├── schemes/
    └── question-bank/
```

## 10. RINGKASAN

Saya memahami bahwa ini adalah proyek fresh (belum ada kode Laravel). Saya perlu:
1. Scaffold Laravel project baru
2. Buat semua migrations sesuai schema di atas
3. Buat semua models dengan relationships
4. Buat UserSeeder dengan 24 akun yang diberikan (+ TUK Cikarang)
5. Buat controllers, routes, middleware
6. Buat Blade views untuk seluruh flow
7. Buat dedicated pages per instrument per scheme (FR.IA.01-03, FR.IA.05, FR.AK.01, FR.AK.07)
8. Signature component, progress stepper, APL02 table component
9. State restoration logic
10. Auto-grading CBT service
11. Export PDF templates (placeholder for now)
12. Proxy/impersonation feature

Prinsip: Clean code, zero bloat, Laravel conventions, Tailwind + Alpine.js minimal.
