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

        // ─── Park Fees (Per person – EX VAT) ────────────────────────────────
        //  Columns:  type          category  rate
        //  Types:    non_resident | resident | citizen
        //  Category: adult        | child
        $parkRates = [
            // Tarangire National Park
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 52.50],
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 26.27],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Manyara National Park
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 52.50],
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 26.27],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Ngorongoro Conservation Area
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 63.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Serengeti National Park
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 73.50],
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 37.29],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Nyerere National Park (Selous)
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 73.50],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'adult', 'rate' => 37.29],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Ruaha National Park
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 16.10],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Mikumi National Park
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 16.10],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],

            // Oldovai – per person net
            ['park' => 'Oldovai', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Oldovai', 'type' => 'non_resident', 'category' => 'child', 'rate' => 31.50],
            ['park' => 'Oldovai', 'type' => 'resident',     'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Oldovai', 'type' => 'resident',     'category' => 'child', 'rate' => 31.50],
            ['park' => 'Oldovai', 'type' => 'citizen',      'category' => 'adult', 'rate' =>  5.08],
            ['park' => 'Oldovai', 'type' => 'citizen',      'category' => 'child', 'rate' =>  1.02],
        ];

        foreach ($parkRates as $row) {
            ParkRate::create([
                'park_id'  => $parkId($row['park']),
                'type'     => $row['type'],
                'category' => $row['category'],
                'rate'     => $row['rate'],
            ]);
        }

        // ─── Concession Fees (Per person – EX VAT) ──────────────────────────
        $concessionRates = [
            // Tarangire National Park
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 42.00],
            ['park' => 'Tarangire National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 42.00],
            ['park' => 'Tarangire National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 21.00],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Tarangire National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],

            // Manyara National Park
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 52.50],
            ['park' => 'Manyara National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 52.50],
            ['park' => 'Manyara National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 21.00],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Manyara National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],

            // Ngorongoro Conservation Area
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 52.50],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'adult', 'rate' => 52.50],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'resident',     'category' => 'child', 'rate' => 21.00],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Ngorongoro Conservation Area', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],

            // Serengeti National Park
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 63.00],
            ['park' => 'Serengeti National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 63.00],
            ['park' => 'Serengeti National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 21.00],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Serengeti National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],

            // Nyerere National Park (Selous)
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 63.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'non_resident', 'category' => 'child', 'rate' => 21.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'adult', 'rate' => 63.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'resident',     'category' => 'child', 'rate' => 21.00],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Nyerere National Park (Selous)', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],

            // Ruaha National Park
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Ruaha National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 10.50],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 31.50],
            ['park' => 'Ruaha National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Ruaha National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],

            // Mikumi National Park
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'adult', 'rate' => 26.26],
            ['park' => 'Mikumi National Park', 'type' => 'non_resident', 'category' => 'child', 'rate' => 10.50],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'adult', 'rate' => 26.26],
            ['park' => 'Mikumi National Park', 'type' => 'resident',     'category' => 'child', 'rate' => 10.50],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'adult', 'rate' => 12.71],
            ['park' => 'Mikumi National Park', 'type' => 'citizen',      'category' => 'child', 'rate' =>  4.24],
        ];

        foreach ($concessionRates as $row) {
            ConcessionRate::create([
                'park_id'  => $parkId($row['park']),
                'type'     => $row['type'],
                'category' => $row['category'],
                'rate'     => $row['rate'],
            ]);
        }

        // ─── Activities and Transfer Rates (EX VAT) ─────────────────────────
        $transportRates = [
            ['particular' => 'Visit to Mto Wa Mbu (per person)',                                   'rate' =>  35.00],
            ['particular' => 'Visit Masai Village (per vehicle, net)',                             'rate' => 100.00],
            ['particular' => 'AMREF Flying Doctor cover (valid 14 days, per person)',             'rate' =>  12.71],
            ['particular' => 'Drinking water during game drives (per person per day)',            'rate' =>   3.39],
            ['particular' => 'Kilimanjaro / Arusha / Kilimanjaro (per vehicle each way)',         'rate' =>  50.85],
            ['particular' => 'Vehicle at disposal in Moshi or Arusha (per vehicle each way, net)', 'rate' => 127.12],
            ['particular' => 'Packed lunch from Arusha (per person)',                              'rate' =>  21.19],
            ['particular' => 'Hot lunch in Arusha (per person)',                                   'rate' =>  29.66],
        ];

        foreach ($transportRates as $row) {
            TransportRate::create($row);
        }
    }
}
