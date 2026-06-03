<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rows = DB::table('lease_allocations')
            ->whereNotNull('itinerary')
            ->get(['id', 'itinerary', 'itinerary_items', 'start_date']);

        foreach ($rows as $row) {
            $items = $this->parseItinerary(
                (string) $row->itinerary,
                $row->start_date ? (string) $row->start_date : null,
            );
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
        // No-op.
    }

    private function parseItinerary(string $text, ?string $anchor): array
    {
        $anchorYear = null;
        if ($anchor) {
            try {
                $anchorYear = (new \DateTime($anchor))->format('Y');
            } catch (\Throwable $e) {
                $anchorYear = null;
            }
        }
        $anchorYear = $anchorYear ?: date('Y');

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            $date = '';
            $details = $line;

            if (preg_match('/^(.{1,40}?)\s*[:\-\x{2013}\x{2014}]\s*(.+)$/u', $line, $m)) {
                $prefix = trim($m[1]);
                $rest = trim($m[2]);

                $parsed = $this->tryParseDate($prefix, $anchorYear);
                if ($parsed !== null) {
                    $date = $parsed;
                    $details = $rest;
                }
            }

            $items[] = [
                'date' => $date,
                'details' => $details,
            ];
        }

        return $items;
    }

    private function tryParseDate(string $value, string $anchorYear): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formatsWithYear = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'd M Y',
            'd F Y',
            'M d, Y',
            'F d, Y',
        ];
        foreach ($formatsWithYear as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt !== false && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        // Partial dates (no year) — infer from anchor year.
        $partialFormats = [
            'd/m',
            'd-m',
            'd M',
            'd F',
        ];
        foreach ($partialFormats as $format) {
            $dt = \DateTime::createFromFormat($format . '-Y', $value . '-' . $anchorYear);
            if ($dt !== false) {
                $check = \DateTime::createFromFormat($format, $value);
                if ($check !== false && $check->format($format) === $value) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        return null;
    }
};
