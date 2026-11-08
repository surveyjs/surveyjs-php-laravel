<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreatorDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['support-assets' => 'creator-uploads.json'] as $key => $file) {
            DB::table('forms')->insertOrIgnore([
                'key' => $key,
                'json' => file_get_contents(base_path('shared/definitions/'.$file)),
            ]);
        }
    }
}
