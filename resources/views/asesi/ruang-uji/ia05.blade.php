@extends('layouts.app')

@section('title', 'FR.IA.05 - Ujian Teori CBT Pilihan Ganda')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Ruang Uji</span>
            </a>
            <div class="flex items-center gap-2">
                @if($isSubmitted)
                    <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia05c']) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-cyan-50 text-cyan-800 border border-cyan-300 hover:bg-cyan-100 transition shadow-xs"
                       title="Lihat Lembar Jawaban Pilihan Ganda FR.IA.05.C">
                        <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Dokumen FR.IA.05.C</span>
                    </a>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>✓ Jawaban CBT Telah Terkirim</span>
                    </span>
                @else
                    <div class="flex items-center gap-2 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200 text-xs font-medium" id="autosave-container">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" id="autosave-dot"></span>
                        <span id="autosave-status" class="text-slate-700 text-[11px] font-semibold">Tersimpan</span>
                    </div>
                @endif
            </div>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                FR.IA.05. PERTANYAAN TERTULIS (CBT)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                Skema: <strong class="text-slate-800">{{ $assessment->schedule->scheme->name }}</strong> &bull; 
                Kode Jadwal: <span class="font-mono font-bold">{{ $assessment->schedule->code }}</span>
            </p>
        </div>
    </div>

    @if($assessment->schedule?->is_closed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-2xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center gap-3 shadow-xs">
            <span class="text-xl shrink-0">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Seluruh lembar ujian CBT berada dalam mode lihat (read-only). Jawaban telah tersimpan secara permanen.</p>
            </div>
        </div>
    @endif

    @if($questions->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-8 text-center text-amber-900 text-sm">
            Belum ada bank soal yang diaktifkan untuk jadwal dan skema ini. Silakan hubungi asesor atau admin LSP.
        </div>
    @else
        <form action="{{ route('asesi.assessment.instrument.ia05.submit', $assessment) }}" method="POST" id="cbtForm">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {{-- Left Column: Question Workspace --}}
                <div class="lg:col-span-8 space-y-4">
                    @foreach($questions as $index => $q)
                        @php
                            $qId = (string) $q->id;
                            $savedVal = $draftAnswers[$qId] ?? null;
                            $isDoubtful = in_array($qId, $draftDoubtful ?? [], true);
                        @endphp
                        <div class="cbt-question-panel bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm space-y-5 {{ $index === 0 ? '' : 'hidden' }}"
                             id="question-panel-{{ $index }}"
                             data-index="{{ $index }}"
                             data-qid="{{ $qId }}">
                            
                            {{-- Panel Header --}}
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-brand-50 text-brand-700 text-xs font-bold rounded-lg border border-brand-200 font-mono">
                                        Soal No. {{ $index + 1 }} dari {{ $questions->count() }}
                                    </span>
                                </div>

                                {{-- Ragu-ragu Checkbox --}}
                                @if(! $isSubmitted)
                                    <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 transition text-xs font-bold text-amber-900 select-none">
                                        <input type="checkbox"
                                               class="doubtful-checkbox rounded text-amber-500 focus:ring-amber-400 w-4 h-4 cursor-pointer"
                                               data-index="{{ $index }}"
                                               data-qid="{{ $qId }}"
                                               {{ $isDoubtful ? 'checked' : '' }}>
                                        <span>⚠️ Ragu-ragu</span>
                                    </label>
                                @endif
                            </div>

                            {{-- Question Text --}}
                            <div class="space-y-3">
                                <p class="text-base font-semibold text-slate-900 leading-relaxed">
                                    {{ $q->question_text }}
                                </p>

                                {{-- Optional Question Image --}}
                                @if($q->question_image)
                                    <div class="p-2 border border-slate-200 rounded-lg bg-slate-50 inline-block max-w-full">
                                        <img src="{{ $q->image_url ?? asset('storage/' . $q->question_image) }}"
                                             alt="Gambar Soal No. {{ $index + 1 }}"
                                             class="max-h-72 rounded object-contain cursor-pointer"
                                             onclick="window.open(this.src, '_blank')">
                                        <span class="text-[10px] text-slate-500 block mt-1 text-center">Klik gambar untuk melihat ukuran penuh</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Options --}}
                            <div class="space-y-2.5 pt-2">
                                @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optText)
                                    @if($optText)
                                        <label class="cbt-option-label flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-brand-500 hover:bg-brand-50/30 cursor-pointer transition {{ $savedVal === $optKey ? 'bg-brand-50/60 border-brand-500 ring-1 ring-brand-500' : 'bg-white' }}">
                                            <input type="radio"
                                                   name="answers[{{ $q->id }}]"
                                                   value="{{ $optKey }}"
                                                   data-index="{{ $index }}"
                                                   data-qid="{{ $qId }}"
                                                   class="cbt-radio mt-0.5 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer"
                                                   @checked($savedVal === $optKey)
                                                   @disabled($isSubmitted)>
                                            <div class="flex-1 text-xs sm:text-sm text-slate-800">
                                                <span class="font-bold text-slate-900 mr-1.5">{{ $optKey }}.</span>
                                                <span>{{ $optText }}</span>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Navigation Buttons inside Question Panel --}}
                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                                <button type="button"
                                        class="btn-prev px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 text-xs font-bold transition flex items-center gap-1.5 {{ $index === 0 ? 'invisible pointer-events-none' : '' }}"
                                        onclick="goToQuestion({{ $index - 1 }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    <span>Sebelumnya</span>
                                </button>

                                <span class="text-xs text-slate-400 font-medium hidden sm:inline">
                                    No. {{ $index + 1 }} dari {{ $questions->count() }}
                                </span>

                                @if($index < $questions->count() - 1)
                                    <button type="button"
                                            class="btn-next px-5 py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5"
                                            onclick="goToQuestion({{ $index + 1 }})">
                                        <span>Selanjutnya</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                @else
                                    <button type="button"
                                            class="px-5 py-2.5 rounded-lg bg-slate-200 text-slate-500 text-xs font-bold cursor-not-allowed invisible">
                                        Selanjutnya
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Right Column: Question Palette Sidebar --}}
                <div class="lg:col-span-4 space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm space-y-4 sticky top-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                                Nomor Soal
                            </h2>
                            <span class="text-xs font-bold text-slate-500 font-mono" id="palette-progress">
                                0 / {{ $questions->count() }}
                            </span>
                        </div>

                        {{-- Legend --}}
                        <div class="grid grid-cols-3 gap-2 text-[11px] pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span class="w-3.5 h-3.5 rounded bg-blue-600 shrink-0"></span>
                                <span>Dijawab</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span class="w-3.5 h-3.5 rounded bg-amber-400 shrink-0"></span>
                                <span>Ragu-ragu</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <span class="w-3.5 h-3.5 rounded bg-slate-100 border border-slate-300 shrink-0"></span>
                                <span>Belum</span>
                            </div>
                        </div>

                        {{-- Grid of Number Buttons --}}
                        <div class="grid grid-cols-5 sm:grid-cols-6 lg:grid-cols-5 gap-2 max-h-72 overflow-y-auto pr-1">
                            @foreach($questions as $index => $q)
                                @php
                                    $qId = (string) $q->id;
                                    $hasAnswer = isset($draftAnswers[$qId]) && $draftAnswers[$qId] !== null && $draftAnswers[$qId] !== '';
                                    $isDoubtful = in_array($qId, $draftDoubtful ?? [], true);
                                    
                                    $btnClass = 'bg-slate-100 text-slate-700 border-slate-200 hover:border-slate-400';
                                    if ($isDoubtful) {
                                        $btnClass = 'bg-amber-400 text-slate-900 border-amber-500 font-black';
                                    } elseif ($hasAnswer) {
                                        $btnClass = 'bg-blue-600 text-white border-blue-700 font-bold';
                                    }
                                @endphp
                                <button type="button"
                                        id="palette-btn-{{ $index }}"
                                        data-index="{{ $index }}"
                                        data-qid="{{ $qId }}"
                                        class="palette-btn w-full aspect-square rounded-lg text-xs flex items-center justify-center transition border {{ $btnClass }} {{ $index === 0 ? 'ring-2 ring-brand-500 ring-offset-1' : '' }}"
                                        onclick="goToQuestion({{ $index }})">
                                    {{ $index + 1 }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Stats Summary --}}
                        <div class="bg-slate-50 rounded-lg p-3 text-xs space-y-1.5 border border-slate-200 text-slate-600">
                            <div class="flex justify-between">
                                <span>Sudah Terjawab:</span>
                                <strong class="text-blue-700" id="stat-answered">0</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Ragu-ragu:</span>
                                <strong class="text-amber-700" id="stat-doubtful">0</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Belum Dijawab:</span>
                                <strong class="text-slate-700" id="stat-unanswered">{{ $questions->count() }}</strong>
                            </div>
                        </div>

                        {{-- Final Submit Section --}}
                        @if(! $isSubmitted)
                            <div class="pt-2 space-y-2">
                                <div id="submit-validation-msg" class="p-2.5 rounded-lg text-xs leading-relaxed hidden"></div>

                                <button type="submit"
                                        id="btnSubmitCbt"
                                        class="w-full py-3 px-4 rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-2 bg-slate-300 text-slate-500 cursor-not-allowed"
                                        disabled>
                                    <span>Kirim Jawaban CBT</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <p class="text-[11px] text-slate-400 text-center leading-tight">
                                    Pastikan seluruh soal terisi dan tidak ada yang ditandai ragu-ragu untuk mengaktifkan tombol kirim.
                                </p>
                            </div>
                        @else
                            <div class="p-3 bg-emerald-50 rounded-lg text-center text-xs text-emerald-800 border border-emerald-200 font-semibold">
                                @if($assessment->schedule?->is_closed)
                                    🔒 Jadwal ditutup. Lembar jawaban CBT dalam mode lihat (read-only).
                                @else
                                    Sesi CBT telah selesai dan jawaban tersimpan.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

