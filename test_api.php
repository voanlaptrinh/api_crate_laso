<?php
// Test script for API with zodiac house information

$url = 'http://localhost/store_laso.php';

$data = [
    'ho_ten' => 'Test User',
    'gioi_tinh' => 'Nam',
    'nam_xem' => 2024,
    'dl_ngay' => 15,
    'dl_thang' => 3,
    'dl_nam' => 1990,
    'dl_gio' => 14,
    'dl_phut' => 30
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    die("Error calling API\n");
}

$response = json_decode($result, true);

if ($response['success']) {
    echo "API call successful!\n\n";

    // Check if stars now include 'cung' field
    echo "Checking for zodiac house (cung) in star data:\n";
    echo str_repeat('-', 50) . "\n";

    $hasZodiacInfo = false;

    foreach ($response['data']['laso_details']['palaces'] as $palace => $palaceData) {
        // Check chinh_tinh stars
        if (isset($palaceData['chinh_tinh']) && !empty($palaceData['chinh_tinh'])) {
            foreach ($palaceData['chinh_tinh'] as $star) {
                if (isset($star['cung'])) {
                    echo "✓ Star '{$star['name']}' has zodiac house: {$star['cung']}\n";
                    $hasZodiacInfo = true;
                }
            }
        }

        // Check phu_tinh_cat stars
        if (isset($palaceData['phu_tinh_cat']) && !empty($palaceData['phu_tinh_cat'])) {
            foreach ($palaceData['phu_tinh_cat'] as $star) {
                if (isset($star['cung'])) {
                    echo "✓ Star '{$star['name']}' has zodiac house: {$star['cung']}\n";
                    $hasZodiacInfo = true;
                }
            }
        }

        // Check phu_tinh_sat stars
        if (isset($palaceData['phu_tinh_sat']) && !empty($palaceData['phu_tinh_sat'])) {
            foreach ($palaceData['phu_tinh_sat'] as $star) {
                if (isset($star['cung'])) {
                    echo "✓ Star '{$star['name']}' has zodiac house: {$star['cung']}\n";
                    $hasZodiacInfo = true;
                }
            }
        }

        // Check special stars
        if (isset($palaceData['special']) && !empty($palaceData['special'])) {
            foreach ($palaceData['special'] as $star) {
                if (isset($star['cung'])) {
                    echo "✓ Star '{$star['name']}' has zodiac house: {$star['cung']}\n";
                    $hasZodiacInfo = true;
                }
            }
        }
    }

    if ($hasZodiacInfo) {
        echo "\n✅ SUCCESS: Stars now include zodiac house information!\n";
    } else {
        echo "\n❌ ERROR: Stars do not include zodiac house information.\n";
    }

    // Save full response to file for inspection
    file_put_contents('api_response.json', json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\nFull response saved to api_response.json\n";

} else {
    echo "API call failed: " . $response['message'] . "\n";
}