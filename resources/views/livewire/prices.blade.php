<div>
    <input wire:model="search" type="text" placeholder="Search users..."/>
    <button wire:click="$refresh">gO</button>
    <ul>
        @foreach($users as $user)
            <li>{{ $user->date }}</li>
        @endforeach
    </ul>
</div>
