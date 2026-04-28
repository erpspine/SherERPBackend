<?php

use App\Models\Quotation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->string('quotation_number', 20)->nullable()->unique()->after('id');
        });

        // Backfill existing quotations in chronological order, grouping by year-month
        $quotations = DB::table('quotations')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        $monthlyCounters = [];

        foreach ($quotations as $row) {
            $ym = substr($row->created_at ?? now()->toDateTimeString(), 0, 7); // "YYYY-MM"
            $monthlyCounters[$ym] = ($monthlyCounters[$ym] ?? 0) + 1;

            [$year, $month] = explode('-', $ym);
            $number = 'QT-' . $year . '-' . $month . '-' . str_pad((string) $monthlyCounters[$ym], 4, '0', STR_PAD_LEFT);

            DB::table('quotations')->where('id', $row->id)->update(['quotation_number' => $number]);
        }
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropColumn('quotation_number');
        });
    }
};
