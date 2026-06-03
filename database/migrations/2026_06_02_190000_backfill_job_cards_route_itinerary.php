<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rows = DB::table('job_cards')
            ->select('id', 'route_itinerary', 'additional_details', 'route_summary')
            ->get();

        foreach ($rows as $row) {
            $existing = $this->decodeJson($row->route_itinerary);
            if (!empty($existing)) {
                // Already has structured data — just normalize keys/shape.
                $normalized = $this->normalize($existing);
                if ($normalized !== $existing) {
                    DB::table('job_cards')
                        ->where('id', $row->id)
                        ->update(['route_itinerary' => json_encode($normalized)]);
                }
                continue;
            }

            $sourceText = (string) ($row->additional_details ?? '');
            if (trim($sourceText) === '') {
                $sourceText = (string) ($row->route_summary ?? '');
            }

            $items = $this->parseText($sourceText);
            if (empty($items)) {
                continue;
            }

            DB::table('job_cards')
                ->where('id', $row->id)
                ->update(['route_itinerary' => json_encode($items)]);
        }
    }

    public function down(): void
    {
        // No-op: reversing would lose structure. Original free-text columns are untouched.
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Normalize an array of itinerary items into the canonical
     * { date, dayDescription, allowancePerDay } shape used by the controller.
     */
    private function normalize(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $date = trim((string) ($item['date'] ?? $item['dayDate'] ?? $item['dayTitle'] ?? ''));
            $description = trim((string) ($item['dayDescription'] ?? $item['dateDescription'] ?? $item['description'] ?? $item['details'] ?? ''));
            $allowanceRaw = $item['allowancePerDay'] ?? $item['allowance_per_day'] ?? null;
            $allowance = null;
            if ($allowanceRaw !== null && $allowanceRaw !== '' && is_numeric($allowanceRaw)) {
                $allowance = (float) $allowanceRaw;
            }

            if ($date === '' && $description === '' && $allowance === null) {
                continue;
            }

            $out[] = [
                'date' => $date,
                'dayDescription' => $description,
                'allowancePerDay' => $allowance,
            ];
        }
        return $out;
    }

    /**
     * Parse a multi-line free-text itinerary into structured items.
     * Lines like "31/05 - Transfer JRO ..." or "02 Jun - Arrive JRO ..."
     * are split into { date, dayDescription }. Lines without a recognisable
     * date prefix are kept as a description-only row.
     */
    private function parseText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            $date = '';
            $description = $line;

            if (preg_match('/^(.{1,40}?)\s*[:\-\x{2013}\x{2014}]\s*(.+)$/u', $line, $m)) {
                $prefix = trim($m[1]);
                $rest = trim($m[2]);
                $parsed = $this->tryParseDate($prefix);
                if ($parsed !== null) {
                    $date = $parsed;
                    $description = $rest;
                }
            }

            $items[] = [
                'date' => $date,
                'dayDescription' => $description,
                'allowancePerDay' => null,
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

        // Day/month-only formats: assume current year context isn't critical;
        // we return the original token in dd/mm/yyyy form when a year is given,
        // or dd/mm (left as-is) when not. Frontend stores dd/mm/yyyy.
        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'd M Y',
            'd F Y',
            'M d, Y',
            'F d, Y',
        ];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt !== false && $dt->format($format) === $value) {
                return $dt->format('d/m/Y');
            }
        }

        // Day/month without year — keep as-is so a human can complete it later.
        $shortFormats = ['d/m', 'd-m', 'd M', 'd F'];
        foreach ($shortFormats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt !== false && $dt->format($format) === $value) {
                return $value;
            }
        }

        return null;
    }
};
