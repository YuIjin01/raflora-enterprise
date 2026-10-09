<?php
\ = 'resources/views/admin/booking-show.blade.php';
\ = file_get_contents(\);

// Replace the start to the first section
\ = strpos(\, '<!-- FINANCIAL OVERVIEW & SETTLEMENT SUMMARY (F-FIN-03) -->');

\ = <<<HTML
<x-admin-layout title="Booking Review Workspace">
    <div class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-purple-700">Booking Review Workspace</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Booking #{{ \\\->id }} &middot; {{ ucfirst(\\\->event_type) }}</h1>
            <p class="mt-1 text-sm text-slate-500">Client: {{ \\\->client?->full_name ?? 'Guest' }} | Ref: {{ 'RB-' . date('Y', strtotime(\\\->created_at)) . '-' . str_pad(\\\->id, 4, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @php
                \\\ = \\\->status === 'cancelled'
                    && (\\\->returns()->exists() || \\\->hasDispatchedReusableMaterials());
                \\\ = \\\->returns->where('status', 'Completed')->isNotEmpty();
                \\\ = in_array(\\\->status, ['event_completed', 'pending_return', 'pending_resolution', 'completed'], true) || \\\;
            @endphp
            @if(\\\)
                @if(\\\->status === 'completed' || \\\)
                    <a href="{{ route('admin.return-tracking.manage', \\\) }}" class="rf-btn rf-btn-outline text-xs">
                        <i class="fa-solid fa-boxes-packing mr-1"></i> View Return Audit
                    </a>
                @else
                    <a href="{{ route('admin.return-tracking.manage', \\\) }}" class="rf-btn rf-btn-primary text-xs">
                        <i class="fa-solid fa-boxes-packing mr-1"></i> Manage Return Audit
                    </a>
                @endif
            @endif
            <a href="{{ route('admin.bookings') }}" class="rf-btn rf-btn-outline">
                &larr; Back to All Bookings
            </a>
        </div>
    </div>

    <!-- Booking Progress Tracker -->
    @php
        \\\ = [
            'pending' => 1,
            'quotation_sent' => 2,
            'approved' => 3,
            'admin_approved' => 4,
            'payment_pending' => 5,
            'payment_submitted' => 5,
            'downpayment_received' => 6,
            'in_preparation' => 6,
            'confirmed' => 6,
            'event_in_progress' => 7,
            'event_completed' => 8,
            'pending_return' => 8,
            'pending_resolution' => 8,
            'completed' => 9
        ];
        \\\ = \\\[\\\->status] ?? 0;
        
        // Handling terminal states
        if (in_array(\\\->status, ['cancelled', 'cancellation_requested', 'declined'])) {
            \\\ = -1;
        }

        \\\ = [
            ['title' => 'Booking Request', 'status' => (\\\ >= 0 ? 'completed' : 'cancelled')],
            ['title' => 'Raflora Review', 'status' => (\\\ >= 1 ? (\\\ > 1 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Quotation', 'status' => (\\\ >= 2 ? (\\\ > 2 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Client Approval', 'status' => (\\\ >= 3 ? (\\\ > 3 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Admin Approval', 'status' => (\\\ >= 4 ? (\\\ > 4 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Payment', 'status' => (\\\ >= 5 ? (\\\ > 5 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Preparation', 'status' => (\\\ >= 6 ? (\\\ > 6 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Event Execution', 'status' => (\\\ >= 7 ? (\\\ > 7 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Material Return', 'status' => (\\\ >= 8 ? (\\\ > 8 ? 'completed' : 'current') : 'upcoming')],
            ['title' => 'Completion', 'status' => (\\\ >= 9 ? 'completed' : 'upcoming')],
        ];
    @endphp

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm overflow-hidden">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Booking Progress Tracker</h3>
        <div class="relative">
            <div class="absolute top-1/2 left-0 h-0.5 w-full bg-slate-100 -translate-y-1/2" aria-hidden="true"></div>
            <ul class="relative flex justify-between">
                @foreach(\\\ as \\\ => \\\)
                    <li class="flex flex-col items-center group">
                        <div class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full {{ \\\['status'] === 'completed' ? 'bg-purple-600 text-white' : (\\\['status'] === 'current' ? 'bg-amber-500 text-white ring-4 ring-amber-100' : 'bg-slate-200 text-slate-400') }}">
                            @if(\\\['status'] === 'completed')
                                <i class="fa-solid fa-check text-[10px]"></i>
                            @elseif(\\\['status'] === 'current')
                                <div class="h-2 w-2 rounded-full bg-white"></div>
                            @else
                                <div class="h-1.5 w-1.5 rounded-full bg-slate-300"></div>
                            @endif
                        </div>
                        <span class="absolute top-8 w-24 text-center text-[10px] font-semibold {{ \\\['status'] === 'completed' ? 'text-purple-700' : (\\\['status'] === 'current' ? 'text-amber-700' : 'text-slate-400') }} break-words leading-tight">
                            {{ \\\['title'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="h-8"></div>
        </div>
    </div>

    @if(session('success'))
        <div class="rf-alert rf-alert--success mb-6" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rf-alert rf-alert--danger mb-6" role="alert"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>{{ session('error') }}</div>
    @endif

    @if(!empty(\\\) && \\\)
        <div class="rf-alert rf-alert--warning mb-6" role="status">
            This quotation is locked because the booking is currently {{ strtoupper(\\\->status) }}.
        </div>
    @endif

    @php
        \\\ = \\\ ?? false;
        \\\ = \\\;
        \\\ = \\\ ?? (float) \\\->total_obligation;
        \\\ = \\\ ?? (float) \\\->total_paid;
        \\\ = \\\ ?? (float) \\\->remaining_balance;
        \\\ = \\\ ?? (float) \\\->damage_charges;
        \\\ = \\\ ?? (float) (\\\->final_quoted_price ?? \\\->total_quoted ?? 0);
        \\\ = \\\ ?? (in_array(\\\->status, ['event_in_progress', 'event_completed', 'pending_return', 'pending_resolution'], true) && (\\\ > 0));
    @endphp

    <!-- Booking Navigation Tabs -->
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-1 sm:space-x-6 overflow-x-auto" aria-label="Tabs">
            <button type="button" onclick="switchTab('tab-overview')" class="tab-button active border-purple-500 text-purple-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-overview">Overview</button>
            <button type="button" onclick="switchTab('tab-details')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-details">Event Details</button>
            <button type="button" onclick="switchTab('tab-quotation')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-quotation">Quotation</button>
            <button type="button" onclick="switchTab('tab-materials')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-materials">Materials & Inventory</button>
            <button type="button" onclick="switchTab('tab-preparation')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-preparation">Preparation</button>
            <button type="button" onclick="switchTab('tab-staff')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-staff">Staff Assignment</button>
            <button type="button" onclick="switchTab('tab-communication')" class="tab-button border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition" data-target="tab-communication">Communication</button>
        </nav>
    </div>

    <!-- Tab Contents -->
    <div id="tab-overview" class="tab-content block space-y-6">
HTML;

\ = substr(\, \);

// Wrap sections in tabs based on aria-labelledby or id
function wrapSection(&\, \, \, \, \, \ = true) {
    \ = strpos(\, \);
    if (\ !== false) {
        // We assume the section ends at the next <section or at the end
        \ = strpos(\, '<section ', \ + 1);
        if (\ === false) {
            // Check for end of layout if last section
            \ = strpos(\, '</x-admin-layout>');
            if (\ === false) \ = strlen(\);
        }
        
        \ = substr(\, \, \ - \);
        
        // Remove it from original
        \ = substr_replace(\, '', \, \ - \);
        
        if (\) {
            return \ . \ . \;
        } else {
            return '';
        }
    }
    return '';
}

// 1. Overview (Financial, Payments)
\ = '';
\ .= wrapSection(\, '<!-- FINANCIAL OVERVIEW & SETTLEMENT SUMMARY (F-FIN-03) -->', '<section', '', '');
\ .= wrapSection(\, '<section class="rf-panel overflow-hidden p-5 sm:p-6 mb-6" aria-labelledby="payments-heading">', '<section', '', '');
\ .= '</div>'; // Close tab-overview

// 2. Event Details
\ = '<div id="tab-details" class="tab-content hidden space-y-6">';
\ .= wrapSection(\, '<!-- SECTION 1: CLIENT REQUEST & INSPIRATION -->', '<section', '', '');
\ .= '</div>';

// 3. Materials & Inventory
\ = '<div id="tab-materials" class="tab-content hidden space-y-6">';
\ .= wrapSection(\, '<!-- SECTION 1.5: MATERIAL PLANNING & PREPARATION SCHEDULING -->', '<section', '', '');
\ .= wrapSection(\, '<section class="rf-panel mt-6 overflow-hidden p-5 sm:p-6" id="dispatch-management-section"', '<section', '', '');
\ .= '</div>';

// 4. Staff Assignment
\ = '<div id="tab-staff" class="tab-content hidden space-y-6">';
\ .= wrapSection(\, '<section class="rf-panel mt-6 p-5 sm:p-6" aria-labelledby="staff-assignment-heading">', '<section', '', '');
\ .= '</div>';

// 5. Preparation
\ = '<div id="tab-preparation" class="tab-content hidden space-y-6">';
\ .= wrapSection(\, '<!-- SECTION 1.5: STAFF PREPARATION CHECKLIST (READ-ONLY) -->', '<section', '', '');
\ .= '</div>';

// 6. Quotation
\ = '<div id="tab-quotation" class="tab-content hidden space-y-6">';
\ .= wrapSection(\, '<!-- SECTION 2: PROPOSAL UPLOADS -->', '<section', '', '');
\ .= wrapSection(\, '<section class="rf-panel mt-6 overflow-hidden p-5 sm:p-6" aria-labelledby="pricing-breakdown-heading">', '<section', '', '');
\ .= '</div>';

// 7. Communication
\ = '<div id="tab-communication" class="tab-content hidden space-y-6">';
\ .= wrapSection(\, '<section class="rf-panel mt-6 overflow-hidden p-5 sm:p-6" aria-labelledby="negotiation-heading">', '<section', '', '');
\ .= '</div>';

\ = <<<HTML
    <script>
        function switchTab(tabId) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block'));
            
            // Show selected
            const selected = document.getElementById(tabId);
            if(selected) {
                selected.classList.remove('hidden');
                selected.classList.add('block');
            }

            // Update buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-purple-500', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });
            const activeBtn = document.querySelector(\.tab-button[data-target="\"]\);
            if(activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-purple-500', 'text-purple-600');
            }
        }
    </script>
</x-admin-layout>
HTML;

// Whatever is left (if anything)
\ = str_replace('</x-admin-layout>', '', \);

\ = \ . \ . \ . \ . \ . \ . \ . \ . \ . \;

file_put_contents('scratch/new_booking_show.blade.php', \);
echo "File created successfully.\n";
?>
