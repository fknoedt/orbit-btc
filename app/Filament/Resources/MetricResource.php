<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MetricResource\Pages;
use App\Models\Metric;
use App\Models\UserMetricAlert;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                            ->modalHeading(fn ($record) => 'Manage Alerts for ' . $record->name)
                            ->modalContent(fn ($record) => view('components.alert-management-modal', ['metricId' => $record->id]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->filters([])
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
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_alert')
                    ->label('+')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('metric_id')
                            ->relationship('metric', 'name')
                            ->required(),
                        Forms\Components\Select::make('frequency_id')
                            ->relationship('frequency', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('threshold')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\Select::make('operator')
                            ->options([
                                '+' => 'Above',
                                '-' => 'Below',
                                '+-' => 'Above or Below',
                            ])
                            ->default('+')
                            ->required(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                    ])
                    ->action(function (array $data) {
                        UserMetricAlert::create([
                            'user_id' => $data['user_id'],
                            'metric_id' => $data['metric_id'],
                            'frequency_id' => $data['frequency_id'],
                            'threshold' => $data['threshold'],
                            'operator' => $data['operator'],
                        ]);
                    })
                    ->modalHeading('Create Alert')
                    ->modalSubmitActionLabel('Create')
                    ->color('blue'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMetrics::route('/'),
        ];
    }
}
