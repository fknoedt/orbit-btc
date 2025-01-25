<?php

namespace App\Filament\Resources\RpcCommandResource\Pages;

use App\Filament\Resources\RpcCommandResource;
use Filament\Resources\Pages\ManageRecords;

class ManageRpcCommands extends ManageRecords
{
    protected static string $resource = RpcCommandResource::class;

    public string $command = '';
    public string $commandOutput = '';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
