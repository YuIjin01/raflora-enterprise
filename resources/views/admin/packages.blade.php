<x-admin-layout title="Packages">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Packages</h2>
            <p class="text-sm text-gray-500">Manage public booking packages, pricing, and master inventory mappings (BOM).</p>
        </div>
        <a href="{{ route('admin.packages.create') }}" class="btn-primary flex-shrink-0 inline-block text-center">
            <i class="fa-solid fa-plus mr-2"></i> Add Package
        </a>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('admin.packages.index') }}" class="border-purple-500 text-purple-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Active Packages
            </a>
            <a href="{{ route('admin.packages.archived') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Archived Packages
            </a>
        </nav>
    </div>

    <!-- Package Grid -->
    @if($packages->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6 relative">
            @foreach($packages as $package)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-purple-900/5 transition-all duration-300 overflow-hidden flex flex-col relative">
                    @php
                        $coverImage = $package->images->first() ? asset('storage/' . $package->images->first()->image_path) : '';
                        $imageCount = $package->images->count();
                    @endphp
                    <div class="relative w-full overflow-hidden bg-slate-100" style="aspect-ratio: 3/2;" onclick="openLightbox('{{ $package->id }}')" role="button" tabindex="0">
                        @if($coverImage)
                            <img src="{{ $coverImage }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out cursor-pointer" alt="{{ $package->title }}">
                            <div class="absolute bottom-2 right-2 bg-slate-900/75 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center gap-1.5 pointer-events-none">
                                <i class="fa-solid fa-camera"></i> {{ $imageCount }}
                            </div>
                        @else
                            <div class="w-full h-full bg-purple-50 flex items-center justify-center text-purple-300 group-hover:scale-110 transition-transform duration-700 ease-in-out cursor-pointer">
                                <i class="fa-solid fa-gift text-5xl"></i>
                            </div>
                        @endif
                        @if(!$package->is_active)
                            <div class="absolute top-2 right-2 bg-slate-900/75 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center pointer-events-none">
                                Inactive
                            </div>
                        @endif
                    </div>
                    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                        <div class="text-center">
                            @if($package->category)
                                <div class="mb-1"><span class="text-[10px] font-bold tracking-widest uppercase text-purple-600">{{ $package->category }}</span></div>
                            @endif
                            <h3 class="font-bold text-[#1e293b] text-[15px] sm:text-base mb-1 line-clamp-1 group-hover:text-purple-700 transition-colors serif">{{ $package->title }}</h3>
                            <p class="text-lg sm:text-xl font-extrabold text-purple-700 mb-4">₱{{ number_format($package->price, 2) }}</p>
                            
                            @if($package->description)
                                <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ $package->description }}</p>
                            @endif
                        </div>
                        
                        <!-- Admin Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                            <button onclick='openViewModal(@json($package))' class="text-sm font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-1.5 transition-colors" type="button">
                                <i class="fa-regular fa-eye"></i> View
                            </button>
                            <a href="{{ route('admin.packages.edit', $package->id) }}" class="text-sm font-semibold text-blue-500 hover:text-blue-700 flex items-center gap-1.5 transition-colors">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <form action="{{ route('admin.packages.archive', $package) }}" method="POST" id="archive-form-{{ $package->id }}" class="inline-block m-0">
                                @csrf
                                <button type="button" onclick="openConfirmModal('archive-form-{{ $package->id }}', 'Archive Package?', 'Archived packages will no longer be available for new bookings.', 'Archive Package', 'archive', this)" class="text-sm font-semibold text-orange-500 hover:text-orange-700 flex items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-box-archive"></i> Archive
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-8 flex justify-center">
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-slate-100">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                <i class="fa-solid fa-gift text-2xl text-slate-400"></i>
            </div>
            <h3 class="text-lg font-medium text-slate-900">No active packages</h3>
            <p class="mt-1 text-sm text-slate-500 mb-6">Get started by creating a new package.</p>
            <a href="{{ route('admin.packages.create') }}" class="inline-flex items-center px-4 py-2 border border-slate-200 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 transition">
                <i class="fa-solid fa-plus mr-2"></i> Add Package
            </a>
        </div>
    @endif

    <!-- View Package Modal -->
    <div id="viewModal" class="fixed inset-0 z-[100] hidden flex-col items-center justify-center bg-slate-900/80 backdrop-blur-[2px] transition-opacity duration-300 p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Modal panel -->
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl flex flex-col relative overflow-hidden my-auto mx-auto max-h-[90vh]">
            <button onclick="closeModal('viewModal')" class="absolute top-4 right-4 sm:top-5 sm:right-5 z-20 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-100 shadow-md transition-transform hover:scale-105 border border-slate-100">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
            
            <div class="px-6 sm:px-8 py-5 border-b border-gray-100 shrink-0 pr-20">
                <h3 class="text-2xl font-bold text-slate-900 serif">View Package</h3>
            </div>
            
            <div class="px-6 sm:px-8 py-6 space-y-6 overflow-y-auto flex-1 bg-slate-50/50">
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">1. Package Information</h4>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Package Name</span>
                            <span id="view_title" class="block text-sm font-semibold text-slate-900"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Package Code</span>
                            <span id="view_package_code" class="block text-sm font-mono text-slate-900 bg-slate-100 px-2 py-0.5 rounded w-max"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Category</span>
                            <span id="view_category" class="block text-sm text-slate-700"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">Price</span>
                            <span id="view_price" class="block text-sm font-semibold text-purple-700"></span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-xs font-medium text-slate-500 mb-1">Status</span>
                            <span id="view_status_badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">2. Description</h4>
                    <p id="view_description" class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed"></p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">3. Included in the Package</h4>
                    <p id="view_included_items" class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed"></p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-4 border-b border-slate-100 pb-2">4. Master Inventory Mapping</h4>
                    <div id="view_inventory_container" class="space-y-2">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
            
            <div class="px-6 sm:px-8 py-4 border-t border-gray-100 flex justify-end gap-3 bg-white shrink-0">
                <button type="button" onclick="closeModal('viewModal')" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">Close</button>
            </div>
        </div>
    </div>

    <!-- Shared Lightbox JavaScript & Markup -->
    @php
        $lightboxData = $packages->map(function($p) {
            return [
                'id' => (string) $p->id,
                'title' => $p->title,
                'tags' => [$p->category],
                'images' => $p->images->pluck('image_path')->map(fn($path) => asset('storage/' . $path))->toArray()
            ];
        })->toArray();
    @endphp

    <div id="galleryLightbox" onclick="closeLightbox()" class="fixed inset-0 z-[100] hidden flex-col items-center justify-center bg-slate-900/80 backdrop-blur-[2px] opacity-0 transition-opacity duration-300 overflow-y-auto p-4 sm:p-6" style="padding: 1.5rem; justify-content: center; align-items: center;">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl flex flex-col relative overflow-hidden my-auto mx-auto" onclick="event.stopPropagation()" style="border-radius: 20px; max-width: 768px; margin-left: auto; margin-right: auto; margin-top: auto; margin-bottom: auto; transform: translateZ(0);">
            <button onclick="closeLightbox()" class="absolute top-4 right-4 sm:top-5 sm:right-5 z-20 w-12 h-12 rounded-full flex items-center justify-center bg-white text-slate-800 hover:bg-slate-100 shadow-md transition-transform hover:scale-105 border border-slate-100" style="border-radius: 9999px; width: 44px; height: 44px;">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

            <div class="relative w-full bg-slate-100 flex justify-center items-center" style="width: 100%; height: 470px;">
                <img id="lightboxMainImg" src="" class="w-full h-full object-contain" alt="Package Image" style="width: 100%; height: 100%; object-fit: contain;">
                
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
                            <i class="fa-solid fa-tag text-slate-400"></i>
                            <span id="lightboxCategory"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.remove('hidden');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'none';
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        
        // Add file listener
        document.getElementById('add_images').addEventListener('change', function(e) {
            const fileList = document.getElementById('add-file-list');
            fileList.innerHTML = '';
            for (let i = 0; i < this.files.length; i++) {
                const p = document.createElement('p');
                p.className = 'text-sm text-green-700 bg-green-50 px-3 py-1.5 rounded-md inline-flex items-center gap-2 mr-2 mb-2 border border-green-200';
                p.innerHTML = '<i class="fa-solid fa-image"></i> ' + this.files[i].name;
                fileList.appendChild(p);
            }
        });

        function toggleInvQty(mode, id) {
            const checkbox = document.getElementById(mode + '_inv_check_' + id);
            const qtyInput = document.getElementById(mode + '_inv_qty_' + id);
            if (checkbox.checked) {
                qtyInput.disabled = false;
                qtyInput.required = true;
                if (!qtyInput.value || parseFloat(qtyInput.value) <= 0) {
                    qtyInput.value = '1';
                }
            } else {
                qtyInput.disabled = true;
                qtyInput.required = false;
                qtyInput.value = '';
            }
            updateInvSummary(mode);
        }

        function updateInvSummary(mode) {
            const checkboxes = document.querySelectorAll('.' + mode + '-inv-check:checked');
            const summary = document.getElementById(mode + '_inv_summary');
            if (summary) {
                summary.textContent = checkboxes.length + ' inventory items selected';
            }
        }

        function filterInventory(mode) {
            const searchInput = document.getElementById(mode + '_inv_search').value.toLowerCase();
            const categorySelect = document.getElementById(mode + '_inv_category').value.toLowerCase();
            const rows = document.querySelectorAll('.' + mode + '-inv-row');
            
            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                const category = row.dataset.category.toLowerCase();
                
                const matchesSearch = name.includes(searchInput);
                const matchesCategory = categorySelect === '' || category === categorySelect;
                
                if (matchesSearch && matchesCategory) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Edit file listener
            const fileList = document.getElementById('edit-file-list');
            fileList.innerHTML = '';
            for (let i = 0; i < this.files.length; i++) {
                const p = document.createElement('p');
                p.className = 'text-sm text-green-700 bg-green-50 px-3 py-1.5 rounded-md inline-flex items-center gap-2 mr-2 mb-2 border border-green-200';
                p.innerHTML = '<i class="fa-solid fa-image"></i> ' + this.files[i].name;
                fileList.appendChild(p);
            }
        });

        function openViewModal(pkg) {
            document.getElementById('view_title').innerText = pkg.title || 'N/A';
            document.getElementById('view_package_code').innerText = pkg.package_code || 'N/A';
            document.getElementById('view_category').innerText = pkg.category || 'N/A';
            document.getElementById('view_price').innerText = '₱' + (parseFloat(pkg.price) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('view_description').innerText = pkg.description || 'No description provided.';
            document.getElementById('view_included_items').innerText = Array.isArray(pkg.included_items) && pkg.included_items.length > 0 ? pkg.included_items.join(', ') : 'No items specified.';
            
            var statusBadge = document.getElementById('view_status_badge');
            if (pkg.is_active) {
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                statusBadge.innerText = 'Active';
            } else {
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
                statusBadge.innerText = 'Archived / Inactive';
            }

            var invContainer = document.getElementById('view_inventory_container');
            invContainer.innerHTML = '';
            
            var hasInventory = false;
            if (pkg.inventory_items && Array.isArray(pkg.inventory_items)) {
                pkg.inventory_items.forEach(function(item) {
                    if (item.pivot && item.pivot.quantity > 0) {
                        hasInventory = true;
                        invContainer.innerHTML += `
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                                <div>
                                    <p class="text-[13px] font-semibold text-gray-700">${item.name}</p>
                                    <p class="text-[11px] text-gray-500">${item.category}</p>
                                </div>
                                <div class="text-[13px] font-medium text-gray-700">
                                    ${item.pivot.quantity} <span class="text-gray-500 font-normal">${item.unit}</span>
                                </div>
                            </div>
                        `;
                    }
                });
            }
            
            if (!hasInventory) {
                invContainer.innerHTML = '<p class="text-[13px] text-gray-500 italic">No inventory mapping configured.</p>';
            }

            openModal('viewModal');
        }
        // Lightbox Functions
        const packageData = @json($lightboxData);
        
        let currentPackageId = null;
        let currentImageIndex = 0;
        let currentImages = [];

        let lightbox, lightboxTitle, lightboxMainImg, lightboxCounter, lightboxThumbnails, lightboxPrevBtn, lightboxNextBtn, lightboxCategory;

        function openLightbox(id) {
            if (!lightbox) {
                lightbox = document.getElementById('galleryLightbox');
                lightboxTitle = document.getElementById('lightboxTitle');
                lightboxMainImg = document.getElementById('lightboxMainImg');
                lightboxCounter = document.getElementById('lightboxCounter');
                lightboxThumbnails = document.getElementById('lightboxThumbnails');
                lightboxPrevBtn = document.getElementById('lightboxPrevBtn');
                lightboxNextBtn = document.getElementById('lightboxNextBtn');
                lightboxCategory = document.getElementById('lightboxCategory');
            }

            const item = packageData.find(g => g.id === String(id));
            if (!item || !item.images || item.images.length === 0) return;

            currentPackageId = id;
            currentImages = item.images;
            currentImageIndex = 0;
            
            lightboxTitle.textContent = item.title;
            if (lightboxCategory) lightboxCategory.textContent = item.tags[0] || 'Uncategorized';
            
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
                currentPackageId = null;
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
</x-admin-layout>
