<?php

namespace App\Filament\Resources\Therapists\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TherapistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Terapis')
                    ->required(),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel(),
                Toggle::make('is_active')
                    ->label('Aktif?')
                    ->default(true)
                    ->required(),
                TextInput::make('created_by'),
                TextInput::make('updated_by'),
            ]);
    }
}
