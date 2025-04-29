<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MetricResource\Pages;
use App\Models\Metric;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MetricResource extends Resource
{
    protected static ?string $model = Metric::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('data_source_id')
                    ->relationship('dataSource', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('column_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('widget_class')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('data_limited_at'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('name')
                            ->label('')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->formatStateUsing(fn ($state) => $state)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-3xl font-extrabold mb-4']),
                        TextEntry::make('description')
                            ->label('')
                            ->formatStateUsing(fn ($state) => $state)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-lg leading-relaxed']),
                        TextEntry::make('dataSource.name')
                            ->label('Data Source')
                            ->formatStateUsing(fn ($state) => $state)
                            ->columnSpan(1),
                        TextEntry::make('data_limited_at')
                            ->label('Time Series Starts On')
                            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->toFormattedDateString() : 'N/A')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dataSource.name')
                    ->label('Data Source')
                    ->sortable(),
                Tables\Columns\TextColumn::make('column_name')
                    ->searchable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('widget_class')
                    ->searchable()
                    ->hidden(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(),
                Tables\Columns\TextColumn::make('data_limited_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('alerts')
                    ->label('Alert')
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell')
                    ->trueColor('blue-600')
                    ->falseColor('gray-500')
                    ->getStateUsing(fn ($record) => $record->user_metric_alerts_count > 0)
                    ->extraAttributes(function ($record) {
                        $hasAlerts = $record->user_metric_alerts_count > 0;
                        return [
                            'class' => $hasAlerts ? 'text-blue-600 dark:text-blue-500 !text-blue-600 dark:!text-blue-500' : 'text-gray-500 dark:text-gray-400 !text-gray-500 dark:!text-gray-400',
                            'style' => $hasAlerts ? 'color: #2563eb !important;' : 'color: #6b7280 !important;',
                        ];
                    })
                    ->action(
                        Tables\Actions\Action::make('manage_alerts')
                            ->modalHeading(fn ($record) => 'Manage Alerts for `' . $record->name . '` Metric')
                            ->modalContent(function ($record) {
                                return new \Illuminate\Support\HtmlString(\Livewire\Livewire::mount('alert-management', ['metricId' => $record->id]));
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close'),
                    ),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Info')
                    ->modalSubmitAction(false)
                    ->modalHeading('')
                    ->modalCancelActionLabel('Close')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('chart')
                            ->label('Chart')
                            ->icon('heroicon-o-presentation-chart-line')
                            ->url(fn ($record) => '/admin/time-series-page?selectedMetrics=' . $record->id)
                            ->color('blue')
                            ->extraAttributes(['style' => 'color: #3D68CC']),
                    ]),
                Tables\Actions\Action::make('chart')
                    ->label('Chart')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->color('blue')
                    ->url(fn ($record) => '/admin/time-series-page?selectedMetrics=' . $record->id)
                    ->extraAttributes(['style' => 'color: #3D68CC']),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMetrics::route('/'),
        ];
    }
}
