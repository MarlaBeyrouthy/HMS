<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([[
            'first_name' => 'rami',
            'last_name'=>'beyrouthy',
            'email' =>'rrr@gmail.com',
            'password' => bcrypt('12341234'),
            'address' => 'damas',
            'phone' =>'09111111',
            'photo'=> 'uploads/users_photo/avatar.jpeg',
            //'permission_id'=>1,
            'personal_id'=>'122jch9'
        ],[
            'first_name' => 'marla',
            'last_name'=>'beyrouthy',
            'email' =>'mmm@gmail.com',
            'password' => bcrypt('12341234'),
            'address' => 'damas',
            'phone' =>'09111111',
            'photo'=> 'uploads/users_photo/avatar.jpeg',
            //'permission_id'=>1,
            'personal_id'=>'122jch9'

        ]]);
    }
}
