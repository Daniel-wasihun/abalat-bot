<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseOffering;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Academic Years, Spiritual Courses, and Offerings...');

        // 1. Academic Year
        $academicYear = AcademicYear::firstOrCreate(
            ['year' => '2026/2027'],
            [
                'name'       => ['en' => '2026/2027 Academic Year', 'am' => '2026/2027 የትምህርት ዘመን'],
                'start_date' => '2026-09-01',
                'end_date'   => '2027-06-30',
                'is_current' => true,
                'is_active'  => true,
            ]
        );

        // 2. Ethiopian Orthodox Spiritual Courses only
        //    Each course is created once per grade class with a unique code suffix.
        $coursesData = [

            // ── Orthodox Faith & Theology ─────────────────────────────────────────
            [
                'name'         => ['en' => 'Introduction to the Orthodox Faith', 'am' => 'ወደ ኦርቶዶክስ እምነት መግቢያ'],
                'code'         => 'FAIT-101',
                'description'  => 'Foundational principles of Ethiopian Orthodox Tewahedo Christianity for young learners.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['child', '1'],
            ],
            [
                'name'         => ['en' => 'Orthodox Theology & Doctrine', 'am' => 'ኦርቶዶክሳዊ ሃይማኖትና ትምህርት'],
                'code'         => 'THEO-201',
                'description'  => 'Study of Orthodox doctrines including the Trinity, incarnation, and salvation.',
                'credit_hours' => 3,
                'semester'     => '1',
                'classes'      => ['4', '5', '6'],
            ],
            [
                'name'         => ['en' => 'Advanced Orthodox Theology', 'am' => 'ከፍተኛ ኦርቶዶክሳዊ ሃይማኖት'],
                'code'         => 'THEO-301',
                'description'  => 'In-depth theology covering patristic writings, councils, and Orthodox creeds.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['THEO-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Church History ────────────────────────────────────────────────────
            [
                'name'         => ['en' => 'History of the Ethiopian Orthodox Church', 'am' => 'የኢትዮጵያ ኦርቶዶክስ ቤተ ክርስቲያን ታሪክ'],
                'code'         => 'HIST-101',
                'description'  => 'Overview of the founding and growth of the Ethiopian Orthodox Tewahedo Church.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['2', '3'],
            ],
            [
                'name'         => ['en' => 'History of the Universal Church', 'am' => 'የዓለም አቀፍ ቤተ ክርስቲያን ታሪክ'],
                'code'         => 'HIST-201',
                'description'  => 'Comprehensive history of Christianity from apostolic times to modern era.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['5', '6'],
            ],
            [
                'name'         => ['en' => 'Martyrs & Saints of the Church', 'am' => 'የቤተ ክርስቲያን ሰማዕታትና ቅዱሳን'],
                'code'         => 'HIST-301',
                'description'  => 'Lives and sacrifices of Ethiopian and universal Orthodox saints and martyrs.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['6', '7', '8'],
            ],

            // ── Scripture & Bible ─────────────────────────────────────────────────
            [
                'name'         => ['en' => 'Biblical Studies — Old Testament', 'am' => 'የብሉይ ኪዳን ጥናት'],
                'code'         => 'BIBL-101',
                'description'  => 'Study of the Old Testament scriptures with Ethiopian Orthodox interpretation.',
                'credit_hours' => 3,
                'semester'     => '1',
                'classes'      => ['1', '2', '3'],
            ],
            [
                'name'         => ['en' => 'Biblical Studies — New Testament', 'am' => 'የሐዲስ ኪዳን ጥናት'],
                'code'         => 'BIBL-201',
                'description'  => 'Systematic study of the Gospels, Acts, Epistles, and Revelation.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['BIBL-101'],
                'classes'      => ['4', '5', '6'],
            ],
            [
                'name'         => ['en' => 'Prophets & Epistles', 'am' => 'ነቢያትና መልእክቶች'],
                'code'         => 'BIBL-301',
                'description'  => 'Deep study of the major prophets and New Testament epistles with patristic commentary.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['BIBL-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Geez Language & Liturgy ───────────────────────────────────────────
            [
                'name'         => ['en' => 'Geez Language & Script', 'am' => 'ግዕዝ ቋንቋ እና ፊደል'],
                'code'         => 'GEEZ-101',
                'description'  => 'Introduction to the Geez alphabet, pronunciation, and basic reading of liturgical texts.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['3', '4'],
            ],
            [
                'name'         => ['en' => 'Geez Literature & Liturgy', 'am' => 'ግዕዝ ሥነ ጽሑፍ እና ቅዳሴ'],
                'code'         => 'GEEZ-201',
                'description'  => 'Reading and interpretation of classical Geez liturgical texts and sacred literature.',
                'credit_hours' => 2,
                'semester'     => '1',
                'prerequisites'=> ['GEEZ-101'],
                'classes'      => ['5', '6'],
            ],
            [
                'name'         => ['en' => 'Advanced Geez & Hymnody', 'am' => 'ከፍተኛ ግዕዝ እና መዝሙር'],
                'code'         => 'GEEZ-301',
                'description'  => 'Advanced Geez scripture analysis, debtara traditions, and zema (sacred chant) composition.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['GEEZ-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Sacraments & Liturgical Practice ─────────────────────────────────
            [
                'name'         => ['en' => 'The Sacraments of the Church', 'am' => 'ምሥጢረ ቤተ ክርስቲያን'],
                'code'         => 'SACR-201',
                'description'  => 'Study of the seven sacraments: Baptism, Confirmation, Eucharist, Penance, Unction, Holy Orders, and Matrimony.',
                'credit_hours' => 3,
                'semester'     => '1',
                'classes'      => ['5', '6', '7'],
            ],
            [
                'name'         => ['en' => 'The Holy Eucharist & Divine Liturgy', 'am' => 'ቅዱስ ቁርባን እና ቅዳሴ'],
                'code'         => 'SACR-301',
                'description'  => 'Theological and practical study of the Divine Liturgy and the Holy Eucharist.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['SACR-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Fasting, Prayer & Spiritual Disciplines ───────────────────────────
            [
                'name'         => ['en' => 'Fasting & Prayer Practices', 'am' => 'ጾምና ጸሎት'],
                'code'         => 'FAST-101',
                'description'  => 'Orthodox fasting calendar, theology of prayer, and practical spiritual discipline.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['2', '3', '4'],
            ],
            [
                'name'         => ['en' => 'Spiritual Warfare & Repentance', 'am' => 'መንፈሳዊ ትግልና ንስሐ'],
                'code'         => 'FAST-201',
                'description'  => 'Theology of repentance, spiritual watchfulness, and the Orthodox path to holiness.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['5', '6'],
            ],

            // ── Church Music & Mezmur ─────────────────────────────────────────────
            [
                'name'         => ['en' => 'Church Music & Mezmur', 'am' => 'የቤተ ክርስቲያን ዜማ (መዝሙር)'],
                'code'         => 'MUZQ-101',
                'description'  => 'Traditional Ethiopian Orthodox church music, Mezmur, and liturgical chanting.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['2', '3', '4'],
            ],
            [
                'name'         => ['en' => 'Advanced Liturgical Music — Zema', 'am' => 'ከፍተኛ ዜማ (ሊቃዊ)'],
                'code'         => 'MUZQ-201',
                'description'  => 'Mastery of traditional zema modes, debtara chanting, and sacred choral performance.',
                'credit_hours' => 2,
                'semester'     => '2',
                'prerequisites'=> ['MUZQ-101'],
                'classes'      => ['6', '7', '8'],
            ],

            // ── Ethics & Christian Life ───────────────────────────────────────────
            [
                'name'         => ['en' => 'Christian Moral Education', 'am' => 'ክርስቲያናዊ ሥነ ምግባር ትምህርት'],
                'code'         => 'MORA-101',
                'description'  => 'Christian ethics, values, and moral formation rooted in Orthodox teaching.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['child', '1', '2'],
            ],
            [
                'name'         => ['en' => 'Orthodox Christian Leadership', 'am' => 'ክርስቲያናዊ አመራር'],
                'code'         => 'LEAD-301',
                'description'  => 'Servant leadership and community responsibility rooted in Orthodox Christian values.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['7', '8'],
            ],

            // ── Amharic Language & Literature ─────────────────────────────────────
            [
                'name'         => ['en' => 'Amharic Language & Spiritual Literature', 'am' => 'አማርኛ ቋንቋ እና የሃይማኖት ሥነ ጽሑፍ'],
                'code'         => 'AMHR-101',
                'description'  => 'Amharic reading, writing, grammar, and study of Ethiopian spiritual literary works.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['1', '2', '3'],
            ],
            [
                'name'         => ['en' => 'Advanced Amharic & Church Literature', 'am' => 'ከፍተኛ አማርኛ እና የቤተ ክርስቲያን ሥነ ጽሑፍ'],
                'code'         => 'AMHR-201',
                'description'  => 'Advanced Amharic with focus on Orthodox theological and historical texts.',
                'credit_hours' => 2,
                'semester'     => '2',
                'prerequisites'=> ['AMHR-101'],
                'classes'      => ['5', '6', '7', '8'],
            ],

            // ── Discipleship & Community ──────────────────────────────────────────
            [
                'name'         => ['en' => 'Discipleship & Evangelism', 'am' => 'ደቀ መዝሙርነትና ስብከተ ወንጌል'],
                'code'         => 'DSCL-201',
                'description'  => 'Principles of Orthodox discipleship, witnessing the faith, and sharing the Gospel.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['4', '5'],
            ],
            [
                'name'         => ['en' => 'Church Community & Social Service', 'am' => 'የቤተ ክርስቲያን ማህበረሰብና አገልግሎት'],
                'code'         => 'COMM-201',
                'description'  => 'Practicum in community service through the mission and ministry of the Orthodox Church.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['6', '7', '8'],
            ],
        ];

        $created = 0;
        $offerings = 0;

        foreach ($coursesData as $cData) {
            foreach ($cData['classes'] as $class) {
                $uniqueCode  = $cData['code'] . '-' . $class;
                $nameEn      = is_array($cData['name']) ? $cData['name']['en'] : $cData['name'];
                $nameAm      = is_array($cData['name']) ? $cData['name']['am'] : $cData['name'];
                $gradeSuffix = $class === 'child' ? 'Child/KG' : 'Grade ' . $class;

                $course = Course::updateOrCreate(
                    ['code' => $uniqueCode],
                    [
                        'name'          => ['en' => $nameEn . ' — ' . $gradeSuffix, 'am' => $nameAm . ' — ' . $gradeSuffix],
                        'description'   => $cData['description'],
                        'credit_hours'  => $cData['credit_hours'],
                        'senbet_class'  => $class,
                        'semester'      => $cData['semester'],
                        'prerequisites' => $cData['prerequisites'] ?? null,
                        'is_active'     => true,
                    ]
                );

                CourseOffering::updateOrCreate(
                    [
                        'course_id'       => $course->id,
                        'academic_year_id'=> $academicYear->id,
                        'senbet_class'    => $class,
                    ],
                    [
                        'semester'  => $cData['semester'],
                        'is_active' => true,
                    ]
                );

                $this->command->line("  ✓ {$nameEn} — {$gradeSuffix} ({$uniqueCode})");
                $created++;
                $offerings++;
            }
        }

        $this->command->info("{$created} courses and {$offerings} offerings seeded successfully!");
    }
}
