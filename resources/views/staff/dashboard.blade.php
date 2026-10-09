@php
    $assignedEvents = $activeEvents ?? $assignedEvents;
    $completedEvents = $completedEvents ?? collect();
    $assignedCount = $assignedEvents->count();
    $defaultChecklistCount = count(\App\Http\Controllers\Staff\EventController::DEFAULT_CHECKLIST);
    $checklistTasks = $assignedEvents->sum(function ($event) use ($defaultChecklistCount) {
        return $event->staffChecklistItems->isNotEmpty()
            ? $event->staffChecklistItems->count()
            : $defaultChecklistCount;
    });
    $pendingChecklist = $assignedEvents->sum(function ($event) use ($defaultChecklistCount) {
        return $event->staffChecklistItems->isNotEmpty()
            ? $event->staffChecklistItems->where('is_completed', false)->count()
            : $defaultChecklistCount;
    });
    $returnFollowUps = $assignedEvents->filter(fn ($event) => in_array($event->status, ['event_completed', 'pending_return', 'pending_resolution'], true))->count();
@endphp

<x-staff-layout title="Staff Workspace">
    <div class="space-y-6">
        <section class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-purple-600">Operational dashboard</p>
                <h2 class="serif mt-1 text-3xl font-bold text-slate-900">Good day, {{ auth()->user()->name }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">This workspace captures the event preparation and return work currently assigned to you.</p>
            </div>

            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                <i class="fa-solid fa-circle-check"></i>
                Access active
            </span>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Staff work summary">
            <div class="rounded-2xl border border-slate-200 border-l-4 border-l-purple-500 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">Active assigned events</p>
                <p class="serif mt-2 text-3xl font-bold text-slate-900">{{ $assignedCount }}</p>
                <p class="mt-2 text-xs text-slate-500">Active operations assigned by Admin</p>
            </div>

            <div class="rounded-2xl border border-slate-200 border-l-4 border-l-amber-500 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">Checklist items</p>
                <p class="serif mt-2 text-3xl font-bold text-slate-900">{{ $checklistTasks }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $pendingChecklist }} pending follow-up</p>
            </div>

            <div class="rounded-2xl border border-slate-200 border-l-4 border-l-sky-500 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">Return follow-ups</p>
                <p class="serif mt-2 text-3xl font-bold text-slate-900">{{ $returnFollowUps }}</p>
                <p class="mt-2 text-xs text-slate-500">Active events pending return or resolution</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Active assigned events</h3>
                    <p class="mt-1 text-sm text-slate-500">Only active operations assigned to your Staff account appear here.</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>

            @forelse($assignedEvents as $event)
                @php
                    $hasInitializedChecklist = $event->staffChecklistItems->isNotEmpty();
                    $remainingChecklist = $hasInitializedChecklist 
                        ? $event->staffChecklistItems->where('is_completed', false)->count() 
                        : $defaultChecklistCount;
                    $totalChecklist = $hasInitializedChecklist 
                        ? $event->staffChecklistItems->count() 
                        : $defaultChecklistCount;
                @endphp
                <a href="{{ route('staff.events.show', $event) }}" class="mb-3 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-purple-300 hover:bg-purple-50/60 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900">{{ ucfirst($event->event_type ?? 'Event') }}</p>
                            @if(!$hasInitializedChecklist)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-amber-800" title="Checklist will initialize upon opening">Setup pending ({{ $defaultChecklistCount }} tasks)</span>
                            @elseif($remainingChecklist === 0)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-emerald-800">All tasks completed</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-600">{{ $remainingChecklist }} of {{ $totalChecklist }} pending</span>
                            @endif

                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ 
                                $event->preparation_status === 'in_preparation' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : (
                                $event->preparation_status === 'ready' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : (
                                $event->preparation_status === 'cancelled' ? 'bg-slate-100 text-slate-600' : 'bg-sky-50 text-sky-700 ring-1 ring-sky-200'
                            )) }}">
                                <i class="fa-solid {{ 
                                    $event->preparation_status === 'in_preparation' ? 'fa-screwdriver-wrench' : (
                                    $event->preparation_status === 'ready' ? 'fa-circle-check' : (
                                    $event->preparation_status === 'cancelled' ? 'fa-ban' : 'fa-clock'
                                )) }} text-[9px]"></i>
                                {{ $event->preparation_status_display_label }}
                            </span>
                        </div>

                        <p class="text-sm text-slate-600">
                            <span class="font-medium">{{ optional($event->event_date)->format('M d, Y') ?? 'Date not scheduled' }}</span>
                            <span class="text-slate-400">·</span>
                            <span>{{ $event->venue ?? 'Venue not specified' }}</span>
                        </p>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            <span>
                                <i class="fa-regular fa-calendar-check text-slate-400 mr-1"></i>
                                Prep start: <strong class="text-slate-700">{{ optional($event->preparation_start_date)->format('M d, Y') ?? 'Not scheduled' }}</strong>
                            </span>
                            @if($event->suggested_procurement_date)
                                <span class="text-slate-300">·</span>
                                <span>
                                    <i class="fa-regular fa-clock text-slate-400 mr-1"></i>
                                    Target floral order: <span class="text-slate-600">{{ $event->suggested_procurement_date->format('M d, Y') }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-purple-100 px-3 py-1.5 text-xs font-semibold text-purple-700 shrink-0">
                        {{ $event->status_display_label }}
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-purple-100 text-2xl text-purple-700">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-slate-900">No active assigned events</h3>
                    <p class="mt-2 max-w-lg text-sm leading-6 text-slate-500">You currently have no active event preparations or return follow-ups assigned to your Staff account.</p>
                    <div class="mt-6 flex items-center gap-2 text-xs font-semibold text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                        Assignments are managed by Admin
                    </div>
                </div>
            @endforelse
        </section>

        @if($completedEvents->isNotEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-slate-900">Past & completed events</h3>
                        <p class="mt-1 text-sm text-slate-500">Historical events you previously handled that have completed all operations and returns.</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach($completedEvents as $completedEvent)
                        <a href="{{ route('staff.events.show', $completedEvent) }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-slate-300 hover:bg-slate-100/60 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-slate-800">{{ ucfirst($completedEvent->event_type ?? 'Event') }}</p>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.15em] text-emerald-700">Completed</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ optional($completedEvent->event_date)->format('M d, Y') ?? 'Date not scheduled' }} · {{ $completedEvent->venue ?? 'Venue not specified' }}</p>
                            </div>

                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600">
                                View records
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">Your workflow</h3>
                        <p class="text-xs text-slate-500">Operational work follows this sequence.</p>
                    </div>
                </div>

                <ol class="mt-5 space-y-3 text-sm text-slate-600">
                    <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">1</span> Event preparation</li>
                    <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">2</span> Event execution</li>
                    <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">3</span> Material return and observation</li>
                </ol>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-900 p-6 text-white shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-purple-200">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">Operational access</h3>
                        <p class="text-xs text-slate-300">Your account stays separate from Admin management.</p>
                    </div>
                </div>

                <p class="mt-5 text-sm leading-6 text-slate-300">Admin manages approvals and assignment rules. Staff records operational work only for events assigned to them.</p>
            </div>
        </section>
    </div>
</x-staff-layout>
