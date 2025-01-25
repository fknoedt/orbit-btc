<div>
    <input wire:model="search" type="text" placeholder="Search Commands..."/>
    <button wire:click="$refresh" wire:confirm="Are you sure you want to gO?">gO</button>
    @foreach($commands as $section => $commandList)
        <h3>{{ $section }}</h3>
        <ul>
            @foreach($commandList as $command)
                <li>
                    @if(count($command) > 1)
                        <strong>{{ $command[0] }}</strong> - {{implode(' ', array_slice($command, 1))}}
                    @else
                        <a href="/rpc/command/{{ $command[0] }}" style="">{{ $command[0] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endforeach
</div>
