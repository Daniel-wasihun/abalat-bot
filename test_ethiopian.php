<?php
function toGregorian($year, $month, $day) {
    $jdn = 1723856 + 365 * ($year - 1) + (int)($year / 4) + 30 * ($month - 1) + $day - 1;
    $parts = jdtogregorian($jdn); // m/d/Y
    $d = explode('/', $parts);
    return sprintf('%04d-%02d-%02d', $d[2], $d[0], $d[1]);
}

function toEthiopian($gregYear, $gregMonth, $gregDay) {
    $jdn = cal_to_jd(CAL_GREGORIAN, $gregMonth, $gregDay, $gregYear);
    $jdnEth = $jdn - 1723856;
    $r = $jdnEth % 1461;
    $n = ($r % 365) + 365 * (int)($r / 1460);
    $ethYear = 4 * (int)($jdnEth / 1461) + (int)($r / 365) - (int)($r / 1460);
    $ethMonth = (int)($n / 30) + 1;
    $ethDay = ($n % 30) + 1;
    return [$ethYear, $ethMonth, $ethDay];
}

echo toGregorian(2016, 1, 1) . " (Should be 2023-09-12)\n";
echo toGregorian(2017, 1, 1) . " (Should be 2024-09-11)\n";
echo toGregorian(2018, 12, 15) . " (Should be 2026-08-21)\n";
