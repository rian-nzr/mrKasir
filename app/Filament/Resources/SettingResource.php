<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Setting;
use App\Models\Store;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SettingResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;


class SettingResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'delete_any',
        ];
    }

    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationGroup = 'Pengaturan Toko';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Toko')
                ->schema([
                    Forms\Components\Select::make('store_id')
                        ->label('Toko')
                        ->options(Store::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->default(function () {
                            $user = auth()->user();
                            if ($user->hasRole('super_admin')) {
                                return session('selected_store_id');
                            }
                            return $user->store_id;
                        })
                        ->disabled(function () {
                            $user = auth()->user();
                            return !$user->hasRole('super_admin');
                        })
                        ->helperText(function () {
                            $user = auth()->user();
                            if (!$user->hasRole('super_admin')) {
                                return 'Toko ditentukan berdasarkan akun Anda';
                            }
                            return null;
                        }),
                ]),
                Forms\Components\Section::make('Profil Toko')
                ->schema([
                Forms\Components\TextInput::make('shop')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Toko'),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255)
                    ->label('Alamat Toko'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255)
                    ->label('Nomor Telepon'),
                ]),
                Forms\Components\Section::make('Setting Printer')
                ->schema([
                Forms\Components\ToggleButtons::make('print_via_mobile')
                    ->required()
                    ->label('Tipe Print')
                    ->options([
                        0 => 'Kabel',
                        1 => 'Bluetooth'
                    ])
                    ->grouped()
                    ->helperText('Pastikan setiap masuk halaman kasir sambungkan bluetooth terlebih dahulu')
                    ->live(),
                Forms\Components\TextInput::make('name_printer')
                    ->maxLength(255)
                    ->label('Nama Printer')
                    ->helperText('Samakan dengan nama printer yang anda gunakan dan sudah terdaftar atau terhubung di server yang sama. Contoh: Epson T20')
                    ->hidden(fn (Get $get) => $get('print_via_mobile') == true), // Disembunyikan jika print_via_mobile bernilai true
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->required()
                    ->helperText('Pastikan format gambar adalah PNG')
                    ->directory('images')
                    ->label('Logo Toko'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Toko')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->label('Logo Toko'),
                Tables\Columns\TextColumn::make('shop')
                    ->label('Nama Toko')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat Toko')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_printer')
                    ->label('Nama Printer')
                    ->searchable(),
                Tables\Columns\IconColumn::make('print_via_mobile')
                    ->label('Print Via Mobile')
                    ->boolean(),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                
                if ($user->hasRole('super_admin')) {
                    // Super admin bisa lihat semua atau filter berdasarkan store yang dipilih
                    if (session('selected_store_id')) {
                        return $query->where('store_id', session('selected_store_id'));
                    }
                    return $query; // Tampilkan semua
                } else {
                    // User biasa hanya bisa lihat setting toko mereka
                    return $query->where('store_id', $user->store_id);
                }
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
            'index' => Pages\ListSettings::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        if ($user->hasRole('super_admin')) {
            // Super admin bisa buat setting jika store yang dipilih belum punya setting
            if (session('selected_store_id')) {
                return !Setting::where('store_id', session('selected_store_id'))->exists();
            }
            return true; // Bisa buat setting untuk store manapun
        } else {
            // User biasa bisa buat setting jika toko mereka belum punya
            return !Setting::where('store_id', $user->store_id)->exists();
        }
    }
}
