<div wire:key="alert-management-{{ $metricId }}" wire:init="initialize">
    {{ $this->table }}
    <x-filament-actions::modals />
</div>

@script
<script>
    window.addEventListener('refresh-table', function () {
        window.Livewire.find('{{ $this->getId() }}').$refresh();
    });
</script>
@endscript
