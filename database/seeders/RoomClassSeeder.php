<?php

namespace Database\Seeders;

use App\Models\RoomClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roomClasses = [
            ['class_name' => 'Single Room', 'base_price' => 100.00, 'bed_type' => 'Single', 'number_of_beds' => 1],
            ['class_name' => 'Double Room', 'base_price' => 150.00, 'bed_type' => 'Double', 'number_of_beds' => 2],
            ['class_name' => 'Triple Room', 'base_price' => 200.00, 'bed_type' => 'Triple', 'number_of_beds' => 3],
            ['class_name' => 'Quad Room', 'base_price' => 250.00, 'bed_type' => 'Quad', 'number_of_beds' => 4],
        ];

        foreach ($roomClasses as $roomClass) {
            RoomClass::create($roomClass);
        }
    }
}
