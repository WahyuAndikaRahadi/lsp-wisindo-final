<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class WilayahController extends Controller
{
    /**
     * Get all data from local cahyadsn/wilayah Kepmendagri 2025 dataset.
     *
     * @return array{source: string, provinces: array<int, array{code: string, name: string}>, regencies: array<string, array<int, array{code: string, name: string}>>}
     */
    private function getDataset(): array
    {
        return Cache::rememberForever('cahyadsn_wilayah_data', function (): array {
            $path = resource_path('data/wilayah_indonesia.json');
            if (! File::exists($path)) {
                return ['source' => '', 'provinces' => [], 'regencies' => []];
            }

            $json = File::get($path);

            return json_decode($json, true) ?: ['source' => '', 'provinces' => [], 'regencies' => []];
        });
    }

    /**
     * Get entire dataset for instant zero-latency client selection.
     */
    public function all(): JsonResponse
    {
        return response()->json($this->getDataset());
    }

    /**
     * List all 38 provinces in Indonesia.
     */
    public function provinces(): JsonResponse
    {
        $data = $this->getDataset();

        return response()->json($data['provinces'] ?? []);
    }

    /**
     * List all regencies / cities for a given province code.
     */
    public function regencies(Request $request, ?string $provinceCode = null): JsonResponse
    {
        $code = $provinceCode ?: $request->query('province_code');
        $data = $this->getDataset();
        $regencies = $code ? ($data['regencies'][$code] ?? []) : ($data['regencies'] ?? []);

        return response()->json($regencies);
    }
}
