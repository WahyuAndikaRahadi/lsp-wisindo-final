<script>
    if (typeof window.initSuratTugasModal !== 'function') {
        window.initSuratTugasModal = function () {
            return {
                suratTugasOpen: false,
                suratTugasLoading: false,
                suratTugasError: false,
                suratTugasHtml: '',
                currentScheduleId: null,
                openModal(id) {
                    this.currentScheduleId = id;
                    this.suratTugasOpen = true;
                    this.suratTugasLoading = true;
                    this.suratTugasError = false;
                    this.suratTugasHtml = '';
                    
                    fetch(`/asesor/schedule/${id}/surat-tugas?modal=1`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Gagal memuat dokumen');
                        }
                        return res.text();
                    })
                    .then(html => {
                        this.suratTugasHtml = html;
                        this.suratTugasLoading = false;
                    })
                    .catch(err => {
                        this.suratTugasError = true;
                        this.suratTugasLoading = false;
                    });
                },
                printDoc() {
                    if (!this.currentScheduleId) return;
                    let iframe = document.getElementById('print-surat-tugas-frame');
                    if (!iframe) {
                        iframe = document.createElement('iframe');
                        iframe.id = 'print-surat-tugas-frame';
                        iframe.style.position = 'fixed';
                        iframe.style.right = '0';
                        iframe.style.bottom = '0';
                        iframe.style.width = '0';
                        iframe.style.height = '0';
                        iframe.style.border = '0';
                        document.body.appendChild(iframe);
                    }
                    iframe.src = `/asesor/schedule/${this.currentScheduleId}/surat-tugas?autoprint=1`;
                }
            };
        };
    }
</script>

<div x-data="window.initSuratTugasModal()"
     @open-surat-tugas.window="openModal($event.detail.scheduleId)"
     @keydown.escape.window="suratTugasOpen = false">

    {{-- Modal Overlay & Container --}}
    <div x-show="suratTugasOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 transition-opacity">
        
        <div @click.away="suratTugasOpen = false"
             class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full border border-slate-200 flex flex-col max-h-[92vh] overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="px-4 sm:px-6 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-brand-600/30 text-amber-400 flex items-center justify-center shrink-0 border border-amber-400/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold tracking-tight text-white">Pratinjau Surat Tugas Asesor</h3>
                        <p class="text-[10px] sm:text-[11px] text-slate-400">Dokumen Penugasan Resmi Lembaga Sertifikasi Profesi PrO Indo</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button"
                            @click="printDoc()"
                            :disabled="suratTugasLoading || suratTugasError"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer"
                            title="Cetak Surat Tugas">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span class="hidden sm:inline">Cetak Dokumen</span>
                    </button>

                    <button type="button"
                            @click="suratTugasOpen = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                            title="Tutup Modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body (Scrollable & Responsive) --}}
            <div class="overflow-y-auto p-3 sm:p-6 bg-slate-100/70 flex-1">
                {{-- Loading Spinner --}}
                <div x-show="suratTugasLoading" class="py-16 text-center space-y-3">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-brand-600 border-t-transparent"></div>
                    <p class="text-xs text-slate-500 font-medium">Memuat dokumen resmi Surat Tugas Asesor...</p>
                </div>

                {{-- Error State --}}
                <div x-show="!suratTugasLoading && suratTugasError" class="py-12 px-6 text-center space-y-2 bg-white rounded-xl border border-rose-200">
                    <div class="w-10 h-10 mx-auto rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-rose-700">Gagal memuat dokumen Surat Tugas Asesor</p>
                    <p class="text-[11px] text-slate-500">Pastikan Anda memiliki hak akses penugasan pada jadwal asesmen ini.</p>
                </div>

                {{-- Document Paper Container --}}
                <div x-show="!suratTugasLoading && !suratTugasError" 
                     class="bg-white rounded-xl shadow-md border border-slate-200 p-4 sm:p-8 max-w-3xl mx-auto overflow-x-auto"
                     x-html="suratTugasHtml">
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 shrink-0">
                <span class="text-[11px] text-slate-400">Format Resmi Dokumen A4 BNSP</span>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="suratTugasOpen = false" 
                            class="px-3.5 py-1.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 font-semibold rounded-lg text-xs transition cursor-pointer">
                        Tutup
                    </button>
                    <button type="button" 
                            @click="printDoc()" 
                            :disabled="suratTugasLoading || suratTugasError"
                            class="px-3.5 py-1.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold rounded-lg text-xs transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
