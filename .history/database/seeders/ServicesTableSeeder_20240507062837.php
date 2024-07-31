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
            ['service_name' => '1F', 'status' => 'available', 'room_number' => '1', 'room_class_id' => 1],
            ['service_name' => 'GF', 'status' => 'booked', 'room_number' => '2', 'room_class_id' => 2],
            ['service_name' => '1F', 'status' => 'maintenance', 'room_number' => '3', 'room_class_id' => 3],
            ['service_name' => '2F', 'status' => 'available', 'room_number' => '4', 'room_class_id' => 2],
        ];
    }
}
