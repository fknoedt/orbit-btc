<?php

namespace App\Filament\Resources;

use App\Enum\Operators;
use App\Filament\Resources\UserModelResource\Pages;
use App\Models\Metric;
use App\Models\UserModel;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserModelResource extends Resource
{
    protected static ?string $model = UserModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable()
                    ->hidden(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('threshold')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('email_to_notify')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('telegram_to_notify')
                    ->label('Telegram')
                    ->searchable(),
                IconColumn::make('is_paused')
                    ->boolean()
                    ->label('Active')
                    ->trueIcon('heroicon-o-pause')
                    ->falseIcon('heroicon-o-play')
                    ->falseColor('success')
                    ->trueColor('danger'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserModels::route('/'),
            'create' => Pages\CreateUserModel::route('/create'),
            'view' => Pages\ViewUserModel::route('/{record}'),
            'edit' => Pages\EditUserModel::route('/{record}/edit'),
        ];
    }

    public static function getInfoSchema(): array
    {
        return [
            Forms\Components\Hidden::make('id'),
            TextInput::make('user_id')
                ->required()
                ->numeric()
                ->hidden(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->placeholder('What is your Model looking for?')
                ->required()
                ->columnSpanFull(),
            TextInput::make('threshold')
                ->numeric()
                ->hidden(),
            TextInput::make('last_score')
                ->numeric()
                ->hidden(),
            TextInput::make('email_to_notify')
                ->label('Notify Email')
                ->hint('Alerts will be sent to this email when the threshold is hit')
                ->email()
                ->maxLength(255),
            TextInput::make('telegram_to_notify')
                ->tel()
                ->maxLength(255),
        ];
    }

    public static function getMetricsSchema(): array
    {
        $metrics = Metric::with('dataSource')->get();

        return [
            Section::make()
                //->columns(4)
                ->schema([
                    // Repeatable field for invoice items
                    Repeater::make('userModelMetrics')
                        // Defined as a relationship to the InvoiceProduct model
                        ->relationship()
                        ->lazy()
                        ->defaultItems(0)
                        ->deleteAction(
                            fn (Action $action) => $action->requiresConfirmation(),
                        )
                        ->schema([
                            Select::make('metric_id')
                                ->label('Metric')
                                ->options(
                                    $metrics->mapWithKeys(function (Metric $metric) {
                                        return [$metric->id => sprintf('%s (%s)', $metric->name, $metric->dataSource->name)];
                                    })
                                )
                                ->columns(1)
                                ->required(),
                            Select::make('operator')
                                ->label('Operator')
                                ->columns(1)
                                ->options(
                                    Operators::class
                                )
                                ->required(),
                            TextInput::make('oscillation_threshold')
                                ->mask(
                                    RawJs::make("parseFloat(\$input) > 100 ? '100' : (\$input[2] === '.' ? '99.99' : (\$input[1] === '.' ? '9.99' : '999'))")
                                )
                                ->numeric()
                                ->placeholder('%')
                                ->label('Oscillation')
                                ->columns(1)
                                ->default(1)
                                ->required(),
                            Radio::make('weight')
                                ->label('Weight')
                                ->inline()
                                ->columns(1)
                                ->options(
                                    [0, 1, 2, 3, 4, 5],
                                )
                                ->required(),
                        ])
                        // Disable reordering
                        ->reorderable(false)
                        ->columns(4)
                ]),
        ];
    }

    public static function getThresholdSchema(): array
    {
        return [
            Toggle::make('is_paused')
                ->label('Monitoring Paused?')
                ->default(false)
                ->columns(1),
            TextInput::make('threshold')
                ->mask(
                    RawJs::make("parseFloat(\$input) > 100 ? '100' : (\$input[2] === '.' ? '99.99' : (\$input[1] === '.' ? '9.99' : '999'))")
                )
                ->numeric()
                ->placeholder('%')
                ->label('Final Threshold')
                ->hint('This is the sum of each Metric\'s score x their weight (?)')
                ->columns(1)
                ->default(1)
                ->required(),
        ];
    }
}
