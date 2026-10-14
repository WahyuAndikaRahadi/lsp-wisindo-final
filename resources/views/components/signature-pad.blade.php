@props([
    'name' => 'signature_data',
    'value' => null,
    'signature' => null,
    'label' => 'Tanda Tangan Digital',
    'required' => false,
    'height' => 160,
])

@php
    $initialValue = $value ?? $signature ?? old($name);
    $padId = 'sig_pad_' . md5($name . microtime());
@endphp

<div x-data="signaturePadComponent({
    canvasId: '{{ $padId }}',
    inputName: '{{ $name }}',
    initialImage: @js($initialValue)
})" x-init="initPad()" class="w-full">
    @if($label)
        <label class="block text-sm font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
            <span>{{ $label }} @if($required)<span class="text-rose-500">*</span>@endif</span>
            <span class="text-xs text-slate-400 font-normal">Gunakan mouse atau sentuhan jari</span>
        </label>
    @endif

    <div class="relative border-2 border-dashed border-slate-300 rounded-lg bg-slate-50 overflow-hidden hover:border-slate-400 transition">
        <canvas 
            id="{{ $padId }}" 
            class="w-full bg-white touch-none cursor-crosshair block" 
            style="height: {{ $height }}px;"
        ></canvas>

        <div class="absolute bottom-2 right-2 flex items-center space-x-2">
            <button 
                type="button" 
                @click="clearCanvas()" 
                class="px-2.5 py-1 text-xs font-medium text-slate-600 bg-white/90 hover:bg-slate-100 border border-slate-200 rounded shadow-sm transition"
            >
                Hapus / Reset
            </button>
        </div>

        <div x-show="isEmpty && !hasDrawn" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-400 text-xs italic">
            Tanda tangani di area ini
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" :value="signatureData" @if($required) required @endif>

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
        isEmpty: !config.initialImage,
        hasDrawn: false,
        isDrawing: false,
        ctx: null,
        canvas: null,

        initPad() {
            this.$nextTick(() => {
                this.canvas = document.getElementById(this.canvasId);
                if (!this.canvas) return;

                this.ctx = this.canvas.getContext('2d');
                this.resizeCanvas();
                window.addEventListener('resize', () => this.resizeCanvas());

                // Mouse events
                this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
                this.canvas.addEventListener('mousemove', (e) => this.draw(e));
                window.addEventListener('mouseup', () => this.stopDrawing());

                // Touch events
                this.canvas.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    this.startDrawing(e.touches[0]);
                }, { passive: false });
                this.canvas.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    this.draw(e.touches[0]);
                }, { passive: false });
                this.canvas.addEventListener('touchend', (e) => {
                    this.stopDrawing();
                });

                if (config.initialImage) {
                    this.loadInitialImage(config.initialImage);
                }
            });
        },

        resizeCanvas() {
            if (!this.canvas) return;
            const rect = this.canvas.getBoundingClientRect();
            const prevData = this.canvas.toDataURL();

            // Set actual canvas size to match element display size
            this.canvas.width = rect.width;
            this.canvas.height = rect.height;

            this.ctx.lineWidth = 2.5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#0f172a'; // slate-900

            if (this.signatureData) {
                this.loadInitialImage(this.signatureData);
            }
        },

        loadInitialImage(dataUrl) {
            if (!dataUrl || !this.ctx) return;
            const img = new Image();
            img.onload = () => {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.drawImage(img, 0, 0, this.canvas.width, this.canvas.height);
                this.isEmpty = false;
            };
            img.src = dataUrl;
        },

        getCoordinates(event) {
            const rect = this.canvas.getBoundingClientRect();
            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top
            };
        },

        startDrawing(e) {
            this.isDrawing = true;
            this.hasDrawn = true;
            this.isEmpty = false;
            const coords = this.getCoordinates(e);
            this.ctx.beginPath();
            this.ctx.moveTo(coords.x, coords.y);
        },

        draw(e) {
            if (!this.isDrawing) return;
            const coords = this.getCoordinates(e);
            this.ctx.lineTo(coords.x, coords.y);
            this.ctx.stroke();
        },

        stopDrawing() {
            if (!this.isDrawing) return;
            this.isDrawing = false;
            this.ctx.closePath();
            this.saveData();
        },

        clearCanvas() {
            if (!this.canvas || !this.ctx) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.signatureData = '';
            this.isEmpty = true;
            this.hasDrawn = false;
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
