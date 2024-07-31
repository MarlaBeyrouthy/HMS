<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
    }
}
