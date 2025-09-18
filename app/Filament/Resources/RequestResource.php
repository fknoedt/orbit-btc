<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Dev';

    protected static ?int $navigationSort = 3;

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
                    ->trueIcon('heroicon-o-command-line')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-window')
                    ->falseColor('info')
                    ->label('Origin'),
                TextColumn::make('elapsed_time')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dataSource.name')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('dataSource.name')
                    ->relationship('dataSource', 'name')
                    ->attribute('data_source_id'),
                SelectFilter::make('Status Code')
                    ->options(
                        array_combine(
                            array_keys(Response::$statusTexts),
                            array_map(
                                fn($code, $text) => "$code $text",
                                array_keys(Response::$statusTexts),
                                Response::$statusTexts
                            )
                        )
                    )
                    ->attribute('http_status_code'),
                //Tables\Filters\Filter::make('date')->label('Since')
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('start'),
                        DatePicker::make('end'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['end'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->label('Date Range')
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
