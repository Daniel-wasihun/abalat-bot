<?php
$file = 'database/seeders/UserSeeder.php';
$content = file_get_contents($file);

// Replace phone generation
$content = str_replace(
    "'+251' . collect(['7', '9'])->random() . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT)",
    "collect(['7', '9'])->random() . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT)",
    $content
);

// Update createFullUser signature to just take First, Father, GF instead of English/Amharic with spaces
$content = preg_replace('/\$createFullUser = function \(\$email, \$nameEn, \$nameAm, \$password, \$roleSlug, \$assignerId = null, \$userType = \'staff\'\)/', 
'$createFullUser = function ($email, $first, $father, $password, $roleSlug, $assignerId = null, $userType = \'staff\')', 
$content);

// Update User::updateOrCreate inside createFullUser
$content = str_replace(
    "'name'      => \$nameEn,",
    "'name'      => \$first,",
    $content
);

// Update UserInfo::updateOrCreate inside createFullUser
$content = str_replace(
    "'father_name'           => 'Father of ' . \$nameEn,",
    "'father_name'           => \$father,",
    $content
);
$content = str_replace(
    "'grandfather_name'      => 'Grandfather ' . \$nameEn,",
    "'grandfather_name'      => 'Alemu',",
    $content
);
$content = str_replace(
    "'christian_name'        => 'Woldemariam ' . \$nameEn,",
    "'christian_name'        => 'Woldemariam',",
    $content
);

// Update the actual calls
$content = str_replace(
    "            'Super Administrator (Yared)',\n            'ሱፐር አድሚን (ያሬድ)',",
    "            'Yared',\n            'Alemayehu',",
    $content
);
$content = str_replace(
    "            'School Administrator (Teklehaimanot)',\n            'አስተዳዳሪ (ተክለሃይማኖት)',",
    "            'Teklehaimanot',\n            'Belay',",
    $content
);
$content = str_replace(
    "('admin2@lms.com', 'Admin Fasil', 'አድሚን ፋሲል',",
    "('admin2@lms.com', 'Fasil', 'Kebede',",
    $content
);
$content = str_replace(
    "('admin3@lms.com', 'Admin Mahlet', 'አድሚን ማህሌት',",
    "('admin3@lms.com', 'Mahlet', 'Tadesse',",
    $content
);
$content = str_replace(
    "            'Teacher (Kidanemariam)',\n            'መምህር (ኪዳነማርያም)',",
    "            'Kidanemariam',\n            'Zewde',",
    $content
);
$content = str_replace(
    "            'Student (Gebre Meskel)',\n            'ተማሪ (ገብረ መስቀል)',",
    "            'Gebremeskel',\n            'Tessema',",
    $content
);
$content = str_replace(
    "            'Memhir Admin (Zena Markos)',\n            'መምህር አድሚን (ዜና ማርቆስ)',",
    "            'Zenamarkos',\n            'Abebe',",
    $content
);
$content = str_replace(
    "            'Student Teacher (Ephrem)',\n            'ተማሪ መምህር (ኤፍሬም)',",
    "            'Ephrem',\n            'Haile',",
    $content
);

file_put_contents($file, $content);
echo "UserSeeder updated successfully.\n";
