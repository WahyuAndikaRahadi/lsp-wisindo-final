@props([
    'name' => 'signature_data',
    'value' => null,
    'signature' => null,
    'label' => 'Tanda Tangan Digital',
    'required' => false,
    'height' => 140,
    'profileSignature' => null,
    'disabled' => false,
])

@php
    $initialValue = $value ?? $signature ?? old($name);
    $userProfileSignature = $profileSignature ?? auth()->user()?->profile?->signature_data;
    $padId = 'sig_pad_' . md5($name . microtime());
    $isDisabled = (bool) $disabled;
    $canvasHeight = (int) $height ?: 140;
@endphp

<div x-data="signaturePadComponent({
    canvasId: '{{ $padId }}',
    inputName: '{{ $name }}',
    initialImage: @js($initialValue),
    profileSignature: @js($userProfileSignature),
    disabled: @js($isDisabled),
    required: @js((bool) $required)
})" x-init="initPad()" class="w-full max-w-[340px]">
    @if($label)
        <div class="flex items-center justify-between mb-1.5 max-w-[340px]">
            <label class="block text-xs sm:text-sm font-semibold text-slate-700">
                {{ $label }} @if($required && ! $isDisabled)<span class="text-rose-500">*</span>@endif
            </label>
            @if($isDisabled)
                <span class="inline-flex items-center text-[10px] sm:text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                    <svg class="w-3 h-3 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Terkunci
                </span>
            @endif
        </div>
    @endif

    <div class="relative border-2 rounded-xl overflow-hidden transition w-full max-w-[340px] bg-white shadow-2xs"
         style="height: {{ $canvasHeight }}px;"
         :class="hasError ? 'border-rose-500 ring-2 ring-rose-200' : (@js($isDisabled) ? 'border-slate-200 bg-slate-50' : 'border-dashed border-slate-300 hover:border-slate-400')">
        <canvas 
            id="{{ $padId }}" 
            width="680" 
            height="280"
            class="w-full h-full block bg-white @if($isDisabled) pointer-events-none cursor-default @else touch-none cursor-crosshair @endif" 
            style="aspect-ratio: 17 / 7;"
        ></canvas>

        @if(! $isDisabled)
            <div class="absolute bottom-2 right-2 flex items-center space-x-1.5 z-10">
                <button 
                    type="button" 
                    @click="clearCanvas()" 
                    class="px-2.5 py-1 text-[11px] font-semibold text-slate-600 bg-white/95 hover:bg-slate-100 border border-slate-200 rounded-md shadow-2xs transition"
                >
                    Hapus / Reset
                </button>
            </div>

            <div x-show="isEmpty && !hasDrawn" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-400 text-xs italic select-none">
                Goreskan tanda tangan di sini
            </div>
        @endif
    </div>

    <template x-if="hasError">
        <p class="text-rose-600 text-xs mt-1.5 font-semibold flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="errorMessage"></span>
        </p>
    </template>

    @if(! $isDisabled && $userProfileSignature)
        <div class="mt-2 flex items-center max-w-[340px]">
            <label class="inline-flex items-center text-xs font-semibold text-slate-700 cursor-pointer select-none">
                <input 
                    type="checkbox" 
                    x-model="useProfileSignature" 
                    @change="toggleProfileSignature()" 
                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 mr-2 cursor-pointer"
                >
                <span>Gunakan tanda tangan profil</span>
            </label>
        </div>
    @endif

    <input type="hidden" name="{{ $name }}" :value="signatureData">

    @error($name)
        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

