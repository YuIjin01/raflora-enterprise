<div class="relative w-full max-w-full overflow-hidden bg-white" data-annotated-image-stage @if($imageAspectRatio) style="aspect-ratio: {{ $imageAspectRatio }};" @endif>
    <img src="{{ $previewImage }}" alt="Inspiration Image" class="block h-auto w-full object-contain transition duration-200" />
    <svg class="pointer-events-none absolute inset-0 z-10 h-full w-full" data-annotation-connectors aria-hidden="true"></svg>

    @foreach($overlayItems as $item)
        @php
            $location = $item['visual_location'] ?? null;
            $canAnnotate = ($item['source_type'] ?? null) === 'detected' && is_array($location)
                && isset($location['x'], $location['y']);
            $name = trim((string) ($item['name'] ?? 'Detected element'));
            $shortName = mb_strlen($name) > 22 ? mb_substr($name, 0, 22) . '…' : $name;
            $areaBadge = trim((string) ($item['area_label'] ?? $item['area_key'] ?? 'Area'));
        @endphp
        @if($canAnnotate)
            @php
                $left = (float) $location['x'] * 100;
                $top = (float) $location['y'] * 100;
                $cropWidth = min(1.0 - (float) $location['x'], max(0.12, (float) ($location['width'] ?? 0.18)));
                $cropHeight = min(1.0 - (float) $location['y'], max(0.12, (float) ($location['height'] ?? 0.18)));
                $cropLeft = min((float) $location['x'], 1.0 - $cropWidth);
                $cropTop = min((float) $location['y'], 1.0 - $cropHeight);
                $imageRatio = !empty($imageWidth) && !empty($imageHeight) ? (float) $imageWidth / (float) $imageHeight : 1.0;
                $backgroundWidth = max(1.0 / $cropWidth, $imageRatio / $cropHeight);
                $backgroundHeight = $backgroundWidth / $imageRatio;
                $cropCenterX = $cropLeft + ($cropWidth / 2);
                $cropCenterY = $cropTop + ($cropHeight / 2);
                $cropPositionX = $backgroundWidth > 1 ? (($cropCenterX * $backgroundWidth - 0.5) / ($backgroundWidth - 1)) * 100 : 50;
                $cropPositionY = $backgroundHeight > 1 ? (($cropCenterY * $backgroundHeight - 0.5) / ($backgroundHeight - 1)) * 100 : 50;
                $cropPositionX = max(0, min(100, $cropPositionX));
                $cropPositionY = max(0, min(100, $cropPositionY));
            @endphp
            <span class="pointer-events-none absolute z-20 block h-3.5 w-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-emerald-400 shadow-[0_0_0_4px_rgba(52,211,153,0.15)]" data-annotation-anchor style="left: {{ $left }}%; top: {{ $top }}%;" aria-hidden="true"></span>
            <div class="pointer-events-none absolute z-20 max-w-[180px]" data-annotation-card data-anchor-x="{{ $left }}" data-anchor-y="{{ $top }}">
                <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-white/95 px-2 py-1.5 shadow-sm backdrop-blur-sm" data-annotation-card-body>
                    <span class="h-9 w-9 shrink-0 rounded-lg border border-emerald-100 bg-cover bg-no-repeat" data-annotation-crop style="background-image: url('{{ $previewImage }}'); background-size: {{ $backgroundWidth * 100 }}% auto; background-position: {{ $cropPositionX }}% {{ $cropPositionY }}%;" aria-label="Image crop for {{ $name }}"></span>
                    <span class="min-w-0">
                        <span class="block text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">{{ $areaBadge }}</span>
                        <span class="mt-0.5 block truncate text-[11px] font-semibold leading-tight text-slate-700">{{ $shortName }}</span>
                    </span>
                </div>
            </div>
        @endif
    @endforeach

    <button type="button" class="pointer-events-auto absolute bottom-4 right-4 z-30 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/95 px-3 py-2 text-sm font-semibold text-slate-700 shadow-lg transition hover:bg-white" data-image-lightbox-trigger data-image-src="{{ $previewImage }}" aria-label="View full inspiration image">
        <span>View full image</span>
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l5 5v13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 0v5h8M15 13l-6 6m0-6l6 6"/></svg>
    </button>
</div>

@once
<style>
    [data-lightbox-content] [data-annotated-image-stage] {
        width: fit-content;
        max-width: 100%;
        max-height: calc(90vh - 2rem);
        aspect-ratio: auto !important;
    }

    [data-lightbox-content] [data-annotated-image-stage] > img {
        width: auto;
        max-width: 100%;
        height: auto;
        max-height: calc(90vh - 2rem);
        object-fit: contain;
    }
