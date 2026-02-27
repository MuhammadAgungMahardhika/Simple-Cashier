<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('/csv/customers.csv');

        // Buka file CSV
        if (File::exists($csvFile)) {
            // Baca file CSV
            if (($handle = fopen($csvFile, 'r')) !== false) {

                while (($row = fgetcsv($handle, 1000, ",")) !== false) {

                    if (count($row) < 9) {
                        continue;
                    }

                    DB::table('customers')->insert([
                        'code' => Customer::generateCustomerCode(),
                        'name' => trim($row[2]),
                        'birth_place' => trim($row[3]),
                        'birth_date' => Carbon::createFromFormat('d-M-y', trim($row[4]))->format('Y-m-d'),
                        'address' => trim($row[5]),
                        'phone' => trim($row[6]),
                        'member_started_at' => Carbon::createFromFormat('d-M-y', trim($row[7]))->format('Y-m-d'),
                        'member_expired_at' => Carbon::createFromFormat('d-M-y', trim($row[8]))->format('Y-m-d'),
                        'created_by' => 'Sistem',
                        'updated_by' => 'Sistem',
                    ]);
                }

                fclose($handle);
            }
        }
    }
}
