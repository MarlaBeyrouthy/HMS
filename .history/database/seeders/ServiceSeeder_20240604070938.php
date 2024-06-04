<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'service_name' => 'Daily Room Cleaning',
                'description'=>'asdasd',
                'price' => 15.00,
                'duration'=>';casdcas'
            ],
            [
                'service_name' => 'Wi-Fi Access',
                'price' => 5.00,
            ],
            [
                'service_name' => 'Laundry Service',
                'price' => 25.00,
            ],
            [
                'service_name' => 'Swimming Pool Access',
                'price' => 10.00,
            ],
            [
                'service_name' => 'Breakfast',
                'price' => 12.00,
            ],
            [
                'service_name' => 'Airport Shuttle',
                'price' => 20.00,
            ],
            [
                'service_name' => 'Parking',
                'price' => 8.00,
            ],
            [
                'service_name' => 'Fitness Center Access',
                'price' => 10.00,
            ],
            [
                'service_name' => 'Spa Services',
                'price' => 50.00,
            ],
            [
                'service_name' => 'Conference Room Rental',
                'price' => 100.00,
            ],*/
        ];
    
        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
