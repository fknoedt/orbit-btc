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

class UserModelResource extends Resource
{
    protected static ?string $model = UserModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static ?array $options = [];

    protected static ?string $label = 'Model';

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
}
