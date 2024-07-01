<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['floor' => '1F', 'status' => 'available', 'room_number' => '1', 'room_class_id' => 1, 'view' => 'sea',],
            ['floor' => 'GF', 'status' => 'available', 'room_number' => '2', 'room_class_id' => 2, 'view' => 'garden'],
            ['floor' => '1F', 'status' => 'maintenance', 'room_number' => '3', 'room_class_id' => 3, 'view' => 'city'],
            ['floor' => '2F', 'status' => 'available', 'room_number' => '4', 'room_class_id' => 2, 'view' => 'mountain'],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
