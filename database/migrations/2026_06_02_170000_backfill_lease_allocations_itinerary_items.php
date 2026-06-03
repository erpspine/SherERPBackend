<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rows = DB::table('lease_allocations')
            ->whereNotNull('itinerary')
            ->where(function ($q) {
                $q->whereNull('itinerary_items')
                    ->orWhere('itinerary_items', '')
                    ->orWhere('itinerary_items', '[]');
            })
            ->get(['id', 'itinerary']);

        foreach ($rows as $row) {
            $items = $this->parseItinerary((string) $row->itinerary);
            if (empty($items)) {
                continue;
            }
            DB::table('lease_allocations')
                ->where('id', $row->id)
                ->update(['itinerary_items' => json_encode($items)]);
        }
    }

    public function down(): void
    {
        // No-op: we keep parsed data; reversing would lose structure.
    }

    private function parseItinerary(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            $date = '';
            $details = $line;

            // Split on first ":" or "-"/en-dash/em-dash so we can inspect the prefix.
            if (preg_match('/^(.{1,40}?)\s*[:\-\x{2013}\x{2014}]\s*(.+)$/u', $line, $m)) {
                $prefix = trim($m[1]);
                $rest = trim($m[2]);

                $parsed = $this->tryParseDate($prefix);
                if ($parsed !== null) {
                    $date = $parsed;
                    $details = $rest;
                } else {
                    // Leave date blank, keep full line as details so labels like
                    // "Day 1" are preserved.
                    $details = $line;
                }
            }

            $items[] = [
                'date' => $date,
                'details' => $details,
            ];
        }

        return $items;
    }

    private function tryParseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Common formats first to avoid strtotime accepting things like "Day 1".
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'd M Y',
            'd F Y',
            'M d, Y',
            'F d, Y',
            'd M',
            'd F',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt !== false && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }
};
