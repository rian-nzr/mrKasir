<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;


class Dashboard extends BaseDashboard
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    
    use BaseDashboard\Concerns\HasFiltersForm;
    
    

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Dari Tanggal')
                            ->placeholder('Default: Hari ini')
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->placeholder('Default: Hari ini')
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->description('Jika tidak ada tanggal yang dipilih, dashboard akan menampilkan data hari ini secara otomatis.'),
            ]);
    }

   

}