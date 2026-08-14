<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Seed realistic student users assigned to specific grade levels
     * and enroll them in the course offerings for their grade.
     */
    public function run(): void
    {
        $this->command->info('Seeding student users...');

        $studentRole = Role::where('slug', 'student')->first();

        if (! $studentRole) {
            $this->command->error('Student role not found. Run RoleSeeder first.');
            return;
        }

        // Grade → list of student records
        $studentsByGrade = [
            '1' => [
                ['email' => 'sara.bekele@student.com',    'name_en' => 'Sara Bekele',       'name_am' => 'ሳራ በቀለ',       'gender' => 'female', 'phone' => '+251911000101'],
                ['email' => 'mikael.girma@student.com',   'name_en' => 'Mikael Girma',      'name_am' => 'ሚካኤል ግርማ',     'gender' => 'male',   'phone' => '+251922000102'],
                ['email' => 'marta.alemu@student.com',    'name_en' => 'Marta Alemu',       'name_am' => 'ማርታ አለሙ',      'gender' => 'female', 'phone' => '+251933000103'],
            ],
            '2' => [
                ['email' => 'yonas.tadesse@student.com',  'name_en' => 'Yonas Tadesse',     'name_am' => 'ዮናስ ታደሰ',      'gender' => 'male',   'phone' => '+251944000201'],
                ['email' => 'hiwot.tesfaye@student.com',  'name_en' => 'Hiwot Tesfaye',     'name_am' => 'ህይወት ተስፋዬ',    'gender' => 'female', 'phone' => '+251955000202'],
                ['email' => 'dawit.haile@student.com',    'name_en' => 'Dawit Haile',       'name_am' => 'ዳዊት ሃይሌ',      'gender' => 'male',   'phone' => '+251966000203'],
            ],
            '3' => [
                ['email' => 'kidus.mekonnen@student.com', 'name_en' => 'Kidus Mekonnen',    'name_am' => 'ቅዱስ መኮንን',      'gender' => 'male',   'phone' => '+251911000301'],
                ['email' => 'tigist.worku@student.com',   'name_en' => 'Tigist Worku',      'name_am' => 'ትግስት ወርቁ',     'gender' => 'female', 'phone' => '+251922000302'],
                ['email' => 'brhane.abebe@student.com',   'name_en' => 'Brhane Abebe',      'name_am' => 'ብርሃነ አበበ',      'gender' => 'male',   'phone' => '+251933000303'],
                ['email' => 'daniel.alemu@student.com',   'name_en' => 'Daniel Alemu',      'name_am' => 'ዳንኤል አለሙ',      'gender' => 'male',   'phone' => '+251944000304'],
                ['email' => 'leah.tadesse@student.com',   'name_en' => 'Leah Tadesse',      'name_am' => 'ሊያ ታደሰ',       'gender' => 'female', 'phone' => '+251955000305'],
            ],
            '4' => [
                ['email' => 'selam.girma@student.com',    'name_en' => 'Selam Girma',       'name_am' => 'ሰላም ግርማ',      'gender' => 'female', 'phone' => '+251944000401'],
                ['email' => 'mekdes.yohannes@student.com','name_en' => 'Mekdes Yohannes',   'name_am' => 'መቅደስ ዮሃንስ',    'gender' => 'female', 'phone' => '+251955000402'],
                ['email' => 'natnael.samuel@student.com', 'name_en' => 'Natnael Samuel',    'name_am' => 'ናትናኤል ሳሙኤል',   'gender' => 'male',   'phone' => '+251966000403'],
            ],
            '5' => [
                ['email' => 'biruk.tesfaye@student.com',  'name_en' => 'Biruk Tesfaye',     'name_am' => 'ብሩክ ተስፋዬ',     'gender' => 'male',   'phone' => '+251911000501'],
                ['email' => 'roman.bekele@student.com',   'name_en' => 'Roman Bekele',      'name_am' => 'ሮማን በቀለ',      'gender' => 'female', 'phone' => '+251922000502'],
                ['email' => 'abel.mulatu@student.com',    'name_en' => 'Abel Mulatu',       'name_am' => 'አቤል ሙላቱ',      'gender' => 'male',   'phone' => '+251933000503'],
            ],
            '6' => [
                ['email' => 'betelhem.alemu@student.com', 'name_en' => 'Betelhem Alemu',    'name_am' => 'ቤተልሄም አለሙ',    'gender' => 'female', 'phone' => '+251944000601'],
                ['email' => 'yared.mesfin@student.com',   'name_en' => 'Yared Mesfin',      'name_am' => 'ያሬድ መስፍን',     'gender' => 'male',   'phone' => '+251955000602'],
                ['email' => 'eden.girma@student.com',     'name_en' => 'Eden Girma',        'name_am' => 'ኤደን ግርማ',      'gender' => 'female', 'phone' => '+251966000603'],
            ],
            '7' => [
                ['email' => 'henok.haile@student.com',    'name_en' => 'Henok Haile',       'name_am' => 'ሄኖክ ሃይሌ',      'gender' => 'male',   'phone' => '+251911000701'],
                ['email' => 'liya.tadesse@student.com',   'name_en' => 'Liya Tadesse',      'name_am' => 'ሊያ ታደሰ',       'gender' => 'female', 'phone' => '+251922000702'],
                ['email' => 'kirubel.tesfaye@student.com','name_en' => 'Kirubel Tesfaye',   'name_am' => 'ኪሩቤል ተስፋዬ',   'gender' => 'male',   'phone' => '+251933000703'],
                ['email' => 'biniam.girma@student.com',   'name_en' => 'Biniam Girma',      'name_am' => 'ቢንያም ግርማ',     'gender' => 'male',   'phone' => '+251944000704'],
                ['email' => 'makda.getachew@student.com', 'name_en' => 'Makda Getachew',    'name_am' => 'ማክዳ ጌታቸው',    'gender' => 'female', 'phone' => '+251955000705'],
            ],
            '8' => [
                ['email' => 'mahlet.yohannes@student.com','name_en' => 'Mahlet Yohannes',   'name_am' => 'ማህሌት ዮሃንስ',    'gender' => 'female', 'phone' => '+251944000801'],
                ['email' => 'dawit.fekadu@student.com',   'name_en' => 'Dawit Fekadu',      'name_am' => 'ዳዊት ፈቃዱ',      'gender' => 'male',   'phone' => '+251955000802'],
                ['email' => 'selamawit.abebe@student.com','name_en' => 'Selamawit Abebe',   'name_am' => 'ሰላማዊት አበበ',    'gender' => 'female', 'phone' => '+251966000803'],
                ['email' => 'solomon.tesfaye@student.com','name_en' => 'Solomon Tesfaye',   'name_am' => 'ሰለሞን ተስፋዬ',   'gender' => 'male',   'phone' => '+251977000804'],
                ['email' => 'frehiwot.haile@student.com', 'name_en' => 'Frehiwot Haile',    'name_am' => 'ፍሬህይወት ሃይሌ',   'gender' => 'female', 'phone' => '+251988000805'],
            ],
        ];

        $totalCreated = 0;
        $totalEnrolled = 0;

        foreach ($studentsByGrade as $gradeClass => $students) {
            // Resolve all course offerings for this grade
            $offeringIds = DB::table('course_offerings')
                ->where('senbet_class', $gradeClass)
                ->where('is_active', true)
                ->pluck('id', 'course_id')
                ->toArray();  // course_id => offering_id

            foreach ($students as $data) {
                // Create/update the user
                $user = User::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'      => ['en' => $data['name_en'], 'am' => $data['name_am']],
                        'password'  => Hash::make('password123'),
                        'is_active' => true,
                    ]
                );

                // Assign student role
                $user->roles()->syncWithoutDetaching([
                    $studentRole->id => [
                        'assigned_by' => null,
                        'start_date'  => Carbon::now(),
                        'end_date'    => null,
                        'is_active'   => true,
                    ]
                ]);

                // Create user info profile
                DB::table('user_info')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'registration_id'       => 'STU-' . strtoupper(substr(md5($data['email']), 0, 8)),
                        'gender'                => $data['gender'],
                        'phone_number'          => $data['phone'],
                        'father_name'           => 'Father ' . $data['name_en'],
                        'grandfather_name'      => 'Grandfather ' . $data['name_en'],
                        'christian_name'        => 'Christian ' . $data['name_en'],
                        'spiritual_father_name' => 'Aba Daniel',
                        'sub_city'              => collect(['Bole','Kirkos','Yeka','Arada','Gulele'])->random(),
                        'woreda'                => (string) rand(1, 14),
                        'house_number'          => (string) rand(100, 999),
                        'address'               => 'Addis Ababa, Ethiopia',
                        'status'                => 'active',
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]
                );

                // Create senbet membership (grade assignment)
                DB::table('senbet_memberships')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'senbet_class'            => $gradeClass,
                        'date_of_birth'           => Carbon::now()->subYears(rand(6 + intval($gradeClass), 10 + intval($gradeClass)))->format('Y-m-d'),
                        'education_level'         => 'Grade ' . $gradeClass,
                        'registration_date'       => Carbon::now()->subMonths(rand(1, 12))->format('Y-m-d'),
                        'previous_participation'  => rand(0, 1),
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]
                );

                // Enroll student in all course offerings for their grade
                foreach ($offeringIds as $courseId => $offeringId) {
                    DB::table('course_enrollments')->updateOrInsert(
                        [
                            'student_id'          => $user->id,
                            'course_offering_id'  => $offeringId,
                        ],
                        [
                            'course_id'  => $courseId,
                            'status'     => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $totalEnrolled++;
                }

                $totalCreated++;
                $this->command->line("  ✓ {$data['name_en']} (Grade {$gradeClass}) — " . count($offeringIds) . ' course(s)');
            }
        }

        $this->command->info("{$totalCreated} students seeded with {$totalEnrolled} enrollments. Default password: password123");
    }
}
