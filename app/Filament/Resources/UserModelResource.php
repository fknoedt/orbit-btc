<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserModelResource\Pages;
use App\Models\UserModel;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserModelResource extends Resource
{
    protected static ?string $model = UserModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static ?array $options = [];

    protected static ?string $label = 'Model';

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
                    ->searchable(),
                TextColumn::make('total_signal_value')
                    ->label('Signal Value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('buy_or_sell')
                    ->label('Signal')
                    ->sortable(),
                TextColumn::make('threshold')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('time_horizon')
                    ->label('Time Horizon')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('warning')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('warning'),
                IconColumn::make('error')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('')
                    ->trueColor('danger'),
                TextColumn::make('email_to_notify')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('telegram_to_notify')
                    ->label('Telegram')
                    ->toggleable(isToggledHiddenByDefault: true)
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return $query->where('user_id', auth()->id())->orderByDesc('total_signal_value');
    }
}