{{-- jQuery for Auto-save, Palette Navigation, and State Validation --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    const totalQuestions = {{ $questions->count() }};
    const isSubmitted = {{ $isSubmitted ? 'true' : 'false' }};
    let currentIndex = 0;

    // In-memory answer and doubtful state (seeded from server-side draft)
    let answers = {!! json_encode((object) $draftAnswers) !!};
    let doubtfulList = {!! json_encode(array_values($draftDoubtful ?? [])) !!};

    // Ensure CSRF for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function goToQuestion(index) {
        if (index < 0 || index >= totalQuestions) return;

        // Hide current panel and remove ring from old button
        $('#question-panel-' + currentIndex).addClass('hidden');
        $('#palette-btn-' + currentIndex).removeClass('ring-2 ring-brand-500 ring-offset-1');

        // Show new panel and add ring to new button
        currentIndex = index;
        $('#question-panel-' + currentIndex).removeClass('hidden');
        $('#palette-btn-' + currentIndex).addClass('ring-2 ring-brand-500 ring-offset-1');

        // Scroll to question panel smoothly on mobile
        if (window.innerWidth < 1024) {
            $('html, body').animate({
                scrollTop: $('#question-panel-' + currentIndex).offset().top - 80
            }, 200);
        }
    }

    function updateQuestionUI(index, qid) {
        const hasAnswer = answers[qid] !== undefined && answers[qid] !== null && answers[qid] !== '';
        const isDoubtful = doubtfulList.includes(String(qid));

        const $btn = $('#palette-btn-' + index);

        // Reset base classes
        $btn.removeClass('bg-slate-100 text-slate-700 border-slate-200 hover:border-slate-400 bg-amber-400 text-slate-900 border-amber-500 font-black bg-blue-600 text-white border-blue-700 font-bold');

        if (isDoubtful) {
            $btn.addClass('bg-amber-400 text-slate-900 border-amber-500 font-black');
        } else if (hasAnswer) {
            $btn.addClass('bg-blue-600 text-white border-blue-700 font-bold');
        } else {
            $btn.addClass('bg-slate-100 text-slate-700 border-slate-200 hover:border-slate-400');
        }
    }

    function updateOverallStatsAndSubmitButton() {
        let answeredCount = 0;
        $('.cbt-question-panel').each(function() {
            const qid = $(this).data('qid');
            if (answers[qid] !== undefined && answers[qid] !== null && answers[qid] !== '') {
                answeredCount++;
            }
        });

        const doubtfulCount = doubtfulList.length;
        const unansweredCount = totalQuestions - answeredCount;

        $('#stat-answered').text(answeredCount + ' / ' + totalQuestions);
        $('#stat-doubtful').text(doubtfulCount);
        $('#stat-unanswered').text(unansweredCount);
        $('#palette-progress').text(answeredCount + ' / ' + totalQuestions);

        if (isSubmitted) return;

        const $btn = $('#btnSubmitCbt');
        const $msg = $('#submit-validation-msg');

        if (unansweredCount > 0) {
            $btn.prop('disabled', true)
                .removeClass('bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer')
                .addClass('bg-slate-300 text-slate-500 cursor-not-allowed');
            $msg.removeClass('hidden')
                .removeClass('bg-amber-50 text-amber-900 border-amber-200')
                .addClass('bg-rose-50 text-rose-800 border border-rose-200')
                .html('<strong>Belum Lengkap:</strong> Masih ada ' + unansweredCount + ' soal yang belum dijawab.');
        } else if (doubtfulCount > 0) {
            $btn.prop('disabled', true)
                .removeClass('bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer')
                .addClass('bg-slate-300 text-slate-500 cursor-not-allowed');
            $msg.removeClass('hidden')
                .removeClass('bg-rose-50 text-rose-800 border-rose-200')
                .addClass('bg-amber-50 text-amber-900 border border-amber-200')
                .html('<strong>Perhatian:</strong> Terdapat ' + doubtfulCount + ' soal bertanda ragu-ragu. Hilangkan tanda ragu-ragu sebelum mengirim.');
        } else {
            $btn.prop('disabled', false)
                .removeClass('bg-slate-300 text-slate-500 cursor-not-allowed')
                .addClass('bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer');
            $msg.addClass('hidden');
        }
    }

    let autosaveTimer = null;
    function triggerAutosave() {
        if (isSubmitted) return;

        $('#autosave-dot').removeClass('bg-emerald-500 bg-rose-500').addClass('bg-amber-500 animate-pulse');
        $('#autosave-status').text('Menyimpan...');

        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(function() {
            $.post("{{ route('asesi.assessment.instrument.ia05.autosave', $assessment) }}", {
                answers: answers,
                doubtful: doubtfulList
            }).done(function(res) {
                $('#autosave-dot').removeClass('bg-amber-500 animate-pulse bg-rose-500').addClass('bg-emerald-500');
                $('#autosave-status').text('Otomatis Tersimpan');
            }).fail(function() {
                $('#autosave-dot').removeClass('bg-amber-500 animate-pulse bg-emerald-500').addClass('bg-rose-500');
                $('#autosave-status').text('Gagal Menyimpan');
            });
        }, 300);
    }

    $(document).ready(function() {
        // Initial sync of button colors and stats
        $('.cbt-question-panel').each(function() {
            const idx = $(this).data('index');
            const qid = $(this).data('qid');
            updateQuestionUI(idx, qid);
        });
        updateOverallStatsAndSubmitButton();

        // Radio button change (Select Answer)
        $(document).on('change', '.cbt-radio', function() {
            if (isSubmitted) return;

            const qid = $(this).data('qid');
            const idx = $(this).data('index');
            const val = $(this).val();

            answers[qid] = val;

            // Highlight chosen option card
            const $panel = $('#question-panel-' + idx);
            $panel.find('.cbt-option-label').removeClass('bg-brand-50/60 border-brand-500 ring-1 ring-brand-500').addClass('bg-white');
            $(this).closest('.cbt-option-label').removeClass('bg-white').addClass('bg-brand-50/60 border-brand-500 ring-1 ring-brand-500');

            updateQuestionUI(idx, qid);
            updateOverallStatsAndSubmitButton();
            triggerAutosave();
        });

        // Doubtful checkbox change
        $(document).on('change', '.doubtful-checkbox', function() {
            if (isSubmitted) return;

            const qid = String($(this).data('qid'));
            const idx = $(this).data('index');
            const checked = $(this).is(':checked');

            if (checked) {
                if (!doubtfulList.includes(qid)) {
                    doubtfulList.push(qid);
                }
            } else {
                doubtfulList = doubtfulList.filter(id => id !== qid);
            }

            updateQuestionUI(idx, qid);
            updateOverallStatsAndSubmitButton();
            triggerAutosave();
        });

        // Submit form confirmation
        $('#cbtForm').on('submit', function(e) {
            let answeredCount = 0;
            $('.cbt-question-panel').each(function() {
                const qid = $(this).data('qid');
                if (answers[qid] !== undefined && answers[qid] !== null && answers[qid] !== '') {
                    answeredCount++;
                }
            });

            if (answeredCount < totalQuestions) {
                e.preventDefault();
                if (window.Notiflix && Notiflix.Report) {
                    Notiflix.Report.warning(
                        'Soal Belum Lengkap',
                        'Anda belum menjawab seluruh soal (' + answeredCount + ' dari ' + totalQuestions + ' butir soal). Harap lengkapi semua soal sebelum mengirim.',
                        'Mengerti'
                    );
                } else {
                    alert('Anda belum menjawab seluruh soal. Harap lengkapi semua soal sebelum mengirim.');
                }
                return false;
            }

            if (doubtfulList.length > 0) {
                e.preventDefault();
                if (window.Notiflix && Notiflix.Report) {
                    Notiflix.Report.warning(
                        'Soal Masih Ragu-ragu',
                        'Terdapat ' + doubtfulList.length + ' butir soal bertanda ragu-ragu. Harap periksa dan hilangkan tanda ragu-ragu sebelum menyelesaikan ujian.',
                        'Mengerti'
                    );
                } else {
                    alert('Terdapat ' + doubtfulList.length + ' soal bertanda ragu-ragu. Harap pastikan seluruh jawaban sebelum menyelesaikan ujian.');
                }
                return false;
            }

            if (this.dataset.confirmed !== 'true') {
                e.preventDefault();
                const formEl = this;
                if (window.notiflixConfirm) {
                    window.notiflixConfirm(
                        'Konfirmasi Selesai Ujian CBT',
                        'Apakah Anda yakin ingin menyelesaikan dan mengirim jawaban ujian CBT ini?<br><br><strong>Catatan:</strong> Jawaban yang sudah dikirim tidak dapat diubah kembali.',
                        'Ya, Kirim Jawaban',
                        'Periksa Kembali',
                        function() {
                            formEl.dataset.confirmed = 'true';
                            if (window.Notiflix && Notiflix.Loading) {
                                Notiflix.Loading.pulse('Menyimpan jawaban ujian CBT & memproses penilaian...');
                            }
                            formEl.submit();
                        },
                        function() {},
                        { okButtonBackground: '#1E2B6E' }
                    );
                } else if (confirm('Apakah Anda yakin ingin menyelesaikan dan mengirim jawaban ujian CBT ini? Jawaban yang sudah dikirim tidak dapat diubah kembali.')) {
                    formEl.dataset.confirmed = 'true';
                    formEl.submit();
                }
                return false;
            }
        });
    });
</script>
@endsection
