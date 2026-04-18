<?php

namespace Database\Seeders;

use App\Models\ConcessionRate;
use App\Models\Park;
use App\Models\ParkRate;
use App\Models\TransportRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatesSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Clean existing rates ───────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TransportRate::truncate();
        ConcessionRate::truncate();
        ParkRate::truncate();
        Park::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ─── Parks ──────────────────────────────────────────────────────────
        $parks = [
            ['name' => 'Tarangire National Park',       'region' => 'Manyara'],
            ['name' => 'Manyara National Park',          'region' => 'Manyara'],
            ['name' => 'Ngorongoro Conservation Area',   'region' => 'Arusha'],
            ['name' => 'Serengeti National Park',        'region' => 'Mara'],
            ['name' => 'Nyerere National Park (Selous)', 'region' => 'Lindi/Ruvuma'],
            ['name' => 'Ruaha National Park',            'region' => 'Iringa'],
            ['name' => 'Mikumi National Park',           'region' => 'Morogoro'],
            ['name' => 'Oldovai',                        'region' => 'Arusha'],
        ];

        foreach ($parks as $park) {
            Park::create(['name' => $park['name'], 'region' => $park['region'], 'status' => 'Active']);
        }

        // ─── Helper to get a park by name ───────────────────────────────────
        $parkId = static function (string $name): int {
            $id = Park::where('name', $name)->value('id');

            if ($id === null) {
                throw new \RuntimeException("Unknown park in rates seeder: {$name}");
            }

            return (int) $id;
        };

        // ─── Park Fees (Per person – Incl VAT) ──────────────────────────────
        //  Columns:  type          category  rate
        //  Types:    non_resident | resident | citizen
        //  Category: adult        | child
        $parkRates = [
            // Tarangire National Park
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 61.95],
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 31.00],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Manyara National Park
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 61.95],
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 31.00],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Ngorongoro Conservation Area
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 74.34],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Serengeti National Park
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 86.73],
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 44.00],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Nyerere National Park (Selous)
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 86.73],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'adult', 'rate' => 44.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Ruaha National Park
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 19.00],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Mikumi National Park
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 19.00],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],

            // Oldovai – per person net
            ['park' => 'Oldovai', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Oldovai', 'type' => 'non_resident', 'category' => 'child', 'rate' => 12.39],
            ['park' => 'Oldovai', 'type' => 'resident',     'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Oldovai', 'type' => 'resident',     'category' => 'child', 'rate' =>  0.00],
            ['park' => 'Oldovai', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  6.00],
            ['park' => 'Oldovai', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.20],
        ];

        foreach ($parkRates as $row) {
            ParkRate::create([
                'park_id'  => $parkId($row['park']),
                'type'     => $row['type'],
                'category' => $row['category'],
                'rate'     => $row['rate'],
            ]);
        }

        // ─── Concession Fees (Per person – Incl VAT) ────────────────────────
        $concessionRates = [
            // Tarangire National Park
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 49.56],
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 49.56],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 24.78],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],

            // Manyara National Park
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 61.95],
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 61.95],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 24.78],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],

            // Ngorongoro Conservation Area
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 61.95],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'adult', 'rate' => 61.95],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'child', 'rate' => 24.78],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],

            // Serengeti National Park
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 74.34],
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 74.34],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 24.78],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],

            // Nyerere National Park (Selous)
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 74.34],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'child', 'rate' => 24.78],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'adult', 'rate' => 74.34],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'child', 'rate' => 24.78],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],

            // Ruaha National Park
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 12.39],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 37.17],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 12.39],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],

            // Mikumi National Park
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 30.98],
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 12.39],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 30.98],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 12.39],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 15.00],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  0.00],
        ];

        foreach ($concessionRates as $row) {
            ConcessionRate::create([
                'park_id'  => $parkId($row['park']),
                'type'     => $row['type'],
                'category' => $row['category'],
                'rate'     => $row['rate'],
            ]);
        }

        // ─── Transport Rates (net, excl VAT – stored as rate only) ──────────
        $transportRates = [
            ['particular' => 'Per vehicle per day net – 3 days or less',                          'rate' => 350.00],
            ['particular' => 'Per vehicle per day net – 4 days or more',                          'rate' => 235.00],
            ['particular' => 'Per vehicle per day net – A/C Vehicle Supplement',                  'rate' =>  50.00],
            ['particular' => 'Crater Entry fee – per vehicle net',                                'rate' => 262.50],
            ['particular' => 'Additional for driving to North Serengeti – per vehicle',           'rate' => 150.00],
            ['particular' => 'Additional for driving to West Serengeti',                          'rate' => 100.00],
            ['particular' => 'Empty leg from Serengeti to Arusha or Arusha to Serengeti',         'rate' => 120.00],
            ['particular' => 'Pick up Isabania Boarder from Serengeti',                           'rate' => 300.00],
            ['particular' => 'Pick up Namanga border – from Arusha',                              'rate' => 120.00],
            ['particular' => 'Bank Charges',                                                      'rate' =>  50.00],
        ];

        foreach ($transportRates as $row) {
            TransportRate::create($row);
        }
    }
}