@once
@push('scripts')
<script>
function signaturePadComponent(config) {
    return {
        canvasId: config.canvasId,
        signatureData: config.initialImage || '',
        profileSignature: config.profileSignature || '',
        disabled: Boolean(config.disabled),
        required: Boolean(config.required),
        hasError: false,
        errorMessage: '',
        useProfileSignature: false,
        isEmpty: !config.initialImage,
        hasDrawn: Boolean(config.initialImage),
        isDrawing: false,
        ctx: null,
        canvas: null,

        toggleProfileSignature() {
            if (this.disabled) return;
            if (this.useProfileSignature && this.profileSignature) {
                this.loadInitialImage(this.profileSignature);
                this.signatureData = this.profileSignature;
                this.isEmpty = false;
                this.hasDrawn = true;
                this.hasError = false;
                this.errorMessage = '';
            } else {
                this.clearCanvas();
            }
        },

        initPad() {
            this.$nextTick(() => {
                this.canvas = document.getElementById(this.canvasId);
                if (!this.canvas) return;

                // Fixed internal buffer resolution (2x HiDPI of standard 340x140)
                this.canvas.width = 680;
                this.canvas.height = 280;

                this.ctx = this.canvas.getContext('2d');
                this.setupContext();

                if (config.initialImage) {
                    this.loadInitialImage(config.initialImage);
                    if (config.profileSignature && config.initialImage === config.profileSignature) {
                        this.useProfileSignature = true;
                    }
                }

                if (this.disabled) {
                    return;
                }

                // Pointer Events for smooth unified handling across mouse, pen, and touch
                this.canvas.addEventListener('pointerdown', (e) => this.handlePointerDown(e));
                this.canvas.addEventListener('pointermove', (e) => this.handlePointerMove(e));
                this.canvas.addEventListener('pointerup', (e) => this.handlePointerUp(e));
                this.canvas.addEventListener('pointercancel', (e) => this.handlePointerUp(e));

                // Hook to form submit to guarantee latest signature data is captured & enforce required validation
                const form = this.canvas.closest('form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        if (!this.disabled && !this.isEmpty && this.hasDrawn) {
                            this.saveData();
                        }
                        if (this.required && !this.disabled && (!this.signatureData || this.signatureData.trim() === '' || this.isEmpty)) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.hasError = true;
                            this.errorMessage = 'Tanda tangan digital wajib digoreskan sebelum menyimpan.';
                            this.canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return false;
                        }
                    });
                }
            });
        },

        setupContext() {
            if (!this.ctx) return;
            this.ctx.lineWidth = 4; // 4px on 680x280 buffer = 2px on 340x140 screen (natural pen stroke)
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#0f172a'; // slate-900
            this.ctx.fillStyle = '#0f172a';
        },

        loadInitialImage(dataUrl) {
            if (!dataUrl || !this.ctx) return;
            const img = new Image();
            img.onload = () => {
                if (!this.canvas || !this.ctx) return;
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

                // Aspect-ratio preserving contain fit inside standard 680x280 box
                const hRatio = this.canvas.width / img.width;
                const vRatio = this.canvas.height / img.height;
                const ratio = Math.min(hRatio, vRatio);
                const drawW = img.width * ratio;
                const drawH = img.height * ratio;
                const shiftX = (this.canvas.width - drawW) / 2;
                const shiftY = (this.canvas.height - drawH) / 2;

                this.ctx.drawImage(img, 0, 0, img.width, img.height, shiftX, shiftY, drawW, drawH);
                this.isEmpty = false;
                this.hasDrawn = true;
            };
            img.src = dataUrl;
        },

        getCoordinates(e) {
            const rect = this.canvas.getBoundingClientRect();
            const scaleX = rect.width > 0 ? this.canvas.width / rect.width : 1;
            const scaleY = rect.height > 0 ? this.canvas.height / rect.height : 1;
            return {
                x: (e.clientX - rect.left) * scaleX,
                y: (e.clientY - rect.top) * scaleY
            };
        },

        handlePointerDown(e) {
            if (this.disabled) return;
            try {
                this.canvas.setPointerCapture(e.pointerId);
            } catch (err) {}

            this.isDrawing = true;
            this.hasDrawn = true;
            this.isEmpty = false;
            this.hasError = false;
            this.errorMessage = '';
            this.useProfileSignature = false;

            const coords = this.getCoordinates(e);

            // Draw initial dot immediately on touch/click
            this.ctx.beginPath();
            this.ctx.arc(coords.x, coords.y, this.ctx.lineWidth / 2, 0, Math.PI * 2);
            this.ctx.fill();

            // Begin continuous stroke
            this.ctx.beginPath();
            this.ctx.moveTo(coords.x, coords.y);
        },

        handlePointerMove(e) {
            if (!this.isDrawing || this.disabled) return;
            const coords = this.getCoordinates(e);
            this.ctx.lineTo(coords.x, coords.y);
            this.ctx.stroke();
        },

        handlePointerUp(e) {
            if (!this.isDrawing) return;
            this.isDrawing = false;
            this.ctx.closePath();
            try {
                this.canvas.releasePointerCapture(e.pointerId);
            } catch (err) {}
            this.saveData();
        },

        clearCanvas() {
            if (this.disabled) return;
            if (!this.canvas || !this.ctx) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.signatureData = '';
            this.isEmpty = true;
            this.hasDrawn = false;
            this.useProfileSignature = false;
        },

        saveData() {
            if (!this.canvas) return;
            this.signatureData = this.canvas.toDataURL('image/png');
        }
    };
}
</script>
@endpush
@endonce
