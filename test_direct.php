<?php
// Direct test without HTTP server

// Load the helper files
require_once __DIR__ . '/laso/helpers/CustomLunarConverterHelper.php';
require_once __DIR__ . '/laso/helpers/TuViHelper.php';
require_once __DIR__ . '/laso/helpers/slugify.php';

// Test data
$validated = [
    'ho_ten'    => 'Test User',
    'gioi_tinh' => 'Nam',
    'nam_xem'   => 2024,
    'dl_ngay'   => 15,
    'dl_thang'  => 3,
    'dl_nam'    => 1990,
    'dl_gio'    => 14,
    'dl_phut'   => 30,
    'location'  => null,
];

// Prepare data
$gio_padded = str_pad($validated['dl_gio'], 2, '0', STR_PAD_LEFT);
$phut_padded = str_pad($validated['dl_phut'], 2, '0', STR_PAD_LEFT);

$dateString = "{$validated['dl_nam']}-{$validated['dl_thang']}-{$validated['dl_ngay']}-{$gio_padded}-{$phut_padded}";

$originalDuongLich = DateTime::createFromFormat('Y-n-j-H-i', $dateString);

if ($originalDuongLich === false) {
    die("Cannot create DateTime object\n");
}

$duongLich = clone $originalDuongLich;

// Convert to lunar calendar
$amLich = CustomLunarConverterHelper::fromGregorian($duongLich);
$canChiMonth = TuViHelper::canchiThang((int)$amLich['year'], (int)$amLich['month']);
$jd = TuViHelper::jdFromDate($originalDuongLich->format('d'), $originalDuongLich->format('m'), $originalDuongLich->format('Y'));
$canChiNgay = TuViHelper::canchiNgayByJD($jd);
$canChiNamXem = TuViHelper::canchiNam($validated['nam_xem']);

$birthHour = (int)$validated['dl_gio'];
$zodiacHourRangeString = '';

if ($birthHour >= 23 || $birthHour < 1) {
    $zodiacHourRangeString = '23:00 - 01:00';
} elseif ($birthHour >= 1 && $birthHour < 3) {
    $zodiacHourRangeString = '01:00 - 03:00';
} elseif ($birthHour >= 3 && $birthHour < 5) {
    $zodiacHourRangeString = '03:00 - 05:00';
} elseif ($birthHour >= 5 && $birthHour < 7) {
    $zodiacHourRangeString = '05:00 - 07:00';
} elseif ($birthHour >= 7 && $birthHour < 9) {
    $zodiacHourRangeString = '07:00 - 09:00';
} elseif ($birthHour >= 9 && $birthHour < 11) {
    $zodiacHourRangeString = '09:00 - 11:00';
} elseif ($birthHour >= 11 && $birthHour < 13) {
    $zodiacHourRangeString = '11:00 - 13:00';
} elseif ($birthHour >= 13 && $birthHour < 15) {
    $zodiacHourRangeString = '13:00 - 15:00';
} elseif ($birthHour >= 15 && $birthHour < 17) {
    $zodiacHourRangeString = '15:00 - 17:00';
} elseif ($birthHour >= 17 && $birthHour < 19) {
    $zodiacHourRangeString = '17:00 - 19:00';
} elseif ($birthHour >= 19 && $birthHour < 21) {
    $zodiacHourRangeString = '19:00 - 21:00';
} elseif ($birthHour >= 21 && $birthHour < 23) {
    $zodiacHourRangeString = '21:00 - 23:00';
}

$normalizedData = [
    'ho_ten' => $validated['ho_ten'],
    'gioi_tinh' => $validated['gioi_tinh'],
    'nam_xem' => $validated['nam_xem'],
    'tuoi' => $validated['nam_xem'] - (int)$originalDuongLich->format('Y') + 1,
    'duong_lich_str' => $originalDuongLich->format('H:i') . ' ngày ' . $originalDuongLich->format('d/m/Y'),
    'duong_lich_hieu_chinh_str' => '',
    'am_lich_str' => "Giờ {$amLich['hour_chi']}, ngày {$amLich['day']}/{$amLich['month']}/{$amLich['year']} (Năm {$amLich['can']} {$amLich['chi']})",
    'gio_am_sinh_chi_am' => $amLich['hour_chi'],
    'gio_am_sinh_am' => $zodiacHourRangeString,
    'can_chi_thang' => $canChiMonth,
    'can_chi_ngay' => $canChiNgay,
    'can_chi_nam_xem' => $canChiNamXem,
    'duong_lich' => [
        'day' => $originalDuongLich->format('d'),
        'month' => $originalDuongLich->format('m'),
        'year' => $originalDuongLich->format('Y'),
    ],
    'lunar' => $amLich,
];

// Generate Tu Vi chart
$tuviHelper = new TuViHelper();
$laSo = $tuviHelper->generate($normalizedData);

// Check for ngu hanh (five elements) information
echo "Testing ngũ hành (five elements) information in star data:\n";
echo str_repeat('=', 60) . "\n\n";

$foundStarsWithNguHanh = 0;
$totalStars = 0;
$sampleStars = [];

foreach ($laSo['palaces'] as $palace => $palaceData) {
    $starTypes = ['chinh_tinh', 'phu_tinh_cat', 'phu_tinh_sat', 'special', 'luu'];

    foreach ($starTypes as $type) {
        if (isset($palaceData[$type]) && !empty($palaceData[$type])) {
            foreach ($palaceData[$type] as $star) {
                $totalStars++;
                if (isset($star['hanh'])) {
                    $foundStarsWithNguHanh++;
                    if ($foundStarsWithNguHanh <= 15) { // Show first 15 examples
                        echo "✓ {$star['name']} -> Ngũ hành: {$star['hanh']} (Cung: {$star['cung']})\n";
                        $sampleStars[] = $star;
                    }
                } else {
                    echo "❌ {$star['name']} -> Không có ngũ hành\n";
                }
            }
        }
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Results:\n";
echo "Total stars found: $totalStars\n";
echo "Stars with ngũ hành: $foundStarsWithNguHanh\n";
echo "Percentage: " . ($totalStars > 0 ? round($foundStarsWithNguHanh / $totalStars * 100, 2) : 0) . "%\n\n";

if ($foundStarsWithNguHanh == $totalStars && $totalStars > 0) {
    echo "✅ SUCCESS: All stars now include ngũ hành (five elements) information!\n";
} elseif ($foundStarsWithNguHanh > 0) {
    echo "⚠️ PARTIAL: Some stars include ngũ hành information.\n";
} else {
    echo "❌ ERROR: No stars include ngũ hành information.\n";
}

// Save sample output
$sampleOutput = [
    'summary' => [
        'total_stars' => $totalStars,
        'stars_with_ngu_hanh' => $foundStarsWithNguHanh
    ],
    'sample_palace' => array_slice($laSo['palaces'], 0, 1, true)
];

file_put_contents('test_result.json', json_encode($sampleOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nSample output saved to test_result.json\n";