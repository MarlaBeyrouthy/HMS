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
            ['service_name' => '1F', 'price' => 'available'],
            ['service_name' => 'GF', 'price' => 'booked'],
            ['service_name' => '1F', 'price' => 'maintenance'],
            ['service_name' => '2F', 'price' => 'available', 'room_number' => '4', 'room_class_id' => 2],
        ];
    }
}
