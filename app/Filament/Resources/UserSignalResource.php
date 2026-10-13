<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSignalResource\Pages;
use App\Models\UserSignal;
use App\Services\UserSignalService;
use Carbon\Carbon;
use Closure;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

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
                self::tooltipColumn('name', self::nameTooltip(...))
                    ->sortable()
                    ->searchable(),
                self::tooltipColumn('user_signal_metrics_count', self::metricNames(...))
                    ->label('# Metrics'),
                self::tooltipColumn('total_signal_value', self::signalValueTooltip(...), self::signalValueCell(...))
                    ->label('Signal Value')
                    ->sortable(),
                self::tooltipColumn('buy_or_sell', self::actionTooltip(...))
                    ->label('Action')
                    ->sortable(),
                self::tooltipColumn('time_horizon', self::timeHorizonTooltip(...), self::timeHorizonCell(...))
                    ->label('Time Horizon')
                    ->sortable(),
                self::tooltipColumn('total_simulated_trades', self::simulatedTradesTooltip(...), self::simulatedTradesCell(...))
                    ->label('Simulated Trades')
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
                self::tooltipColumn(
                    'first_date_calculated',
                    fn (UserSignal $record): string => 'Scoring starts once every metric in this signal has data.',
                    self::startDateCell(...),
                )
                    ->label('Start Date')
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
                self::tooltipColumn(
                    'is_paused',
                    fn (UserSignal $record): string => 'Play means Orbit is scoring this signal. Pause freezes calculations, notifications, and performance tracking.',
                    self::activeCell(...),
                )
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('performance')
                    ->label('Performance')
                    ->icon('heroicon-m-arrow-trending-up')
                    ->color('gray')
                    ->url(fn (UserSignal $record): string => "/app/user-signal-score/{$record->id}"),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount('userSignalMetrics')
                    ->with([
                        'userSignalMetrics.metric' => fn ($metricQuery) => $metricQuery->withTrashed(),
                    ]);

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

    /**
     * Column whose hover or tap target is the value, not the full cell.
     *
     * The tooltip closure returns the bubble text, or null when there is nothing to show.
     * The extra closure may set text, color, bold, and icon. Pass text when a formatted
     * value can be null; a missing text key falls back to the raw column state.
     * icon is a Heroicon name and replaces the text.
     * Those keys stay inside cell. A top-level view key such as label collides
     * with TextColumn's public methods, which Filament injects as closures.
     *
     * @param  Closure(UserSignal): (?string)  $tooltip
     * @param  Closure(UserSignal): array{text?: ?string, color?: ?string, bold?: bool, icon?: ?string}  $extra
     */
    private static function tooltipColumn(string $name, Closure $tooltip, ?Closure $extra = null): TextColumn
    {
        return TextColumn::make($name)
            ->view('filament.tables.columns.pinned-tooltip')
            ->viewData(function (UserSignal $record) use ($tooltip, $extra): array {
                return [
                    'cell' => [
                        'tooltip' => $tooltip($record),
                        ...($extra ? $extra($record) : []),
                    ],
                ];
            });
    }

    private static function nameTooltip(UserSignal $record): ?string
    {
        return filled($record->description) ? $record->description : null;
    }

    private static function metricNames(UserSignal $record): ?string
    {
        $names = $record->userSignalMetrics
            ->map(fn ($userSignalMetric) => $userSignalMetric->metric?->name)
            ->filter()
            ->sort()
            ->values();

        return $names->isEmpty() ? null : $names->implode(', ');
    }

    private static function numericLabel(mixed $state): ?string
    {
        if (! is_numeric($state)) {
            return null;
        }

        return Number::format($state);
    }

    private static function tradeSizeLabel(): string
    {
        $amount = Number::currency(
            UserSignalService::tradeSizeInUsd(),
            strtoupper((string) config('btc.currency', 'USD')),
            precision: 0,
        );

        return $amount === false ? (string) UserSignalService::tradeSizeInUsd() : $amount;
    }

    private static function startDateLabel(UserSignal $record): ?string
    {
        if (blank($record->first_date_calculated)) {
            return null;
        }

        return Carbon::parse($record->first_date_calculated)->format('d M Y');
    }

    private static function startDateCell(UserSignal $record): array
    {
        return [
            'text' => self::startDateLabel($record),
        ];
    }

    private static function activeCell(UserSignal $record): array
    {
        $paused = (bool) $record->is_paused;

        return [
            'text' => null,
            'icon' => $paused ? 'heroicon-o-pause' : 'heroicon-o-play',
            'color' => $paused ? 'danger' : 'success',
        ];
    }

    private static function signalValueCell(UserSignal $record): array
    {
        return [
            'text' => self::numericLabel($record->total_signal_value),
            'color' => $record->total_signal_value > 0 ? 'success' : 'danger',
            'bold' => true,
        ];
    }

    private static function timeHorizonCell(UserSignal $record): array
    {
        return [
            'text' => self::numericLabel($record->time_horizon),
        ];
    }

    private static function simulatedTradesCell(UserSignal $record): array
    {
        return [
            'text' => self::numericLabel($record->total_simulated_trades),
        ];
    }

    private static function signalValueTooltip(UserSignal $record): string
    {
        $since = self::startDateLabel($record);
        $sinceClause = $since ? " since {$since}" : '';

        return "Simulated profit or loss{$sinceClause}. Each trade is ".self::tradeSizeLabel().', scored over the time horizon.';
    }

    private static function actionTooltip(UserSignal $record): string
    {
        $action = $record->buy_or_sell;
        $verb = match ($action) {
            'buy' => 'buying',
            'sell' => 'selling',
            default => 'a trade of',
        };
        $label = filled($action) ? ucfirst($action) : 'Trade';

        return "{$label}. Orbit simulates {$verb} ".self::tradeSizeLabel().' of bitcoin when the daily score crosses the threshold.';
    }

    private static function timeHorizonTooltip(UserSignal $record): string
    {
        if (! is_numeric($record->time_horizon)) {
            return 'Orbit scores the trade from the price change over this window, then waits out that window before another trade.';
        }

        $days = (int) $record->time_horizon;

        if ($days === 1) {
            return '1 day. Orbit scores the trade from the price change over that day, then waits out that day before another trade.';
        }

        $dayLabel = self::numericLabel($days) ?? (string) $days;

        return "{$dayLabel} days. Orbit scores the trade from the price change over those days, then waits out those days before another trade.";
    }

    private static function simulatedTradesTooltip(UserSignal $record): string
    {
        $count = (int) $record->total_simulated_trades;
        $formattedCount = self::numericLabel($count) ?? (string) $count;
        $trades = $count === 1 ? '1 trade' : "{$formattedCount} trades";
        $since = self::startDateLabel($record);
        $sinceClause = $since ? " since {$since}" : '';

        return "{$trades} of ".self::tradeSizeLabel()."{$sinceClause}. Counted when the score crosses the threshold and no earlier trade is still open.";
    }
}
