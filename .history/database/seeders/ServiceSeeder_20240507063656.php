<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'service_name' => 'Service 1',
                'price' => 10.99,
            ],
            [
                'service_name' => 'Service 2',
                'price' => 19.99,
            ],
            // يمكنك إضافة المزيد من الخدمات هنا
        ];
    
        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
