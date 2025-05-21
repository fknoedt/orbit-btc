<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSignalResource\Pages;
use App\Models\UserSignal;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserSignalResource extends Resource
{
    protected static ?string $model = UserSignal::class;

    protected static ?string $navigationIcon = 'heroicon-o-rss';

    public static ?array $options = [];

    protected static ?string $label = 'Your Signals';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable()
                    ->hidden(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_signal_value')
                    ->label('Signal Value')
                    ->numeric()
                    ->weight(FontWeight::Bold)
                    ->color(fn ($record) => $record->total_signal_value > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('buy_or_sell')
                    ->label('Action')
                    ->sortable(),
                TextColumn::make('time_horizon')
                    ->label('Time Horizon')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_simulated_trades')
                    ->label('Simulated Trades')
                    ->numeric()
                    ->sortable(),
                /*IconColumn::make('warning')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('warning'),
                IconColumn::make('error')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('')
                    ->trueColor('danger'),*/
                TextColumn::make('first_date_calculated')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('scores_last_updated_at')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_paused')
                    ->boolean()
                    ->label('Active')
                    ->trueIcon('heroicon-o-pause')
                    ->falseIcon('heroicon-o-play')
                    ->falseColor('success')
                    ->trueColor('danger'),
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
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->role_id === config('data.role_id.super_admin')) {
                    $query->where('user_id', auth()->id())
                        ->orWhere('user_id', config('data.system_user_id'));
                } else {
                    $query->where('user_id', auth()->id());
                }
                $query->orderByDesc('total_signal_value');
            });
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
            'index' => Pages\ListUserSignals::route('/'),
            'create' => Pages\CreateUserSignal::route('/create'),
            'view' => Pages\ViewUserSignal::route('/{record}'),
            'edit' => Pages\EditUserSignal::route('/{record}/edit'),
        ];
    }
}
