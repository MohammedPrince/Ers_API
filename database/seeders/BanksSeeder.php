<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BanksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('bank_users')->insert([
            'bank_name' => 'BOK',
            'bank_password' => Hash::make('BOK@CESD*#09'),
            'bank_ip' => '127.0.0.1',
        ]);
        DB::table('bank_users')->insert([
            'bank_name' => 'FIB',
            'bank_password' => Hash::make('FIB@CESD*#09'),
            'bank_ip' => '127.0.0.1',
        ]);
    }
}
