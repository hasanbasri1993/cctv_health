<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CctvResource\Pages;
use App\Models\Cctv;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CctvResource extends Resource
{
    protected static ?string $model = Cctv::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ip')
                    ->ipv4()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('status')
                    ->inline(false)
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip')
                    ->openUrlInNewTab()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('health')
                    ->getStateUsing(function (Cctv $cctv) {
                        $health = $cctv->health()->latest()->first();
                        if ($health) {
                            return $health->temprature.' °C';
                        }

                        return 'No data';
                    })
                    ->description(function (Cctv $cctv) {
                        $health = $cctv->health()->latest()->first();
                        if ($health) {
                            return $health->powerOnDay.' Days';
                        }

                        return 'No data';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_ok')
                    ->getStateUsing(function (Cctv $cctv) {
                        $health = $cctv->health()->latest()->first();
                        if ($health) {
                            return $health->selfEvaluaingStatus;
                        }

                        return 'No data';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('has_camera')
                    ->numeric()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Camera'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCctvs::route('/'),
            'create' => Pages\CreateCctv::route('/create'),
            'view' => Pages\ViewCctv::route('/{record}'),
            'edit' => Pages\EditCctv::route('/{record}/edit'),
        ];
    }
}
