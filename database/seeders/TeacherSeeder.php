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
                'name_en'  => 'Habte Mariam',
                'name_am'  => 'ሀብተ ማርያም',
                'phone'    => '+251911234501',
                'gender'   => 'male',
                'sub_city' => 'Bole',
                'woreda'   => '05',
            ],
            [
                'email'    => 'winta.selassie@school.com',
                'name_en'  => 'Winta Selassie',
                'name_am'  => 'ዊንታ ሥላሴ',
                'phone'    => '+251922345602',
                'gender'   => 'female',
                'sub_city' => 'Kirkos',
                'woreda'   => '08',
            ],
            [
                'email'    => 'kidane.wold@school.com',
                'name_en'  => 'Kidane Wold',
                'name_am'  => 'ኪዳነ ወልድ',
                'phone'    => '+251933456703',
                'gender'   => 'male',
                'sub_city' => 'Yeka',
                'woreda'   => '11',
            ],
            [
                'email'    => 'amete.mikael@school.com',
                'name_en'  => 'Amete Mikael',
                'name_am'  => 'አመተ ሚካኤል',
                'phone'    => '+251944567804',
                'gender'   => 'female',
                'sub_city' => 'Arada',
                'woreda'   => '02',
            ],
            [
                'email'    => 'gebre.kidan@school.com',
                'name_en'  => 'Gebre Kidan',
                'name_am'  => 'ገብረ ኪዳን',
                'phone'    => '+251955678905',
                'gender'   => 'male',
                'sub_city' => 'Nifas Silk',
                'woreda'   => '07',
            ],
            [
                'email'    => 'mahlet.tsion@school.com',
                'name_en'  => 'Mahlet Tsion',
                'name_am'  => 'ማህሌተ ጽዮን',
                'phone'    => '+251966789006',
                'gender'   => 'female',
                'sub_city' => 'Gulele',
                'woreda'   => '04',
            ],
            [
                'email'    => 'yared.tewabe@school.com',
                'name_en'  => 'Yared Tewabe',
                'name_am'  => 'ያሬድ ተዋበ',
                'phone'    => '+251977890107',
                'gender'   => 'male',
                'sub_city' => 'Lideta',
                'woreda'   => '06',
            ],
            [
                'email'    => 'fikerte.mariam@school.com',
                'name_en'  => 'Fikerte Mariam',
                'name_am'  => 'ፍቅርተ ማርያም',
                'phone'    => '+251988901208',
                'gender'   => 'female',
                'sub_city' => 'Kolfe Keranio',
                'woreda'   => '10',
            ],
        ];

        foreach ($teachers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => ['en' => $data['name_en'], 'am' => $data['name_am']],
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
                    'father_name'           => 'Father of ' . $data['name_en'],
                    'grandfather_name'      => 'Grandfather ' . $data['name_en'],
                    'christian_name'        => $data['name_en'], // Already spiritual
                    'spiritual_father_name' => 'Aba ' . explode(' ', $data['name_en'])[0],
                    'sub_city'              => $data['sub_city'],
                    'woreda'                => $data['woreda'],
                    'house_number'          => (string)rand(100, 999),
                    'address'               => $data['sub_city'] . ', Addis Ababa, Ethiopia',
                ]
            );

            $this->command->line('  ✓ ' . $data['name_en'] . ' <' . $data['email'] . '>');
        }

        $this->command->info(count($teachers) . ' spiritual teacher users seeded. Default password: password123');
    }
}
