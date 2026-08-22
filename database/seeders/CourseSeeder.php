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
                'name' => '2026/2027 Academic Year',
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
                'name' => 'Introduction to the Orthodox Faith',
                'code'         => 'FAIT-101',
                'description'  => 'Foundational principles of Ethiopian Orthodox Tewahedo Christianity for young learners.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['child', '1'],
            ],
            [
                'name' => 'Orthodox Theology & Doctrine',
                'code'         => 'THEO-201',
                'description'  => 'Study of Orthodox doctrines including the Trinity, incarnation, and salvation.',
                'credit_hours' => 3,
                'semester'     => '1',
                'classes'      => ['4', '5', '6'],
            ],
            [
                'name' => 'Advanced Orthodox Theology',
                'code'         => 'THEO-301',
                'description'  => 'In-depth theology covering patristic writings, councils, and Orthodox creeds.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['THEO-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Church History ────────────────────────────────────────────────────
            [
                'name' => 'History of the Ethiopian Orthodox Church',
                'code'         => 'HIST-101',
                'description'  => 'Overview of the founding and growth of the Ethiopian Orthodox Tewahedo Church.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['2', '3'],
            ],
            [
                'name' => 'History of the Universal Church',
                'code'         => 'HIST-201',
                'description'  => 'Comprehensive history of Christianity from apostolic times to modern era.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['5', '6'],
            ],
            [
                'name' => 'Martyrs & Saints of the Church',
                'code'         => 'HIST-301',
                'description'  => 'Lives and sacrifices of Ethiopian and universal Orthodox saints and martyrs.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['6', '7', '8'],
            ],

            // ── Scripture & Bible ─────────────────────────────────────────────────
            [
                'name' => 'Biblical Studies — Old Testament',
                'code'         => 'BIBL-101',
                'description'  => 'Study of the Old Testament scriptures with Ethiopian Orthodox interpretation.',
                'credit_hours' => 3,
                'semester'     => '1',
                'classes'      => ['1', '2', '3'],
            ],
            [
                'name' => 'Biblical Studies — New Testament',
                'code'         => 'BIBL-201',
                'description'  => 'Systematic study of the Gospels, Acts, Epistles, and Revelation.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['BIBL-101'],
                'classes'      => ['4', '5', '6'],
            ],
            [
                'name' => 'Prophets & Epistles',
                'code'         => 'BIBL-301',
                'description'  => 'Deep study of the major prophets and New Testament epistles with patristic commentary.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['BIBL-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Geez Language & Liturgy ───────────────────────────────────────────
            [
                'name' => 'Geez Language & Script',
                'code'         => 'GEEZ-101',
                'description'  => 'Introduction to the Geez alphabet, pronunciation, and basic reading of liturgical texts.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['3', '4'],
            ],
            [
                'name' => 'Geez Literature & Liturgy',
                'code'         => 'GEEZ-201',
                'description'  => 'Reading and interpretation of classical Geez liturgical texts and sacred literature.',
                'credit_hours' => 2,
                'semester'     => '1',
                'prerequisites'=> ['GEEZ-101'],
                'classes'      => ['5', '6'],
            ],
            [
                'name' => 'Advanced Geez & Hymnody',
                'code'         => 'GEEZ-301',
                'description'  => 'Advanced Geez scripture analysis, debtara traditions, and zema (sacred chant) composition.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['GEEZ-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Sacraments & Liturgical Practice ─────────────────────────────────
            [
                'name' => 'The Sacraments of the Church',
                'code'         => 'SACR-201',
                'description'  => 'Study of the seven sacraments: Baptism, Confirmation, Eucharist, Penance, Unction, Holy Orders, and Matrimony.',
                'credit_hours' => 3,
                'semester'     => '1',
                'classes'      => ['5', '6', '7'],
            ],
            [
                'name' => 'The Holy Eucharist & Divine Liturgy',
                'code'         => 'SACR-301',
                'description'  => 'Theological and practical study of the Divine Liturgy and the Holy Eucharist.',
                'credit_hours' => 3,
                'semester'     => '2',
                'prerequisites'=> ['SACR-201'],
                'classes'      => ['7', '8'],
            ],

            // ── Fasting, Prayer & Spiritual Disciplines ───────────────────────────
            [
                'name' => 'Fasting & Prayer Practices',
                'code'         => 'FAST-101',
                'description'  => 'Orthodox fasting calendar, theology of prayer, and practical spiritual discipline.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['2', '3', '4'],
            ],
            [
                'name' => 'Spiritual Warfare & Repentance',
                'code'         => 'FAST-201',
                'description'  => 'Theology of repentance, spiritual watchfulness, and the Orthodox path to holiness.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['5', '6'],
            ],

            // ── Church Music & Mezmur ─────────────────────────────────────────────
            [
                'name' => 'Church Music & Mezmur',
                'code'         => 'MUZQ-101',
                'description'  => 'Traditional Ethiopian Orthodox church music, Mezmur, and liturgical chanting.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['2', '3', '4'],
            ],
            [
                'name' => 'Advanced Liturgical Music — Zema',
                'code'         => 'MUZQ-201',
                'description'  => 'Mastery of traditional zema modes, debtara chanting, and sacred choral performance.',
                'credit_hours' => 2,
                'semester'     => '2',
                'prerequisites'=> ['MUZQ-101'],
                'classes'      => ['6', '7', '8'],
            ],

            // ── Ethics & Christian Life ───────────────────────────────────────────
            [
                'name' => 'Christian Moral Education',
                'code'         => 'MORA-101',
                'description'  => 'Christian ethics, values, and moral formation rooted in Orthodox teaching.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['child', '1', '2'],
            ],
            [
                'name' => 'Orthodox Christian Leadership',
                'code'         => 'LEAD-301',
                'description'  => 'Servant leadership and community responsibility rooted in Orthodox Christian values.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['7', '8'],
            ],

            // ── Amharic Language & Literature ─────────────────────────────────────
            [
                'name' => 'Amharic Language & Spiritual Literature',
                'code'         => 'AMHR-101',
                'description'  => 'Amharic reading, writing, grammar, and study of Ethiopian spiritual literary works.',
                'credit_hours' => 2,
                'semester'     => '1',
                'classes'      => ['1', '2', '3'],
            ],
            [
                'name' => 'Advanced Amharic & Church Literature',
                'code'         => 'AMHR-201',
                'description'  => 'Advanced Amharic with focus on Orthodox theological and historical texts.',
                'credit_hours' => 2,
                'semester'     => '2',
                'prerequisites'=> ['AMHR-101'],
                'classes'      => ['5', '6', '7', '8'],
            ],

            // ── Discipleship & Community ──────────────────────────────────────────
            [
                'name' => 'Discipleship & Evangelism',
                'code'         => 'DSCL-201',
                'description'  => 'Principles of Orthodox discipleship, witnessing the faith, and sharing the Gospel.',
                'credit_hours' => 2,
                'semester'     => '2',
                'classes'      => ['4', '5'],
            ],
            [
                'name' => 'Church Community & Social Service',
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
                $nameEn      = $cData['name'];
                $gradeSuffix = $class === 'child' ? 'Child/KG' : 'Grade ' . $class;

                $course = Course::updateOrCreate(
                    ['code' => $uniqueCode],
                    [
                        'name' => $nameEn . ' — ' . $gradeSuffix,
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