</style>
<script>
    window.positionAnnotatedImages = (root = document) => {
        root.querySelectorAll('[data-annotated-image-stage]').forEach((stage) => {
            const connectors = stage.querySelector('[data-annotation-connectors]');
            const cards = [...stage.querySelectorAll('[data-annotation-card]')];
            if (!connectors || !cards.length) return;

            if (!stage.annotationResizeObserver && typeof ResizeObserver !== 'undefined') {
                stage.annotationResizeObserver = new ResizeObserver(() => window.positionAnnotatedImages(stage));
                stage.annotationResizeObserver.observe(stage);
            }

            const stageRect = stage.getBoundingClientRect();
            if (stageRect.width <= 0 || stageRect.height <= 0) return;
            const gap = 10;
            const compactStage = stageRect.width < 700 && cards.length > 4;
            if (compactStage) {
                const compactWidth = Math.max(120, Math.min(170, Math.floor((stageRect.width - (gap * 4)) / 3)));
                cards.forEach((card) => {
                    card.style.width = `${compactWidth}px`;
                    card.style.maxWidth = `${compactWidth}px`;
                    card.querySelector('[data-annotation-card-body]')?.style.setProperty('width', '100%');
                });
            } else {
                cards.forEach((card) => {
                    card.style.width = '';
                    card.style.maxWidth = '';
                    card.querySelector('[data-annotation-card-body]')?.style.removeProperty('width');
                });
            }
            const anchors = cards.map((card) => ({
                x: Number(card.dataset.anchorX) / 100 * stageRect.width,
                y: Number(card.dataset.anchorY) / 100 * stageRect.height,
            }));
            const placed = [];
            const candidates = (x, y, width, height) => [
                [x + 12, y - height / 2], [x - width - 12, y - height / 2],
                [x - width / 2, y + 14], [x - width / 2, y - height - 14],
            ];
            const overlaps = (a, b) => a.left < b.right + gap && a.right + gap > b.left && a.top < b.bottom + gap && a.bottom + gap > b.top;
            const clampRect = (left, top, width, height) => ({
                left: Math.max(4, Math.min(left, stageRect.width - width - 4)),
                top: Math.max(4, Math.min(top, stageRect.height - height - 4)),
                right: 0, bottom: 0,
            });

            cards.forEach((card, index) => {
                const width = card.offsetWidth;
                const height = card.offsetHeight;
                const anchor = anchors[index];
                let best = null;
                candidates(anchor.x, anchor.y, width, height).forEach(([left, top], candidateIndex) => {
                    const rect = clampRect(left, top, width, height);
                    rect.right = rect.left + width;
                    rect.bottom = rect.top + height;
                    const cardOverlap = placed.reduce((total, other) => total + (overlaps(rect, other) ? 1 : 0), 0);
                    const coversAnchor = anchors.slice(0, index).some((point) => point.x >= rect.left && point.x <= rect.right && point.y >= rect.top && point.y <= rect.bottom);
                    const score = cardOverlap * 10000 + (coversAnchor ? 1000 : 0) + candidateIndex;
                    if (!best || score < best.score) best = { ...rect, score };
                });

                card.style.left = `${best.left}px`;
                card.style.top = `${best.top}px`;
                placed.push(best);
            });

            if (placed.some((rect, index) => placed.slice(0, index).some((other) => overlaps(rect, other)))) {
                placed.length = 0;
                cards.forEach((card, index) => {
                    const width = card.offsetWidth;
                    const height = card.offsetHeight;
                    const anchor = anchors[index];
                    let nearest = null;

                    for (let top = 4; top <= stageRect.height - height - 4; top += 12) {
                        for (let left = 4; left <= stageRect.width - width - 4; left += 12) {
                            const rect = { left, top, right: left + width, bottom: top + height };
                            const collisionCount = placed.reduce((total, other) => total + (overlaps(rect, other) ? 1 : 0), 0);
                            const coversAnchor = anchors.some((point, pointIndex) => pointIndex !== index
                                && point.x >= left && point.x <= rect.right
                                && point.y >= top && point.y <= rect.bottom);
                            const distance = Math.hypot((left + width / 2) - anchor.x, (top + height / 2) - anchor.y);
                            const score = collisionCount * 100000 + (coversAnchor ? 10000 : 0) + distance;
                            if (!nearest || score < nearest.score) nearest = { ...rect, score };
                        }
                    }

                    if (nearest) {
                        card.style.left = `${nearest.left}px`;
                        card.style.top = `${nearest.top}px`;
                        placed[index] = nearest;
                    }
                });
            }

            connectors.replaceChildren();
            anchors.forEach((anchor, index) => {
                const rect = placed[index];
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', anchor.x);
                line.setAttribute('y1', anchor.y);
                line.setAttribute('x2', Math.max(rect.left, Math.min(anchor.x, rect.right)));
                line.setAttribute('y2', Math.max(rect.top, Math.min(anchor.y, rect.bottom)));
                line.setAttribute('stroke', '#34d399');
                line.setAttribute('stroke-width', '2');
                connectors.appendChild(line);
            });

        });
    };

    const initializeAnnotatedImages = () => {
        window.positionAnnotatedImages();
        requestAnimationFrame(() => window.positionAnnotatedImages());
        document.querySelectorAll('[data-annotated-image-stage] img').forEach((image) => {
            image.addEventListener('load', () => window.positionAnnotatedImages(image.closest('[data-annotated-image-stage]')));
        });
        const quotationState = document.getElementById('quotationState');
        if (quotationState && typeof MutationObserver !== 'undefined') {
            new MutationObserver(() => window.positionAnnotatedImages(quotationState)).observe(quotationState, {
                attributes: true,
                attributeFilter: ['class', 'style'],
            });
        }
        window.addEventListener('resize', () => window.positionAnnotatedImages());
    };

    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', initializeAnnotatedImages, { once: true });
    } else {
        initializeAnnotatedImages();
    }
</script>
@endonce