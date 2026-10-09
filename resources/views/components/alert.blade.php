@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => true,
    'inline' => false,
])

@php
    $normalizedType = match ($type) {
        'error' => 'danger',
        'information' => 'info',
        default => $type,
    };

    $colors = [
        'info' => 'bg-blue-50 border-blue-300 text-blue-900',
        'success' => 'bg-emerald-50 border-emerald-300 text-emerald-900',
        'warning' => 'bg-amber-50 border-amber-300 text-amber-900',
        'danger' => 'bg-rose-50 border-rose-300 text-rose-900',
    ];

    $icons = [
        'info' => 'fa-circle-info text-blue-600',
        'success' => 'fa-circle-check text-emerald-600',
        'warning' => 'fa-triangle-exclamation text-amber-600',
        'danger' => 'fa-circle-xmark text-rose-600',
    ];

    $colorClass = $colors[$normalizedType] ?? $colors['info'];
    $iconClass = $icons[$normalizedType] ?? $icons['info'];
    $isAutoDismiss = !$inline && in_array($normalizedType, ['success', 'info']);
    $role = in_array($normalizedType, ['danger', 'warning']) ? 'alert' : 'status';

    $containerClasses = $inline
        ? "rf-alert w-full rounded-2xl border p-4 shadow-sm flex items-start gap-3 {$colorClass}"
        : "rf-toast-alert fixed top-4 right-4 sm:top-6 sm:right-6 z-[9999] w-[calc(100vw-2rem)] sm:w-96 rounded-2xl border shadow-2xl transition-all duration-300 translate-y-0 opacity-100 flex items-start p-4 gap-3 {$colorClass}";
@endphp

<div role="{{ $role }}" class="{{ $containerClasses }}" data-auto-dismiss="{{ $isAutoDismiss ? 'true' : 'false' }}">
    <i class="fa-solid {{ $iconClass }} text-lg mt-0.5 shrink-0" aria-hidden="true"></i>
    <div class="flex-1 text-sm font-medium leading-5">
        @if($title)
            <div class="font-bold text-sm tracking-wide mb-1">{{ $title }}</div>
        @endif
        <div>{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button
            type="button"
            class="shrink-0 rounded-lg p-1 opacity-70 hover:opacity-100 hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-black/20 transition"
            aria-label="Dismiss alert"
            onclick="this.closest('.rf-toast-alert, .rf-alert').remove()"
        >
            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
        </button>
    @endif
</div>

@if(!$inline)
    <script>
        if (!window.rfToastInitialized) {
            window.rfToastInitialized = true;
            document.addEventListener('DOMContentLoaded', function() {
                const repositionToasts = () => {
                    const toasts = Array.from(document.querySelectorAll('.rf-toast-alert:not(.dismissing)'));
                    let currentTop = window.innerWidth < 640 ? 16 : 24;
                    toasts.forEach((toast) => {
                        toast.style.top = `${currentTop}px`;
                        currentTop += toast.offsetHeight + 12;
                    });
                };

                const initToasts = () => {
                    const toasts = document.querySelectorAll('.rf-toast-alert:not([data-initialized="true"])');
                    toasts.forEach(toast => {
                        toast.setAttribute('data-initialized', 'true');
                        if (toast.getAttribute('data-auto-dismiss') === 'true') {
                            setTimeout(() => {
                                if (toast && document.body.contains(toast)) {
                                    toast.classList.add('dismissing');
                                    toast.style.opacity = '0';
                                    toast.style.transform = 'translateY(-10px)';
                                    setTimeout(() => {
                                        toast.remove();
                                        repositionToasts();
                                    }, 300);
                                }
                            }, 5000);
                        }
                    });
                    repositionToasts();
                };

                initToasts();

                const observer = new MutationObserver((mutations) => {
                    let hasNewToast = false;
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1 && (node.classList.contains('rf-toast-alert') || node.querySelector('.rf-toast-alert'))) {
                                hasNewToast = true;
                            }
                        });
                    });
                    if (hasNewToast) initToasts();
                });

                observer.observe(document.body, { childList: true, subtree: true });
                window.addEventListener('resize', repositionToasts);
            });
        }
    </script>
@endif
