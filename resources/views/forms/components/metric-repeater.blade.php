{{-- Filament repeater view, with the composite warning above the metric cards. --}}
@php
    use Filament\Forms\Components\Actions\Action;
    use Filament\Support\Enums\Alignment;

    $containers = $getChildComponentContainers();

    $addAction = $getAction($getAddActionName());
    $addBetweenAction = $getAction($getAddBetweenActionName());
    $cloneAction = $getAction($getCloneActionName());
    $collapseAllAction = $getAction($getCollapseAllActionName());
    $expandAllAction = $getAction($getExpandAllActionName());
    $deleteAction = $getAction($getDeleteActionName());
    $moveDownAction = $getAction($getMoveDownActionName());
    $moveUpAction = $getAction($getMoveUpActionName());
    $reorderAction = $getAction($getReorderActionName());
    $extraItemActions = $getExtraItemActions();

    $hasItemNumbers = $hasItemNumbers();
    $isAddable = $isAddable();
    $isCloneable = $isCloneable();
    $isCollapsible = $isCollapsible();
    $isDeletable = $isDeletable();
    $isReorderableWithButtons = $isReorderableWithButtons();
    $isReorderableWithDragAndDrop = $isReorderableWithDragAndDrop();

    $collapseAllActionIsVisible = $isCollapsible && $collapseAllAction->isVisible();
    $expandAllActionIsVisible = $isCollapsible && $expandAllAction->isVisible();

    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{}"
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-fo-repeater grid gap-y-4'])
        }}
    >
        @if ($collapseAllActionIsVisible || $expandAllActionIsVisible)
            <div
                @class([
                    'flex gap-x-3',
                    'hidden' => count($containers) < 2,
                ])
            >
                @if ($collapseAllActionIsVisible)
                    <span
                        x-on:click="$dispatch('repeater-collapse', '{{ $statePath }}')"
                    >
                        {{ $collapseAllAction }}
                    </span>
                @endif

                @if ($expandAllActionIsVisible)
                    <span
                        x-on:click="$dispatch('repeater-expand', '{{ $statePath }}')"
                    >
                        {{ $expandAllAction }}
                    </span>
                @endif
            </div>
        @endif

            @if (count($containers) > 1)
                <div class="metric-composite-note">
                    <p>You have a composite Signal with more than one metric. Some metrics move more than others. You can use the <strong>Multiply movement by</strong> field to raise or lower how much each metric matters, or use Orbit's hints to level them.</p>
                    <div class="metric-composite-actions">
                        @if ($canLevelMetrics ?? false)
                            <button type="button" class="metric-help-link" wire:click="levelMetrics">Level the metrics</button>
                        @endif
                        <button
                            type="button"
                            class="metric-help-link"
                            x-on:click="
                            const wizard = $el.closest('.fi-fo-wizard');
                            if (! wizard || ! window.Alpine) return;
                            window.Alpine.$data(wizard).step = 'help';
                            wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        "
                        >Learn more</button>
                    </div>
                    @if (($canLevelMetrics ?? false) && filled($this->levelMetricsNotice ?? null))
                        <p class="metric-composite-notice" role="status">{{ $this->levelMetricsNotice }}</p>
                    @endif
                </div>
            @endif

        @if (count($containers))
            <ul>
                <x-filament::grid
                    :default="$getGridColumns('default')"
                    :sm="$getGridColumns('sm')"
                    :md="$getGridColumns('md')"
                    :lg="$getGridColumns('lg')"
                    :xl="$getGridColumns('xl')"
                    :two-xl="$getGridColumns('2xl')"
                    :wire:end.stop="'mountFormComponentAction(\'' . $statePath . '\', \'reorder\', { items: $event.target.sortable.toArray() })'"
                    x-sortable
                    :data-sortable-animation-duration="$getReorderAnimationDuration()"
                    class="items-start metric-rule-grid"
                >
                    @foreach ($containers as $uuid => $item)
                        @php
                            $visibleExtraItemActions = array_filter(
                                $extraItemActions,
                                fn (Action $action): bool => $action(['item' => $uuid])->isVisible(),
                            );
                            $cloneAction = $cloneAction(['item' => $uuid]);
                            $cloneActionIsVisible = $isCloneable && $cloneAction->isVisible();
                            $deleteAction = $deleteAction(['item' => $uuid]);
                            $deleteActionIsVisible = $isDeletable && $deleteAction->isVisible();
                            $moveDownAction = $moveDownAction(['item' => $uuid])->disabled($loop->last);
                            $moveDownActionIsVisible = $isReorderableWithButtons && $moveDownAction->isVisible();
                            $moveUpAction = $moveUpAction(['item' => $uuid])->disabled($loop->first);
                            $moveUpActionIsVisible = $isReorderableWithButtons && $moveUpAction->isVisible();
                            $reorderActionIsVisible = $isReorderableWithDragAndDrop && $reorderAction->isVisible();
                        @endphp

                        <li
                            wire:ignore.self
                            wire:key="{{ $this->getId() }}.{{ $item->getStatePath() }}.{{ $field::class }}.item"
                            x-data="{
                                isCollapsed: @js($isCollapsed($item)),
                            }"
                            x-on:expand="isCollapsed = false"
                            x-on:repeater-expand.window="$event.detail === '{{ $statePath }}' && (isCollapsed = false)"
                            x-on:repeater-collapse.window="$event.detail === '{{ $statePath }}' && (isCollapsed = true)"
                            x-sortable-item="{{ $uuid }}"
                            class="metric-rule"
                            x-bind:class="{ 'fi-collapsed': isCollapsed }"
                        >
                            <div
                                class="fi-fo-repeater-item metric-rule-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
                            >
                                <div
                                    x-show="! isCollapsed"
                                    @class([
                                        'fi-fo-repeater-item-content metric-rule-body',
                                        'has-actions' => $deleteActionIsVisible,
                                    ])
                                >
                                    <div class="metric-rule-fields">
                                        {{ $item }}
                                    </div>

                                    @if ($deleteActionIsVisible)
                                        <div class="metric-rule-actions">
                                            {{ $deleteAction }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </li>

                        @if (! $loop->last)
                            @if ($isAddable && $addBetweenAction(['afterItem' => $uuid])->isVisible())
                                <li class="flex w-full justify-center">
                                    <div
                                        class="fi-fo-repeater-add-between-action-ctn rounded-lg bg-white dark:bg-gray-900"
                                    >
                                        {{ $addBetweenAction(['afterItem' => $uuid]) }}
                                    </div>
                                </li>
                            @elseif (filled($labelBetweenItems = $getLabelBetweenItems()))
                                <li
                                    class="relative border-t border-gray-200 dark:border-white/10"
                                >
                                    <span
                                        class="absolute -top-3 left-3 px-1 text-sm font-medium"
                                    >
                                        {{ $labelBetweenItems }}
                                    </span>
                                </li>
                            @endif
                        @endif
                    @endforeach
                </x-filament::grid>
            </ul>
        @endif

        @if ($isAddable && $addAction->isVisible())
            <div
                @class([
                    'flex',
                    match ($getAddActionAlignment()) {
                        Alignment::Start, Alignment::Left => 'justify-start',
                        Alignment::Center, null => 'justify-center',
                        Alignment::End, Alignment::Right => 'justify-end',
                        default => $alignment,
                    },
                ])
            >
                {{ $addAction }}
            </div>
        @endif
    </div>
</x-dynamic-component>
