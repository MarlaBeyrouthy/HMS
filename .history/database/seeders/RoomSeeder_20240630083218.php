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
            ['floor' => '1F', 'status' => 'available', 'room_number' => '1', 'room_class_id' => 1, 'view' => 'sea','photo'=>'C:\wamp64\www\HMS\public\uploads\room_photo\city.jpg'],
            ['floor' => 'GF', 'status' => 'available', 'room_number' => '2', 'room_class_id' => 2, 'view' => 'garden','photo'=>'C:\wamp64\www\HMS\public\uploads\room_photo\garden.jpg'],
            ['floor' => '1F', 'status' => 'maintenance', 'room_number' => '3', 'room_class_id' => 3, 'view' => 'city','photo'=>'C:\wamp64\www\HMS\public\uploads\room_photo\city.jpg'],
            ['floor' => '2F', 'status' => 'available', 'room_number' => '4', 'room_class_id' => 2, 'view' => 'mountain','photo'=>'C:\wamp64\www\HMS\public\uploads\room_photo\city.jpg'],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
