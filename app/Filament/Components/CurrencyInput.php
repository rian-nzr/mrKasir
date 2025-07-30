<?php

namespace App\Filament\Components;

use Filament\Forms\Components\TextInput;

class CurrencyInput extends TextInput
{
    protected string $view = 'filament.components.currency-input';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->prefix('Rp')
            ->placeholder('0')
            ->inputMode('numeric')
            ->extraInputAttributes([
                'class' => 'currency-input',
                'x-data' => 'currencyInput',
                'x-on:input' => 'formatCurrency($event)',
                'x-on:blur' => 'formatCurrency($event)',
            ])
            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
            ->dehydrateStateUsing(fn ($state) => $state ? (int) str_replace(['.', ',', ' '], '', $state) : null);
    }
}
