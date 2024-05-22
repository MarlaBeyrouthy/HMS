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
        Service::create([
            'service_name' => 'Room Cleaning',
            'price' => 50.00,
        ]);

        Service::create([
            'service_name' => 'Laundry',
            'price' => 20.00,
        ]);

        $services = [
            ['service_name' => 'Room Cleaning', 'price' => 'available'],
            ['service_name' => 'Laundry', 'price' => 'booked'],
            ['service_name' => 'Laundry VIP', 'price' => 'maintenance'],
            ['service_name' => 'Resturant', 'price' => 'available'],
        ];
    }
}
