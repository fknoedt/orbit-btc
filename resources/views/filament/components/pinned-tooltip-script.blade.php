{{--
    One document listener set, not one per cell. This Alpine build has no
    cleanup hook, so per-cell window listeners would leak across Livewire morphs.
    trigger stays manual: Tippy ignores show() when touch is false, and a phone
    tap on a row link would navigate before a hover tooltip could stay open.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        if (document.documentElement.dataset.pinnedTooltip === 'ready') {
            return;
        }

        document.documentElement.dataset.pinnedTooltip = 'ready';

        const tooltips = new WeakMap();
        const hoverQuery = window.matchMedia('(hover: hover) and (pointer: fine)');

        const componentFor = (element) => (element ? tooltips.get(element) : null);

        const componentFromTarget = (target) => componentFor(target?.closest?.('[data-pinned-tooltip]'));

        const componentInsideControl = (target) => {
            const host = target?.closest?.('a, button');

            if (! host) {
                return null;
            }

            const tips = host.querySelectorAll('[data-pinned-tooltip]');

            return tips.length === 1 ? componentFor(tips[0]) : null;
        };

        const eachTooltip = (callback) => {
            document.querySelectorAll('[data-pinned-tooltip]').forEach((element) => {
                const tooltip = componentFor(element);

                if (tooltip) {
                    callback(tooltip, element);
                }
            });
        };

        const dismissPinnedExcept = (target) => {
            eachTooltip((tooltip, element) => {
                if (! tooltip.pinned) {
                    return;
                }

                const popper = tooltip.tip()?.popper;

                if (target && (element.contains(target) || popper?.contains(target))) {
                    return;
                }

                tooltip.dismiss();
            });
        };

        document.addEventListener('focusin', (event) => {
            const tooltip = componentFromTarget(event.target) ?? componentInsideControl(event.target);

            if (tooltip?.canHover) {
                tooltip.show();
            }
        });

        document.addEventListener('focusout', (event) => {
            const tooltip = componentFromTarget(event.target) ?? componentInsideControl(event.target);

            if (tooltip?.canHover && ! tooltip.pinned) {
                tooltip.hide();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            eachTooltip((tooltip) => tooltip.dismiss());
        });

        // Capture runs before the cell's click handler, so opening one tip
        // closes the others, and a tap on the bubble itself does not dismiss it.
        document.addEventListener('click', (event) => {
            dismissPinnedExcept(event.target);
        }, true);

        // Scroll does not bubble. Capture hears the table's overflow scroller.
        // Only pinned tips hide, so a layout shift cannot cancel a mouse hover.
        document.addEventListener('scroll', () => {
            dismissPinnedExcept(null);
        }, true);

        hoverQuery.addEventListener('change', () => {
            eachTooltip((tooltip) => {
                tooltip.canHover = hoverQuery.matches;
                tooltip.dismiss();
            });
        });

        Alpine.data('pinnedTooltip', () => ({
            pinned: false,
            canHover: hoverQuery.matches,

            get placement() {
                return this.canHover ? 'right' : 'top';
            },

            get maxWidth() {
                return Math.min(320, window.innerWidth - 24);
            },

            init() {
                tooltips.set(this.$el, this);
            },

            tip() {
                return this.$el.__x_tippy || this.$el._tippy;
            },

            show() {
                this.tip()?.show();
            },

            hide() {
                this.tip()?.hide();
            },

            dismiss() {
                this.pinned = false;
                this.hide();
            },

            onHoverStart() {
                if (this.canHover) {
                    this.show();
                }
            },

            onHoverEnd() {
                if (this.canHover && ! this.pinned) {
                    this.hide();
                }
            },

            onPress(event) {
                if (this.canHover) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                this.pinned = ! this.pinned;

                if (this.pinned) {
                    this.show();
                } else {
                    this.hide();
                }
            },
        }));
    });
</script>
