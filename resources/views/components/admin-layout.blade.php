@props(['title' => 'Admin Dashboard'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Raflora Enterprises</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen w-full bg-slate-50">

    <div id="adminSidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-950/40 backdrop-blur-[1px] lg:hidden" aria-hidden="true"></div>

    <!-- LEFT SIDEBAR: RESPONSIVE OFF-CANVAS / FIXED -->
    <aside id="adminSidebar" class="fixed inset-y-0 left-0 w-64 h-full flex-shrink-0 bg-white border-r border-gray-200 overflow-y-auto flex flex-col justify-between z-40 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div>
            <!-- Sidebar Header / Logo -->
            <div class="flex w-full items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
                <img src="{{ asset('assets/images/logo.jpg') }}" alt="Raflora Enterprises" class="h-10 w-10 shrink-0 rounded-xl object-cover shadow-sm ring-1 ring-slate-200">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-900">Raflora Enterprises</p>
                    <p class="truncate text-[11px] font-medium text-slate-500">Admin workspace</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-5 px-3 py-4" aria-label="Admin navigation">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2 ml-1">Overview</p>
                    <a href="{{ route('admin.dashboard') }}" class="rf-nav-item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                        <i class="fa-solid fa-gauge w-5" aria-hidden="true"></i><span>Dashboard</span>
                    </a>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2 ml-1">Bookings</p>
                    <details class="group" {{ request()->routeIs('admin.bookings*') ? 'open' : '' }}>
                        <summary class="rf-nav-item cursor-pointer list-none justify-between [&::-webkit-details-marker]:hidden {{ request()->routeIs('admin.bookings*') ? 'is-active' : '' }}">
                            <div class="flex items-center gap-[0.75rem]">
                                <i class="fa-solid fa-calendar-days w-5" aria-hidden="true"></i>
                                <span>Booking Management</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-70 transition-transform duration-200 group-open:rotate-180"></i>
                        </summary>
                        <div class="mt-1 ml-3 pl-4 border-l border-slate-200 space-y-1">
                            <a href="{{ route('admin.bookings') }}" class="rf-nav-item !py-2 !text-sm {{ request()->routeIs('admin.bookings*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.bookings*')) aria-current="page" @endif>
                                <span>All Bookings</span>
                            </a>
                        </div>
                    </details>
                    <a href="{{ route('admin.notifications') }}" class="rf-nav-item {{ request()->routeIs('admin.notifications*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.notifications*')) aria-current="page" @endif>
                        <i class="fa-solid fa-bell w-5" aria-hidden="true"></i>
                        <span>Notifications</span>
                        @php
                            $unreadAlerts = \App\Models\AdminAlert::where('is_read', false)->count()
                                + \App\Models\Booking::whereIn('status', ['payment_submitted', 'payment_pending'])->count();
                        @endphp
                        @if($unreadAlerts > 0)
                            <span class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $unreadAlerts }}</span>
                        @endif
                    </a>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2 ml-1">Operations</p>
                    <a href="{{ route('admin.gallery') }}" class="rf-nav-item {{ request()->routeIs('admin.gallery*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.gallery*')) aria-current="page" @endif>
                        <i class="fa-regular fa-image w-5" aria-hidden="true"></i><span>Gallery Management</span>
                    </a>
                    <a href="{{ route('admin.packages.index') }}" class="rf-nav-item {{ request()->routeIs('admin.packages.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.packages.*')) aria-current="page" @endif>
                        <i class="fa-solid fa-gift w-5" aria-hidden="true"></i><span>Package Management</span>
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="rf-nav-item {{ request()->routeIs('admin.inventory*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.inventory*')) aria-current="page" @endif>
                        <i class="fa-solid fa-boxes-stacked w-5" aria-hidden="true"></i><span>Inventory Management</span>
                    </a>
                    <a href="{{ route('admin.return-tracking') }}" class="rf-nav-item {{ request()->routeIs('admin.return-tracking*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.return-tracking*')) aria-current="page" @endif>
                        <i class="fa-solid fa-truck-ramp-box w-5" aria-hidden="true"></i><span>Return Tracking</span>
                    </a>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2 ml-1">Clients</p>
                    <a href="{{ route('admin.client-records') }}" class="rf-nav-item {{ request()->routeIs('admin.client-records*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.client-records*')) aria-current="page" @endif>
                        <i class="fa-solid fa-folder-open w-5" aria-hidden="true"></i><span>Client Records</span>
                    </a>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2 ml-1">Analytics</p>
                    <a href="{{ route('admin.reports') }}" class="rf-nav-item {{ request()->routeIs('admin.reports*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.reports*')) aria-current="page" @endif>
                        <i class="fa-solid fa-chart-pie w-5" aria-hidden="true"></i><span>Reports & Analytics</span>
                    </a>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2 ml-1">System</p>
                    <a href="{{ route('admin.settings') }}" class="rf-nav-item {{ request()->routeIs('admin.settings*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.settings*')) aria-current="page" @endif>
                        <i class="fa-solid fa-users-gear w-5" aria-hidden="true"></i><span>Account Management</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Log Out Button Locked At Bottom of Sidebar -->
        <div class="p-4 border-t border-slate-200 bg-white">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="rf-btn rf-btn-ghost w-full">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- RIGHT MAIN CONTAINER -->
    <div class="flex flex-col lg:pl-64 min-h-screen w-full">
        
        <!-- Mobile Top Bar -->
        <div class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-3 py-2.5 backdrop-blur lg:hidden">
            <button id="mobileAdminBrandToggle" type="button" class="flex min-w-0 items-center gap-3 rounded-xl p-1 focus:outline-none focus-visible:ring-4 focus-visible:ring-purple-200" aria-controls="adminSidebar" aria-expanded="false" aria-label="Toggle admin navigation">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm" aria-hidden="true"><i class="fa-solid fa-bars"></i></span>
                <span id="mobileAdminPanelName" class="truncate text-sm font-bold text-slate-900">{{ $title }}</span>
            </button>
            <a href="{{ route('admin.notifications') }}" aria-label="Open admin notifications" class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm">
                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                @if($unreadAlerts > 0)
                    <span class="absolute -top-1 -right-1 h-3 w-3 rounded-full border-2 border-white bg-red-600"></span>
                @endif
            </a>
        </div>

        <!-- Top Fixed Page Header -->
        <header class="sticky top-0 z-20 hidden flex-shrink-0 items-center justify-between border-b border-slate-200 bg-white/85 px-6 py-3.5 backdrop-blur lg:flex">
            <div class="flex items-center gap-3">
                <h1 class="serif text-xl font-bold text-slate-900 xl:text-2xl">{{ $title }}</h1>
            </div>
            <div class="flex items-center gap-2 md:gap-4">
                <a href="{{ route('home') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-800 transition">View Site &rarr;</a>
                <a href="{{ route('admin.notifications') }}" aria-label="Open admin notifications" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 transition hover:bg-slate-100 relative">
                    <i class="fa-solid fa-bell text-slate-600" aria-hidden="true"></i>
                    @if($unreadAlerts > 0)
                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-600 rounded-full border-2 border-white"></span>
                    @endif
                </a>
            </div>
        </header>

        <!-- Main Work Area -->
        <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 pb-12 sm:p-6 xl:p-8" aria-label="Admin workspace content">
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
        </main>
    </div>

    <script>
        function toggleAdminSidebarState() {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                return;
            }

            const sidebar = document.getElementById('adminSidebar');
            if (!sidebar) return;

            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('adminSidebar');
            const mobileToggle = document.getElementById('mobileAdminBrandToggle');
            const sidebarBackdrop = document.getElementById('adminSidebarBackdrop');
            const mobilePanelName = document.getElementById('mobileAdminPanelName');
            const navLinks = document.querySelectorAll('#adminSidebar a');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleAdminSidebarState();
                    syncSidebarState();
                });
            }

            const syncSidebarState = () => {
                const isOpen = sidebar && !sidebar.classList.contains('-translate-x-full');
                mobileToggle?.setAttribute('aria-expanded', String(Boolean(isOpen)));
                sidebarBackdrop?.classList.toggle('hidden', !isOpen);
            };

            window.toggleAdminSidebar = () => {
                toggleAdminSidebarState();
                syncSidebarState();
            };
            sidebarBackdrop?.addEventListener('click', () => {
                sidebar?.classList.add('-translate-x-full');
                syncSidebarState();
                mobileToggle?.focus();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && sidebar && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                    syncSidebarState();
                    mobileToggle?.focus();
                }
            });

            const rightContainer = document.querySelector('.lg\\:pl-64');
            if (rightContainer) {
                rightContainer.addEventListener('click', function(e) {
                    if (mobileToggle && mobileToggle.contains(e.target)) {
                        return;
                    }
                    if (window.matchMedia('(max-width: 1023px)').matches) {
                        const sidebar = document.getElementById('adminSidebar');
                        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                            sidebar.classList.add('-translate-x-full');
                            syncSidebarState();
                        }
                    }
                });
            }

            if (sidebar && window.matchMedia('(min-width: 1024px)').matches) {
                sidebar.classList.remove('-translate-x-full');
            }

            if (mobilePanelName) {
                const currentTitle = document.querySelector('header h1');
                if (currentTitle && currentTitle.textContent.trim()) {
                    mobilePanelName.textContent = currentTitle.textContent.trim();
                }
            }

            navLinks.forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.matchMedia('(max-width: 1023px)').matches) {
                        const sidebar = document.getElementById('adminSidebar');
                        if (sidebar) {
                            sidebar.classList.add('-translate-x-full');
                                    syncSidebarState();
                        }
                    }
                });
            });
        });
    </script>

    <x-confirm-modal />
</body>
</html>