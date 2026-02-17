<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('/csv/services.csv');

        // Buka file CSV
        if (File::exists($csvFile)) {
            // Baca file CSV
            $csvData = array_map('str_getcsv', file($csvFile));
            foreach ($csvData as $row) {
                DB::table('services')->insert([
                    'service_group_id' => $row[0],
                    'code' => $row[1],
                    'name' => $row[2],
                    'price' => $row[3],
                    'member_price' => $row[4],
                    'package_price' => $row[5],
                    'member_package_price' => $row[6],
                    'fee' => $row[7],
                    'member_fee' => $row[8],
                ]);
            }
        }
    }
}
