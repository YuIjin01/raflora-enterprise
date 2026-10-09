<x-app-layout title="Notifications">
    <x-client-layout active="notifications">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="rounded-[2rem] bg-white/95 backdrop-blur-md shadow-xl border border-slate-200/60 p-5 sm:p-8">
                <div class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div class="flex items-center">
                        <h1 class="text-3xl font-black text-slate-900">Notifications</h1>
                        <x-info-popover title="Notifications">
                            Manage your event updates, quotes, and admin notices.
                        </x-info-popover>
                    </div>

                    @if($notifications->count())
                        <div class="rf-badge rf-badge--primary">
                            {{ $unreadCount }} New Update{{ $unreadCount === 1 ? '' : 's' }}
                        </div>
                    @endif
                </div>

                @if($notifications->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center text-slate-500 shadow-sm">
                        <p class="text-base font-medium">You have no booking updates yet.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($notificationData as $notification)
                            <div class="notification-row flex flex-col gap-4 rounded-2xl border border-slate-200 p-5 transition sm:flex-row sm:items-start sm:justify-between {{ $notification['is_read'] ? 'bg-white' : 'bg-emerald-50/40 ring-1 ring-emerald-200' }}" data-notification-id="{{ $notification['id'] }}" data-read="{{ $notification['is_read'] ? '1' : '0' }}">
                                
                                <div class="flex-1 space-y-3">
                                    <div class="flex items-start justify-between sm:justify-start sm:gap-4">
                                        <div class="text-xl font-bold text-slate-900">{{ $notification['booking_event'] }}</div>
                                        @if(!$notification['is_read'])
                                            <span class="rf-badge rf-badge--primary shrink-0">New</span>
                                        @endif
                                    </div>
                                    <div class="text-sm font-medium text-slate-500">{{ $notification['timestamp'] }}</div>
                                    
                                    <div>
                                        <span class="inline-flex rounded-md bg-emerald-100 px-2 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-800">
                                            {{ $notification['badge'] }}
                                        </span>
                                        <p class="mt-2 text-sm leading-6 text-slate-700">
                                            {{ Str::limit($notification['summary'], 140) }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mt-2 shrink-0 sm:mt-0 sm:w-auto">
                                    <button
                                        type="button"
                                        data-booking-id="{{ $notification['booking_id'] ?? '' }}"
                                        data-notification-id="{{ $notification['id'] }}"
                                        data-mark-read-url="{{ route('client.notifications.mark-as-read', ['notification' => $notification['id']]) }}"
                                        data-notification='@json($notification)'
                                        class="view-details-btn inline-flex min-h-11 w-full sm:w-auto items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-slate-300"
                                    >
                                        View Details
                                    </button>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div id="notificationModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modalEventTitle">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-[#0F2E5B]">Latest admin update</p>
                        <h2 id="modalEventTitle" class="mt-3 text-3xl font-black text-slate-900"></h2>
                        <p id="modalEventMeta" class="mt-2 text-sm text-slate-500"></p>
                    </div>
                    <button id="closeNotificationModal" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100 transition" aria-label="Close details">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div id="modalBody" class="space-y-5 px-6 py-5 text-slate-800"></div>

                <div class="border-t border-slate-200 px-6 py-4">
                    <div class="flex gap-3">
                        <a id="modalActionLink" href="#" class="hidden flex-1 rounded-xl bg-[#1E7E34] px-4 py-3 text-sm font-bold text-white hover:bg-[#155d27] transition text-center">Open Booking</a>
                        <button id="closeModalFooter" type="button" class="flex-1 rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-slate-700 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const modal = document.getElementById('notificationModal');
                const modalTitle = document.getElementById('modalEventTitle');
                const modalMeta = document.getElementById('modalEventMeta');
                const modalBody = document.getElementById('modalBody');
                const closeButtons = [
                    document.getElementById('closeNotificationModal'),
                    document.getElementById('closeModalFooter')
                ];
                const bellBadge = document.getElementById('notificationBellBadge');

                function updateBellBadge(count) {
                    if (!bellBadge) {
                        return;
                    }

                    const nextCount = Math.max(0, Number(count) || 0);
                    bellBadge.dataset.unreadCount = String(nextCount);
                    if (nextCount <= 0) {
                        bellBadge.textContent = '';
                        bellBadge.classList.add('hidden');
                        return;
                    }

                    bellBadge.textContent = nextCount > 9 ? '9+' : String(nextCount);
                    bellBadge.classList.remove('hidden');
                }

                function markNotificationAsRead(button) {
                    const row = button.closest('.notification-row');
                    const notificationId = button.dataset.notificationId;
                    const markReadUrl = button.dataset.markReadUrl;

                    if (!notificationId || !markReadUrl || row?.dataset.read === '1') {
                        return;
                    }

                    fetch(markReadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({}),
                    })
                        .then((response) => response.ok ? response.json() : null)
                        .then((data) => {
                            if (!data || !data.success) {
                                return;
                            }

                            row.dataset.read = '1';
                            row.classList.remove('bg-emerald-50/40', 'ring-1', 'ring-emerald-200');
                            row.classList.add('bg-white');
                            const indicator = row.querySelector('.h-2.5.w-2.5.rounded-full.bg-emerald-600');
                            if (indicator) {
                                indicator.remove();
                            }

                            updateBellBadge(data.unread_count ?? 0);
                        })
                        .catch(() => {});
                }

                function renderItemLines(items, container) {
                    if (!items || !items.length) {
                        return;
                    }

                    const list = document.createElement('ul');
                    list.className = 'space-y-2';

                    items.forEach((item) => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center justify-between gap-6 text-base font-medium text-slate-800';

                        const label = document.createElement('span');
                        label.textContent = item;

                        const qty = document.createElement('span');
                        qty.className = 'text-slate-700';

                        li.appendChild(label);
                        list.appendChild(li);
                    });

                    container.appendChild(list);
                }

                function renderSection(title, bodyHtml, accentClass) {
                    const section = document.createElement('div');
                    section.className = 'rounded-2xl border p-4 ' + accentClass;

                    const heading = document.createElement('p');
                    heading.className = 'mb-3 text-[10px] font-black uppercase tracking-[0.24em]';
                    heading.textContent = title;
                    section.appendChild(heading);

                    const content = document.createElement('div');
                    content.innerHTML = bodyHtml;
                    section.appendChild(content);

                    return section;
                }

                function renderNotificationDetails(notification) {
                    modalTitle.textContent = notification.booking_event || 'Booking';
                    modalMeta.textContent = [notification.event_date, notification.timestamp].filter(Boolean).join(' • ');
                    modalBody.innerHTML = '';

                    const details = notification.details || {};

                    if (details.custom_note) {
                        const note = document.createElement('div');
                        note.className = 'rounded-xl border border-slate-200 bg-slate-50 p-4';
                        note.innerHTML = '<p class="text-sm leading-6 text-slate-700">' + details.custom_note.replace(/\n/g, '<br>') + '</p>';
                        modalBody.appendChild(note);
                    }

                    if (details.items && details.items.length) {
                        const section = document.createElement('div');
                        section.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-4';

                        const heading = document.createElement('p');
                        heading.className = 'mb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-600';
                        heading.textContent = 'Items';
                        section.appendChild(heading);

                        const list = document.createElement('ul');
                        list.className = 'space-y-2';

                        details.items.forEach((item) => {
                            const li = document.createElement('li');
                            li.className = 'flex items-center justify-between gap-4 text-base font-medium text-slate-800';

                            const label = document.createElement('span');
                            label.textContent = item;

                            li.appendChild(label);
                            list.appendChild(li);
                        });

                        section.appendChild(list);
                        modalBody.appendChild(section);
                    }

                    if (details.removed_items && details.removed_items.length) {
                        const section = document.createElement('div');
                        section.className = 'rounded-2xl border border-red-200 bg-red-50 p-4';

                        const heading = document.createElement('p');
                        heading.className = 'mb-3 text-[10px] font-black uppercase tracking-[0.24em] text-red-700';
                        heading.textContent = 'Removed Items';
                        section.appendChild(heading);

                        const list = document.createElement('ul');
                        list.className = 'space-y-2';

                        details.removed_items.forEach((item) => {
                            const li = document.createElement('li');
                            li.className = 'text-sm font-semibold text-red-700';
                            li.textContent = item;
                            list.appendChild(li);
                        });

                        section.appendChild(list);
                        modalBody.appendChild(section);
                    }

                    if (details.price_changes && details.price_changes.length) {
                        const section = document.createElement('div');
                        section.className = 'rounded-2xl border border-emerald-200 bg-emerald-50 p-4';

                        const heading = document.createElement('p');
                        heading.className = 'mb-3 text-[10px] font-black uppercase tracking-[0.24em] text-emerald-700';
                        heading.textContent = 'Price Changes';
                        section.appendChild(heading);

                        const list = document.createElement('ul');
                        list.className = 'space-y-2';

                        details.price_changes.forEach((item) => {
                            const li = document.createElement('li');
                            li.className = 'text-sm font-bold text-emerald-700';
                            li.textContent = item;
                            list.appendChild(li);
                        });

                        section.appendChild(list);
                        modalBody.appendChild(section);
                    }

                    if (!details.custom_note && (!details.items || !details.items.length) && (!details.removed_items || !details.removed_items.length) && (!details.price_changes || !details.price_changes.length)) {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600';
                        emptyState.textContent = 'No detail information was attached to this update.';
                        modalBody.appendChild(emptyState);
                    }
                }

                const selectedBookingId = new URLSearchParams(window.location.search).get('booking');
                const analysisBase = "{{ url('/bookings/analysis') }}";

                document.querySelectorAll('.view-details-btn').forEach((button) => {
                    const openNotification = () => {
                        const notification = JSON.parse(button.dataset.notification || '{}');
                        renderNotificationDetails(notification);
                        // show modal and action
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        const actionLink = document.getElementById('modalActionLink');
                        if (actionLink) {
                            if (notification.booking_id) {
                                const isMsg = (notification.title && notification.title.toLowerCase().includes('message'));
                                actionLink.href = analysisBase + '/' + notification.booking_id + (isMsg ? '#booking-conversation-root' : '');
                                actionLink.classList.remove('hidden');
                                actionLink.textContent = isMsg ? 'Open Conversation' : 'Open Booking';
                            } else {
                                actionLink.classList.add('hidden');
                            }
                        }
                        markNotificationAsRead(button);
                    };

                    button.addEventListener('click', openNotification);

                    if (selectedBookingId && button.dataset.bookingId === String(selectedBookingId)) {
                        openNotification();
                    }
                });

                closeButtons.forEach((button) => {
                    if (!button) {
                        return;
                    }

                    button.addEventListener('click', () => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    });
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                });
            })();
        </script>
    </x-client-layout>
</x-app-layout>
