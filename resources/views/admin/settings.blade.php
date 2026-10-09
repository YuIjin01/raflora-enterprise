<x-admin-layout title="Account Management">
    @php($isAdmin = auth()->user()?->role === 'admin')

    <div class="mx-auto max-w-[1600px] space-y-6">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-purple-600">Administration</p>
                <h2 class="serif mt-1 text-3xl font-bold text-slate-900">Account Management</h2>
                <p class="mt-1 text-sm text-slate-500">Manage your account, review system activity, and prepare staff access.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-open-modal="auditModal" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Audit Trail</button>
                <button type="button" data-open-modal="passwordModal" class="inline-flex items-center gap-2 rounded-lg bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800"><i class="fa-solid fa-key" aria-hidden="true"></i> Reset My Password</button>
                @if($isAdmin)
                    <a href="{{ route('admin.email-change.show') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-600"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Change Email</a>
                    <button type="button" data-open-modal="accountModal" class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Account</button>
                @endif
            </div>
        </div>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-1">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-purple-100 text-xl font-bold text-purple-700" aria-label="Current user initials">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Signed in as</p>
                        <h3 class="mt-1 truncate text-xl font-bold text-slate-900">{{ auth()->user()->name }}</h3>
                        <p class="truncate text-sm text-slate-500">{{ auth()->user()->email }}</p>
                        <span class="mt-3 inline-flex rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold capitalize text-purple-700">{{ auth()->user()->role }}</span>
                    </div>
                </div>
                <div class="mt-6 border-t border-slate-100 pt-4 text-sm text-slate-600">
                    <div class="flex justify-between gap-4"><span>Access level</span><strong class="capitalize text-slate-900">{{ auth()->user()->role }} access</strong></div>
                    <div class="mt-2 flex justify-between gap-4"><span>Account status</span><strong class="text-emerald-700">Active</strong></div>
                </div>
            </div>

            @if($isAdmin)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-1">
                <div class="mb-4">
                    <h3 class="font-bold text-slate-900">Business Configuration</h3>
                    <p class="mt-1 text-xs text-slate-500">Secured operational thresholds controlling quotations &amp; reconfirmation.</p>
                </div>

                @if(session('impact_preview'))
                    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900" role="alert">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                            <div>
                                <h4 class="text-sm font-bold text-amber-800">Explicit Confirmation Required</h4>
                                <p class="mt-1 text-xs text-amber-700">Please review the operational impact of your requested threshold change:</p>
                                <ul class="mt-2 list-disc pl-4 text-xs space-y-1 text-amber-800">
                                    @foreach(session('impact_preview')['impacts'] as $impact)
                                        <li>{{ $impact }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 pt-3 border-t border-amber-200 space-y-3">
                            @csrf
                            <input type="hidden" name="confirmed" value="1">
                            <input type="hidden" name="downpayment_percentage" value="{{ session('impact_preview')['pending_values']['downpayment_percentage'] }}">
                            <input type="hidden" name="long_term_booking_threshold_days" value="{{ session('impact_preview')['pending_values']['long_term_booking_threshold_days'] }}">
                            <input type="hidden" name="price_reconfirmation_threshold_days" value="{{ session('impact_preview')['pending_values']['price_reconfirmation_threshold_days'] }}">
                            <input type="hidden" name="change_reason" value="{{ session('impact_preview')['pending_values']['change_reason'] }}">

                            <div class="flex items-center gap-2">
                                <button type="submit" class="w-full rounded-lg bg-amber-700 px-3 py-2 text-xs font-bold text-white hover:bg-amber-800 transition shadow-sm">
                                    <i class="fa-solid fa-check mr-1"></i> I Understand — Confirm &amp; Save
                                </button>
                                <a href="{{ route('admin.settings') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                            </div>
                        </form>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <div class="flex justify-between items-center">
                            <label for="downpayment_percentage" class="block text-sm font-semibold text-slate-700">Downpayment (%)</label>
                            <span class="text-xs text-purple-600 font-bold">Current: {{ \App\Models\Setting::getSetting('downpayment_percentage', 50.0) }}%</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">Applies to newly issued quotations.</p>
                        <input type="number" id="downpayment_percentage" name="downpayment_percentage" value="{{ old('downpayment_percentage', \App\Models\Setting::getSetting('downpayment_percentage', 50.0)) }}" step="0.01" min="0" max="100" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 text-slate-900">
                    </div>

                    <div class="border-t border-slate-100 pt-3">
                        <div class="flex justify-between items-center">
                            <label for="long_term_booking_threshold_days" class="block text-sm font-semibold text-slate-700">Long-term Booking Threshold (days)</label>
                            <span class="text-xs text-purple-600 font-bold">Current: {{ \App\Models\Setting::getLongTermBookingThresholdDays() }}d</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">Events scheduled farther than this are marked tentative. Does not mutate existing snapshots.</p>
                        <input type="number" id="long_term_booking_threshold_days" name="long_term_booking_threshold_days" value="{{ old('long_term_booking_threshold_days', \App\Models\Setting::getLongTermBookingThresholdDays()) }}" min="1" max="730" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 text-slate-900">
                    </div>

                    <div class="border-t border-slate-100 pt-3">
                        <div class="flex justify-between items-center">
                            <label for="price_reconfirmation_threshold_days" class="block text-sm font-semibold text-slate-700">Price Reconfirmation Due (days before event)</label>
                            <span class="text-xs text-purple-600 font-bold">Current: {{ \App\Models\Setting::getPriceReconfirmationThresholdDays() }}d</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">Scheduled alert triggers when event is within this window. Does not mutate prices or bookings.</p>
                        <input type="number" id="price_reconfirmation_threshold_days" name="price_reconfirmation_threshold_days" value="{{ old('price_reconfirmation_threshold_days', \App\Models\Setting::getPriceReconfirmationThresholdDays()) }}" min="1" max="365" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 text-slate-900">
                    </div>

                    <div class="border-t border-slate-100 pt-3">
                        <label for="change_reason" class="block text-sm font-semibold text-slate-700">Change Justification / Reason (Optional)</label>
                        <input type="text" id="change_reason" name="change_reason" placeholder="e.g., Seasonal supplier price update window adjustment" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-xs focus:border-purple-500 focus:ring-purple-500 text-slate-900">
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 transition shadow-sm">
                        <i class="fa-solid fa-sliders mr-1.5"></i>Review &amp; Update Configuration
                    </button>
                </form>
            </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm {{ $isAdmin ? 'xl:col-span-1' : 'xl:col-span-2' }}">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-slate-900">Team accounts</h3>
                        <p class="mt-1 text-xs text-slate-500">Admin and staff accounts with access to the operations panel.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $accounts->count() }} account{{ $accounts->count() === 1 ? '' : 's' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm" aria-label="Admin team accounts">
                        <thead class="border-y border-slate-100 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="px-3 py-3">Name</th>
                                <th scope="col" class="px-3 py-3">Email</th>
                                <th scope="col" class="px-3 py-3">Role</th>
                                <th scope="col" class="px-3 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($accounts as $account)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-3 font-semibold text-slate-800">{{ $account->name }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $account->email }}</td>
                                    <td class="px-3 py-3"><span class="rounded-full {{ $account->role === 'admin' ? 'bg-purple-50 text-purple-700' : 'bg-sky-50 text-sky-700' }} px-2.5 py-1 text-xs font-semibold capitalize">{{ $account->role }}</span></td>
                                    <td class="px-3 py-3 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i> Active</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-8 text-center text-sm text-slate-500">No admin or staff accounts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @if($isAdmin)
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                            <i class="fa-solid fa-database" aria-hidden="true"></i>
                        </span>
                        <h3 class="text-lg font-bold text-slate-900">System Data Management</h3>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Import or export Raflora business data for controlled testing, staging, and demo setup.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('admin.system-data.export') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                        <i class="fa-solid fa-file-export" aria-hidden="true"></i> Export System Data
                    </a>
                    <a href="{{ route('admin.system-data.demo') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i class="fa-solid fa-cloud-arrow-down text-purple-600" aria-hidden="true"></i> Download Demo Dataset
                    </a>
                    <button type="button" data-open-modal="systemDataImportModal" class="inline-flex items-center gap-2 rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                        <i class="fa-solid fa-file-import" aria-hidden="true"></i> Import System Data
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 rounded-lg bg-slate-50 p-4 text-xs text-slate-600 md:grid-cols-3">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-shield-halved mt-0.5 text-purple-600" aria-hidden="true"></i>
                    <div>
                        <strong class="text-slate-800">Sensitive Data Protected:</strong>
                        <p class="mt-0.5 text-slate-500">Passwords, hashes, API keys, sessions, and recovery codes are never exported.</p>
                    </div>
                </div>
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-diagram-project mt-0.5 text-purple-600" aria-hidden="true"></i>
                    <div>
                        <strong class="text-slate-800">Relational Reference Mapping:</strong>
                        <p class="mt-0.5 text-slate-500">Foreign keys are resolved through dataset references and natural business keys.</p>
                    </div>
                </div>
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-rotate mt-0.5 text-purple-600" aria-hidden="true"></i>
                    <div>
                        <strong class="text-slate-800">Atomic Transactions:</strong>
                        <p class="mt-0.5 text-slate-500">Pre-import schema validation ensures all-or-nothing execution without partial states.</p>
                    </div>
                </div>
            </div>
        </section>
        @endif

    </div>

    <div id="passwordModal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
        <div class="absolute inset-0 bg-slate-950/50" data-close-modal="passwordModal"></div>
        <div class="relative mx-auto flex min-h-full max-w-lg items-center justify-center p-4">
            <div class="w-full rounded-xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 id="passwordModalTitle" class="text-xl font-bold text-slate-900">Reset My Password</h3>
                        <p class="mt-1 text-sm text-slate-500">Update the password for {{ auth()->user()->email }}.</p>
                    </div>
                    <button type="button" data-close-modal="passwordModal" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.account.password') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Current password</label>
                        <div class="relative mt-1">
                            <input type="password" id="reset_current_password" name="current_password" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 pr-10">
                            <div class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-slate-400">
                                <button type="button" class="inline-flex items-center justify-center hover:text-slate-600 focus-visible:outline-none transition-colors w-full h-full" aria-label="Show password" onclick="togglePassword('reset_current_password')">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">New password</label>
                        <div class="relative mt-1">
                            <input type="password" id="reset_password" name="password" required minlength="8" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 pr-10">
                            <div class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-slate-400">
                                <button type="button" class="inline-flex items-center justify-center hover:text-slate-600 focus-visible:outline-none transition-colors w-full h-full" aria-label="Show password" onclick="togglePassword('reset_password')">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Confirm new password</label>
                        <div class="relative mt-1">
                            <input type="password" id="reset_password_confirmation" name="password_confirmation" required minlength="8" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 pr-10">
                            <div class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-slate-400">
                                <button type="button" class="inline-flex items-center justify-center hover:text-slate-600 focus-visible:outline-none transition-colors w-full h-full" aria-label="Show password" onclick="togglePassword('reset_password_confirmation')">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button class="w-full rounded-lg bg-purple-700 px-4 py-2.5 font-semibold text-white hover:bg-purple-800">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    @if($isAdmin)
        <div id="accountModal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
            <div class="absolute inset-0 bg-slate-950/50" data-close-modal="accountModal"></div>
            <div class="relative mx-auto flex min-h-full max-w-lg items-center justify-center p-4">
                <div class="w-full rounded-xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="accountModalTitle" class="text-xl font-bold text-slate-900">Add Account</h3>
                            <p class="mt-1 text-sm text-slate-500">Create an admin or staff account for the operations panel.</p>
                        </div>
                        <button type="button" data-close-modal="accountModal" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.account.accounts.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Full name</label>
                            <input type="text" name="name" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Email</label>
                            <input type="email" name="email" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Role</label>
                            <select name="role" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5">
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Temporary password</label>
                            <div class="relative mt-1">
                                <input type="password" id="temp_password" name="password" required minlength="8" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 pr-10">
                                <div class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-slate-400">
                                    <button type="button" class="inline-flex items-center justify-center hover:text-slate-600 focus-visible:outline-none transition-colors w-full h-full" aria-label="Show password" onclick="togglePassword('temp_password')">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Confirm password</label>
                            <div class="relative mt-1">
                                <input type="password" id="temp_password_confirmation" name="password_confirmation" required minlength="8" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 pr-10">
                                <div class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-slate-400">
                                    <button type="button" class="inline-flex items-center justify-center hover:text-slate-600 focus-visible:outline-none transition-colors w-full h-full" aria-label="Show password" onclick="togglePassword('temp_password_confirmation')">
                                        <i class="fa-solid fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white hover:bg-emerald-800">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($isAdmin)
        <div id="systemDataImportModal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="systemDataImportModalTitle">
            <div class="absolute inset-0 bg-slate-950/50" data-close-modal="systemDataImportModal"></div>
            <div class="relative mx-auto flex min-h-full max-w-2xl items-center justify-center p-4">
                <div class="w-full max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h3 id="systemDataImportModalTitle" class="text-xl font-bold text-slate-900">Import System Data</h3>
                            <p class="mt-1 text-sm text-slate-500">Upload a system data archive (.zip) to validate and preview before importing.</p>
                        </div>
                        <button type="button" data-close-modal="systemDataImportModal" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close">&times;</button>
                    </div>

                    <!-- Step 1: File selection & Preview Trigger -->
                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="dataset_zip_file" class="block text-sm font-semibold text-slate-700">Select Dataset Archive (.zip)</label>
                            <input type="file" id="dataset_zip_file" accept=".zip,application/zip" class="mt-1.5 block w-full rounded-lg border border-slate-200 p-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-purple-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-purple-700 hover:file:bg-purple-100">
                            <p class="mt-1 text-xs text-slate-500">Accepted format: Raflora System Data (.zip) with manifest.json and entity files.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" id="btnPreviewDataset" class="inline-flex items-center gap-2 rounded-lg bg-purple-700 px-4 py-2 text-xs font-bold text-white hover:bg-purple-800 transition">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Upload &amp; Preview
                            </button>
                            <span id="previewLoadingIndicator" class="hidden text-xs text-slate-500">
                                <i class="fa-solid fa-circle-notch fa-spin text-purple-600 mr-1"></i> Validating archive structure &amp; relationships...
                            </span>
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div id="importErrorsContainer" class="hidden mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-700" role="alert">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-red-600"></i>
                            <div>
                                <strong class="font-bold text-red-800">Validation Errors:</strong>
                                <ul id="importErrorsList" class="mt-1.5 list-disc pl-4 space-y-1"></ul>
                            </div>
                        </div>
                    </div>

                    <!-- Warnings Alert -->
                    <div id="importWarningsContainer" class="hidden mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700" role="alert">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 text-amber-600"></i>
                            <div>
                                <strong class="font-bold text-amber-800">Warnings:</strong>
                                <ul id="importWarningsList" class="mt-1 list-disc pl-4 space-y-0.5"></ul>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Preview Results -->
                    <div id="importPreviewContainer" class="hidden mt-5 space-y-4 border-t border-slate-100 pt-4">
                        <div class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 text-xs md:grid-cols-4">
                            <div>
                                <span class="text-slate-400">Dataset ID:</span>
                                <p id="previewDatasetId" class="font-semibold text-slate-800 truncate">-</p>
                            </div>
                            <div>
                                <span class="text-slate-400">Mode:</span>
                                <p id="previewDataMode" class="font-semibold capitalize text-purple-700">-</p>
                            </div>
                            <div>
                                <span class="text-slate-400">Format Version:</span>
                                <p id="previewFormatVersion" class="font-semibold text-slate-800">-</p>
                            </div>
                            <div>
                                <span class="text-slate-400">Created:</span>
                                <p id="previewCreatedAt" class="font-semibold text-slate-800 truncate">-</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Entity Record Counts</h4>
                            <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                                <table class="w-full text-left text-xs" aria-label="Entity record counts">
                                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]">
                                        <tr>
                                            <th class="px-3 py-2">Entity</th>
                                            <th class="px-3 py-2 text-right">Records</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewCountsBody" class="divide-y divide-slate-100 text-slate-700"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Confirmation Form -->
                        <form method="POST" action="{{ route('admin.system-data.import') }}" id="confirmImportForm" class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            @csrf
                            <input type="hidden" name="preview_token" id="import_preview_token" value="">
                            <button type="button" data-close-modal="systemDataImportModal" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button type="submit" id="btnConfirmImport" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-check mr-1"></i> Confirm &amp; Import Dataset
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div id="auditModal" class="fixed inset-0 z-50 hidden opacity-0 transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="auditModalTitle"><div class="absolute inset-0 bg-slate-950/50" data-close-modal="auditModal"></div><div class="relative mx-auto flex min-h-full max-w-5xl items-center justify-center p-4"><div class="max-h-[85vh] w-full overflow-hidden rounded-xl bg-white p-6 shadow-2xl"><div class="flex items-start justify-between"><div><h3 id="auditModalTitle" class="text-xl font-bold text-slate-900">Audit Trail</h3><p class="mt-1 text-sm text-slate-500">The latest {{ $auditLogs->count() }} recorded system events.</p></div><button type="button" data-close-modal="auditModal" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close">&times;</button></div><div class="mt-5 max-h-[65vh] overflow-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead class="sticky top-0 border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500"><tr><th class="px-3 py-3">Time</th><th class="px-3 py-3">User</th><th class="px-3 py-3">Action</th><th class="px-3 py-3">Details</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($auditLogs as $log)<tr class="align-top"><td class="whitespace-nowrap px-3 py-3 text-xs text-slate-500">{{ optional($log->created_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td><td class="px-3 py-3 font-medium text-slate-700">{{ $log->user?->name ?? 'System' }}</td><td class="px-3 py-3 text-slate-600">{{ ucwords(str_replace('_', ' ', $log->action ?? 'Activity')) }}</td><td class="px-3 py-3 text-slate-600">{{ is_array($log->details) ? ($log->details['message'] ?? json_encode($log->details)) : ($log->details ?? 'No additional details') }}</td></tr>@endforeach</tbody></table></div></div></div></div>

    <script>
        const togglePassword = (id) => {
            const el = document.getElementById(id);
            if (!el) return;

            const button = el.parentElement?.querySelector('button');
            const icon = button?.querySelector('i');

            if (!button || !icon) return;

            const isHidden = el.type === 'password';
            el.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            icon.classList.toggle('fa-eye-slash', !isHidden);
            icon.classList.toggle('fa-eye', isHidden);
        };

        const initializeAccountModals = () => {
            const closeModal = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0');
                window.setTimeout(() => modal.classList.add('hidden'), 200);
                document.body.classList.remove('overflow-hidden');
            };
            document.querySelectorAll('[data-open-modal]').forEach((button) => button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openModal);
                if (!modal) return;
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                window.requestAnimationFrame(() => modal.classList.add('opacity-100'));
            }));
            document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => closeModal(button.dataset.closeModal)));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach((modal) => closeModal(modal.id)); });
        };

        const initSystemDataImport = () => {
            const btnPreview = document.getElementById('btnPreviewDataset');
            const fileInput = document.getElementById('dataset_zip_file');
            const indicator = document.getElementById('previewLoadingIndicator');
            const errorsContainer = document.getElementById('importErrorsContainer');
            const errorsList = document.getElementById('importErrorsList');
            const warningsContainer = document.getElementById('importWarningsContainer');
            const warningsList = document.getElementById('importWarningsList');
            const previewContainer = document.getElementById('importPreviewContainer');
            const countsBody = document.getElementById('previewCountsBody');
            const previewTokenInput = document.getElementById('import_preview_token');
            const btnConfirm = document.getElementById('btnConfirmImport');

            if (!btnPreview || !fileInput) return;

            btnPreview.addEventListener('click', async () => {
                if (!fileInput.files || fileInput.files.length === 0) {
                    alert('Please select a system data ZIP archive first.');
                    return;
                }

                const formData = new FormData();
                formData.append('dataset_file', fileInput.files[0]);
                formData.append('_token', '{{ csrf_token() }}');

                btnPreview.disabled = true;
                indicator.classList.remove('hidden');
                errorsContainer.classList.add('hidden');
                warningsContainer.classList.add('hidden');
                previewContainer.classList.add('hidden');

                try {
                    const response = await fetch('{{ route("admin.system-data.preview") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        const errs = data.errors || [data.message || 'Validation failed.'];
                        errorsList.innerHTML = errs.map(e => `<li>${e}</li>`).join('');
                        errorsContainer.classList.remove('hidden');
                        if (btnConfirm) btnConfirm.disabled = true;
                        return;
                    }

                    const preview = data.preview;

                    // Populate dataset details
                    document.getElementById('previewDatasetId').textContent = preview.manifest?.dataset_id || '-';
                    document.getElementById('previewDataMode').textContent = preview.manifest?.data_mode || '-';
                    document.getElementById('previewFormatVersion').textContent = preview.manifest?.format_version || '1';
                    document.getElementById('previewCreatedAt').textContent = preview.manifest?.created_at ? new Date(preview.manifest.created_at).toLocaleString() : '-';

                    // Warnings
                    if (preview.warnings && preview.warnings.length > 0) {
                        warningsList.innerHTML = preview.warnings.map(w => `<li>${w}</li>`).join('');
                        warningsContainer.classList.remove('hidden');
                    }

                    // Record counts
                    countsBody.innerHTML = '';
                    const counts = preview.record_counts || {};
                    for (const [entity, count] of Object.entries(counts)) {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td class="px-3 py-1.5 font-medium capitalize">${entity.replace(/_/g, ' ')}</td><td class="px-3 py-1.5 text-right font-mono">${count}</td>`;
                        countsBody.appendChild(row);
                    }

                    // Token
                    if (previewTokenInput && preview.preview_token) {
                        previewTokenInput.value = preview.preview_token;
                    }

                    if (btnConfirm) {
                        btnConfirm.disabled = !preview.isValid;
                    }

                    previewContainer.classList.remove('hidden');
                } catch (err) {
                    errorsList.innerHTML = `<li>Failed to inspect archive: ${err.message}</li>`;
                    errorsContainer.classList.remove('hidden');
                } finally {
                    btnPreview.disabled = false;
                    indicator.classList.add('hidden');
                }
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initializeAccountModals();
                initSystemDataImport();
            }, { once: true });
        } else {
            initializeAccountModals();
            initSystemDataImport();
        }
    </script>
</x-admin-layout>
