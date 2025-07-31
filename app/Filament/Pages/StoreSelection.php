<?php

namespace App\Filament\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Card;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Models\Store;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class StoreSelection extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static string $view = 'filament.pages.store-selection';
    protected static ?string $title = 'Pilih Toko';
    protected static bool $shouldRegisterNavigation = false;
    
    public ?array $data = [];
    public $selected_store_id = null;

    public function mount(): void
    {
        $user = auth()->user();
        
        // Jika user bukan super admin, redirect ke dashboard
        if (!$user || !$user->isSuperAdmin()) {
            $this->redirect('/admin');
            return;
        }
        
        // Jika sudah ada selected store, langsung redirect ke dashboard
        if (session('selected_store_id')) {
            $this->redirect('/admin');
            return;
        }
        
        $this->form->fill([]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Select::make('selected_store_id')
                            ->label('Pilih Toko')
                            ->placeholder('-- Pilih Toko --')
                            ->options(Store::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selected_store_id = $state;
                            })
                    ])
                    ->heading('Pilih Toko untuk Mengelola')
                    ->description('Sebagai Super Admin, Anda perlu memilih toko yang akan dikelola terlebih dahulu.')
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('selectStore')
                ->label('Pilih Toko')
                ->color('primary')
                ->size('lg')
                ->disabled(fn () => !$this->selected_store_id)
                ->action(function () {
                    if (!$this->selected_store_id) {
                        Notification::make()
                            ->title('Pilih toko terlebih dahulu')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Set selected store in session
                    Session::put('selected_store_id', $this->selected_store_id);
                    
                    $store = Store::find($this->selected_store_id);
                    
                    Notification::make()
                        ->title('Toko berhasil dipilih')
                        ->body("Anda sekarang mengelola toko: {$store->name}")
                        ->success()
                        ->send();

                    // Redirect to dashboard
                    return redirect()->route('filament.admin.pages.dashboard');
                })
        ];
    }

    public static function getRoutes(): array
    {
        return [
            '/store-selection' => static::class,
        ];
    }
}
