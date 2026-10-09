<x-admin-layout title="Client Records">
    <div class="mx-auto max-w-[1600px] space-y-6">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-purple-600">Client management</p>
                <h2 class="serif mt-1 text-3xl font-bold text-slate-900">Client Records</h2>
                <p class="mt-1 text-sm text-slate-500">Review the current client roster and the latest booking activity tied to each account.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">
                {{ $clients->count() }} record{{ $clients->count() === 1 ? '' : 's' }}
            </span>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm" aria-label="Client records table">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th scope="col" class="px-6 py-4">Client Name</th>
                            <th scope="col" class="px-6 py-4">Email</th>
                            <th scope="col" class="px-6 py-4">Bookings</th>
                            <th scope="col" class="px-6 py-4">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($clients as $client)
                            <tr class="align-top hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ $client->name ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: 'Unnamed client' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $client->email }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                        {{ $client->bookings_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $client->bookings->first()?->created_at?->format('M d, Y') ?? 'No activity' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                    No client records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
