@extends('layouts.print-muk')

@section('title', 'Surat Tugas Asesor - ' . ($schedule->surat_tugas_number ?: $schedule->name))
@section('doc-title', 'Surat Tugas Asesor')

@section('content')
    @include('asesor.jadwal._surat_tugas_content')
@endsection

@if(request()->has('autoprint'))
    @push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                window.focus();
                window.print();
            }, 300);
        });
    </script>
    @endpush
@endif
