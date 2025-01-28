<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * @todo infolist for raw_response
     * @see https://laraveldaily.com/post/filament-infolist-custom-entry-with-show-more-button
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dataSource.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('class_method')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('http_method')
                    ->searchable()
                    ->label('HTTP'),
                TextColumn::make('args')
                    ->searchable()
                    ->limit(50)
                    ->label('Arguments'),
                TextColumn::make('http_status_code')
                    ->numeric()
                    ->sortable()
                    ->label('Status'),
                Tables\Columns\IconColumn::make('cron')
                    ->boolean()
                    ->label('Console'),
                TextColumn::make('elapsed_time')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListRequests::route('/'),
        ];
    }
}
