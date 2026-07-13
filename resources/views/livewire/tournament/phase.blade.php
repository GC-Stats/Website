<?php

/**
 * GC-Stats — Tournament bracket Livewire component
 *
 * Volt component that loads the cached tournament page data and exposes
 * the selected phase's bracket/standings to the bracket view.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use Livewire\Volt\Component;

new class extends Component {
    public $phase;
    public $teams = [];

    public function with()
    {
        return [
            'phase' => $this->phase,
            'allTeams' => $this->teams,
        ];
    }

}; ?>

<div class="w-full">
    @if(!empty($phase))
        <div class="flex flex-col gap-12">
            <x-tournament.phase-node :node="$phase" :teams="$allTeams" :show-heading="true" />
        </div>
    @endif
</div>


<script>
    function bracketPanZoom() {
        return {
            scale: 1,
            ready: false,
            containerHeight: 800,
            offset: { x: 0, y: 0 },
            isDragging: false,
            lastMousePos: { x: 0, y: 0 },
            lastTouchDistance: 0,
            ticking: false,

            showHint: false,
            hintTimeout: null,

            init() {
                this.$nextTick(() => {
                    if (this.$el.offsetParent !== null) {
                        this.fitToContainer();
                    }
                });

                const parentWithShow = this.$el.closest('[x-show]');
                if (parentWithShow) {
                    const mo = new MutationObserver(() => {
                        if (this.$el.offsetParent !== null && this.scale === 1) {
                            this.$nextTick(() => this.fitToContainer());
                        }
                    });
                    mo.observe(parentWithShow, { attributes: true, attributeFilter: ['style'] });
                }
            },

            fitToContainer() {
                const container = this.$el;
                const content   = this.$el.querySelector('[\\:style]')
                    ?? this.$el.querySelector('.inline-block');
                if (!content) return;

                const cW = container.clientWidth;
                const iW = content.scrollWidth;
                const iH = content.scrollHeight;

                const minHeight = Math.max(window.innerHeight - 160, 600);
                const maxHeight = window.innerHeight * 1.8;

                const scaleX = Math.min(cW / iW, 1.5) * 0.95;
                const desiredHeight = iH * scaleX;

                let cH;
                if (desiredHeight > maxHeight) {
                    cH = maxHeight;
                    this.scale = (maxHeight / iH) * 0.98;
                } else {
                    cH = Math.max(desiredHeight, minHeight);
                    this.scale = scaleX;
                }

                this.containerHeight = cH;

                this.offset.x = Math.round((cW - iW * this.scale) / 2);
                this.offset.y = Math.round((cH - iH * this.scale) / 2);

                this.ready = true;
                this.$nextTick(() => {
                    const inner = this.$el.querySelector('.inline-block.p-10');
                    if (inner) void inner.offsetHeight;
                    requestAnimationFrame(drawBracketConnectors);
                });
            },

            updateTransform() {
                if (!this.ticking) {
                    window.requestAnimationFrame(() => {
                        this.ticking = false;
                    });
                    this.ticking = true;
                }
            },

            startDragging(e) {
                this.isDragging = true;
                this.lastMousePos = { x: e.clientX, y: e.clientY };
            },

            drag(e) {
                if (!this.isDragging) return;
                this.offset.x = Math.round(this.offset.x + e.clientX - this.lastMousePos.x);
                this.offset.y = Math.round(this.offset.y + e.clientY - this.lastMousePos.y);
                this.lastMousePos = { x: e.clientX, y: e.clientY };
                this.updateTransform();
            },

            stopDragging() { this.isDragging = false; },

            showHint: false,
            hintTimeout: null,

            zoom(e) {
                if (!e.ctrlKey) {
                    this.showHint = true;
                    clearTimeout(this.hintTimeout);
                    this.hintTimeout = setTimeout(() => this.showHint = false, 1500);
                    return;
                }

                const delta = e.deltaY > 0 ? 0.85 : 1.15;
                const newScale = Math.min(Math.max(0.4, this.scale * delta), 1.5);
                this.scale = newScale;
                this.updateTransform();
                requestAnimationFrame(drawBracketConnectors);
            },

            startTouch(e) {
                if (e.touches.length === 1) {
                    this.isDragging = true;
                    this.lastMousePos = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                } else if (e.touches.length === 2) {
                    this.isDragging = false;
                    this.lastTouchDistance = this.getDistance(e.touches);
                }
            },

            moveTouch(e) {
                if (e.touches.length === 1 && this.isDragging) {
                    this.offset.x = Math.round(this.offset.x + e.touches[0].clientX - this.lastMousePos.x);
                    this.offset.y = Math.round(this.offset.y + e.touches[0].clientY - this.lastMousePos.y);
                    this.lastMousePos = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                } else if (e.touches.length === 2) {
                    const distance = this.getDistance(e.touches);
                    const delta = distance / this.lastTouchDistance;
                    this.scale = Math.min(Math.max(0.4, this.scale * delta), 1.5);
                    this.lastTouchDistance = distance;
                }
                this.updateTransform();
            },

            stopTouch() { this.isDragging = false; },
            getDistance(touches) {
                return Math.hypot(touches[0].clientX - touches[1].clientX, touches[0].clientY - touches[1].clientY);
            }
        }
    }
</script>
