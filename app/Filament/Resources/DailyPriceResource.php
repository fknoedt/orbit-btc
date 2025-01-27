<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyPriceResource\Pages;
use App\Models\DailyPrice;
use App\Tables\Columns\PriceLinkColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DailyPriceResource extends Resource
{
    protected static ?string $model = DailyPrice::class;

    protected static ?string $navigationIcon = 'heroicon-s-currency-dollar';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('data_source_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('market_cap')
                    ->numeric(),
                Forms\Components\TextInput::make('total_volume')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('dataSource.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('market_cap')
                    ->money()
                    ->sortable(),
                TextColumn::make('total_volume')
                    ->money()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('dataSource.name')
                    ->relationship('dataSource', 'name')
                    ->attribute('data_source_id'),
                //Tables\Filters\Filter::make('date')->label('Since')
                Filter::make('date')
                    ->form([
                        DatePicker::make('start'),
                        DatePicker::make('end'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['end'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                ->label('Date Range')
            ])
            /*->actions([
                Tables\Actions\EditAction::make(),
            ])*/
            /*->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])*/;
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
            'index' => Pages\ListDailyPrices::route('/'),
        ];
    }
}
