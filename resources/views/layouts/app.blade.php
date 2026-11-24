<!DOCTYPE html>
<html lang="id" class="h-full bg-lsp-canvas">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'LSP PrO Indo' }} - Sistem Sertifikasi BNSP</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-lsp-proindo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-lsp-proindo.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Inter (Modern Sans-Serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        lsp: {
                            navy: '#1E2B6E',
                            'navy-dark': '#162052',
                            'navy-light': '#2A3B94',
                            red: '#CE181E',
                            'red-dark': '#B01419',
                            canvas: '#F8FAFC',
                            'input-bg': '#F8FAFC',
                            title: '#0F172A',
                            muted: '#64748B',
                        },
                        brand: {
                            50: '#EEF3FC',
                            100: '#D9E4FA',
                            200: '#B8CFF7',
                            500: '#2A3B94',
                            600: '#1E2B6E',
                            700: '#162052',
                            800: '#11183E',
                            900: '#0B1028',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Inter"', 'system-ui', '-apple-system', 'sans-serif'],
                        serif: ['"Plus Jakarta Sans"', '"Inter"', 'system-ui', '-apple-system', 'sans-serif'],
                        heading: ['"Plus Jakarta Sans"', '"Inter"', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Notiflix CDN (v3.3.0) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notiflix@3.3.0/src/notiflix.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.3.0/dist/notiflix-aio-3.3.0.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        body, button, input, select, textarea {
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif;
            letter-spacing: -0.015em;
        }

        /* Simplified Clean Design System */
        .card-contrast,
        [class*="shadow-[2px_2px"],
        .border-2.border-black,
        .border-2.border-slate-900 {
            border-width: 1px !important;
            border-color: #E2E8F0 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05) !important;
        }

        /* Form Inputs & Controls */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="tel"],
        input[type="date"],
        input[type="time"],
        input[type="file"],
        select,
        textarea {
            border: 1px solid #CBD5E1 !important;
            background-color: #FFFFFF !important;
            color: #0F172A !important;
            font-size: 0.875rem !important;
            border-radius: 0.5rem !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #1E2B6E !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(30, 43, 110, 0.12) !important;
        }

        /* Clean Modern Tables */
        table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }

        table thead tr {
            background-color: #F8FAFC !important;
            border-bottom: 1px solid #E2E8F0 !important;
        }

        table thead th {
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        table tbody td {
            border-bottom: 1px solid #F1F5F9 !important;
            color: #334155 !important;
        }

        /* Smooth Touch Scrolling */
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }

        /* Auth Container */
        .max-w-4xl.bg-white {
            border: 1px solid #E2E8F0 !important;
            border-radius: 1rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full font-sans text-slate-800 antialiased bg-lsp-canvas" x-data="{ sidebarOpen: false }">

    <!-- Desktop Left Screen Vertical Dual Bar (Hidden on Mobile) -->
    <div class="hidden md:flex fixed left-0 top-0 bottom-0 w-1.5 z-50 pointer-events-none">
        <div class="w-0.75 bg-lsp-navy h-full"></div>
        <div class="w-0.75 bg-lsp-red h-full"></div>
    </div>

    @guest
        <!-- Guest Content (Login, Register) -->
        <main class="min-h-screen flex flex-col justify-center items-center p-3 sm:p-6 md:pl-8">
            @yield('content')
        </main>
    @else
        <!-- Authenticated Layout with Sidebar -->
        <div class="min-h-screen flex flex-col">

            <!-- Mobile Sidebar Backdrop -->
            <div x-show="sidebarOpen" 
                 x-cloak
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs md:hidden transition-opacity"></div>

            <!-- Sidebar Navigation -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 md:left-1.5 z-50 md:z-30 w-72 md:w-64 bg-white border-r border-slate-200 shadow-xl md:shadow-none flex flex-col justify-between transition-transform duration-200 ease-in-out md:translate-x-0">
                
                <!-- Sidebar Top Section -->
                <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
                    <!-- Top Horizontal Dual Stripe -->
                    <div class="w-full flex h-1 shrink-0">
                        <div class="w-2/3 bg-lsp-navy"></div>
                        <div class="w-1/3 bg-lsp-red"></div>
                    </div>

                    <!-- Brand / Logo with Mobile Close Button -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('images/logo-lsp-wisindo.png') }}" 
                                 alt="Logo LSP" 
                                 class="h-10 sm:h-12 w-auto object-contain flex-shrink-0">
                            <div class="flex flex-col">
                                <span class="font-sans font-bold text-base leading-tight text-lsp-title tracking-tight">
                                    LSP PrO Indo
                                </span>
                                <span class="text-[9px] font-bold text-lsp-red uppercase tracking-wider mt-0.5">
                                    PRAKTISI OTOMOTIF
                                </span>
                                <span class="text-[9px] text-slate-400 font-medium">
                                    Lisensi BNSP
                                </span>
                            </div>
                        </div>

                        <!-- Mobile Close Button -->
                        <button @click="sidebarOpen = false" 
                                type="button" 
                                aria-label="Tutup Menu"
                                class="md:hidden p-1.5 -mr-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation Links Grouped by Role -->
                    <nav class="p-3 space-y-1 text-xs">
                        
                        @if(auth()->user()->isRole('ASESI'))
                            <div class="px-3 pt-3 pb-1 text-[10px] font-extrabold text-lsp-muted uppercase tracking-wider">
                                Menu Asesi
                            </div>
                            <a href="{{ route('asesi.dashboard') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('asesi.dashboard') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span>Dashboard Asesi</span>
                            </a>
                            <a href="{{ route('asesi.profile.edit') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('asesi.profile.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                                </svg>
                                <span>Biodata & TTD Digital</span>
                            </a>
                            <a href="{{ route('asesi.jadwal.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('asesi.jadwal.*') || request()->routeIs('asesi.assessment.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Jadwal Asesmen Uji</span>
                            </a>
                            <a href="{{ route('asesi.certificates.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('asesi.certificates.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Sertifikat Saya</span>
                            </a>

                        @elseif(auth()->user()->isRole('ASESOR'))
                            <div class="px-3 pt-3 pb-1 text-[10px] font-extrabold text-lsp-muted uppercase tracking-wider">
                                Menu Asesor
                            </div>
                            <a href="{{ route('asesor.jadwal.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('asesor.jadwal.*') || request()->routeIs('asesor.schedule.*') || request()->routeIs('asesor.assessment.*') || request()->routeIs('asesor.review-apl*') || request()->routeIs('asesor.ruang-uji*') || request()->routeIs('asesor.ak*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Jadwal Penugasan Uji</span>
                            </a>
                            <a href="{{ route('asesor.profile.edit') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('asesor.profile.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                                </svg>
                                <span>Biodata & TTD Asesor</span>
                            </a>

                        @elseif(auth()->user()->isAdmin() || in_array(auth()->user()->role, ['KOMITE_TEKNIS']))
                            <div class="px-3 pt-3 pb-1 text-[10px] font-extrabold text-lsp-muted uppercase tracking-wider">
                                Manajemen Asesmen
                            </div>
                            <a href="{{ route('admin.dashboard') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                <span>Dashboard Admin</span>
                            </a>
                            <a href="{{ route('admin.profile.edit') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.profile.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                                </svg>
                                <span>Profil &amp; TTD Admin</span>
                            </a>
                            <a href="{{ route('admin.jadwal.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.jadwal.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Kelola Jadwal Asesmen</span>
                            </a>
                            <a href="{{ route('admin.certificates.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.certificates.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Kelola Sertifikat</span>
                            </a>
                            <a href="{{ route('admin.dokumen-muk.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.dokumen-muk.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Dokumen MUK Ekspor</span>
                            </a>
                            <a href="{{ route('admin.appeals.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.appeals.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                </svg>
                                <span>Pusat Umpan Asesi</span>
                            </a>
                            <a href="{{ route('admin.proxy.index') }}" 
                               class="flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-semibold transition {{ request()->routeIs('admin.proxy.*') ? 'bg-lsp-input-bg text-lsp-navy border-l-4 border-lsp-navy font-bold' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                <svg class="w-4 h-4 text-lsp-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span>Switch Role (Proxy)</span>
                            </a>

                            @if(auth()->user()->isRole('SUPER_ADMIN'))
                                <div class="px-3 pt-4 pb-1 text-[10px] font-extrabold text-lsp-muted uppercase tracking-wider">
                                    Super Admin Master
                                </div>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="flex items-center space-x-3 px-3 py-2 rounded text-xs font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-lsp-input-bg text-lsp-navy font-bold border-l-4 border-lsp-navy' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span>Master Pengguna</span>
                                </a>
                                <a href="{{ route('admin.schemes.index') }}" 
                                   class="flex items-center space-x-3 px-3 py-2 rounded text-xs font-medium transition {{ request()->routeIs('admin.schemes.*') ? 'bg-lsp-input-bg text-lsp-navy font-bold border-l-4 border-lsp-navy' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <span>Master Skema Sertifikasi</span>
                                </a>
                                <a href="{{ route('admin.question-banks.index') }}" 
                                   class="flex items-center space-x-3 px-3 py-2 rounded text-xs font-medium transition {{ request()->routeIs('admin.question-banks.*') ? 'bg-lsp-input-bg text-lsp-navy font-bold border-l-4 border-lsp-navy' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Bank Soal CBT (IA05)</span>
                                </a>
                                <a href="{{ route('admin.backups.index') }}" 
                                   class="flex items-center space-x-3 px-3 py-2 rounded text-xs font-medium transition {{ request()->routeIs('admin.backups.*') ? 'bg-lsp-input-bg text-lsp-navy font-bold border-l-4 border-lsp-navy' : 'text-slate-600 hover:text-lsp-navy hover:bg-slate-50' }}">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                    </svg>
                                    <span>Backup Database (SQL)</span>
                                </a>
                            @endif
                        @endif

                        <!-- Logout / Switch Back Button (dipaling bawah setelah sidebar) -->
                        @if(session('admin_impersonator_id'))
                            <div class="pt-3 mt-3 border-t border-slate-100">
                                <form action="{{ route('proxy.switch-back') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" 
                                            title="Kembali ke Sesi Admin" 
                                            class="w-full flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-bold transition text-white bg-amber-600 hover:bg-amber-700 active:bg-amber-800 shadow-xs focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1 cursor-pointer">
                                        <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                                        </svg>
                                        <span>Kembali ke Admin</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="pt-3 mt-3 border-t border-slate-100">
                                <form action="{{ route('logout') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" 
                                            title="Keluar dari Sistem" 
                                            class="w-full flex items-center space-x-3 px-3 py-2.5 rounded text-xs font-bold transition text-white bg-red-600 hover:bg-red-700 active:bg-red-800 shadow-xs hover:shadow focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 cursor-pointer">
                                        <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        <span>Log Out</span>
                                    </button>
                                </form>
                            </div>
                        @endif

                    </nav>
                </div>

                <!-- Sidebar Bottom: User Profile -->
                <div class="p-3 border-t border-slate-200 bg-slate-50/50 shrink-0">
                    <div class="flex items-center justify-between p-1">
                        @php
                            $userProfileLink = match(true) {
                                auth()->user()->isRole('ASESI') => route('asesi.profile.edit'),
                                auth()->user()->isRole('ASESOR') => route('asesor.profile.edit'),
                                auth()->user()->isAdmin() => route('admin.profile.edit'),
                                default => null,
                            };
                        @endphp
                        @if($userProfileLink)
                            <a href="{{ $userProfileLink }}" title="Buka Profil &amp; Pengaturan" class="flex items-center space-x-2.5 overflow-hidden group hover:opacity-85 transition w-full">
                                <div class="w-8 h-8 rounded bg-lsp-navy group-hover:bg-lsp-navy-dark text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm">
                                    {{ strtoupper(substr(auth()->user()->full_name, 0, 2)) }}
                                </div>
                                <div class="truncate flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-800 group-hover:text-lsp-navy truncate leading-tight">
                                        {{ auth()->user()->full_name }}
                                    </p>
                                    <span class="inline-block px-1.5 py-0.2 text-[9px] font-bold rounded bg-lsp-navy/10 text-lsp-navy uppercase tracking-wider mt-0.5">
                                        {{ auth()->user()->role }}
                                    </span>
                                </div>
                            </a>
                        @else
                            <div class="flex items-center space-x-2.5 overflow-hidden w-full">
                                <div class="w-8 h-8 rounded bg-lsp-navy text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm">
                                    {{ strtoupper(substr(auth()->user()->full_name, 0, 2)) }}
                                </div>
                                <div class="truncate flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-800 truncate leading-tight">
                                        {{ auth()->user()->full_name }}
                                    </p>
                                    <span class="inline-block px-1.5 py-0.2 text-[9px] font-bold rounded bg-lsp-navy/10 text-lsp-navy uppercase tracking-wider mt-0.5">
                                        {{ auth()->user()->role }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </aside>

            <!-- Main Content Area (Shifted for sidebar) -->
            <div class="md:pl-64 flex flex-col flex-1 min-w-0">
                
                <!-- Top Horizontal Header Bar -->
                <header class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-30">
                    <!-- Top dual stripe above the header bar -->
                    <div class="w-full flex h-1">
                        <div class="w-2/3 bg-lsp-navy"></div>
                        <div class="w-1/3 bg-lsp-red"></div>
                    </div>

                    <div class="px-3.5 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3">
                        <!-- Left: Mobile Toggle & Page Title -->
                        <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                            <button @click="sidebarOpen = !sidebarOpen" 
                                    type="button" 
                                    aria-label="Buka Menu"
                                    class="p-2 -ml-1 rounded-lg text-slate-600 hover:text-lsp-navy hover:bg-slate-100 md:hidden focus:outline-none shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                            <h1 class="font-sans font-bold text-base sm:text-lg text-lsp-title tracking-tight truncate">
                                {{ $title ?? 'Sistem Asesmen Digital' }}
                            </h1>
                        </div>

                        <!-- Right: Portal & Impersonation Badge -->
                        <div class="flex items-center space-x-2.5 sm:space-x-3 shrink-0">
                            @if(session('admin_impersonator_id'))
                                <div class="bg-amber-100 border border-amber-300 px-2.5 py-1 rounded text-[11px] sm:text-xs font-semibold text-amber-900 flex items-center space-x-2 shadow-xs">
                                    <span class="truncate max-w-[120px] sm:max-w-none">Proxy: <strong>{{ auth()->user()->full_name }}</strong></span>
                                    <form action="{{ route('proxy.switch-back') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-amber-800 hover:bg-slate-900 text-white px-2 py-0.5 rounded text-[10px] font-bold transition">
                                            Kembali &rarr;
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <div class="hidden sm:flex items-center space-x-2 text-xs text-slate-500 font-medium">
                                <svg class="w-3.5 h-3.5 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ now()->isoFormat('D MMMM Y') }}</span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content Body -->
                <main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    
                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <div class="mb-6 rounded bg-emerald-50 border-l-4 border-emerald-500 p-4 text-xs text-emerald-800 flex items-start space-x-3 shadow-sm">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1 font-medium">{{ session('success') }}</div>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="mb-6 rounded bg-amber-50 border-l-4 border-amber-500 p-4 text-xs text-amber-900 flex items-start space-x-3 shadow-sm">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="flex-1 font-medium">{{ session('warning') }}</div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 rounded bg-rose-50 border-l-4 border-lsp-red p-4 text-xs text-rose-800 flex items-start space-x-3 shadow-sm">
                            <svg class="w-4 h-4 text-lsp-red shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1 font-medium">{{ session('error') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 rounded bg-rose-50 border-l-4 border-lsp-red p-4 text-xs text-rose-800 shadow-sm">
                            <div class="flex items-center space-x-2 font-bold mb-1">
                                <svg class="w-4 h-4 text-lsp-red shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Terjadi kesalahan validasi:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-0.5 text-rose-700 ml-5 text-xs">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>

                <!-- Footer -->
                <footer class="bg-white border-t border-slate-200 py-4 mt-auto">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-[11px] text-slate-500">
                        <p>&copy; {{ date('Y') }} <strong>LSP PrO Indo</strong> (Lembaga Sertifikasi Profesi Praktisi Otomotif Indonesia). Lisensi Resmi BNSP.</p>
                    </div>
                </footer>

            </div>
        </div>
    @endguest

    <script>
        // Configure Notiflix Modules
        if (typeof window !== 'undefined' && window.Notiflix) {
            Notiflix.Confirm.init({
                className: 'notiflix-confirm',
                width: '420px',
                zindex: 99998,
                position: 'center',
                distance: '10px',
                backgroundColor: '#ffffff',
                borderRadius: '16px',
                backOverlay: true,
                backOverlayColor: 'rgba(15, 23, 42, 0.65)',
                rtl: false,
                fontFamily: '"Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif',
                cssAnimation: true,
                cssAnimationDuration: 250,
                cssAnimationStyle: 'fade',
                plainText: false,

                titleColor: '#0F172A',
                titleFontSize: '17px',
                titleMaxLength: 100,

                messageColor: '#475569',
                messageFontSize: '13px',
                messageMaxLength: 1500,

                buttonsFontSize: '13px',
                buttonsMaxLength: 40,
                okButtonColor: '#ffffff',
                okButtonBackground: '#1E2B6E',
                cancelButtonColor: '#475569',
                cancelButtonBackground: '#F1F5F9',
            });

            Notiflix.Loading.init({
                className: 'notiflix-loading',
                zindex: 99999,
                backgroundColor: 'rgba(15, 23, 42, 0.78)',
                fontFamily: '"Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif',
                cssAnimation: true,
                cssAnimationDuration: 300,
                clickToClose: false,
                svgSize: '85px',
                svgColor: '#CE181E',
                messageFontSize: '15px',
                messageMaxLength: 200,
                messageColor: '#ffffff',
            });

            Notiflix.Report.init({
                className: 'notiflix-report',
                width: '420px',
                backgroundColor: '#ffffff',
                borderRadius: '16px',
                rtl: false,
                zindex: 99998,
                backOverlay: true,
                backOverlayColor: 'rgba(15, 23, 42, 0.65)',
                backOverlayClickToClose: false,
                fontFamily: '"Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif',
                svgSize: '72px',
                plainText: false,
                titleFontSize: '17px',
                titleMaxLength: 100,
                messageFontSize: '13px',
                messageMaxLength: 1500,
                buttonFontSize: '13px',
                buttonMaxLength: 40,
                cssAnimation: true,
                cssAnimationDuration: 250,
                cssAnimationStyle: 'fade',

                success: {
                    svgColor: '#10b981',
                    titleColor: '#0F172A',
                    messageColor: '#475569',
                    buttonBackground: '#1E2B6E',
                    buttonColor: '#ffffff',
                    backOverlayColor: 'rgba(15, 23, 42, 0.65)',
                },
                failure: {
                    svgColor: '#CE181E',
                    titleColor: '#0F172A',
                    messageColor: '#475569',
                    buttonBackground: '#CE181E',
                    buttonColor: '#ffffff',
                    backOverlayColor: 'rgba(15, 23, 42, 0.65)',
                },
                warning: {
                    svgColor: '#f59e0b',
                    titleColor: '#0F172A',
                    messageColor: '#475569',
                    buttonBackground: '#1E2B6E',
                    buttonColor: '#ffffff',
                    backOverlayColor: 'rgba(15, 23, 42, 0.65)',
                },
                info: {
                    svgColor: '#1E2B6E',
                    titleColor: '#0F172A',
                    messageColor: '#475569',
                    buttonBackground: '#1E2B6E',
                    buttonColor: '#ffffff',
                    backOverlayColor: 'rgba(15, 23, 42, 0.65)',
                },
            });

            Notiflix.Notify.init({
                width: '320px',
                position: 'right-top',
                distance: '16px',
                opacity: 1,
                borderRadius: '12px',
                rtl: false,
                timeout: 3000,
                messageMaxLength: 300,
                plainText: false,
                showOnlyTheLastOne: false,
                clickToClose: true,
                pauseOnHover: true,
                fontFamily: '"Plus Jakarta Sans", "Inter", system-ui, -apple-system, sans-serif',
                fontSize: '13px',
                cssAnimation: true,
                cssAnimationDuration: 300,
                cssAnimationStyle: 'from-right',
                closeButton: true,
                useIcon: true,
                success: {
                    background: '#059669',
                    textColor: '#ffffff',
                    notiflixIconColor: 'rgba(255,255,255,0.9)',
                },
                failure: {
                    background: '#CE181E',
                    textColor: '#ffffff',
                    notiflixIconColor: 'rgba(255,255,255,0.9)',
                },
                warning: {
                    background: '#d97706',
                    textColor: '#ffffff',
                    notiflixIconColor: 'rgba(255,255,255,0.9)',
                },
                info: {
                    background: '#1E2B6E',
                    textColor: '#ffffff',
                    notiflixIconColor: 'rgba(255,255,255,0.9)',
                },
            });
        }

        // Global Alert Override using Notiflix Report
        if (typeof window !== 'undefined') {
            window.__nativeAlert = window.alert;
            window.alert = function (message) {
                if (window.Notiflix && Notiflix.Report) {
                    var formatted = String(message || '').replace(/\n/g, '<br>');
                    Notiflix.Report.warning('Perhatian', formatted, 'Mengerti');
                } else if (window.__nativeAlert) {
                    window.__nativeAlert(message);
                }
            };
        }

        // Global Pulse Helpers
        window.showPulseLoading = function (message) {
            if (window.Notiflix && Notiflix.Loading) {
                Notiflix.Loading.pulse(message || 'Memproses data, mohon tunggu...');
            }
        };

        window.hidePulseLoading = function (delay) {
            if (window.Notiflix && Notiflix.Loading) {
                Notiflix.Loading.remove(delay || 0);
            }
        };

        // Global Notiflix Confirm Helper
        window.notiflixConfirm = function (title, message, okText, cancelText, onOk, onCancel, customOptions) {
            if (!window.Notiflix || !Notiflix.Confirm) {
                if (typeof onOk === 'function') onOk();
                return;
            }

            var formatted = String(message || '').replace(/\n/g, '<br>');
            var isDanger = /hapus|delete|batal|permanen|nonaktifkan/i.test((message || '') + ' ' + (title || ''));
            var defaultOpts = isDanger ? { okButtonBackground: '#CE181E' } : { okButtonBackground: '#1E2B6E' };
            var opts = Object.assign({}, defaultOpts, customOptions || {});

            Notiflix.Confirm.show(
                title || 'Konfirmasi Tindakan',
                formatted,
                okText || 'Ya, Lanjutkan',
                cancelText || 'Batal',
                function () {
                    if (typeof onOk === 'function') onOk();
                },
                function () {
                    if (typeof onCancel === 'function') onCancel();
                },
                opts
            );
        };

        document.addEventListener('DOMContentLoaded', function () {
            // Auto-convert legacy inline confirmation handlers to data-confirm
            document.querySelectorAll('form[onsubmit*="confirm"], button[onclick*="confirm"], a[onclick*="confirm"]').forEach(function (el) {
                var attr = el.hasAttribute('onsubmit') ? 'onsubmit' : 'onclick';
                var rawCode = el.getAttribute(attr);
                var match = rawCode.match(/confirm\s*\(\s*(['"`])([\s\S]*?)\1\s*\)/);
                if (match && match[2]) {
                    var msg = match[2].replace(/\\n/g, '\n').replace(/\\'/g, "'").replace(/\\"/g, '"');
                    el.removeAttribute(attr);
                    el.setAttribute('data-confirm', msg);
                }
            });

            // Universal data-confirm click interceptor for buttons and links
            document.addEventListener('click', function (e) {
                var trigger = e.target.closest('[data-confirm]');
                if (!trigger || trigger.tagName === 'FORM') return;

                if (trigger.dataset.confirmed === 'true') {
                    delete trigger.dataset.confirmed;
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();

                var message = trigger.getAttribute('data-confirm');
                var title = trigger.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
                var okText = trigger.getAttribute('data-confirm-ok') || 'Ya, Lanjutkan';
                var cancelText = trigger.getAttribute('data-confirm-cancel') || 'Batal';
                var isDanger = trigger.getAttribute('data-confirm-danger') === 'true' || /hapus|delete|batal|permanen|nonaktifkan/i.test(message + ' ' + title);

                window.notiflixConfirm(
                    title,
                    message,
                    okText,
                    cancelText,
                    function () {
                        trigger.dataset.confirmed = 'true';
                        if (trigger.tagName === 'A' && trigger.href) {
                            window.location.href = trigger.href;
                        } else if (trigger.tagName === 'BUTTON' || trigger.tagName === 'INPUT') {
                            var form = trigger.closest('form');
                            if (form) {
                                form.dataset.confirmed = 'true';
                                if (trigger.name && trigger.value) {
                                    var hidden = document.createElement('input');
                                    hidden.type = 'hidden';
                                    hidden.name = trigger.name;
                                    hidden.value = trigger.value;
                                    form.appendChild(hidden);
                                }
                                if (typeof form.requestSubmit === 'function') {
                                    form.requestSubmit(trigger);
                                } else {
                                    form.submit();
                                }
                            } else {
                                trigger.click();
                            }
                        } else {
                            trigger.click();
                        }
                    },
                    function () {},
                    isDanger ? { okButtonBackground: '#CE181E' } : {}
                );
            }, true);

            // Strict numeric-only input restriction for integer fields (kode pos, nik, phone, etc.)
            document.addEventListener('keydown', function (e) {
                const el = e.target;
                if (!el || el.tagName !== 'INPUT') return;
                if (el.matches('.numeric-only, [data-numeric-only], [inputmode="numeric"]')) {
                    if (e.ctrlKey || e.metaKey || (e.key && e.key.length > 1)) {
                        return;
                    }
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                    }
                }
            });

            document.addEventListener('input', function (e) {
                const el = e.target;
                if (!el || el.tagName !== 'INPUT') return;
                if (el.matches('.numeric-only, [data-numeric-only], [inputmode="numeric"]')) {
                    el.value = el.value.replace(/\D/g, '');
                }
            });

            // Form submission interceptor: Confirmation + Loading Pulse + Anti-double-click
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form || form.tagName !== 'FORM') return;

                if (form.hasAttribute('data-no-disable')) return;
                if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

                // Form-level confirmation check
                if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== 'true') {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const message = form.getAttribute('data-confirm');
                    const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
                    const okText = form.getAttribute('data-confirm-ok') || 'Ya, Lanjutkan';
                    const cancelText = form.getAttribute('data-confirm-cancel') || 'Batal';
                    const isDanger = form.getAttribute('data-confirm-danger') === 'true' || /hapus|delete|batal|permanen|nonaktifkan/i.test(message + ' ' + title);

                    window.notiflixConfirm(
                        title,
                        message,
                        okText,
                        cancelText,
                        function () {
                            form.dataset.confirmed = 'true';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit(e.submitter);
                            } else {
                                form.submit();
                            }
                        },
                        function () {},
                        isDanger ? { okButtonBackground: '#CE181E' } : {}
                    );
                    return false;
                }

                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }

                form.dataset.submitting = 'true';

                // Contextual Loading Pulse
                if (!form.hasAttribute('data-no-loading')) {
                    var loadingText = form.getAttribute('data-loading-text') ||
                        (e.submitter && e.submitter.getAttribute('data-loading-text'));

                    if (!loadingText) {
                        var formId = form.id || '';
                        var action = (form.getAttribute('action') || '').toLowerCase();
                        var isDelete = form.querySelector('input[name="_method"][value="DELETE"]') !== null || (form.getAttribute('method') || '').toUpperCase() === 'DELETE';

                        if (formId === 'aplForm' || action.indexOf('/apl') !== -1) {
                            loadingText = 'Mengunggah berkas portofolio & memproses data APL 01...';
                        } else if (formId === 'cbtForm' || action.indexOf('ia-05') !== -1 || action.indexOf('cbt') !== -1) {
                            loadingText = 'Menyimpan jawaban ujian CBT & memproses hasil...';
                        } else if (action.indexOf('/login') !== -1) {
                            loadingText = 'Memverifikasi akun & masuk ke sistem...';
                        } else if (action.indexOf('/register') !== -1) {
                            loadingText = 'Mendaftarkan akun Anda...';
                        } else if (action.indexOf('/logout') !== -1) {
                            loadingText = 'Keluar dari sistem...';
                        } else if (action.indexOf('/certificate') !== -1 || action.indexOf('/sertifikat') !== -1) {
                            loadingText = 'Memproses berkas sertifikat...';
                        } else if (isDelete) {
                            loadingText = 'Menghapus data dari sistem...';
                        } else {
                            loadingText = 'Memproses data, mohon tunggu sebentar...';
                        }
                    }

                    if (window.Notiflix && Notiflix.Loading) {
                        Notiflix.Loading.pulse(loadingText);
                    }
                }
            });

            // Remove loading on pageshow (e.g. browser back button from bfcache)
            window.addEventListener('pageshow', function () {
                if (window.Notiflix && Notiflix.Loading) {
                    Notiflix.Loading.remove();
                }
                document.querySelectorAll('form').forEach(function (form) {
                    delete form.dataset.submitting;
                    delete form.dataset.confirmed;
                });
            });
        });
    </script>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.Notiflix && Notiflix.Notify) {
                    Notiflix.Notify.success(@js(session('success')));
                }
            });
        </script>
    @endif
    @if(session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.Notiflix && Notiflix.Notify) {
                    Notiflix.Notify.warning(@js(session('warning')));
                }
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.Notiflix && Notiflix.Notify) {
                    Notiflix.Notify.failure(@js(session('error')));
                }
            });
        </script>
    @endif

    <script src="{{ asset('js/ajax-tables.js') }}"></script>
    @stack('scripts')
</body>
</html>