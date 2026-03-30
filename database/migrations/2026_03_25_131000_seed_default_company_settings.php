<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $defaults = [
            'company_name' => 'Sher Vehicle Leasing Ltd',
            'company_email' => 'info@sher-leasing.co.tz',
            'company_phone' => '+255 754 123 456',
            'company_address' => 'Sher Leasing HQ, Nyerere Road, Dar es Salaam, Tanzania',
            'tax_registration_number' => 'TIN-123-456-789',
            'default_currency' => 'TZS',
            'default_vat' => '18',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'company_name',
            'company_email',
            'company_phone',
            'company_address',
            'tax_registration_number',
            'default_currency',
            'default_vat',
        ])->delete();
    }
};
