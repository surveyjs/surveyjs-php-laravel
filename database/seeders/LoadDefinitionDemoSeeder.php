<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Integration\StoredJson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoadDefinitionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $definition = json_decode(file_get_contents(base_path('shared/definitions/load-definition-variables-data.json')), flags: JSON_THROW_ON_ERROR);
        StoredJson::put('forms', 'claim-request', $definition);

        $responses = json_decode(file_get_contents(base_path('shared/seed/claim-requests.json')), flags: JSON_THROW_ON_ERROR);
        foreach ($responses as $data) {
            if (! DB::table('responses')->where('form_id', 'claim')->where('data', StoredJson::encode($data))->exists()) {
                StoredJson::insertResponse('claim', $data);
            }
        }
    }
}
