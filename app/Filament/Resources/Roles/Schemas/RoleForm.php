<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Hak akses'),
                Hidden::make('guard_name')->default('web'),
                CheckboxList::make('permissions')
                    ->label('Hak akses')
                    ->searchable()
                    ->relationship('permissions', 'name')
                    ->options(Permission::all()->pluck('name', 'id'))
                    ->required()
                    ->columns(4)
                    ->columnSpanFull()
            ]);
    }
}
