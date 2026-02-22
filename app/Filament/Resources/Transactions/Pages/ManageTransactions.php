<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Exports\TransactionExporter;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Enums\TransactionStatusEnum;
use App\Policies\TransactionPolicy;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManageTransactions extends ManageRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->size(Size::ExtraLarge),
            ExportAction::make() // Add the export action
                ->exporter(TransactionExporter::class) // Link to your existing exporter
                ->fileName(fn() => 'data-transaksi-' . now()->format('Ymd_His'))
                ->icon(Heroicon::ArrowDown)
                ->color('success')
                ->authorize(fn() =>  app(TransactionPolicy::class)->export(Auth::user()))
                ->label('Ekspor Data Transaksi'),
        ];
    }

    public function getTabs(): array
    {
        // Tab "SEMUA"
        $tabs = [
            'all' => Tab::make('all')
                ->label('SEMUA'),
        ];
        // Buat tab untuk tiap status
        foreach (TransactionStatusEnum::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->value)
                ->badgeColor(TransactionStatusEnum::color($status->value))
                ->label(strtoupper($status->label()))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
