<x-admin-layout title="Galleries">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Galleries</h2>
            <p class="text-sm text-gray-500">Manage galleries displayed on the guest gallery page.</p>
        </div>
        <a href="{{ route('admin.gallery.create') }}" class="btn-primary flex-shrink-0">
            <i class="fa-solid fa-plus mr-2"></i> Add Gallery
        </a>
    </div>
    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('admin.gallery') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active Galleries
            </a>
            <a href="{{ route('admin.gallery.archived') }}" class="border-purple-500 text-purple-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Archived Galleries
            </a>
        </nav>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-3 sm:p-4 mb-6">
        <form method="GET" action="{{ route('admin.gallery.archived') }}" class="flex flex-col lg:flex-row gap-3 lg:items-center justify-between" id="filterForm">
            <!-- Search Input -->
            <div class="flex-1 relative w-full lg:w-auto">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search event type, theme, or keyword..." class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
            </div>
            
            <!-- Dropdowns -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full lg:w-auto">
                <div class="w-[calc(50%-4px)] sm:w-auto min-w-[130px]">
                    <select name="event_type" class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition font-semibold text-slate-700 cursor-pointer" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Event Types</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-[calc(50%-4px)] sm:w-auto min-w-[130px]">
                    <select name="theme" class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition font-semibold text-slate-700 cursor-pointer" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Themes</option>
                        @foreach($themes as $theme)
                            <option value="{{ $theme }}" {{ request('theme') == $theme ? 'selected' : '' }}>{{ $theme }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-[calc(50%-4px)] sm:w-auto min-w-[120px]">
                    <select name="year" class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition font-semibold text-slate-700 cursor-pointer" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Years</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-[calc(50%-4px)] sm:w-auto min-w-[120px]">
                    <select name="sort" class="block w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition font-semibold text-slate-700 cursor-pointer" onchange="document.getElementById('filterForm').submit()">
                        <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Latest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    </select>
                </div>
                
                <div class="w-full sm:w-auto mt-2 sm:mt-0 flex gap-2">
                    <button type="submit" class="inline-flex flex-1 sm:flex-none items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl text-white bg-purple-600 hover:bg-purple-700 transition whitespace-nowrap">
                        Search
                    </button>
                    <a href="{{ route('admin.gallery') }}" class="inline-flex flex-1 sm:flex-none items-center justify-center px-4 py-2 border border-slate-200 text-sm font-medium rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition whitespace-nowrap">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Gallery Grid -->
    @if($galleries->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6 relative">
            @foreach($galleries as $gallery)
                @php
                    $firstImg = $gallery->images->first();
                    $coverImage = $firstImg 
                        ? (str_starts_with($firstImg->image_path, 'assets/') ? asset($firstImg->image_path) : asset('storage/' . $firstImg->image_path)) 
                        : asset('assets/images/background.jpg');
                    $imageCount = $gallery->images->count();
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-purple-900/5 transition-all duration-300 overflow-hidden flex flex-col relative">
                    <div class="relative w-full overflow-hidden bg-slate-100" style="aspect-ratio: 3/2;" onclick="openLightbox('{{ $gallery->id }}')" role="button" tabindex="0">
                        <img src="{{ $coverImage }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out cursor-pointer" alt="{{ $gallery->title }}">
                        <div class="absolute bottom-2 right-2 bg-slate-900/75 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center gap-1.5 pointer-events-none">
                            <i class="fa-solid fa-camera"></i> {{ $imageCount }}
                        </div>
                    </div>
                    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-[#1e293b] text-[15px] sm:text-base mb-1.5 line-clamp-1 group-hover:text-purple-700 transition-colors serif">{{ $gallery->title }}</h3>
                            <div class="flex items-center text-slate-500 text-[11px] sm:text-xs font-medium mb-3">
                                <i class="fa-regular fa-calendar mr-1.5"></i> {{ \Carbon\Carbon::parse($gallery->event_date)->format('F j, Y') }}
                            </div>
                            <div class="flex gap-1.5 flex-wrap mb-4">
                                <span class="px-2.5 py-1 bg-[#f3e8ff] text-[#6b21a8] rounded-full text-[10px] font-semibold tracking-wide">{{ $gallery->event_type }}</span>
                                <span class="px-2.5 py-1 bg-[#f3e8ff] text-[#6b21a8] rounded-full text-[10px] font-semibold tracking-wide">{{ $gallery->theme }}</span>
                            </div>
                        </div>
                        
                        <!-- Admin Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <button onclick="openLightbox('{{ $gallery->id }}')" class="text-sm font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-1.5 transition-colors">
                                <i class="fa-regular fa-eye"></i> View
                            </button>
                            <form action="{{ route('admin.gallery.restore', $gallery) }}" method="POST" id="restore-form-{{ $gallery->id }}" class="inline-block">
                                @csrf
                                <button type="button" onclick="openConfirmModal('restore-form-{{ $gallery->id }}', 'Restore Gallery?', 'This gallery will become visible on the Guest Gallery again.', 'Restore Gallery', 'restore', this)" class="text-sm font-semibold text-emerald-500 hover:text-emerald-700 flex items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-rotate-left"></i> Restore
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            {{ $galleries->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-slate-100">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                <i class="fa-solid fa-box-archive text-2xl text-slate-400"></i>
            </div>
            <h3 class="text-lg font-medium text-slate-900">No archived galleries</h3>
            <p class="mt-1 text-sm text-slate-500 mb-6">Archived galleries will appear here when they are removed from the active Gallery.</p>
            <a href="{{ route('admin.gallery.archived') }}" class="inline-flex items-center px-4 py-2 border border-slate-200 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 transition">
                Clear Filters
            </a>
        </div>
    @endif

    <!-- Shared Lightbox JavaScript & Markup (Adapted from Guest Gallery) -->
    @php
        $lightboxData = $galleries->map(function($g) {
            return [
                'id' => (string) $g->id,
                'title' => $g->title,
                'date' => \Carbon\Carbon::parse($g->event_date)->format('F j, Y'),
                'tags' => [$g->event_type, $g->theme],
                'images' => $g->images->pluck('image_path')->map(fn($path) => str_starts_with($path, 'assets/') ? asset($path) : asset('storage/' . $path))->toArray()
            ];
        })->toArray();
    @endphp

    <script>
        const galleryData = @json($lightboxData);
        
        let currentGalleryId = null;
        let currentImageIndex = 0;
        let currentImages = [];

        let lightbox, lightboxTitle, lightboxMainImg, lightboxCounter, lightboxThumbnails, lightboxPrevBtn, lightboxNextBtn, lightboxDate, lightboxEventType, lightboxTheme;

        function openLightbox(id) {
            if (!lightbox) {
                lightbox = document.getElementById('galleryLightbox');
                lightboxTitle = document.getElementById('lightboxTitle');
                lightboxMainImg = document.getElementById('lightboxMainImg');
                lightboxCounter = document.getElementById('lightboxCounter');
                lightboxThumbnails = document.getElementById('lightboxThumbnails');
                lightboxPrevBtn = document.getElementById('lightboxPrevBtn');
                lightboxNextBtn = document.getElementById('lightboxNextBtn');
                lightboxDate = document.getElementById('lightboxDate');
                lightboxEventType = document.getElementById('lightboxEventType');
                lightboxTheme = document.getElementById('lightboxTheme');
            }

            const item = galleryData.find(g => g.id === String(id));
            if (!item || !item.images || item.images.length === 0) return;

            currentGalleryId = id;
            currentImages = item.images;
            currentImageIndex = 0;
            
            lightboxTitle.textContent = item.title;
            if (lightboxDate) lightboxDate.textContent = item.date;
            if (lightboxEventType) lightboxEventType.textContent = item.tags[0] || 'Unspecified';
            if (lightboxTheme) lightboxTheme.textContent = item.tags[1] || 'General';
            
            lightboxThumbnails.innerHTML = '';
            currentImages.forEach((imgUrl, index) => {
                lightboxThumbnails.innerHTML += `
                    <button onclick="goToImage(${index})" class="flex-shrink-0 snap-center w-20 h-20 rounded-md overflow-hidden border-2 transition-all duration-300 ${index === 0 ? 'border-green-600 opacity-100 shadow-sm' : 'border-transparent opacity-50 hover:opacity-100'}" style="width: 80px; height: 80px; border-radius: 8px;">
                        <img src="${imgUrl}" class="w-full h-full object-cover" style="width: 100%; height: 100%; object-fit: cover;">
                    </button>
                `;
            });

            updateLightboxView();
            
            lightbox.classList.remove('hidden');
            setTimeout(() => {
                lightbox.classList.remove('opacity-0');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }

        function closeLightbox() {
            lightbox.classList.add('opacity-0');
            setTimeout(() => {
                lightbox.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                currentGalleryId = null;
            }, 300);
        }

        function updateLightboxView() {
            lightboxMainImg.src = currentImages[currentImageIndex];
            lightboxCounter.textContent = `${currentImageIndex + 1} / ${currentImages.length}`;
            
            lightboxPrevBtn.classList.toggle('hidden', currentImages.length <= 1);
            lightboxNextBtn.classList.toggle('hidden', currentImages.length <= 1);
            
            Array.from(lightboxThumbnails.children).forEach((btn, index) => {
                if (index === currentImageIndex) {
                    btn.classList.remove('border-transparent', 'opacity-50');
                    btn.classList.add('border-green-600', 'opacity-100', 'shadow-sm');
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    btn.classList.add('border-transparent', 'opacity-50');
                    btn.classList.remove('border-green-600', 'opacity-100', 'shadow-sm');
                }
            });
        }

        function prevImage(e) {
            if (e) e.stopPropagation();
            if (currentImages.length <= 1) return;
            currentImageIndex = (currentImageIndex - 1 + currentImages.length) % currentImages.length;
            updateLightboxView();
        }

        function nextImage(e) {
            if (e) e.stopPropagation();
            if (currentImages.length <= 1) return;
            currentImageIndex = (currentImageIndex + 1) % currentImages.length;
            updateLightboxView();
        }

        function goToImage(index) {
            currentImageIndex = index;
            updateLightboxView();
        }

        document.addEventListener('keydown', (e) => {
            if (!lightbox || lightbox.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevImage();
            if (e.key === 'ArrowRight') nextImage();
        });
    </script>

    <!-- Lightbox Modal Markup (Identical to Guest Gallery) -->
    <div id="galleryLightbox" onclick="closeLightbox()" class="fixed inset-0 z-[100] hidden flex-col items-center justify-center bg-slate-900/80 backdrop-blur-[2px] opacity-0 transition-opacity duration-300 overflow-y-auto p-4 sm:p-6" style="padding: 1.5rem; justify-content: center; align-items: center;">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl flex flex-col relative overflow-hidden my-auto mx-auto" onclick="event.stopPropagation()" style="border-radius: 20px; max-width: 768px; margin-left: auto; margin-right: auto; margin-top: auto; margin-bottom: auto; transform: translateZ(0);">
            <button onclick="closeLightbox()" class="absolute top-4 right-4 sm:top-5 sm:right-5 z-20 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-100 shadow-md transition-transform hover:scale-105 border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px;">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

            <div class="relative w-full bg-slate-100 flex justify-center items-center" style="width: 100%; height: 470px;">
                <img id="lightboxMainImg" src="" class="w-full h-full object-contain" alt="Gallery Image" style="width: 100%; height: 100%; object-fit: contain;">
                
                <button onclick="prevImage(event)" id="lightboxPrevBtn" class="absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-50 shadow-md transition-transform hover:scale-105 z-10 hidden border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px; transform: translateY(-50%); top: 50%;">
                    <i class="fa-solid fa-chevron-left text-base"></i>
                </button>
                <button onclick="nextImage(event)" id="lightboxNextBtn" class="absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-50 shadow-md transition-transform hover:scale-105 z-10 hidden border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px; transform: translateY(-50%); top: 50%;">
                    <i class="fa-solid fa-chevron-right text-base"></i>
                </button>
            </div>

            <div class="flex flex-col p-6 sm:px-8 sm:pb-8 sm:pt-6 w-full" style="padding: 1.5rem 2rem 2rem 2rem; width: 100%;">
                <div class="text-center text-slate-800 font-medium text-sm sm:text-base mb-4">
                    <span id="lightboxCounter"></span>
                </div>
                <div class="flex justify-center w-full mb-6 sm:mb-8" style="justify-content: center; width: 100%;">
                    <div id="lightboxThumbnails" class="flex gap-2 sm:gap-3 overflow-x-auto snap-x max-w-full px-1 scrollbar-hide" style="scrollbar-width: none; gap: 0.75rem;">
                    </div>
                </div>
                <div class="w-full text-left" style="width: 100%; text-align: left;">
                    <h3 id="lightboxTitle" class="text-2xl sm:text-3xl font-bold text-slate-900 serif mb-2"></h3>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-slate-600 text-sm sm:text-[15px] font-medium" style="display: flex; gap: 1rem;">
                        <div class="flex items-center gap-1.5" style="display: flex; gap: 0.375rem;">
                            <i class="fa-regular fa-calendar text-slate-400"></i>
                            <span id="lightboxDate"></span>
                        </div>
                        <span class="text-slate-300">|</span>
                        <div class="flex items-center gap-1.5" style="display: flex; gap: 0.375rem;">
                            <i class="fa-solid fa-tag text-slate-400"></i>
                            <span id="lightboxEventType"></span>
                        </div>
                        <span class="text-slate-300">|</span>
                        <div class="flex items-center gap-1.5" style="display: flex; gap: 0.375rem;">
                            <i class="fa-brands fa-pagelines text-slate-400"></i>
                            <span id="lightboxTheme"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
