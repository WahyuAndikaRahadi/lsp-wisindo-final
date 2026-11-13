        @php
            $ak03Statements = [
                1 => 'Saya mendapatkan penjelasan yang cukup memadai mengenai proses asesmen / uji kompetensi.',
                2 => 'Saya diberikan kesempatan untuk mempelajari standar kompetensi yang akan diujikan dan menilai diri sendiri terhadap pencapaiannya.',
                3 => 'Asesor memberikan kesempatan untuk mendiskusikan / menegosiasikan metoda, instrumen, dan sumber asesmen serta jadwal uji.',
                4 => 'Asesor berusaha menggali seluruh bukti pendukung yang sesuai dengan latar belakang pelatihan dan pengalaman saya.',
                5 => 'Saya sepenuhnya diberikan kesempatan untuk mendemonstrasikan kompetensi yang saya miliki selama asesmen berlangsung.',
                6 => 'Saya mendapatkan penjelasan yang memadai mengenai keputusan asesmen (K / BK).',
                7 => 'Asesor memberikan umpan balik yang membangun dan mendukung setelah asesmen serta arahan tindak lanjutnya.',
                8 => 'Asesor bersama saya mempelajari semua dokumen asesmen serta menandatanganinya secara transparan.',
                9 => 'Saya mendapatkan jaminan kerahasiaan hasil asesmen serta penjelasan mengenai penanganan berkas portofolio.',
                10 => 'Asesor menggunakan keterampilan komunikasi yang efektif, santun, dan profesional selama asesmen.',
            ];
        @endphp

        @if($feedbacks->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Belum Ada Umpan Balik Masuk</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Formulir FR.AK.03 akan otomatis terkumpul di sini setelah peserta menyelesaikan rangkaian uji kompetensi.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($feedbacks as $fb)
                    @php
                        $ratings = $fb->ratings_json ?? [];
                        $yesCount = 0;
                        $noCount = 0;
                        foreach($ratings as $r) {
                            if(($r['value'] ?? '') === 'YA') $yesCount++;
                            if(($r['value'] ?? '') === 'TIDAK') $noCount++;
                        }
                    @endphp

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden" x-data="{ expanded: false }">
                        <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/60 border-b border-slate-100">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-extrabold text-sm text-slate-900">{{ $fb->assessment->asesi->full_name }}</span>
                                    <span class="text-xs text-slate-500">({{ $fb->assessment->schedule->scheme->name ?? '-' }})</span>
                                    <span class="text-[11px] text-slate-400">&bull; Jadwal: {{ $fb->assessment->schedule->name ?? '-' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1">
                                    Asesor Penguji: <strong>{{ $fb->assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}</strong> &bull; 
                                    Diisi pada: {{ \Carbon\Carbon::parse($fb->created_at)->isoFormat('D MMMM Y, HH:mm') }} WIB
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="flex items-center gap-1.5 text-xs font-bold">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $yesCount }} YA
                                    </span>
                                    @if($noCount > 0)
                                        <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                                            {{ $noCount }} TIDAK
                                        </span>
                                    @endif
                                </div>
                                <button type="button" 
                                        @click="expanded = !expanded" 
                                        class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 transition flex items-center gap-1">
                                    <span x-text="expanded ? 'Tutup Rincian' : 'Lihat 10 Butir'"></span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- General Notes if any --}}
                        @if($fb->general_notes)
                            <div class="px-4 sm:px-6 py-3 bg-amber-50/50 border-b border-amber-100 text-xs text-amber-950 flex items-start gap-2">
                                <span class="font-bold text-amber-700 shrink-0">Catatan Asesi:</span>
                                <span class="italic">{{ $fb->general_notes }}</span>
                            </div>
                        @endif

                        {{-- Expanded 10 Items Breakdown --}}
                        <div x-show="expanded" x-collapse class="p-3 sm:p-6 bg-white overflow-x-auto md:overflow-x-visible">
                            <table class="w-full md:min-w-[600px] text-xs">
                                <thead class="hidden md:table-header-group bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="p-2.5 text-center w-10">No</th>
                                        <th class="p-2.5 text-left">Pernyataan Evaluasi</th>
                                        <th class="p-2.5 text-center w-24">Jawaban</th>
                                        <th class="p-2.5 text-left w-56">Catatan Asesi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-0 md:divide-y divide-slate-100 block md:table-row-group space-y-2.5 md:space-y-0">
                                    @foreach($ak03Statements as $num => $stmt)
                                        @php
                                            $itemVal = $ratings[$num]['value'] ?? '-';
                                            $itemComment = $ratings[$num]['comment'] ?? '-';
                                        @endphp
                                        <tr class="flex flex-col bg-slate-50/70 rounded-xl border border-slate-200 p-3 gap-1.5 md:table-row md:border-0 md:p-0 md:bg-transparent md:hover:bg-slate-50/50">
                                            <td class="hidden md:table-cell p-2.5 text-center text-slate-400 font-mono">{{ $num }}</td>
                                            <td class="w-full md:table-cell p-0 md:p-2.5 text-slate-800 leading-relaxed">
                                                <span class="md:hidden font-mono font-bold text-slate-500 mr-1.5">#{{ $num }}</span>{{ $stmt }}
                                            </td>
                                            <td class="w-full md:w-24 md:table-cell p-0 md:p-2.5 text-left md:text-center flex items-center gap-2 md:table-cell">
                                                <span class="md:hidden text-[11px] text-slate-500 font-semibold">Hasil:</span>
                                                @if($itemVal === 'YA')
                                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[11px]">YA</span>
                                                @elseif($itemVal === 'TIDAK')
                                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[11px]">TIDAK</span>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                            <td class="w-full md:w-56 md:table-cell p-0 md:p-2.5 text-slate-600 italic">
                                                <span class="md:hidden text-[11px] text-slate-500 font-semibold not-italic inline mr-1">Catatan:</span>
                                                {{ $itemComment !== '-' && !empty($itemComment) ? $itemComment : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="pt-2">
                    {{ $feedbacks->links() }}
                </div>
            </div>
        @endif
