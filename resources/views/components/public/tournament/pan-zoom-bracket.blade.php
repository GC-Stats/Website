{{--
    GC-Stats — Pan/zoom bracket container

    Wraps arbitrary bracket content (a single bracket grid, or several
    brackets grouped side by side) in the draggable/zoomable canvas shared
    across every bracket view in the tournament phase tree.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div x-data="bracketPanZoom()"
     role="region"
     aria-label="{{ __('tournament.bracket.label') }}"
     tabindex="0"
     class="relative w-full overflow-hidden bg-black/10 rounded-lg cursor-grab active:cursor-grabbing border border-white/5 select-none"
     :style="`height: ${containerHeight}px`"
     @mousedown="startDragging($event)"
     @mousemove="drag($event)"
     @mouseup="stopDragging()"
     @mouseleave="stopDragging()"
     @wheel="zoom($event); if($event.ctrlKey) $event.preventDefault()">

    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 pointer-events-none" aria-hidden="true">
        <span class="text-[8px] font-bold uppercase tracking-widest text-white/20">
            <kbd class="bg-white/5 px-1 py-0.5 rounded">Ctrl</kbd> + Mouse Wheel to zoom
        </span>
    </div>

    <div :style="`transform: translate3d(${offset.x}px, ${offset.y}px, 0) scale(${scale}); transform-origin: 0 0; visibility: ${ready ? 'visible' : 'hidden'};`"
         class="inline-block p-10 min-w-max">
        {{ $slot }}
    </div>
</div>
