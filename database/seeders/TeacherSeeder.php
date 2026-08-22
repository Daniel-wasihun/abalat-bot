<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInfo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Seed realistic teacher users with the teacher role and spiritual names.
     */
    public function run(): void
    {
        $this->command->info('Seeding teacher users (spiritual names)...');

        $teacherRole = Role::where('slug', 'teacher')->first();

        if (! $teacherRole) {
            $this->command->error('Teacher role not found. Run RoleSeeder first.');
            return;
        }

        $teachers = [
            [
                'email'    => 'habte.mariam@school.com',
                'first'    => 'Habte',
                'father'   => 'Mariam',
                'name_am'  => 'ሀብተ ማርያም',
                'phone'    => '911234501',
                'gender'   => 'male',
                'sub_city' => 'Bole',
                'woreda'   => '05',
            ],
            [
                'email'    => 'winta.selassie@school.com',
                'first'    => 'Winta',
                'father'   => 'Selassie',
                'name_am'  => 'ዊንታ ሥላሴ',
                'phone'    => '922345602',
                'gender'   => 'female',
                'sub_city' => 'Kirkos',
                'woreda'   => '08',
            ],
            [
                'email'    => 'kidane.wold@school.com',
                'first'    => 'Kidane',
                'father'   => 'Wold',
                'name_am'  => 'ኪዳነ ወልድ',
                'phone'    => '933456703',
                'gender'   => 'male',
                'sub_city' => 'Yeka',
                'woreda'   => '11',
            ],
            [
                'email'    => 'amete.mikael@school.com',
                'first'    => 'Amete',
                'father'   => 'Mikael',
                'name_am'  => 'አመተ ሚካኤል',
                'phone'    => '944567804',
                'gender'   => 'female',
                'sub_city' => 'Arada',
                'woreda'   => '02',
            ],
            [
                'email'    => 'gebre.kidan@school.com',
                'first'    => 'Gebre',
                'father'   => 'Kidan',
                'name_am'  => 'ገብረ ኪዳን',
                'phone'    => '955678905',
                'gender'   => 'male',
                'sub_city' => 'Nifas Silk',
                'woreda'   => '07',
            ],
            [
                'email'    => 'mahlet.tsion@school.com',
                'first'    => 'Mahlet',
                'father'   => 'Tsion',
                'name_am'  => 'ማህሌተ ጽዮን',
                'phone'    => '966789006',
                'gender'   => 'female',
                'sub_city' => 'Gulele',
                'woreda'   => '04',
            ],
            [
                'email'    => 'yared.tewabe@school.com',
                'first'    => 'Yared',
                'father'   => 'Tewabe',
                'name_am'  => 'ያሬድ ተዋበ',
                'phone'    => '977890107',
                'gender'   => 'male',
                'sub_city' => 'Lideta',
                'woreda'   => '06',
            ],
            [
                'email'    => 'fikerte.mariam@school.com',
                'first'    => 'Fikerte',
                'father'   => 'Mariam',
                'name_am'  => 'ፍቅርተ ማርያም',
                'phone'    => '988901208',
                'gender'   => 'female',
                'sub_city' => 'Kolfe Keranio',
                'woreda'   => '10',
            ],
        ];

        foreach ($teachers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['first'],
                    'password'  => Hash::make('password123'),
                    'is_active' => true,
                ]
            );

            // Assign teacher role
            $user->roles()->syncWithoutDetaching([
                $teacherRole->id => [
                    'assigned_by' => null,
                    'start_date'  => Carbon::now(),
                    'end_date'    => null,
                    'is_active'   => true,
                ]
            ]);

            // Create user profile
            UserInfo::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'registration_id'       => UserInfo::generateNextRegistrationId(),
                    'gender'                => $data['gender'],
                    'phone_number'          => $data['phone'],
                    'father_name'           => $data['father'],
                    'grandfather_name'      => 'Alemu',
                    'christian_name'        => $data['first'], // Already spiritual
                    'spiritual_father_name' => 'Aba Gebre Selassie',
                    'sub_city'              => $data['sub_city'],
                    'woreda'                => $data['woreda'],
                    'house_number'          => (string)rand(100, 999),
                    'address'               => $data['sub_city'] . ', Addis Ababa, Ethiopia',
                ]
            );

            $this->command->line('  ✓ ' . $data['first'] . ' ' . $data['father'] . ' <' . $data['email'] . '>');
        }

        $this->command->info(count($teachers) . ' spiritual teacher users seeded. Default password: password123');
    }
}
