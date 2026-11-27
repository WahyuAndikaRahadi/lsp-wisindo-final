<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetencyUnit;
use App\Models\JobGroup;
use App\Models\Kuk;
use App\Models\Scheme;
use App\Models\SchemeCommittee;
use App\Models\UnitElement;
use App\Services\SignatureHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchemeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Scheme::withCount('competencyUnits', 'questionBanks')->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $schemes = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('admin.schemes._table', compact('schemes'));
        }

        return view('admin.schemes.index', compact('schemes'));
    }

    public function show(Scheme $scheme): View
    {
        $scheme->load([
            'competencyUnits.jobGroup',
            'competencyUnits.elements.kuks',
            'jobGroups.competencyUnits',
            'penyusun',
            'validators',
        ]);

        return view('admin.schemes.show', compact('scheme'));
    }

    public function create(): View
    {
        return view('admin.schemes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:m_schemes,code',
            'name' => 'required|string|max:255',
            'scheme_type' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'passing_grade_theory' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'penyusun' => 'nullable|array',
            'penyusun.*.name' => 'nullable|string|max:255',
            'penyusun.*.no_met' => 'nullable|string|max:100',
            'penyusun.*.signature' => 'nullable|string',
            'penyusun.*.signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
            'validator' => 'nullable|array',
            'validator.*.name' => 'nullable|string|max:255',
            'validator.*.no_met' => 'nullable|string|max:100',
            'validator.*.signature' => 'nullable|string',
            'validator.*.signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $scheme = DB::transaction(function () use ($validated, $request) {
            $scheme = Scheme::create(collect($validated)->only([
                'code', 'name', 'scheme_type', 'version', 'description', 'passing_grade_theory', 'is_active',
            ])->toArray());

            $this->syncCommittees($scheme, $request);

            return $scheme;
        });

        return redirect()->route('admin.schemes.show', $scheme->id)
            ->with('success', 'Skema sertifikasi berhasil dibuat.');
    }

    public function edit(Scheme $scheme): View
    {
        $scheme->load(['penyusun', 'validators']);

        return view('admin.schemes.edit', compact('scheme'));
    }

    public function update(Request $request, Scheme $scheme): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('m_schemes')->ignore($scheme->id)],
            'name' => 'required|string|max:255',
            'scheme_type' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'passing_grade_theory' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'penyusun' => 'nullable|array',
            'penyusun.*.name' => 'nullable|string|max:255',
            'penyusun.*.no_met' => 'nullable|string|max:100',
            'penyusun.*.signature' => 'nullable|string',
            'penyusun.*.signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
            'validator' => 'nullable|array',
            'validator.*.name' => 'nullable|string|max:255',
            'validator.*.no_met' => 'nullable|string|max:100',
            'validator.*.signature' => 'nullable|string',
            'validator.*.signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        DB::transaction(function () use ($scheme, $validated, $request) {
            $scheme->update(collect($validated)->only([
                'code', 'name', 'scheme_type', 'version', 'description', 'passing_grade_theory', 'is_active',
            ])->toArray());

            $this->syncCommittees($scheme, $request);
        });

        return redirect()->route('admin.schemes.show', $scheme->id)
            ->with('success', 'Skema berhasil diperbarui.');
    }

    private function syncCommittees(Scheme $scheme, Request $request): void
    {
        foreach (['PENYUSUN' => 'penyusun', 'VALIDATOR' => 'validator'] as $role => $inputKey) {
            $rawItems = $request->input($inputKey, []);
            if (! is_array($rawItems)) {
                continue;
            }

            $scheme->committees()->where('role', $role)->delete();

            $sequence = 1;
            foreach ($rawItems as $origIndex => $itemData) {
                if (! is_array($itemData)) {
                    continue;
                }

                $name = trim((string) ($itemData['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $signature = $itemData['signature'] ?? null;

                // Check if file was uploaded for this item directly
                $file = $request->file("{$inputKey}.{$origIndex}.signature_file");
                if ($file && $file->isValid()) {
                    $signature = SignatureHelper::trimSignature($file->getRealPath(), 12);
                } elseif ($signature) {
                    $signature = SignatureHelper::trimSignature($signature, 12);
                }

                SchemeCommittee::create([
                    'scheme_id' => $scheme->id,
                    'role' => $role,
                    'name' => $name,
                    'no_met' => ! empty($itemData['no_met']) ? trim((string) $itemData['no_met']) : null,
                    'signature' => $signature,
                    'sequence' => $sequence++,
                ]);
            }
        }
    }

    public function destroy(Scheme $scheme): RedirectResponse
    {
        if ($scheme->schedules()->exists()) {
            $scheme->update(['is_active' => false]);

            return redirect()->route('admin.schemes.index')
                ->with('warning', 'Skema tidak dapat dihapus permanen karena sudah digunakan dalam jadwal asesmen. Status skema telah dinonaktifkan.');
        }

        $scheme->delete();

        return redirect()->route('admin.schemes.index')
            ->with('success', 'Skema berhasil dihapus.');
    }

    /**
     * Store a new job group (Kelompok Pekerjaan) under a scheme.
     */
    public function storeJobGroup(Request $request, Scheme $scheme): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'nullable|integer|min:1',
        ]);

        $maxSeq = $scheme->jobGroups()->max('sequence') ?? 0;
        $sequence = (int) ($validated['sequence'] ?? ($maxSeq + 1));

        JobGroup::create([
            'scheme_id' => $scheme->id,
            'sequence' => $sequence,
            'name' => trim($validated['name']),
        ]);

        return redirect()->route('admin.schemes.show', $scheme->id)
            ->with('success', 'Kelompok pekerjaan baru berhasil ditambahkan.');
    }

    /**
     * Update an existing job group (Kelompok Pekerjaan).
     */
    public function updateJobGroup(Request $request, JobGroup $jobGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('m_job_groups', 'sequence')
                    ->where('scheme_id', $jobGroup->scheme_id)
                    ->ignore($jobGroup->id),
            ],
        ]);

        $jobGroup->update([
            'name' => trim($validated['name']),
            'sequence' => (int) $validated['sequence'],
        ]);

        return redirect()->route('admin.schemes.show', $jobGroup->scheme_id)
            ->with('success', "Kelompok Pekerjaan {$jobGroup->sequence} berhasil diperbarui.");
    }

    /**
     * Delete a job group (units remain, unlinked).
     */
    public function destroyJobGroup(JobGroup $jobGroup): RedirectResponse
    {
        $schemeId = $jobGroup->scheme_id;
        $name = $jobGroup->name;

        $jobGroup->delete();

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', "Kelompok pekerjaan \"{$name}\" berhasil dihapus.");
    }

    /**
     * Store a new competency unit under a scheme.
     */
    public function storeUnit(Request $request, Scheme $scheme): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'standard_reference' => 'nullable|string|max:100',
            'sequence' => 'nullable|integer|min:1',
            'job_group_id' => [
                'nullable',
                'integer',
                Rule::exists('m_job_groups', 'id')->where('scheme_id', $scheme->id),
            ],
            'job_group_sequence' => 'nullable|integer|min:1',
        ]);

        $maxSeq = $scheme->competencyUnits()->max('sequence') ?? 0;
        $jobGroupId = ! empty($validated['job_group_id']) ? (int) $validated['job_group_id'] : null;
        $jobGroupSeq = $validated['job_group_sequence'] ?? null;
        if ($jobGroupId !== null && $jobGroupSeq === null) {
            $jobGroupSeq = ((int) CompetencyUnit::withoutGlobalScopes()->where('job_group_id', $jobGroupId)->max('job_group_sequence')) + 1;
        }

        $scheme->competencyUnits()->create([
            'job_group_id' => $jobGroupId,
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
            'standard_reference' => $validated['standard_reference'] ?? 'SKKNI',
            'sequence' => (int) ($validated['sequence'] ?? ($maxSeq + 1)),
            'job_group_sequence' => $jobGroupId !== null ? (int) $jobGroupSeq : null,
        ]);

        return redirect()->route('admin.schemes.show', $scheme->id)
            ->with('success', 'Unit kompetensi baru berhasil ditambahkan.');
    }

    /**
     * Update an existing competency unit.
     */
    public function updateUnit(Request $request, CompetencyUnit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'standard_reference' => 'nullable|string|max:100',
            'sequence' => 'nullable|integer|min:1',
            'job_group_id' => [
                'nullable',
                'integer',
                Rule::exists('m_job_groups', 'id')->where('scheme_id', $unit->scheme_id),
            ],
            'job_group_sequence' => 'nullable|integer|min:1',
        ]);

        $jobGroupId = ! empty($validated['job_group_id']) ? (int) $validated['job_group_id'] : null;
        $jobGroupSeq = $validated['job_group_sequence'] ?? null;
        if ($jobGroupId !== null && $jobGroupSeq === null) {
            $jobGroupSeq = ((int) CompetencyUnit::withoutGlobalScopes()->where('job_group_id', $jobGroupId)->max('job_group_sequence')) + 1;
        }

        $unit->update([
            'job_group_id' => $jobGroupId,
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
            'standard_reference' => $validated['standard_reference'] ?? $unit->standard_reference,
            'sequence' => (int) ($validated['sequence'] ?? $unit->sequence),
            'job_group_sequence' => $jobGroupId !== null ? (int) $jobGroupSeq : null,
        ]);

        return redirect()->route('admin.schemes.show', $unit->scheme_id)
            ->with('success', "Unit kompetensi {$unit->code} berhasil diperbarui.");
    }

    /**
     * Delete a competency unit.
     */
    public function destroyUnit(CompetencyUnit $unit): RedirectResponse
    {
        $schemeId = $unit->scheme_id;
        $code = $unit->code;

        $unit->delete();

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', "Unit kompetensi {$code} berhasil dihapus.");
    }

    /**
     * Store a new competency element under a unit.
     */
    public function storeElement(Request $request, CompetencyUnit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $maxSeq = $unit->elements()->max('sequence') ?? 0;
        $unit->elements()->create([
            'title' => trim($validated['title']),
            'sequence' => $maxSeq + 1,
        ]);

        return redirect()->route('admin.schemes.show', $unit->scheme_id)
            ->with('success', 'Elemen kompetensi berhasil ditambahkan.');
    }

    /**
     * Update an existing element.
     */
    public function updateElement(Request $request, UnitElement $element): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $element->update([
            'title' => trim($validated['title']),
        ]);

        $schemeId = $element->competencyUnit?->scheme_id;

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', 'Elemen kompetensi berhasil diperbarui.');
    }

    /**
     * Delete an element.
     */
    public function destroyElement(UnitElement $element): RedirectResponse
    {
        $schemeId = $element->competencyUnit?->scheme_id;
        $element->delete();

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', 'Elemen kompetensi berhasil dihapus.');
    }

    /**
     * Store a new KUK under an element.
     */
    public function storeKuk(Request $request, UnitElement $element): RedirectResponse
    {
        $validated = $request->validate([
            'code_number' => 'required|string|max:20',
            'statement' => 'required|string',
        ]);

        $maxSeq = $element->kuks()->max('sequence') ?? 0;
        $element->kuks()->create([
            'code_number' => trim($validated['code_number']),
            'statement' => trim($validated['statement']),
            'sequence' => $maxSeq + 1,
        ]);

        $schemeId = $element->competencyUnit?->scheme_id;

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', "Kriteria Unjuk Kerja {$validated['code_number']} berhasil ditambahkan.");
    }

    /**
     * Update a KUK.
     */
    public function updateKuk(Request $request, Kuk $kuk): RedirectResponse
    {
        $validated = $request->validate([
            'code_number' => 'required|string|max:20',
            'statement' => 'required|string',
        ]);

        $kuk->update([
            'code_number' => trim($validated['code_number']),
            'statement' => trim($validated['statement']),
        ]);

        $schemeId = $kuk->unitElement?->competencyUnit?->scheme_id;

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', "Kriteria Unjuk Kerja {$kuk->code_number} berhasil diperbarui.");
    }

    /**
     * Delete a KUK.
     */
    public function destroyKuk(Kuk $kuk): RedirectResponse
    {
        $schemeId = $kuk->unitElement?->competencyUnit?->scheme_id;
        $code = $kuk->code_number;
        $kuk->delete();

        return redirect()->route('admin.schemes.show', $schemeId)
            ->with('success', "Kriteria Unjuk Kerja {$code} berhasil dihapus.");
    }
}
