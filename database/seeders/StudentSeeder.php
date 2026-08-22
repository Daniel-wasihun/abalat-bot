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
                ['email' => 'sara.bekele@student.com',    'first' => 'Sara',      'father' => 'Bekele',     'name_am' => 'ሳራ በቀለ',       'gender' => 'female', 'phone' => '911000101'],
                ['email' => 'mikael.girma@student.com',   'first' => 'Mikael',    'father' => 'Girma',      'name_am' => 'ሚካኤል ግርማ',     'gender' => 'male',   'phone' => '922000102'],
                ['email' => 'marta.alemu@student.com',    'first' => 'Marta',     'father' => 'Alemu',      'name_am' => 'ማርታ አለሙ',      'gender' => 'female', 'phone' => '933000103'],
            ],
            '2' => [
                ['email' => 'yonas.tadesse@student.com',  'first' => 'Yonas',     'father' => 'Tadesse',    'name_am' => 'ዮናስ ታደሰ',      'gender' => 'male',   'phone' => '944000201'],
                ['email' => 'hiwot.tesfaye@student.com',  'first' => 'Hiwot',     'father' => 'Tesfaye',    'name_am' => 'ህይወት ተስፋዬ',    'gender' => 'female', 'phone' => '955000202'],
                ['email' => 'dawit.haile@student.com',    'first' => 'Dawit',     'father' => 'Haile',      'name_am' => 'ዳዊት ሃይሌ',      'gender' => 'male',   'phone' => '966000203'],
            ],
            '3' => [
                ['email' => 'kidus.mekonnen@student.com', 'first' => 'Kidus',     'father' => 'Mekonnen',   'name_am' => 'ቅዱስ መኮንን',      'gender' => 'male',   'phone' => '911000301'],
                ['email' => 'tigist.worku@student.com',   'first' => 'Tigist',    'father' => 'Worku',      'name_am' => 'ትግስት ወርቁ',     'gender' => 'female', 'phone' => '922000302'],
                ['email' => 'brhane.abebe@student.com',   'first' => 'Brhane',    'father' => 'Abebe',      'name_am' => 'ብርሃነ አበበ',      'gender' => 'male',   'phone' => '933000303'],
                ['email' => 'daniel.alemu@student.com',   'first' => 'Daniel',    'father' => 'Alemu',      'name_am' => 'ዳንኤል አለሙ',      'gender' => 'male',   'phone' => '944000304'],
                ['email' => 'leah.tadesse@student.com',   'first' => 'Leah',      'father' => 'Tadesse',    'name_am' => 'ሊያ ታደሰ',       'gender' => 'female', 'phone' => '955000305'],
            ],
            '4' => [
                ['email' => 'selam.girma@student.com',    'first' => 'Selam',     'father' => 'Girma',      'name_am' => 'ሰላም ግርማ',      'gender' => 'female', 'phone' => '944000401'],
                ['email' => 'mekdes.yohannes@student.com','first' => 'Mekdes',    'father' => 'Yohannes',   'name_am' => 'መቅደስ ዮሃንስ',    'gender' => 'female', 'phone' => '955000402'],
                ['email' => 'natnael.samuel@student.com', 'first' => 'Natnael',   'father' => 'Samuel',     'name_am' => 'ናትናኤል ሳሙኤል',   'gender' => 'male',   'phone' => '966000403'],
            ],
            '5' => [
                ['email' => 'biruk.tesfaye@student.com',  'first' => 'Biruk',     'father' => 'Tesfaye',    'name_am' => 'ብሩክ ተስፋዬ',     'gender' => 'male',   'phone' => '911000501'],
                ['email' => 'roman.bekele@student.com',   'first' => 'Roman',     'father' => 'Bekele',     'name_am' => 'ሮማን በቀለ',      'gender' => 'female', 'phone' => '922000502'],
                ['email' => 'abel.mulatu@student.com',    'first' => 'Abel',      'father' => 'Mulatu',     'name_am' => 'አቤል ሙላቱ',      'gender' => 'male',   'phone' => '933000503'],
            ],
            '6' => [
                ['email' => 'betelhem.alemu@student.com', 'first' => 'Betelhem',  'father' => 'Alemu',      'name_am' => 'ቤተልሄም አለሙ',    'gender' => 'female', 'phone' => '944000601'],
                ['email' => 'yared.mesfin@student.com',   'first' => 'Yared',     'father' => 'Mesfin',     'name_am' => 'ያሬድ መስፍን',     'gender' => 'male',   'phone' => '955000602'],
                ['email' => 'eden.girma@student.com',     'first' => 'Eden',      'father' => 'Girma',      'name_am' => 'ኤደን ግርማ',      'gender' => 'female', 'phone' => '966000603'],
            ],
            '7' => [
                ['email' => 'henok.haile@student.com',    'first' => 'Henok',     'father' => 'Haile',      'name_am' => 'ሄኖክ ሃይሌ',      'gender' => 'male',   'phone' => '911000701'],
                ['email' => 'liya.tadesse@student.com',   'first' => 'Liya',      'father' => 'Tadesse',    'name_am' => 'ሊያ ታደሰ',       'gender' => 'female', 'phone' => '922000702'],
                ['email' => 'kirubel.tesfaye@student.com','first' => 'Kirubel',   'father' => 'Tesfaye',    'name_am' => 'ኪሩቤል ተስፋዬ',   'gender' => 'male',   'phone' => '933000703'],
                ['email' => 'biniam.girma@student.com',   'first' => 'Biniam',    'father' => 'Girma',      'name_am' => 'ቢንያም ግርማ',     'gender' => 'male',   'phone' => '944000704'],
                ['email' => 'makda.getachew@student.com', 'first' => 'Makda',     'father' => 'Getachew',   'name_am' => 'ማክዳ ጌታቸው',    'gender' => 'female', 'phone' => '955000705'],
            ],
            '8' => [
                ['email' => 'mahlet.yohannes@student.com','first' => 'Mahlet',    'father' => 'Yohannes',   'name_am' => 'ማህሌት ዮሃንስ',    'gender' => 'female', 'phone' => '944000801'],
                ['email' => 'dawit.fekadu@student.com',   'first' => 'Dawit',     'father' => 'Fekadu',     'name_am' => 'ዳዊት ፈቃዱ',      'gender' => 'male',   'phone' => '955000802'],
                ['email' => 'selamawit.abebe@student.com','first' => 'Selamawit', 'father' => 'Abebe',      'name_am' => 'ሰላማዊት አበበ',    'gender' => 'female', 'phone' => '966000803'],
                ['email' => 'solomon.tesfaye@student.com','first' => 'Solomon',   'father' => 'Tesfaye',    'name_am' => 'ሰለሞን ተስፋዬ',   'gender' => 'male',   'phone' => '977000804'],
                ['email' => 'frehiwot.haile@student.com', 'first' => 'Frehiwot',  'father' => 'Haile',      'name_am' => 'ፍሬህይወት ሃይሌ',   'gender' => 'female', 'phone' => '988000805'],
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
                        'name'      => $data['first'],
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
                        'registration_id'       => \App\Models\UserInfo::generateNextRegistrationId(),
                        'gender'                => $data['gender'],
                        'phone_number'          => $data['phone'],
                        'father_name'           => $data['father'],
                        'grandfather_name'      => 'Alemu',
                        'christian_name'        => 'Woldemariam ' . $data['first'],
                        'spiritual_father_name' => 'Aba Gebre Selassie',
                        'sub_city'              => 'Bole',
                        'woreda'                => '05',
                        'house_number'          => '123',
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
                $this->command->line("  ✓ {$data['first']} {$data['father']} (Grade {$gradeClass}) — " . count($offeringIds) . ' course(s)');
            }
        }

        $this->command->info("{$totalCreated} students seeded with {$totalEnrolled} enrollments. Default password: password123");
    }
}
