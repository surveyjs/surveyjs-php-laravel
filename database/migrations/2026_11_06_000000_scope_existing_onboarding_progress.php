<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The previous demo always used Alice, so retain her existing draft
        $alice = DB::table('users')->where('email', 'alice@example.com')->value('id');
        if ($alice !== null && ! DB::table('progress')->where('key', $alice.':onboarding')->exists()) {
            DB::table('progress')->where('key', 'onboarding')->update(['key' => $alice.':onboarding']);
        }
    }

    public function down(): void
    {
        $alice = DB::table('users')->where('email', 'alice@example.com')->value('id');
        if ($alice !== null && ! DB::table('progress')->where('key', 'onboarding')->exists()) {
            DB::table('progress')->where('key', $alice.':onboarding')->update(['key' => 'onboarding']);
        }
    }
};
