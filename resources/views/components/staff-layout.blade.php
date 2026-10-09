@props(['title' => 'Staff Workspace'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Raflora Enterprises</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen lg:flex">
        {{-- Backdrop for mobile drawer --}}
        <div id="staffSidebarBackdrop"
             class="fixed inset-0 z-40 hidden bg-slate-950/40 backdrop-blur-[1px] transition-opacity duration-300 lg:hidden"
             aria-hidden="true"></div>

        {{-- Staff Navigation Sidebar: Responsive Drawer (<lg) / Static Sidebar (lg+) --}}
        <aside id="staffSidebar"
               class="fixed inset-y-0 left-0 z-50 flex h-full w-72 flex-shrink-0 flex-col justify-between overflow-y-auto border-r border-slate-200 bg-white transition-transform duration-300 ease-in-out -translate-x-full lg:static lg:z-auto lg:h-auto lg:min-h-screen lg:w-72 lg:translate-x-0 lg:overflow-visible"
               aria-label="Staff navigation sidebar">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/images/logo.jpg') }}" alt="Raflora logo" class="h-12 w-12 rounded-full object-cover shadow-sm ring-2 ring-slate-100">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-slate-500">Raflora</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">Staff Workspace</p>
                        </div>
                    </div>
                    <button id="closeStaffSidebarBtn" type="button"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 lg:hidden"
                            aria-label="Close Staff navigation">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <nav class="p-4" aria-label="Staff navigation">
                    <p class="mb-2 ml-1 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Operations</p>
                    <a href="{{ route('staff.dashboard') }}"
                       class="flex items-center gap-3 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3 font-semibold text-purple-800 shadow-sm transition hover:border-purple-200 hover:bg-purple-100"
                       @if(request()->routeIs('staff.dashboard*') || request()->routeIs('staff.events.*')) aria-current="page" @endif>
                        <i class="fa-solid fa-gauge w-5 text-base" aria-hidden="true"></i>
                        <span>Workspace</span>
                    </a>
                </nav>
            </div>

            <div class="border-t border-slate-200 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white/90 px-4 py-4 backdrop-blur-sm sm:px-8">
                <div class="flex items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-3">
                        <button id="mobileStaffToggle" type="button"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 lg:hidden"
                                aria-controls="staffSidebar"
                                aria-expanded="false"
                                aria-label="Open Staff navigation">
                            <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
                        </button>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-purple-600">Raflora Enterprises</p>
                            <h1 class="serif mt-1 text-xl font-bold text-slate-900 sm:text-2xl">{{ $title }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <span class="hidden rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-700 sm:inline-flex">Online</span>
                        <div class="flex items-center gap-2 sm:gap-3 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-sm text-slate-600">
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-purple-700">{{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700" aria-label="Log out">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 bg-slate-100 p-4 sm:p-6 lg:p-8" aria-label="Staff workspace content">
                <div class="mx-auto max-w-7xl">
                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <x-alert type="success">{{ session('success') }}</x-alert>
                    @endif
                    @if(session('error'))
                        <x-alert type="danger">{{ session('error') }}</x-alert>
                    @endif
                    @if(session('warning'))
                        <x-alert type="warning">{{ session('warning') }}</x-alert>
                    @endif
                    @if(session('info'))
                        <x-alert type="info">{{ session('info') }}</x-alert>
                    @endif
                    @if($errors->any())
                        <x-alert type="danger">
                            <div class="font-semibold mb-1">Please fix the following errors:</div>
                            <ul class="list-disc list-inside space-y-0.5 text-[13px]">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('staffSidebar');
            const backdrop = document.getElementById('staffSidebarBackdrop');
            const toggleBtn = document.getElementById('mobileStaffToggle');
            const closeBtn = document.getElementById('closeStaffSidebarBtn');
            const navLinks = sidebar ? sidebar.querySelectorAll('a') : [];

            if (!sidebar || !backdrop || !toggleBtn) return;

            const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

            const updateAriaAndInert = (isOpen) => {
                if (isDesktop()) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.removeAttribute('inert');
                    sidebar.removeAttribute('aria-hidden');
                    backdrop.classList.add('hidden');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                    toggleBtn.setAttribute('aria-label', 'Open Staff navigation');
                    document.body.classList.remove('overflow-hidden');
                } else if (isOpen) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.removeAttribute('inert');
                    sidebar.setAttribute('aria-hidden', 'false');
                    backdrop.classList.remove('hidden');
                    toggleBtn.setAttribute('aria-expanded', 'true');
                    toggleBtn.setAttribute('aria-label', 'Close Staff navigation');
                    document.body.classList.add('overflow-hidden');
                    if (closeBtn) {
                        closeBtn.focus();
                    }
                } else {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.setAttribute('inert', '');
                    sidebar.setAttribute('aria-hidden', 'true');
                    backdrop.classList.add('hidden');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                    toggleBtn.setAttribute('aria-label', 'Open Staff navigation');
                    document.body.classList.remove('overflow-hidden');
                }
            };

            // Set initial state based on viewport
            updateAriaAndInert(false);

            toggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = !sidebar.classList.contains('-translate-x-full');
                updateAriaAndInert(!isOpen);
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    updateAriaAndInert(false);
                    toggleBtn.focus();
                });
            }

            backdrop.addEventListener('click', function () {
                updateAriaAndInert(false);
                toggleBtn.focus();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !isDesktop()) {
                    const isOpen = !sidebar.classList.contains('-translate-x-full');
                    if (isOpen) {
                        updateAriaAndInert(false);
                        toggleBtn.focus();
                    }
                }
            });

            navLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    if (!isDesktop()) {
                        updateAriaAndInert(false);
                    }
                });
            });

            window.addEventListener('resize', function () {
                if (isDesktop()) {
                    updateAriaAndInert(false);
                } else {
                    if (sidebar.classList.contains('-translate-x-full')) {
                        sidebar.setAttribute('inert', '');
                        sidebar.setAttribute('aria-hidden', 'true');
                    }
                }
            });
        });
    </script>
</body>
</html>
