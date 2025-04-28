<?php

namespace App\Http\Livewire;

use App\Models\Frequency;
use App\Models\UserMetricAlert;
use App\Models\Metric;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AlertManagement extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    public $metricId;
    public $metricName;
    public $frequencies;

    public function mount($metricId, $metricName)
    {
        $this->metricId = $metricId;
        $this->metricName = $metricName;
        $this->frequencies = Frequency::all();
        logger("Mounted AlertManagement with metricId: {$metricId}, metricName: {$metricName}");
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return UserMetricAlert::query()->where('metric_id', $this->metricId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('frequency.name')->label('Frequency'),
                Tables\Columns\TextColumn::make('threshold')->numeric(),
                Tables\Columns\TextColumn::make('operator')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '+' => 'Above',
                        '-' => 'Below',
                        '+-' => 'Above or Below',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_alert')
                    ->label('Add Alert')
                    ->color('warning')
                    ->modalHeading(fn () => "Create Alert for {$this->metricName}")
                    ->form([
                        Forms\Components\Select::make('frequency_id')
                            ->label('Frequency')
                            ->options($this->frequencies->pluck('name', 'id'))
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
                        Forms\Components\Hidden::make('metric_id')
                            ->default($this->metricId),
                    ])
                    ->action(function (array $data) {
                        logger("Creating alert with data: " . json_encode($data));
                        UserMetricAlert::create($data);
                        logger("Alert created");
                        $this->dispatch('refresh-table');
                        logger("Dispatched refresh-table event");
                    })
                    ->modalSubmitActionLabel('Create')
                    ->modalCancelActionLabel('Cancel'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
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
                            ->required(),
                    ])
                    ->after(function () {
                        $this->dispatch('refresh-table');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->dispatch('refresh-table');
                    }),
            ]);
    }

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    public function render()
    {
        return view('livewire.alert-management');
    }
}
