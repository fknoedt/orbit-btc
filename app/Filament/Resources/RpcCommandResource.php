<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RpcCommandResource\Pages;
use App\Models\RpcCommand;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;

class RpcCommandResource extends Resource
{
    protected static ?string $model = RpcCommand::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Tools';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('command')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->alignLeft(),

                TextColumn::make('arguments')
                    ->searchable()
                    ->sortable()
                    ->color('secondary')
                    ->alignLeft(),

                IconColumn::make('can_run')
                    ->boolean()
                    ->sortable()
                    ->searchable()

            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('runCommand')
                    ->url(fn (RpcCommand $record): string => route('rpc-command.run', $record->command))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-play')
                    ->label('Run')
                    ->visible(fn ($record) => $record->can_run),
                Action::make('docs')
                    ->url(fn (RpcCommand $record): string => route('rpc-command.docs', $record->command))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document')
                    ->label('Docs')
                    ->color(Color::Blue),
                /*Action::make('Run')
                    ->modal('rpcCommand')
                    ->modalCancelActionLabel('Close')
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    // ->modalSubmitAction(false)
                    ->modalDescription('Run an RPC Command straight on a BTC Node')
                    ->form([
                        TextInput::make('command'),
                        Textarea::make('commandOutput')
                    ])
                    ->fillForm(fn (RpcCommand $record): array => [
                        'command' => $record->command,
                        'commandOutput' => ''
                    ])
                    ->action(function (array $data, RpcCommand $record): void {
                        // TODO: to avoid the modal from closing, we need to halt()..is there a way to update the form?
                        throw new Halt();
                    })

                    ->visible(fn ($record) => $record->can_run)

                    ->extraModalFooterActions(fn (Action $action): array => [
                        $action->makeModalAction('Run')
                            ->name('Run it!?!')
                            ->action('runCommand')
                            ->arguments(['command' => 'thissssss'])
                            ->extraAttributes(['commando' => 'AND .. thissssss'])
                            ->eventData(['command' => 'thissssss!!!'])
                            ->livewireTarget(Pages\ManageRpcCommands::class)
                        //$action->makeModalSubmitAction('Run Command', arguments: ['command' => 'coWa', 'action' => 'rpcCommand'])
                    ])*/
            ], position: ActionsPosition::AfterColumns);
            /*->actions([
                Action::make('Run')
                    ->modalDescription('Run an RPC Command straight on a BTC Node')
                    ->modalContent(fn(RpcCommand $record) => view('run-rpc-command', ['record' => $record]))
                    ->modalCancelActionLabel('Close')
                    //->modalSubmitAction(false)
                    ->extraModalFooterActions(fn (Action $action): array => [
                        $action->makeModalAction('Run')->name('Run it!')->action('runCommand')
                    ])
            ], position: ActionsPosition::BeforeColumns);*/
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRpcCommands::route('/'),
        ];
    }
}
