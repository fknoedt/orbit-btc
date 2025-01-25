<div>
    <label type="text" wire:model="command">{{ $record->command }}</label>
    <textarea class="scale-100" wire:model="commandOutput"></textarea>
    <button wire:click="$refresh" wire:confirm="Are you sure you want to gO?">gO</button>
</div>
