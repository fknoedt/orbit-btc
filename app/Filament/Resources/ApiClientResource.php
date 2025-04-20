<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiClientResource\Pages;
use App\Models\ApiClient;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Rawilk\FilamentPasswordInput\Password;

class ApiClientResource extends Resource
{
    protected const int DEFAULT_CLIENT_KEY_LENGTH = 32;

    protected static ?string $model = ApiClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Password::make('client_key')
                    ->label('Client Key')
                    ->required()
                    ->revealable(true)
                    ->copyable()
                    ->regeneratePassword(using: fn () => Str::password(self::DEFAULT_CLIENT_KEY_LENGTH), notify: true)
                    ->default(fn () => Str::password(self::DEFAULT_CLIENT_KEY_LENGTH))
                    ->maxLength(255),
                /*TextInput::make('api_version')
                    ->maxLength(10),
                Forms\Components\DateTimePicker::make('last_request')->readOnly(),
                TextInput::make('last_ip')
                    ->ip()
                    ->readOnly(),*/
                Toggle::make('accepting_connections')
                    ->required(),
                Toggle::make('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('accepting_connections')
                    ->boolean(),
                TextColumn::make('client_key')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => '****'),
                TextColumn::make('api_version')
                    ->searchable(),
                TextColumn::make('last_request')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_ip'),
                TextColumn::make('total_requests')
                    ->numeric()
                    ->label('Total Requests'),
                IconColumn::make('active')
                    ->boolean(),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListApiClients::route('/'),
            'create' => Pages\CreateApiClient::route('/create'),
            'edit' => Pages\EditApiClient::route('/{record}/edit'),
        ];
    }
}
