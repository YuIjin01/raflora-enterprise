<x-app-layout title="Account Settings">
    <x-client-layout active="account-settings">
        <section class="rf-panel mx-auto max-w-5xl p-5 sm:p-8">
            <div class="mb-4 flex items-center gap-4">
                <div class="relative shrink-0">
                    @php
                        $profileImage = $user->profile_image && \Storage::disk('public')->exists($user->profile_image)
                            ? asset('storage/' . $user->profile_image)
                            : null;
                    @endphp

                    @if($profileImage)
                        <img src="{{ $profileImage }}" alt="Profile image for {{ $user->name }}" class="h-14 w-14 rounded-full border-2 border-white/20 object-cover shadow-sm">
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-slate-200 bg-slate-100 shadow-sm">
                            <i class="fa-solid fa-user text-xl text-slate-400" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 leading-tight">{{ $user->name }}</h2>
                    <a href="#" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 hover:underline">View Profile</a>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-200">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest px-2 mb-2">General</h3>
                
                <nav class="flex flex-col">
                    <a href="#" class="group flex items-center justify-between py-3 px-2 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <span class="font-medium">Help Center</span>
                        <i class="fa-solid fa-chevron-right text-slate-300 text-sm group-hover:text-slate-400 transition-colors"></i>
                    </a>
                    
                    <a href="#" class="group flex items-center justify-between py-3 px-2 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <span class="font-medium">Terms & Policies</span>
                        <i class="fa-solid fa-chevron-right text-slate-300 text-sm group-hover:text-slate-400 transition-colors"></i>
                    </a>
                    
                    <a href="#" class="group flex items-center justify-between py-3 px-2 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <span class="font-medium">Settings</span>
                        <i class="fa-solid fa-chevron-right text-slate-300 text-sm group-hover:text-slate-400 transition-colors"></i>
                    </a>
                </nav>
            </div>
            
            <div class="mt-4 pt-4 border-t border-slate-200">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-between py-3 px-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 text-left font-semibold">
                        <span>Log Out</span>
                        <i class="fa-solid fa-arrow-right-from-bracket text-red-400"></i>
                    </button>
                </form>
            </div>

            {{-- 
            Preserved Edit Form for Future Separation
            <form method="POST" action="{{ route('account-settings.update') }}" enctype="multipart/form-data" class="space-y-8 mt-10">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <label class="rf-field">
                        <span class="rf-label">First Name</span>
                        <input id="first_name" type="text" name="first_name" placeholder="First Name" value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name" class="form-control" />
                    </label>

                    <label class="rf-field">
                        <span class="rf-label">Last Name</span>
                        <input id="last_name" type="text" name="last_name" placeholder="Last Name" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name" class="form-control" />
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <label class="rf-field">
                        <span class="rf-label">Email</span>
                        <input id="email" type="email" name="email" placeholder="Email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="form-control" />
                    </label>

                    <label class="rf-field">
                        <span class="rf-label">Contact Number</span>
                        <input id="mobile_number" type="text" name="mobile_number" placeholder="Contact Number" value="{{ old('mobile_number', $user->mobile_number) }}" autocomplete="tel" class="form-control" />
                    </label>
                </div>

                <label class="rf-field">
                    <span class="rf-label">Address</span>
                    <input id="address" type="text" name="address" placeholder="Address" value="{{ old('address', $user->address) }}" autocomplete="street-address" class="form-control" />
                </label>

                <fieldset class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                    <legend class="px-2 text-lg font-semibold text-slate-900">Security</legend>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <label class="rf-field">
                            <span class="rf-label">Current Password</span>
                            <input id="current_password" type="password" name="current_password" placeholder="Current Password" autocomplete="current-password" class="form-control" />
                        </label>
                        <label class="rf-field">
                            <span class="rf-label">New Password</span>
                            <input id="new_password" type="password" name="new_password" placeholder="New Password" autocomplete="new-password" class="form-control" />
                        </label>
                        <label class="rf-field">
                            <span class="rf-label">Confirm Password</span>
                            <input id="new_password_confirmation" type="password" name="new_password_confirmation" placeholder="Confirm New Password" autocomplete="new-password" class="form-control" />
                        </label>
                    </div>
                </fieldset>

                <label class="rf-field">
                    <span class="rf-label">Profile Image</span>
                    <input id="profile_image" type="file" name="profile_image" accept="image/*" class="form-control" />
                </label>

                <button type="submit" class="btn-primary w-full justify-center">Save Changes</button>
            </form>
            --}}
        </section>
    </x-client-layout>
</x-app-layout>
