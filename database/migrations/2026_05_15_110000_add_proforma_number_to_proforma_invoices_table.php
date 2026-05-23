<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table): void {
            $table->string('proforma_number', 30)->nullable()->unique()->after('id');
        });

        $sequenceByPrefix = [];

        $existingNumbers = DB::table('proforma_invoices')
            ->whereNotNull('proforma_number')
            ->pluck('proforma_number');

        foreach ($existingNumbers as $number) {
            if (preg_match('/^(PI-\d{4}-\d{2}-)(\d+)$/', (string) $number, $matches) !== 1) {
                continue;
            }

            $prefix = $matches[1];
            $seq = (int) $matches[2];
            $sequenceByPrefix[$prefix] = max($sequenceByPrefix[$prefix] ?? 0, $seq);
        }

        $rows = DB::table('proforma_invoices')
            ->select('id', 'created_at', 'proforma_number')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            if (!empty($row->proforma_number)) {
                continue;
            }

            $timestamp = $row->created_at ? strtotime((string) $row->created_at) : time();
            if ($timestamp === false) {
                $timestamp = time();
            }

            $prefix = 'PI-' . date('Y', $timestamp) . '-' . date('m', $timestamp) . '-';
            $seq = ($sequenceByPrefix[$prefix] ?? 0) + 1;
            $candidate = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

            while (DB::table('proforma_invoices')->where('proforma_number', $candidate)->exists()) {
                $seq++;
                $candidate = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            }

            DB::table('proforma_invoices')
                ->where('id', $row->id)
                ->update(['proforma_number' => $candidate]);

            $sequenceByPrefix[$prefix] = $seq;
        }
    }

    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table): void {
            $table->dropUnique('proforma_invoices_proforma_number_unique');
            $table->dropColumn('proforma_number');
        });
    }
};
