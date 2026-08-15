<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SenbetClass;

class SenbetClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            ['code' => 'children', 'name' => 'Children (Hitsanat)'],
            ['code' => 'beginners', 'name' => 'Beginners (Mewael)'],
            ['code' => 'intermediate', 'name' => 'Intermediate (Mekakelegna)'],
            ['code' => 'youth', 'name' => 'Youth (Wetatoch)'],
            ['code' => 'adults', 'name' => 'Adults (Gobezoch)'],
            ['code' => 'dikonat', 'name' => 'Ye Dikonat Timhirt'],
            ['code' => 'kahanat', 'name' => 'Ye Kahanat Timhirt'],
            ['code' => 'choir', 'name' => 'Choir (Mezemeran)'],
        ];

        foreach ($classes as $classData) {
            SenbetClass::updateOrCreate(
                ['code' => $classData['code']],
                [
                    'name' => $classData['name'],
                    'number_of_sections' => 2,
                    'intake_capacity_per_section' => 40,
                    'is_active' => true,
                ]
            );
        }
    }
}
