<div wire:key="alert-management-{{ $metricId }}">
    {{ $this->table }}

    @script
    <script>
        $wire.on('refresh-table', () => {
            $wire.$refresh();
        });
    </script>
    @endscript
</div>
