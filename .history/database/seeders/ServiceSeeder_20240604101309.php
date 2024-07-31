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
                'name' => 'Daily Room Cleaning',
                'description' => 'Daily cleaning service for your room.',
                'price' => 15.00,
                'duration' => '1 hour'
            ],
            [
                'name' => 'Wi-Fi Access',
                'description' => '24/7 high-speed internet access.',
                'price' => 5.00,
                'duration' => '24 hours'
            ],
            [
                'name' => 'Laundry Service',
                'description' => 'Full laundry service for your clothes.',
                'price' => 25.00,
                'duration' => '24 hours'
            ],
            [
                'name' => 'Swimming Pool Access',
                'description' => 'Access to the swimming pool.',
                'price' => 10.00,
                'duration' => '1 day'
            ],
            [
                'name' => 'Breakfast',
                'description' => 'Buffet breakfast including various options.',
                'price' => 12.00,
                'duration' => '2 hours'
            ],
            [
                'name' => 'Airport Shuttle',
                'description' => 'Shuttle service to and from the airport.',
                'price' => 20.00,
                'duration' => '1 hour'
            ],
            [
                'name' => 'Parking',
                'description' => 'Secure parking spot for your vehicle.',
                'price' => 8.00,
                'duration' => '24 hours'
            ],
            [
                'name' => 'Fitness Center Access',
                'description' => 'Access to the fitness center.',
                'price' => 10.00,
                'duration' => '1 day'
            ],
            [
                'name' => 'Spa Services',
                'description' => 'Relaxing spa services including massage and treatments.',
                'price' => 50.00,
                'duration' => '2 hours'
            ],
            [
                'name' => 'Conference Room Rental',
                'description' => 'Rental of conference room with all amenities.',
                'price' => 100.00,
                'duration' => '8 hours'
            ],
        ];
    
        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
