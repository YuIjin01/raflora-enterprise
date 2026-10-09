<?php
\ = 'app/Http/Controllers/Admin/BookingController.php';
\ = file_get_contents(\);

\ = \"    public function index(Request \\\): View
    {
        \\\ = [
            'all',
            'approved',
            'pending',
            'quotation_sent',
            'admin_approved',
            'payment_pending',
            'payment_submitted',
            'fully_paid',
            'downpayment_received',
            'confirmed',
            'event_in_progress',
            'event_completed',
            'pending_return',
            'pending_resolution',
            'completed',
            'declined',
            'cancelled',
            'change_requested',
            'cancellation_requested',
        ];

        \\\ = (string) \\\->query('status', 'all');
        \\\ = in_array(\\\, \\\, true) ? \\\ : 'all';

        \\\ = trim((string) \\\->query('search', ''));

        \\\ = Booking::with(['client', 'payments', 'returns.returnItems']);

        // Status Filter
        if (\\\ !== 'all') {
            \\\->where('status', \\\);
        }

        // Search Filter
        if (\\\ !== '') {
            \\\->where(function (\\\) use (\\\) {
                if (is_numeric(\\\)) {
                    \\\->orWhere('id', (int) \\\);
                }
                \\\->orWhere('event_type', 'like', \\\"%{\\\}%\\\")
                  ->orWhere('venue', 'like', \\\"%{\\\}%\\\")
                  ->orWhere('guest_name', 'like', \\\"%{\\\}%\\\")
                  ->orWhere('guest_email', 'like', \\\"%{\\\}%\\\")
                  ->orWhereHas('client', function (\\\) use (\\\) {
                      \\\->where('full_name', 'like', \\\"%{\\\}%\\\")
                         ->orWhere('email', 'like', \\\"%{\\\}%\\\");
                  });
            });
        }

        \\\ = \\\->latest()->paginate(15)->withQueryString();

        // Queue counts for quick filters
        \\\ = Booking::where('status', 'approved')->count();
        \\\ = Booking::whereIn('status', ['pending', 'change_requested'])->count();
        \\\ = Booking::where(function (\\\) {
            \\\->whereIn('status', ['payment_submitted', 'payment_pending'])
                  ->orWhereHas('payments', function (\\\) {
                      \\\->where('status', 'pending');
                  });
        })->count();
        \\\ = Booking::count();

        return view('admin.bookings', [
            'bookings' => \\\,
            'statusFilter' => \\\,
            'searchTerm' => \\\,
            'awaitingApprovalCount' => \\\,
            'pendingReviewCount' => \\\,
            'paymentVerificationCount' => \\\,
            'totalBookingsCount' => \\\,
        ]);
    }\";

\ = \"    public function index(Request \\\): View
    {
        \\\ = [
            'all',
            'approved',
            'pending',
            'quotation_sent',
            'admin_approved',
            'payment_pending',
            'payment_submitted',
            'fully_paid',
            'downpayment_received',
            'confirmed',
            'event_in_progress',
            'event_completed',
            'pending_return',
            'pending_resolution',
            'completed',
            'declined',
            'cancelled',
            'change_requested',
            'cancellation_requested',
        ];

        \\\ = (string) \\\->query('status', 'all');
        \\\ = in_array(\\\, \\\, true) ? \\\ : 'all';

        \\\ = trim((string) \\\->query('search', ''));
        \\\ = \\\->query('date_from');
        \\\ = \\\->query('date_to');

        \\\ = Booking::with(['client', 'payments', 'returns.returnItems']);

        // Status Filter
        if (\\\ !== 'all') {
            \\\->where('status', \\\);
        }

        // Date Filters
        if (\\\) {
            \\\->whereDate('event_date', '>=', \\\);
        }
        if (\\\) {
            \\\->whereDate('event_date', '<=', \\\);
        }

        // Search Filter
        if (\\\ !== '') {
            \\\->where(function (\\\) use (\\\) {
                if (is_numeric(\\\)) {
                    \\\->orWhere('id', (int) \\\);
                }
                \\\->orWhere('event_type', 'like', \\\"%{\\\}%\\\")
                  ->orWhere('venue', 'like', \\\"%{\\\}%\\\")
                  ->orWhere('guest_name', 'like', \\\"%{\\\}%\\\")
                  ->orWhere('guest_email', 'like', \\\"%{\\\}%\\\")
                  ->orWhereHas('client', function (\\\) use (\\\) {
                      \\\->where('full_name', 'like', \\\"%{\\\}%\\\")
                         ->orWhere('email', 'like', \\\"%{\\\}%\\\");
                  });
            });
        }

        \\\ = \\\->latest()->paginate(15)->withQueryString();

        // Queue counts for quick filters
        \\\ = Booking::where('status', 'approved')->count();
        \\\ = Booking::whereIn('status', ['pending', 'change_requested'])->count();
        \\\ = Booking::where(function (\\\) {
            \\\->whereIn('status', ['payment_submitted', 'payment_pending'])
                  ->orWhereHas('payments', function (\\\) {
                      \\\->where('status', 'pending');
                  });
        })->count();
        \\\ = Booking::count();

        return view('admin.bookings', [
            'bookings' => \\\,
            'statusFilter' => \\\,
            'searchTerm' => \\\,
            'dateFrom' => \\\,
            'dateTo' => \\\,
            'awaitingApprovalCount' => \\\,
            'pendingReviewCount' => \\\,
            'paymentVerificationCount' => \\\,
            'totalBookingsCount' => \\\,
        ]);
    }\";

if (strpos(\, \) !== false) {
    \ = str_replace(\, \, \);
    file_put_contents(\, \);
    echo \"Replaced successfully.\\n\";
} else {
    echo \"Old method not found.\\n\";
}
?>
