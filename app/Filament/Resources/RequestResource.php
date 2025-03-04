<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * @todo infolist with raw_response
     * @see https://laraveldaily.com/post/filament-infolist-custom-entry-with-show-more-button
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns(components: [
                TextColumn::make('created_at')
                    ->dateTime('m/d/y H:i:s') // @todo centralize it
                    ->sortable(),
                TextColumn::make('class_method')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('http_method')
                    ->searchable()
                    ->label('HTTP'),
                TextColumn::make('args')
                    ->searchable()
                    ->limit(50)
                    ->label('Arguments')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('http_status_code')
                    ->numeric()
                    ->sortable()
                    ->label('Status'),
                IconColumn::make('cron')
                    ->boolean()
                    ->label('Console'),
                TextColumn::make('elapsed_time')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dataSource.name')
                    ->numeric()
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
