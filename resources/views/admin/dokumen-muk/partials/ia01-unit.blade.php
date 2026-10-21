{{-- Unit Header Box --}}
<table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 8px; margin-bottom: 3px; font-size: 8pt; page-break-inside: avoid;">
    <tr>
        <td style="width: 25%; border-right: 1px solid #000; padding: 4px 6px; font-weight: bold; vertical-align: middle;">
            Unit Kompetensi {{ $unitNumber }}
        </td>
        <td style="width: 12%; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 6px; font-weight: bold;">
            Kode Unit
        </td>
        <td style="width: 2%; border-bottom: 1px solid #000; padding: 3px 2px; text-align: center;">:</td>
        <td style="border-bottom: 1px solid #000; padding: 3px 6px; font-weight: bold; font-family: monospace;">
            {{ $unit->code }}
        </td>
    </tr>
    <tr>
        <td style="border-right: 1px solid #000; padding: 4px 6px;"></td>
        <td style="border-right: 1px solid #000; padding: 3px 6px; font-weight: bold;">
            Judul Unit
        </td>
        <td style="padding: 3px 2px; text-align: center;">:</td>
        <td style="padding: 3px 6px; font-weight: bold;">
            {{ $unit->title }}
        </td>
    </tr>
</table>

{{-- 7-Column Observation Table --}}
<table class="bnsp-table" style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 8pt; margin-bottom: 10px;">
    <thead>
        <tr style="background-color: #f1f5f9; text-align: center; font-weight: bold;">
            <th style="border: 1px solid #000; padding: 4px; width: 4%;" rowspan="2">No.</th>
            <th style="border: 1px solid #000; padding: 4px; width: 20%;" rowspan="2">Elemen</th>
            <th style="border: 1px solid #000; padding: 4px; width: 38%;" rowspan="2">Kriteria Unjuk Kerja</th>
            <th style="border: 1px solid #000; padding: 4px; width: 18%;" rowspan="2">Standar Industri atau Tempat Kerja</th>
            <th style="border: 1px solid #000; padding: 4px; width: 14%;" colspan="2">Pencapaian</th>
            <th style="border: 1px solid #000; padding: 4px; width: 6%;" rowspan="2">Penilaian Lanjut</th>
        </tr>
        <tr style="background-color: #f1f5f9; text-align: center; font-weight: bold;">
            <th style="border: 1px solid #000; padding: 3px; width: 7%;">Ya</th>
            <th style="border: 1px solid #000; padding: 3px; width: 7%;">Tidak</th>
        </tr>
    </thead>
    <tbody>
        @foreach($unit->elements as $elIdx => $element)
            @php $kukCount = count($element->kuks); @endphp
            @foreach($element->kuks as $kIdx => $kuk)
                @php
                    $kukRes = $savedKuks[$kuk->id]['result'] ?? 'K';
                    $isYa = in_array(strtoupper((string)$kukRes), ['K', 'YA', '1', 'TRUE']);
                @endphp
                <tr style="page-break-inside: avoid;">
                    @if($kIdx === 0)
                        <td style="border: 1px solid #000; padding: 4px; text-align: center; vertical-align: top; font-weight: bold;" rowspan="{{ $kukCount }}">
                            {{ $elIdx + 1 }}
                        </td>
                        <td style="border: 1px solid #000; padding: 4px 6px; vertical-align: top; font-weight: bold;" rowspan="{{ $kukCount }}">
                            {{ $element->title }}
                        </td>
                    @endif
                    <td style="border: 1px solid #000; padding: 4px 6px; vertical-align: top;">
                        <span style="font-weight: bold; margin-right: 2px;">{{ $kuk->code_number }}</span>
                        {{ $kuk->statement }}
                    </td>
                    @if($kIdx === 0)
                        <td style="border: 1px solid #000; padding: 4px 6px; vertical-align: top; text-align: center; font-size: 7.5pt;" rowspan="{{ $kukCount }}">
                            Workshop Manual<br><br>Report sheet
                        </td>
                    @endif
                    <td style="border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle;">
                        <span class="check-box">{!! $isYa ? '&radic;' : '&nbsp;' !!}</span>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle;">
                        <span class="check-box">{!! !$isYa ? '&radic;' : '&nbsp;' !!}</span>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle;">
                        &nbsp;
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
