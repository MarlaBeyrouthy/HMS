<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ServicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $services = [
            ['service_name' => 'Room Cleaning', 'price' => '100'],
            ['service_name' => 'Laundry', 'price' => '255'],
            ['service_name' => 'Laundry VIP', 'price' => '155'],
            ['service_name' => 'Resturant', 'price' => '120'],
        ];
    }
}
