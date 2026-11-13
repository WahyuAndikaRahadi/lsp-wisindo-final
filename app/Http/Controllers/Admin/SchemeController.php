<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\Scheme;
use App\Models\UnitElement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $scheme->load('competencyUnits.elements.kuks');

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
        ]);

        Scheme::create($validated);

        return redirect()->route('admin.schemes.index')
            ->with('success', 'Skema berhasil dibuat.');
    }

    public function edit(Scheme $scheme): View
    {
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
        ]);

        $scheme->update($validated);

        return redirect()->route('admin.schemes.show', $scheme->id)
            ->with('success', 'Skema berhasil diperbarui.');
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
     * Store a new competency unit under a scheme.
     */
    public function storeUnit(Request $request, Scheme $scheme): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'standard_reference' => 'nullable|string|max:100',
        ]);

        $maxSeq = $scheme->competencyUnits()->max('sequence') ?? 0;
        $scheme->competencyUnits()->create([
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
            'standard_reference' => $validated['standard_reference'] ?? 'SKKNI',
            'sequence' => $maxSeq + 1,
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
        ]);

        $unit->update([
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
            'standard_reference' => $validated['standard_reference'] ?? $unit->standard_reference,
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
