{{-- Tim Penyusun & Validator MUK Section --}}
<div class="space-y-6">
    {{-- 1. Tim Penyusun MUK --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 text-xs font-bold bg-brand-50 text-brand-700 rounded-md border border-brand-200">TIM PENYUSUN</span>
                    <h2 class="text-base font-bold text-slate-900">Penyusun Dokumen MUK</h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">Daftar asesor penyusun dokumen MUK (MAPA, IA, AK) untuk skema sertifikasi ini.</p>
            </div>
            <button type="button" @click="addPenyusun()" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 text-xs font-bold rounded-lg transition self-start sm:self-auto cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Penyusun</span>
            </button>
        </div>

        <div class="space-y-4">
            <template x-for="(item, index) in penyusun" :key="item.id">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3 relative hover:border-slate-300 transition">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                        <span class="text-xs font-bold text-slate-700" x-text="'Penyusun #' + (index + 1)"></span>
                        <button type="button" @click="removePenyusun(index)" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Asesor Penyusun <span class="text-rose-500">*</span></label>
                            <input type="text" :name="'penyusun[' + index + '][name]'" x-model="item.name" required
                                   placeholder="Contoh: George Edwin A"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Registrasi Asesor (No. MET)</label>
                            <input type="text" :name="'penyusun[' + index + '][no_met]'" x-model="item.no_met"
                                   placeholder="Contoh: MET. 000.000683 2021"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white font-mono focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    {{-- TTD Box --}}
                    <div class="pt-2">
                        <label class="block font-bold text-slate-700 text-xs mb-1.5">Tanda Tangan Digital (TTD)</label>

                        {{-- Mode 1: Signature is present (Preview) --}}
                        <div x-show="item.signature" class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-3.5 bg-white border border-slate-200 rounded-xl">
                            <div class="h-24 w-56 sm:h-28 sm:w-64 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-center overflow-hidden p-2 shrink-0 shadow-2xs">
                                <img :src="item.signature" alt="TTD Preview" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="space-y-1.5">
                                <span class="text-xs font-bold text-emerald-600 block">✓ Tanda Tangan Tersedia</span>
                                <p class="text-[11px] text-slate-500">Tanda tangan digital ini akan digunakan secara otomatis pada dokumen MUK.</p>
                                <button type="button" @click="clearSignature(item)" class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline cursor-pointer pt-1 block">
                                    Ganti / Hapus TTD
                                </button>
                            </div>
                        </div>

                        {{-- Mode 2: Signature not set (Input / Canvas) --}}
                        <div x-show="!item.signature" class="space-y-2">
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" @click="item.ttd_tab = 'upload'"
                                        class="px-2.5 py-1 rounded-md font-semibold transition border text-[11px] cursor-pointer"
                                        :class="item.ttd_tab === 'upload' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
                                    📁 Unggah Berkas PNG/JPG
                                </button>
                                <button type="button" @click="item.ttd_tab = 'draw'"
                                        class="px-2.5 py-1 rounded-md font-semibold transition border text-[11px] cursor-pointer"
                                        :class="item.ttd_tab === 'draw' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
                                    ✍️ Tanda Tangan Manual (Coretan)
                                </button>
                            </div>

                            {{-- Tab 1: Upload File --}}
                            <div x-show="item.ttd_tab === 'upload'" class="p-3 bg-white border border-slate-200 rounded-lg">
                                <input type="file" accept="image/png,image/jpeg,image/webp" @change="handleFileUpload($event, item)"
                                       class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-slate-300 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer">
                                <span class="text-[10px] text-slate-400 mt-1 block">Format: PNG, JPG, atau WEBP transparan (maks. 5 MB)</span>
                            </div>

                            {{-- Tab 2: Manual Draw Canvas --}}
                            <div x-show="item.ttd_tab === 'draw'" class="p-3 bg-white border border-slate-200 rounded-lg space-y-2">
                                <canvas width="520" height="180"
                                        class="w-full max-w-lg h-36 bg-slate-50 border border-dashed border-slate-300 rounded-lg touch-none cursor-crosshair block"
                                        x-init="$nextTick(() => initCanvas($el, item))"></canvas>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400 italic">Goreskan tanda tangan di atas kotak canvas</span>
                                    <button type="button" @click="clearCanvasElement($el)" class="text-slate-500 hover:text-slate-800 font-semibold underline cursor-pointer">
                                        Bersihkan Coretan
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Input for Base64 Data --}}
                        <input type="hidden" :name="'penyusun[' + index + '][signature]'" :value="item.signature">
                    </div>
                </div>
            </template>

            <div x-show="penyusun.length === 0" class="p-4 text-center border-2 border-dashed border-slate-200 rounded-xl text-xs text-slate-400">
                Belum ada tim penyusun yang ditambahkan. Klik <strong>+ Tambah Penyusun</strong> di atas.
            </div>
        </div>
    </div>

    {{-- 2. Tim Validator MUK --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 text-xs font-bold bg-amber-50 text-amber-700 rounded-md border border-amber-200">TIM VALIDATOR</span>
                    <h2 class="text-base font-bold text-slate-900">Validator Dokumen MUK</h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">Daftar asesor validator yang memvalidasi perangkat asesmen skema ini.</p>
            </div>
            <button type="button" @click="addValidator()" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold rounded-lg transition self-start sm:self-auto cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Validator</span>
            </button>
        </div>

        <div class="space-y-4">
            <template x-for="(item, index) in validator" :key="item.id">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3 relative hover:border-slate-300 transition">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                        <span class="text-xs font-bold text-slate-700" x-text="'Validator #' + (index + 1)"></span>
                        <button type="button" @click="removeValidator(index)" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Asesor Validator <span class="text-rose-500">*</span></label>
                            <input type="text" :name="'validator[' + index + '][name]'" x-model="item.name" required
                                   placeholder="Contoh: Lulut Budiyono"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Registrasi Asesor (No. MET)</label>
                            <input type="text" :name="'validator[' + index + '][no_met]'" x-model="item.no_met"
                                   placeholder="Contoh: MET. 000.002985 2023"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white font-mono focus:ring-1 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>

                    {{-- TTD Box --}}
                    <div class="pt-2">
                        <label class="block font-bold text-slate-700 text-xs mb-1.5">Tanda Tangan Digital (TTD)</label>

                        {{-- Mode 1: Signature is present (Preview) --}}
                        <div x-show="item.signature" class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-3.5 bg-white border border-slate-200 rounded-xl">
                            <div class="h-24 w-56 sm:h-28 sm:w-64 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-center overflow-hidden p-2 shrink-0 shadow-2xs">
                                <img :src="item.signature" alt="TTD Preview" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="space-y-1.5">
                                <span class="text-xs font-bold text-emerald-600 block">✓ Tanda Tangan Tersedia</span>
                                <p class="text-[11px] text-slate-500">Tanda tangan digital ini akan digunakan secara otomatis pada dokumen MUK.</p>
                                <button type="button" @click="clearSignature(item)" class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline cursor-pointer pt-1 block">
                                    Ganti / Hapus TTD
                                </button>
                            </div>
                        </div>

                        {{-- Mode 2: Signature not set (Input / Canvas) --}}
                        <div x-show="!item.signature" class="space-y-2">
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" @click="item.ttd_tab = 'upload'"
                                        class="px-2.5 py-1 rounded-md font-semibold transition border text-[11px] cursor-pointer"
                                        :class="item.ttd_tab === 'upload' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
                                    📁 Unggah Berkas PNG/JPG
                                </button>
                                <button type="button" @click="item.ttd_tab = 'draw'"
                                        class="px-2.5 py-1 rounded-md font-semibold transition border text-[11px] cursor-pointer"
                                        :class="item.ttd_tab === 'draw' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
                                    ✍️ Tanda Tangan Manual (Coretan)
                                </button>
                            </div>

                            {{-- Tab 1: Upload File --}}
                            <div x-show="item.ttd_tab === 'upload'" class="p-3 bg-white border border-slate-200 rounded-lg">
                                <input type="file" accept="image/png,image/jpeg,image/webp" @change="handleFileUpload($event, item)"
                                       class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-slate-300 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer">
                                <span class="text-[10px] text-slate-400 mt-1 block">Format: PNG, JPG, atau WEBP transparan (maks. 5 MB)</span>
                            </div>

                            {{-- Tab 2: Manual Draw Canvas --}}
                            <div x-show="item.ttd_tab === 'draw'" class="p-3 bg-white border border-slate-200 rounded-lg space-y-2">
                                <canvas width="520" height="180"
                                        class="w-full max-w-lg h-36 bg-slate-50 border border-dashed border-slate-300 rounded-lg touch-none cursor-crosshair block"
                                        x-init="$nextTick(() => initCanvas($el, item))"></canvas>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400 italic">Goreskan tanda tangan di atas kotak canvas</span>
                                    <button type="button" @click="clearCanvasElement($el)" class="text-slate-500 hover:text-slate-800 font-semibold underline cursor-pointer">
                                        Bersihkan Coretan
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden Input for Base64 Data --}}
                        <input type="hidden" :name="'validator[' + index + '][signature]'" :value="item.signature">
                    </div>
                </div>
            </template>

            <div x-show="validator.length === 0" class="p-4 text-center border-2 border-dashed border-slate-200 rounded-xl text-xs text-slate-400">
                Belum ada tim validator yang ditambahkan. Klik <strong>+ Tambah Validator</strong> di atas.
            </div>
        </div>
    </div>
</div>
