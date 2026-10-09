@props(['title' => 'Admin Dashboard', 'description' => null])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Raflora Enterprises</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
    </style>
    <script>
        (function() {
            try {
                if (localStorage.getItem('raflora_admin_sidebar_collapsed') === 'true' && window.innerWidth >= 1024) {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen w-full bg-slate-50">

    <!-- Mobile Slide-Over Backdrop -->
    <div id="adminSidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-950/40 backdrop-blur-[2px] transition-opacity duration-300 lg:hidden" aria-hidden="true" onclick="closeAdminSidebar()"></div>

    @php
        $unreadAlerts = \App\Models\AdminAlert::where('is_read', false)->count()
            + \App\Models\Booking::whereIn('status', ['payment_submitted', 'payment_pending'])->count();
    @endphp

    <!-- LEFT SIDEBAR: RESPONSIVE OFF-CANVAS / FIXED DESKTOP -->
    <aside
        id="adminSidebar"
        class="fixed inset-y-0 left-0 w-64 h-full flex-shrink-0 bg-white border-r border-slate-200 z-40 -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out flex flex-col justify-between overflow-hidden shadow-xs"
        aria-label="Admin sidebar"
    >
        <!-- Top Section: Brand Header & Collapse Toggles -->
        <div class="rf-sidebar-header p-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-white shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0 group" title="Raflora Admin Dashboard">
                    <img src="{{ asset('assets/images/raflora_flower_emblem_transparent.png') }}" alt="Raflora Enterprises" class="h-9 w-9 object-contain shrink-0">
                    <div class="rf-sidebar-brand-text min-w-0">
                        <span class="block text-sm font-bold text-slate-900 tracking-tight font-serif truncate group-hover:text-emerald-700 transition">Raflora</span>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest truncate">Floral Event Management</span>
                    </div>
                </a>
            </div>

            <!-- Action Controls (Desktop Collapse Button / Mobile Close Button) -->
            <div class="flex items-center gap-1 shrink-0">
                <!-- Desktop Collapse Button -->
                <button
                    type="button"
                    id="desktopSidebarToggleBtn"
                    onclick="toggleDesktopSidebar()"
                    class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-700 transition shadow-2xs cursor-pointer"
                    aria-label="Toggle sidebar collapse"
                    title="Toggle sidebar width"
                >
                    <i class="rf-collapse-btn-icon fa-solid fa-chevron-left text-[11px] transition-transform duration-200"></i>
                </button>

                <!-- Mobile Close Button -->
                <button
                    type="button"
                    onclick="closeAdminSidebar()"
                    class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                    aria-label="Close navigation"
                >
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
        </div>

        <!-- Middle Section: Scrolling Navigation Region -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden p-3 space-y-5" aria-label="Admin navigation">
            <!-- GROUP 1: OVERVIEW -->
            <div class="rf-nav-section">
                <p class="rf-sidebar-section-title text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1.5">Overview</p>
                <div class="space-y-1">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.dashboard*') ? 'is-active' : '' }}"
                        title="Dashboard"
                        data-title="Dashboard"
                        @if(request()->routeIs('admin.dashboard*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-gauge-high w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Dashboard</span>
                        <span class="rf-collapsed-tooltip">Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 2: BOOKINGS -->
            <div class="rf-nav-section">
                <p class="rf-sidebar-section-title text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1.5">Bookings</p>
                <div class="space-y-1">
                    <a
                        href="{{ route('admin.bookings') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.bookings*') ? 'is-active' : '' }}"
                        title="Booking Management"
                        data-title="Booking Management"
                        @if(request()->routeIs('admin.bookings*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-calendar-days w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Booking Management</span>
                        <span class="rf-collapsed-tooltip">Booking Management</span>
                    </a>

                    <a
                        href="{{ route('admin.notifications') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.notifications*') ? 'is-active' : '' }}"
                        title="Notifications"
                        data-title="Notifications"
                        @if(request()->routeIs('admin.notifications*')) aria-current="page" @endif
                    >
                        <div class="relative w-5 text-center shrink-0 flex items-center justify-center">
                            <i class="fa-solid fa-bell text-sm" aria-hidden="true"></i>
                            @if($unreadAlerts > 0)
                                <span class="rf-nav-badge-dot hidden absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-600 rounded-full ring-2 ring-white"></span>
                            @endif
                        </div>
                        <span class="rf-sidebar-label text-sm truncate">Notifications</span>
                        @if($unreadAlerts > 0)
                            <span class="rf-nav-badge-pill ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-rose-600 text-[10px] font-bold text-white shadow-2xs">
                                {{ $unreadAlerts > 99 ? '99+' : $unreadAlerts }}
                            </span>
                        @endif
                        <span class="rf-collapsed-tooltip">Notifications</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 3: OPERATIONS -->
            <div class="rf-nav-section">
                <p class="rf-sidebar-section-title text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1.5">Operations</p>
                <div class="space-y-1">
                    <a
                        href="{{ route('admin.gallery') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.gallery*') ? 'is-active' : '' }}"
                        title="Gallery Management"
                        data-title="Gallery Management"
                        @if(request()->routeIs('admin.gallery*')) aria-current="page" @endif
                    >
                        <i class="fa-regular fa-image w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Gallery Management</span>
                        <span class="rf-collapsed-tooltip">Gallery Management</span>
                    </a>

                    <a
                        href="{{ route('admin.packages.index') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.packages*') ? 'is-active' : '' }}"
                        title="Package Management"
                        data-title="Package Management"
                        @if(request()->routeIs('admin.packages*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-gift w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Package Management</span>
                        <span class="rf-collapsed-tooltip">Package Management</span>
                    </a>

                    <a
                        href="{{ route('admin.inventory.index') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.inventory*') ? 'is-active' : '' }}"
                        title="Inventory Management"
                        data-title="Inventory Management"
                        @if(request()->routeIs('admin.inventory*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-boxes-stacked w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Inventory Management</span>
                        <span class="rf-collapsed-tooltip">Inventory Management</span>
                    </a>

                    <a
                        href="{{ route('admin.return-tracking') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.return-tracking*') ? 'is-active' : '' }}"
                        title="Return Tracking"
                        data-title="Return Tracking"
                        @if(request()->routeIs('admin.return-tracking*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-truck-ramp-box w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Return Tracking</span>
                        <span class="rf-collapsed-tooltip">Return Tracking</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 4: CLIENTS -->
            <div class="rf-nav-section">
                <p class="rf-sidebar-section-title text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1.5">Clients</p>
                <div class="space-y-1">
                    <a
                        href="{{ route('admin.client-records') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.client-records*') ? 'is-active' : '' }}"
                        title="Client Records"
                        data-title="Client Records"
                        @if(request()->routeIs('admin.client-records*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-folder-open w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Client Records</span>
                        <span class="rf-collapsed-tooltip">Client Records</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 5: ANALYTICS -->
            <div class="rf-nav-section">
                <p class="rf-sidebar-section-title text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1.5">Analytics</p>
                <div class="space-y-1">
                    <a
                        href="{{ route('admin.reports') }}"
                        class="rf-nav-item relative group {{ request()->routeIs('admin.reports*') ? 'is-active' : '' }}"
                        title="Reports & Analytics"
                        data-title="Reports & Analytics"
                        @if(request()->routeIs('admin.reports*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-chart-pie w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Reports & Analytics</span>
                        <span class="rf-collapsed-tooltip">Reports & Analytics</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 6: SYSTEM -->
            <div class="rf-nav-section">
                <p class="rf-sidebar-section-title text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1.5">System</p>
                <div class="space-y-1">
                    <a
                        href="{{ route('admin.settings') }}"
                        class="rf-nav-item relative group {{ (request()->routeIs('admin.settings*') || request()->routeIs('admin.users*')) ? 'is-active' : '' }}"
                        title="Account Management"
                        data-title="Account Management"
                        @if(request()->routeIs('admin.settings*') || request()->routeIs('admin.users*')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-users-gear w-5 text-center text-sm shrink-0" aria-hidden="true"></i>
                        <span class="rf-sidebar-label text-sm truncate">Account Management</span>
                        <span class="rf-collapsed-tooltip">Account Management</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Bottom Section: Profile / Account Footer -->
        <div class="rf-sidebar-footer relative p-3 border-t border-slate-200/80 bg-white shrink-0">
            <!-- Account Popover Menu -->
            <div
                id="adminUserDropdownMenu"
                style="display: none;"
                class="absolute bottom-full left-3 right-3 mb-2 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50 text-xs transform transition-all"
            >
                <div class="p-2 border-b border-slate-100 mb-1">
                    <p class="font-bold text-slate-800 truncate">{{ auth()->user()->name ?? 'Raflora Admin' }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email ?? 'admin@raflora.com' }}</p>
                </div>
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-2.5 p-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-emerald-700 font-semibold transition">
                    <i class="fa-solid fa-gear text-slate-400"></i>
                    <span>Account Settings</span>
                </a>
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 p-2 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-purple-700 font-semibold transition">
                    <i class="fa-solid fa-arrow-up-right-from-square text-slate-400"></i>
                    <span>View Public Site</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="w-full mt-1 pt-1 border-t border-slate-100">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 p-2 rounded-xl text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer text-left">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>

            <!-- Profile Summary Card -->
            <div class="flex items-center justify-between gap-2.5">
                <button
                    type="button"
                    onclick="toggleAdminUserMenu()"
                    class="flex items-center gap-2.5 min-w-0 flex-1 p-1 rounded-xl hover:bg-slate-50 transition text-left cursor-pointer"
                    aria-haspopup="true"
                    aria-expanded="false"
                    id="adminUserMenuBtn"
                    title="{{ auth()->user()->name ?? 'Raflora Admin' }} (Administrator)"
                >
                    <div class="relative shrink-0">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center justify-center font-bold text-xs shadow-2xs select-none">
                            {{ strtoupper(substr(auth()->user()->name ?? 'RA', 0, 2)) }}
                        </div>
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </div>
                    <div class="rf-sidebar-footer-text min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-800 truncate">{{ auth()->user()->name ?? 'Raflora Admin' }}</p>
                        <p class="text-[10px] text-slate-400 font-medium truncate">Administrator</p>
                    </div>
                </button>

                <!-- Quick Logout Button in Expanded Footer -->
                <form method="POST" action="{{ route('logout') }}" class="rf-sidebar-footer-text shrink-0">
                    @csrf
                    <button
                        type="submit"
                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer"
                        title="Log Out"
                        aria-label="Log Out"
                    >
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- RIGHT MAIN CONTAINER -->
    <div id="adminMainContainer" class="flex flex-col lg:pl-64 min-h-screen w-full transition-all duration-300 ease-in-out">
        
        <!-- Mobile Top Bar -->
        <div class="sticky top-0 z-30 bg-white px-4 py-3 border-b border-slate-200 flex items-center justify-between lg:hidden shadow-2xs">
            <button
                id="mobileAdminBrandToggle"
                type="button"
                class="flex items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 cursor-pointer"
                aria-controls="adminSidebar"
                aria-expanded="false"
                aria-label="Toggle admin navigation"
            >
                <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700 shadow-2xs">
                    <i class="fa-solid fa-bars text-sm"></i>
                </div>
                <img src="{{ asset('assets/images/raflora_flower_emblem_transparent.png') }}" alt="Raflora logo" class="h-8 w-8 object-contain">
                <span id="mobileAdminPanelName" class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800 truncate max-w-[200px]">{{ $title }}</span>
            </button>
        </div>

        <!-- Top Fixed Page Header on Desktop (Single Source of Page Context) -->
        <header class="hidden lg:flex flex-shrink-0 z-10 bg-white border-b border-slate-200 px-6 py-4 items-center justify-between shadow-2xs min-h-[4.25rem]">
            <div class="min-w-0">
                <h1 class="serif text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ $title }}</h1>
                @if(!empty($description))
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">{{ $description }}</p>
                @endif
            </div>
        </header>

        <!-- Main Work Area -->
        <main class="flex-1 bg-slate-50 p-4 pb-12 sm:p-6" aria-label="Admin workspace content">
            {{ $slot }}
        </main>
    </div>

    <!-- UI Interaction Scripts -->
    <script>
        // Desktop Collapse Toggle
        function toggleDesktopSidebar() {
            const isCurrentlyCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
            if (isCurrentlyCollapsed) {
                document.documentElement.classList.remove('sidebar-collapsed');
                localStorage.setItem('raflora_admin_sidebar_collapsed', 'false');
            } else {
                document.documentElement.classList.add('sidebar-collapsed');
                localStorage.setItem('raflora_admin_sidebar_collapsed', 'true');
            }
            // Close popover menu if open
            closeAdminUserMenu();
        }

        // Mobile Slide-over Drawer Controls
        function toggleAdminSidebarState() {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                return;
            }

            const sidebar = document.getElementById('adminSidebar');
            if (!sidebar) return;

            if (sidebar.classList.contains('-translate-x-full')) {
                openAdminSidebar();
            } else {
                closeAdminSidebar();
            }
        }

        function openAdminSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('adminSidebarBackdrop');
            const mobileToggle = document.getElementById('mobileAdminBrandToggle');

            if (!sidebar) return;

            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
            if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeAdminSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('adminSidebarBackdrop');
            const mobileToggle = document.getElementById('mobileAdminBrandToggle');

            if (!sidebar) return;

            sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
            if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        // Admin User Menu Popover Toggle
        function toggleAdminUserMenu() {
            const menu = document.getElementById('adminUserDropdownMenu');
            const btn = document.getElementById('adminUserMenuBtn');
            if (!menu) return;

            const isShown = menu.style.display === 'block';
            if (isShown) {
                closeAdminUserMenu();
            } else {
                menu.style.display = 'block';
                btn?.setAttribute('aria-expanded', 'true');
            }
        }

        function closeAdminUserMenu() {
            const menu = document.getElementById('adminUserDropdownMenu');
            const btn = document.getElementById('adminUserMenuBtn');
            if (!menu) return;

            menu.style.display = 'none';
            btn?.setAttribute('aria-expanded', 'false');
        }

        // Event listeners initialization
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileAdminBrandToggle');
            const navLinks = document.querySelectorAll('#adminSidebar nav a');

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleAdminSidebarState();
                });
            }

            // Expose globally for backward compatibility
            window.toggleAdminSidebar = toggleAdminSidebarState;
            window.closeAdminSidebar = closeAdminSidebar;

            // Keyboard Escape Listener
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeAdminSidebar();
                    closeAdminUserMenu();
                }
            });

            // Click Outside Popover Listener
            document.addEventListener('click', function(e) {
                const userMenu = document.getElementById('adminUserDropdownMenu');
                const userBtn = document.getElementById('adminUserMenuBtn');
                if (userMenu && userMenu.style.display === 'block') {
                    if (!userMenu.contains(e.target) && !userBtn?.contains(e.target)) {
                        closeAdminUserMenu();
                    }
                }
            });

            // Close mobile menu when clicking nav links
            navLinks.forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.matchMedia('(max-width: 1023px)').matches) {
                        closeAdminSidebar();
                    }
                });
            });
        });
    </script>

    {{-- Global Flash Messages Toast Container (Root Stacking Context) --}}
    <div id="rf-toast-container" class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[99999] flex flex-col gap-3 pointer-events-none w-[calc(100vw-2rem)] sm:w-96 max-w-full" style="isolation: isolate;">
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
    </div>

    <x-confirm-modal />
</body>
</html>