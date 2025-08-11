<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateExistingSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing settings yang belum punya store_id
        $settingsWithoutStore = Setting::whereNull('store_id')->get();
        
        if ($settingsWithoutStore->count() > 0) {
            // Ambil store pertama yang aktif
            $firstStore = Store::where('is_active', true)->first();
            
            if ($firstStore) {
                foreach ($settingsWithoutStore as $setting) {
                    $setting->update(['store_id' => $firstStore->id]);
                    $this->command->info("Updated setting ID {$setting->id} with store_id {$firstStore->id}");
                }
            } else {
                $this->command->warn("No active store found. Please create a store first.");
            }
        } else {
            $this->command->info("All settings already have store_id assigned.");
        }
    }
}
