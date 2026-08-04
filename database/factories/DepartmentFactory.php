<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory {
    protected $model = Department::class;

    public function definition(): array {
        $nameEn = 'Department of ' . $this->faker->unique()->word;
        $amPrefix = ['የሶፍትዌር', 'የኤሌክትሪካል', 'የሲቪል', 'የሜካኒካል'];
        $nameAm = $amPrefix[array_rand($amPrefix)] . ' ምህንድስና';

        return [
            'school_id' => School::factory(),
            'name' => [
                'en' => $nameEn,
                'am' => $nameAm
            ],
            'slug' => Str::slug($nameEn),
            'short_code' => strtoupper(Str::random(2)),
            'total_year' => $this->faker->numberBetween(1, 7),
            'is_active' => true,
            'head_of_deptartment_id' => null,
        ];
    }
}
