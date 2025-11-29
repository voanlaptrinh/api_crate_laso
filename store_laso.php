<?php
// File: api/store_laso.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('ROOT_PATH', __DIR__);
// --- Cài đặt cơ bản ---
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// BƯỚC 1: TẢI COMPOSER AUTOLOADER
require_once __DIR__ . '/vendor/autoload.php';

// Tải các helper của bạn
require_once __DIR__ . '/laso/helpers/CustomLunarConverterHelper.php';
require_once __DIR__ . '/laso/helpers/TuViHelper.php';
require_once __DIR__ . '/laso/helpers/slugify.php';
// BƯỚC 2: SỬ DỤNG PHP GD LIBRARY CHO TẠO ẢNH
// Không cần import thư viện bên ngoài, GD đã có sẵn trong PHP

// --- Hàm tiện ích để gửi response ---
function send_json_response($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}


function generate_public_url($fileName)
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];

    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    return $protocol . $domainName . $path . '/public/storage/' . $fileName;
}


// --- Xử lý Request (Giữ nguyên) ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => 'Method Not Allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(['success' => false, 'message' => 'Invalid JSON input.'], 400);
}

/**
 * Dọn dẹp các file ảnh lá số cũ trong thư mục public/storage.
 * Hàm này chỉ chạy ngẫu nhiên để không ảnh hưởng đến hiệu năng.
 */
function cleanupOldImages()
{
    if (rand(1, 100) !== 1) {
        return;
    }

    $storageDir = __DIR__ . '/public/storage';
    $maxFileAge = 3600 * 24;

    if (!is_dir($storageDir)) {
        return;
    }

    $now = time();

    foreach (glob($storageDir . '/*.png') as $file) {
        $fileAge = $now - filemtime($file);
        if ($fileAge > $maxFileAge) {
            @unlink($file); // Sử dụng @ để bỏ qua lỗi nếu file không xóa được
        }
    }
}

// 1. Xác thực dữ liệu đầu vào (Giữ nguyên)
$errors = [];
$check_required = fn($key) => isset($input[$key]) && $input[$key] !== '';
$check_string = fn($key) => is_string($input[$key]);
$check_integer = fn($key, $min, $max) => isset($input[$key]) && is_numeric($input[$key]) && (int)$input[$key] >= $min && (int)$input[$key] <= $max;
$check_in = fn($key, $allowed) => isset($input[$key]) && in_array($input[$key], $allowed, true);

if (!$check_required('ho_ten') || !$check_string('ho_ten') || strlen($input['ho_ten']) > 100) $errors['ho_ten'] = 'Họ tên là bắt buộc, kiểu chuỗi, tối đa 100 ký tự.';
if (!$check_required('gioi_tinh') || !$check_in('gioi_tinh', ['Nam', 'Nữ'])) $errors['gioi_tinh'] = 'Giới tính phải là "Nam" hoặc "Nữ".';
if (!$check_required('nam_xem') || !$check_integer('nam_xem', 1900, 2200)) $errors['nam_xem'] = 'Năm xem là số nguyên từ 1900 đến 2200.';
if (!$check_required('dl_ngay') || !$check_integer('dl_ngay', 1, 31)) $errors['dl_ngay'] = 'Ngày dương lịch là số nguyên từ 1 đến 31.';
if (!$check_required('dl_thang') || !$check_integer('dl_thang', 1, 12)) $errors['dl_thang'] = 'Tháng dương lịch là số nguyên từ 1 đến 12.';
if (!$check_required('dl_nam') || !$check_integer('dl_nam', 1900, 2200)) $errors['dl_nam'] = 'Năm dương lịch là số nguyên từ 1900 đến 2200.';
if (!$check_required('dl_gio') || !$check_integer('dl_gio', 0, 23)) $errors['dl_gio'] = 'Giờ dương lịch là số nguyên từ 0 đến 23.';
if (!$check_required('dl_phut') || !$check_integer('dl_phut', 0, 59)) $errors['dl_phut'] = 'Phút dương lịch là số nguyên từ 0 đến 59.';

$year = (int)($input['dl_nam'] ?? 0);
if ($year >= 1945 && $year <= 1975) {
    if (!$check_required('location') || !$check_in('location', ['north', 'south'])) {
        $errors['location'] = 'Nơi sinh là bắt buộc (north/south) cho năm sinh từ 1945-1975.';
    }
}

if (empty($errors) && !checkdate($input['dl_thang'], $input['dl_ngay'], $input['dl_nam'])) {
    $errors['dl_ngay'] = 'Ngày dương lịch không hợp lệ.';
}

if (!empty($errors)) {
    send_json_response(['success' => false, 'message' => 'Dữ liệu không hợp lệ.', 'errors' => $errors], 422);
}

$validated = [
    'ho_ten'    => (string) $input['ho_ten'],
    'gioi_tinh' => (string) $input['gioi_tinh'],
    'nam_xem'   => (int) $input['nam_xem'],
    'dl_ngay'   => (int) $input['dl_ngay'],
    'dl_thang'  => (int) $input['dl_thang'],
    'dl_nam'    => (int) $input['dl_nam'],
    'dl_gio'    => (int) $input['dl_gio'],
    'dl_phut'   => (int) $input['dl_phut'],
    'app_name'  => $input['app_name'] ?? 'phongthuydaicat',
    'location'  => $input['location'] ?? null,
];


// 2. Chuẩn bị và hiệu chỉnh dữ liệu (Giữ nguyên)
try {
    $gio_padded = str_pad($validated['dl_gio'], 2, '0', STR_PAD_LEFT);
    $phut_padded = str_pad($validated['dl_phut'], 2, '0', STR_PAD_LEFT);

    // Sử dụng định dạng 'H-i' (giờ và phút có số 0 đứng trước)
    $dateString = "{$validated['dl_nam']}-{$validated['dl_thang']}-{$validated['dl_ngay']}-{$gio_padded}-{$phut_padded}";

    $originalDuongLich = DateTime::createFromFormat(
        'Y-n-j-H-i', // Sửa định dạng thành H-i
        $dateString
    );

    if ($originalDuongLich === false) {
        throw new Exception("Không thể tạo đối tượng ngày giờ từ dữ liệu đầu vào.");
    }

    $duongLich = clone $originalDuongLich;

    $amLich = CustomLunarConverterHelper::fromGregorian($duongLich);
    $canChiMonth = TuViHelper::canchiThang((int)$amLich['year'], (int)$amLich['month']);
    $jd = TuViHelper::jdFromDate($originalDuongLich->format('d'), $originalDuongLich->format('m'), $originalDuongLich->format('Y'));
    $canChiNgay = TuViHelper::canchiNgayByJD($jd);
    $canChiNamXem = TuViHelper::canchiNam($validated['nam_xem']);

    $birthHour = (int)$validated['dl_gio']; // Lấy giờ dương lịch số nguyên đã được validate
    $zodiacHourRangeString = '';

    if ($birthHour >= 23 || $birthHour < 1) { // 23:00 - 00:59
        $zodiacHourRangeString = '23:00 - 01:00';
    } elseif ($birthHour >= 1 && $birthHour < 3) { // 01:00 - 02:59
        $zodiacHourRangeString = '01:00 - 03:00';
    } elseif ($birthHour >= 3 && $birthHour < 5) { // 03:00 - 04:59
        $zodiacHourRangeString = '03:00 - 05:00';
    } elseif ($birthHour >= 5 && $birthHour < 7) { // 05:00 - 06:59
        $zodiacHourRangeString = '05:00 - 07:00';
    } elseif ($birthHour >= 7 && $birthHour < 9) { // 07:00 - 08:59
        $zodiacHourRangeString = '07:00 - 09:00';
    } elseif ($birthHour >= 9 && $birthHour < 11) { // 09:00 - 10:59
        $zodiacHourRangeString = '09:00 - 11:00';
    } elseif ($birthHour >= 11 && $birthHour < 13) { // 11:00 - 12:59
        $zodiacHourRangeString = '11:00 - 13:00';
    } elseif ($birthHour >= 13 && $birthHour < 15) { // 13:00 - 14:59
        $zodiacHourRangeString = '13:00 - 15:00';
    } elseif ($birthHour >= 15 && $birthHour < 17) { // 15:00 - 16:59
        $zodiacHourRangeString = '15:00 - 17:00';
    } elseif ($birthHour >= 17 && $birthHour < 19) { // 17:00 - 18:59
        $zodiacHourRangeString = '17:00 - 19:00';
    } elseif ($birthHour >= 19 && $birthHour < 21) { // 19:00 - 20:59
        $zodiacHourRangeString = '19:00 - 21:00';
    } elseif ($birthHour >= 21 && $birthHour < 23) { // 21:00 - 22:59
        $zodiacHourRangeString = '21:00 - 23:00';
    } else {
        $zodiacHourRangeString = 'Không xác định'; // Trường hợp không mong muốn
    }


    $normalizedData = [
        'ho_ten' => $validated['ho_ten'],
        'gioi_tinh' => $validated['gioi_tinh'],
        'nam_xem' => $validated['nam_xem'],
        'tuoi' => $validated['nam_xem'] - (int)$originalDuongLich->format('Y') + 1,
        'duong_lich_str' => $originalDuongLich->format('H:i') . ' ngày ' . $originalDuongLich->format('d/m/Y'),
        'duong_lich_hieu_chinh_str' => ($originalDuongLich != $duongLich) ? '(Giờ hiệu chỉnh: ' . $duongLich->format('H:i d/m/Y') . ')' : '',
        'am_lich_str' => "Giờ {$amLich['hour_chi']}, ngày {$amLich['day']}/{$amLich['month']}/{$amLich['year']} (Năm {$amLich['can']} {$amLich['chi']})",
        'gio_am_sinh_chi_am' => $amLich['hour_chi_display'],
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
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => 'Lỗi xử lý ngày giờ: ' . $e->getMessage()], 400);
}

// 3. Gọi TuViHelper để tạo lá số (Giữ nguyên)
$tuviHelper = new TuViHelper();
$laSo = $tuviHelper->generate($normalizedData);


// BƯỚC 3: TẠO ẢNH VỚI LOGIC KIỂM TRA CACHE (ĐÃ CẬP NHẬT)
$imageUrl = null;


// Tạo một 'dấu vân tay' duy nhất cho dữ liệu đầu vào
$dataHash = md5(json_encode($validated));
$outputDir = __DIR__ . '/public/storage';

// Hàm render template không còn cần thiết khi dùng GD library

// Hàm tạo ảnh bằng GD Library
function createImageIfNotExists($templateFile, $prefix, $dataHash, $outputDir, $templateData = [])
{
    // ============================
    // 1. Tự động đổi template theo app
    // ============================
    $app = $templateData['app_name'] ?? 'phongthuydaicat';

    // ============================
    // 2. Tạo folder cho từng app
    // ============================
    $appDir = rtrim($outputDir, '/') . '/' . $app;

    if (!is_dir($appDir)) {
        mkdir($appDir, 0775, true);
    }

    // ============================
    // 3. Tạo đường dẫn file
    // ============================
    $fileName = "{$prefix}_{$dataHash}.png";
    $outputPngFile = $appDir . '/' . $fileName;

    // Nếu file đã có → trả về URL ngay
    if (file_exists($outputPngFile)) {
        $GLOBALS['render_time'] = [
            'ms'      => 0,
            'seconds' => 0,
            'created' => false
        ];
        return generate_public_url($app . '/' . $fileName);
    }

    try {
        $start = microtime(true);

        // Tạo ảnh bằng GD Library
        createLasoImageWithGD($outputPngFile, $templateData);

        chmod($outputPngFile, 0755);

        // Thời gian render
        $duration = microtime(true) - $start;

        $GLOBALS['render_time'] = [
            'ms'      => round($duration * 1000, 2),
            'seconds' => round($duration, 2),
            'created' => true
        ];

        // URL đúng
        return generate_public_url($app . '/' . $fileName);
    } catch (Exception $e) {

        send_json_response([
            'success' => true,
            'message' => 'Lấy dữ liệu lá số thành công nhưng không thể tạo ảnh.',
            'error_image_generation' => $e->getMessage(),
        ]);
    }
}

// Hàm helper để vẽ text với font UTF-8
function drawText($image, $size, $x, $y, $text, $color, $fontPath = null)
{
    // Chuyển đổi text sang UTF-8 nếu cần
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    }

    // Nếu có font TTF, sử dụng imagettftext cho UTF-8
    if ($fontPath && file_exists($fontPath)) {
        $fontSize = $size + 6; // Tăng size cho TTF font
        return imagettftext($image, $fontSize, 0, intval($x), intval($y + $fontSize), $color, $fontPath, $text);
    }

    // Fallback cuối cùng: chuyển về ASCII
    $asciiText = transliterateToAscii($text);
    return imagestring($image, $size, intval($x), intval($y), $asciiText, $color);
}

// Hàm chuyển đổi tiếng Việt sang ASCII
function transliterateToAscii($text)
{
    $vietnamese = [
        'à',
        'á',
        'ạ',
        'ả',
        'ã',
        'â',
        'ầ',
        'ấ',
        'ậ',
        'ẩ',
        'ẫ',
        'ă',
        'ằ',
        'ắ',
        'ặ',
        'ẳ',
        'ẵ',
        'è',
        'é',
        'ẹ',
        'ẻ',
        'ẽ',
        'ê',
        'ề',
        'ế',
        'ệ',
        'ể',
        'ễ',
        'ì',
        'í',
        'ị',
        'ỉ',
        'ĩ',
        'ò',
        'ó',
        'ọ',
        'ỏ',
        'õ',
        'ô',
        'ồ',
        'ố',
        'ộ',
        'ổ',
        'ỗ',
        'ơ',
        'ờ',
        'ớ',
        'ợ',
        'ở',
        'ỡ',
        'ù',
        'ú',
        'ụ',
        'ủ',
        'ũ',
        'ư',
        'ừ',
        'ứ',
        'ự',
        'ử',
        'ữ',
        'ỳ',
        'ý',
        'ỵ',
        'ỷ',
        'ỹ',
        'đ',
        'À',
        'Á',
        'Ạ',
        'Ả',
        'Ã',
        'Â',
        'Ầ',
        'Ấ',
        'Ậ',
        'Ẩ',
        'Ẫ',
        'Ă',
        'Ằ',
        'Ắ',
        'Ặ',
        'Ẳ',
        'Ẵ',
        'È',
        'É',
        'Ẹ',
        'Ẻ',
        'Ẽ',
        'Ê',
        'Ề',
        'Ế',
        'Ệ',
        'Ể',
        'Ễ',
        'Ì',
        'Í',
        'Ị',
        'Ỉ',
        'Ĩ',
        'Ò',
        'Ó',
        'Ọ',
        'Ỏ',
        'Õ',
        'Ô',
        'Ồ',
        'Ố',
        'Ộ',
        'Ổ',
        'Ỗ',
        'Ơ',
        'Ờ',
        'Ớ',
        'Ợ',
        'Ở',
        'Ỡ',
        'Ù',
        'Ú',
        'Ụ',
        'Ủ',
        'Ũ',
        'Ư',
        'Ừ',
        'Ứ',
        'Ự',
        'Ử',
        'Ữ',
        'Ỳ',
        'Ý',
        'Ỵ',
        'Ỷ',
        'Ỹ',
        'Đ'
    ];

    $ascii = [
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'u',
        'y',
        'y',
        'y',
        'y',
        'y',
        'd',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'E',
        'E',
        'E',
        'E',
        'E',
        'E',
        'E',
        'E',
        'E',
        'E',
        'E',
        'I',
        'I',
        'I',
        'I',
        'I',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'U',
        'U',
        'U',
        'U',
        'U',
        'U',
        'U',
        'U',
        'U',
        'U',
        'U',
        'Y',
        'Y',
        'Y',
        'Y',
        'Y',
        'D'
    ];

    return str_replace($vietnamese, $ascii, $text);
}

// Hàm tạo ảnh lá số bằng GD Library (load ảnh có sẵn và vẽ content lên)
function createLasoImageWithGD($outputFile, $templateData)
{
    $normalizedData = $templateData['normalizedData'] ?? [];
    $laSo = $templateData['laSo'] ?? [];
    $app_name = $templateData['app_name'] ?? 'phongthuydaicat';

    // Đường dẫn đến ảnh template có sẵn
    $templateImagePath = __DIR__ . '/public/images/la_so_news.png';
// chmod($templateImagePath, 0755);  
    // Kiểm tra xem file template có tồn tại không
    if (!file_exists($templateImagePath)) {
        throw new Exception("Template image not found: $templateImagePath");
    }

    // Load ảnh template có sẵn
    $image = imagecreatefrompng($templateImagePath);
    if ($image === false) {
        throw new Exception("Cannot load template image: $templateImagePath");
    }

    // Lấy kích thước ảnh
    $width = imagesx($image);
    $height = imagesy($image);

    // Font path (thử nhiều font khác nhau)
    $possibleFonts = [
        __DIR__ . '/fonts/arial.ttf',
        __DIR__ . '/fonts/DejaVuSans.ttf',
        __DIR__ . '/fonts/NotoSans-Regular.ttf',
        __DIR__ . '/fonts/Roboto-Regular.ttf',
        // Font hệ thống Windows
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/calibri.ttf',
        'C:/Windows/Fonts/tahoma.ttf',
    ];

    $fontPath = null;
    foreach ($possibleFonts as $font) {
        if (file_exists($font)) {
            $fontPath = $font;
            break;
        }
    }

    // Định nghĩa màu sắc chính xác theo template phonglich CSS
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);

    // Border và background colors
    $borderColor = imagecolorallocate($image, 51, 51, 51);     // #333 - border chính
    $lightgray = imagecolorallocate($image, 204, 204, 204);    // #ccc - border cung
    $footerBorderGray = imagecolorallocate($image, 229, 231, 235); // #e5e7eb - border footer
    $centerBg = imagecolorallocate($image, 243, 229, 171);     // #f3e5ab - màu be/vàng nhạt cho ô trung tâm giống trong hình

    // Màu Ngũ Hành chính xác theo template phonglich CSS
    $kimColor = imagecolorallocate($image, 81, 81, 81);        // #515151 - Kim (Xám)
    $mocColor = imagecolorallocate($image, 0, 98, 29);         // #00621D - Mộc (Xanh lá)
    $thuyColor = imagecolorallocate($image, 1, 95, 136);       // #015F88 - Thủy (Xanh dương)
    $hoaColor = imagecolorallocate($image, 174, 2, 2);         // #AE0202 - Hỏa (Đỏ)
    $thoColor = imagecolorallocate($image, 143, 50, 0);        // #8F3200 - Thổ (Nâu cam)

    // Màu text theo template
    $darkred = imagecolorallocate($image, 116, 0, 16);         // #740010 - tiêu đề chính
    $textGray = imagecolorallocate($image, 75, 85, 99);        // #4b5563 - text phụ
    $orangeText = imagecolorallocate($image, 194, 65, 12);     // #c2410c - lưu tinh

    // Không cần vẽ lại khung - sử dụng ảnh có sẵn
    // Tính toán vị trí các cell dựa trên ảnh template La_so.png
    // Template có margin và grid không bắt đầu từ (0,0)
    $gridMargin = 2; // Giảm margin để content rộng hơn
    $gridWidth = $width - (2 * $gridMargin); // Kích thước grid theo chiều ngang
    $gridHeight = $height - (2 * $gridMargin) - 109; // Trừ 100px cho footer legend
    $cellWidth = intval($gridWidth / 4); // Chiều rộng mỗi cell
    $cellHeight = intval($gridHeight / 4); // Chiều cao mỗi cell - có thể cao hơn chiều rộng
    $startX = $gridMargin;
    $startY = $gridMargin;

    // Thứ tự các cung trong grid 4x4
    $gridOrder = [
        'Tỵ',
        'Ngọ',
        'Mùi',
        'Thân',
        'Thìn',
        null,
        null,
        'Dậu',
        'Mão',
        null,
        null,
        'Tuất',
        'Dần',
        'Sửu',
        'Tý',
        'Hợi',
    ];

    // Map màu cho các chi (theo template phonglich)
    $chiColorMap = [
        'Tý' => $thuyColor,    // ty-class - Thủy
        'Sửu' => $thoColor,    // suu-class - Thổ
        'Dần' => $mocColor,    // dan-class - Mộc
        'Mão' => $mocColor,    // mao-class - Mộc
        'Thìn' => $thoColor,   // thin-class - Thổ
        'Tỵ' => $hoaColor,     // tyj-class - Hỏa
        'Ngọ' => $hoaColor,    // ngo-class - Hỏa
        'Mùi' => $thoColor,    // mui-class - Thổ
        'Thân' => $kimColor,   // than-class - Kim
        'Dậu' => $kimColor,    // dau-class - Kim
        'Tuất' => $thoColor,   // tuat-class - Thổ
        'Hợi' => $thuyColor,   // hoi-class - Thủy
    ];

    // Hàm lấy màu cho sao theo class
    function getSaoColor($className, $kimColor, $mocColor, $thuyColor, $hoaColor, $thoColor, $black)
    {
        if (
            strpos($className, 'sao-thien-co') !== false || strpos($className, 'sao-thien-luong') !== false ||
            strpos($className, 'sao-ltang-mon') !== false || strpos($className, 'sao-tang-mon') !== false ||
            strpos($className, 'sao-tham-lang') !== false || strpos($className, 'sao-n-quang') !== false ||
            strpos($className, 'sao-tuong-quan') !== false || strpos($className, 'sao-ao-hoa') !== false ||
            strpos($className, 'sao-uong-phu') !== false || strpos($className, 'sao-hoa-loc') !== false ||
            strpos($className, 'sao-giai-than') !== false || strpos($className, 'sao-bat-toa') !== false
        ) {
            return $mocColor; // Màu xanh lá - #00621D
        }

        if (
            strpos($className, 'sao-tu-vi') !== false || strpos($className, 'sao-thien-phu') !== false ||
            strpos($className, 'sao-an-quang') !== false || strpos($className, 'sao-thien-uc') !== false ||
            strpos($className, 'sao-thien-phuc') !== false || strpos($className, 'sao-quoc-n') !== false ||
            strpos($className, 'sao-benh-phu') !== false || strpos($className, 'sao-phong-cao') !== false ||
            strpos($className, 'sao-co-than') !== false || strpos($className, 'sao-thien-thuong') !== false ||
            strpos($className, 'sao-thien-tru') !== false || strpos($className, 'sao-thien-tho') !== false ||
            strpos($className, 'sao-thien-tai') !== false || strpos($className, 'sao-ta-phu') !== false ||
            strpos($className, 'sao-loc-ton') !== false || strpos($className, 'sao-thien-quy') !== false ||
            strpos($className, 'sao-ia-giai') !== false || strpos($className, 'sao-phuc-uc') !== false ||
            strpos($className, 'sao-lloc-ton') !== false || strpos($className, 'sao-qua-tu') !== false ||
            strpos($className, 'sao-phuong-cac') !== false
        ) {
            return $thoColor; // Màu nâu cam - #8F3200
        }

        if (
            strpos($className, 'sao-liem-trinh') !== false || strpos($className, 'sao-truc-phu') !== false ||
            strpos($className, 'sao-thai-duong') !== false || strpos($className, 'sao-dieu-khach') !== false ||
            strpos($className, 'sao-thien-viet') !== false || strpos($className, 'sao-ia-khong') !== false ||
            strpos($className, 'sao-ia-kiep') !== false || strpos($className, 'sao-pha-toai') !== false ||
            strpos($className, 'sao-phi-liem') !== false || strpos($className, 'sao-au-quan') !== false ||
            strpos($className, 'sao-lthai-tue') !== false || strpos($className, 'sao-hy-than') !== false ||
            strpos($className, 'sao-thai-tue') !== false || strpos($className, 'sao-thieu-duong') !== false ||
            strpos($className, 'sao-thien-khong') !== false || strpos($className, 'sao-hoa-tinh') !== false ||
            strpos($className, 'sao-thien-ma') !== false || strpos($className, 'sao-ai-hao') !== false ||
            strpos($className, 'sao-linh-tinh') !== false || strpos($className, 'sao-phuc-binh') !== false ||
            strpos($className, 'sao-van-tinh') !== false || strpos($className, 'sao-thien-quan') !== false ||
            strpos($className, 'sao-quan-phu') !== false || strpos($className, 'sao-kiep-sat') !== false ||
            strpos($className, 'sao-nguyet-uc') !== false || strpos($className, 'sao-lthien-ma') !== false ||
            strpos($className, 'sao-tue-pha') !== false || strpos($className, 'sao-tieu-hao') !== false ||
            strpos($className, 'sao-thien-khoi') !== false || strpos($className, 'sao-thien-giai') !== false ||
            strpos($className, 'sao-thien-hinh') !== false || strpos($className, 'sao-ieu-khach') !== false
        ) {
            return $hoaColor; // Màu đỏ - #AE0202
        }

        if (
            strpos($className, 'sao-vu-khuc') !== false || strpos($className, 'sao-that-sat') !== false ||
            strpos($className, 'sao-hoa-cai') !== false || strpos($className, 'sao-a-la') !== false ||
            strpos($className, 'sao-ia-vong') !== false || strpos($className, 'sao-tu-phu') !== false ||
            strpos($className, 'sao-kinh-duong') !== false || strpos($className, 'sao-thai-phu') !== false ||
            strpos($className, 'sao-lbach-ho') !== false || strpos($className, 'sao-bach-ho') !== false ||
            strpos($className, 'sao-la-la') !== false || strpos($className, 'sao-lnvan-tinh') !== false ||
            strpos($className, 'sao-thien-la') !== false || strpos($className, 'sao-lkinh-duong') !== false ||
            strpos($className, 'sao-van-xuong') !== false || strpos($className, 'sao-tau-thu') !== false ||
            strpos($className, 'sao-tuong-tinh') !== false
        ) {
            return $kimColor; // Màu xám - #515151
        }

        // Những sao màu xanh dương (Thủy)
        if (
            strpos($className, 'sao-pha-quan') !== false || strpos($className, 'sao-thai-m') !== false ||
            strpos($className, 'sao-thien-tuong') !== false || strpos($className, 'sao-cu-mon') !== false ||
            strpos($className, 'sao-thien-ong') !== false || strpos($className, 'sao-thien-y') !== false ||
            strpos($className, 'sao-thien-dieu') !== false || strpos($className, 'sao-hong-loan') !== false ||
            strpos($className, 'sao-thieu-m') !== false || strpos($className, 'sao-van-khuc') !== false ||
            strpos($className, 'sao-long-tri') !== false || strpos($className, 'sao-tam-thai') !== false ||
            strpos($className, 'sao-thien-su') !== false || strpos($className, 'sao-luu-ha') !== false ||
            strpos($className, 'sao-lthien-hu') !== false || strpos($className, 'sao-bac-si') !== false ||
            strpos($className, 'sao-thien-khoc') !== false || strpos($className, 'sao-thien-hu') !== false ||
            strpos($className, 'sao-luc-sy') !== false || strpos($className, 'sao-lthien-khoc') !== false ||
            strpos($className, 'sao-thanh-long') !== false || strpos($className, 'sao-long-uc') !== false ||
            strpos($className, 'sao-thai-am') !== false || strpos($className, 'sao-hoa-ky') !== false ||
            strpos($className, 'sao-thien-hy') !== false || strpos($className, 'sao-thien-dong') !== false ||
            strpos($className, 'sao-huu-bat') !== false || strpos($className, 'sao-hoa-quyen') !== false ||
            strpos($className, 'sao-hoa-khoa') !== false || strpos($className, 'sao-dao-hoa') !== false
        ) {
            return $thuyColor; // Màu xanh dương - #015F88
        }

        return $black; // Mặc định màu đen
    }

    // Tính toán Tuần/Triệt markers (tương tự template)
    $tuanPalaces = [];
    $trietPalaces = [];
    if (isset($laSo['palaces']) && is_array($laSo['palaces'])) {
        foreach ($laSo['palaces'] as $chi => $cung) {
            if (isset($cung['special']) && is_array($cung['special'])) {
                foreach ($cung['special'] as $sao) {
                    if ($sao['name'] === 'Tuần') {
                        $tuanPalaces[] = $chi;
                    }
                    if ($sao['name'] === 'Triệt') {
                        $trietPalaces[] = $chi;
                    }
                }
            }
        }
    }

    // Vẽ tam hợp MỆNH - QUAN LỘC - TÀI BẠCH
    $menhChi = null;
    $quanChi = null;
    $taiChi = null;

    foreach ($laSo['palaces'] as $chi => $cung) {
        if (isset($cung['cung_chuc_nang'])) {
            $cungName = $cung['cung_chuc_nang'];
            if (str_contains($cungName, 'MỆNH')) $menhChi = $chi;
            if (str_contains($cungName, 'QUAN LỘC')) $quanChi = $chi;
            if (str_contains($cungName, 'TÀI BẠCH')) $taiChi = $chi;
        }
    }

    $getPalaceAnchorPoint = function ($chi, $gridOrder, $cellWidth, $cellHeight, $startX, $startY) {
        $pos = array_search($chi, $gridOrder);
        if ($pos === false) return null;

        $row = intval($pos / 4);
        $col = $pos % 4;

        $x = $startX + ($col * $cellWidth);
        $y = $startY + ($row * $cellHeight);

        switch ($chi) {
            // Corner Palaces
            case 'Tỵ': return ['x' => $x + $cellWidth, 'y' => $y + $cellHeight]; // Bottom-right anchor
            case 'Thân': return ['x' => $x, 'y' => $y + $cellHeight];           // Bottom-left anchor
            case 'Dần': return ['x' => $x + $cellWidth, 'y' => $y];             // Top-right anchor
            case 'Hợi': return ['x' => $x, 'y' => $y];                         // Top-left anchor

            // Side Palaces (Top Row)
            case 'Ngọ':
            case 'Mùi':
                return ['x' => $x + $cellWidth / 2, 'y' => $y + $cellHeight]; // Midpoint of bottom edge

            // Side Palaces (Bottom Row)
            case 'Sửu':
            case 'Tý':
                return ['x' => $x + $cellWidth / 2, 'y' => $y];             // Midpoint of top edge

            // Side Palaces (Left Column)
            case 'Thìn':
            case 'Mão':
                return ['x' => $x + $cellWidth, 'y' => $y + $cellHeight / 2]; // Midpoint of right edge

            // Side Palaces (Right Column)
            case 'Dậu':
            case 'Tuất':
                return ['x' => $x, 'y' => $y + $cellHeight / 2];             // Midpoint of left edge

            default:
                return null;
        }
    };

    if ($menhChi && $quanChi && $taiChi) {
        $menhAnchor = $getPalaceAnchorPoint($menhChi, $gridOrder, $cellWidth, $cellHeight, $startX, $startY);
        $quanAnchor = $getPalaceAnchorPoint($quanChi, $gridOrder, $cellWidth, $cellHeight, $startX, $startY);
        $taiAnchor = $getPalaceAnchorPoint($taiChi, $gridOrder, $cellWidth, $cellHeight, $startX, $startY);

        if ($menhAnchor && $quanAnchor && $taiAnchor) {
            $lineColor = imagecolorallocatealpha($image, 110, 110, 110, 75); // Gray color with ~50% transparency
            imagesetthickness($image, 2);

            imageline($image, intval($menhAnchor['x']), intval($menhAnchor['y']), intval($quanAnchor['x']), intval($quanAnchor['y']), $lineColor);
            imageline($image, intval($quanAnchor['x']), intval($quanAnchor['y']), intval($taiAnchor['x']), intval($taiAnchor['y']), $lineColor);
            imageline($image, intval($taiAnchor['x']), intval($taiAnchor['y']), intval($menhAnchor['x']), intval($menhAnchor['y']), $lineColor);

            imagesetthickness($image, 1); // Reset to default
        }
    }

    for ($i = 0; $i < 16; $i++) {
        $row = intval($i / 4);
        $col = $i % 4;
        $x = intval($startX + ($col * $cellWidth));
        $y = intval($startY + ($row * $cellHeight));

        $chi = $gridOrder[$i];

        if ($chi === null) {
            // Vẽ ô trung tâm (địa bàn) - không cần vẽ lại khung và background
            if ($row == 1 && $col == 1) {

                // Vẽ thông tin địa bàn theo layout template - căn giữa theo chiều dọc
                $centerX = intval($x + $cellWidth); // Dùng cellWidth cho trung tâm 2x2
                $centerAreaHeight = $cellHeight * 2; // Chiều cao khu vực 2x2
                $totalContentHeight = 280; // Tổng chiều cao nội dung - điều chỉnh để phù hợp với style mẫu
                $centerY = intval($y + ($centerAreaHeight - $totalContentHeight) / 2 - 120); // Căn giữa theo chiều dọc - dịch lên trên 20px

                // Tiêu đề chính: căn giữa và tăng size
                $title = "LÁ SỐ TỬ VI";
                if ($fontPath && file_exists($fontPath)) {
                    $bbox = imagettfbbox(18, 0, $fontPath, $title);
                    $titleWidth = $bbox[4] - $bbox[0];
                } else {
                    $titleWidth = strlen($title) * 14;
                }
                drawText($image, 18, intval($centerX - $titleWidth / 2), $centerY, $title, $darkred, $fontPath);

                // Thông tin cá nhân theo format cột trái-phải như ảnh mẫu
                $infoY = $centerY + 50; // Khoảng cách từ tiêu đề
                $lineHeight = 22; // Line height phù hợp
                $labelX = $centerX - 200; // Vị trí cột trái (dịch sang phải)
                $valueX = $centerX - 40;  // Vị trí cột phải (dịch sang phải)

                // Font sizes
                $infoLabelColor = imagecolorallocate($image, 17, 17, 17);  // #111111
                $infoValueColor = $darkred; // #740010

                // Họ và tên
                $name = $normalizedData['ho_ten'] ?? '';
                $gender = $normalizedData['gioi_tinh'] ?? '';
                drawText($image, 6, $labelX, $infoY, "Họ và tên:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $infoY, "$name ($gender)", $infoValueColor, $fontPath);

                // Định nghĩa vị trí cột can chi và tuổi (thẳng hàng dọc)
                $canChiX = $valueX + 160; // Vị trí can chi (dịch sang phải)
                $tuoiX = $valueX + 160; // Vị trí tuổi (thẳng hàng dọc)

                // Năm
                $year = $normalizedData['duong_lich']['year'] ?? '';
                $lunarCan = $normalizedData['lunar']['can'] ?? '';
                $lunarChi = $normalizedData['lunar']['chi'] ?? '';
                drawText($image, 6, $labelX, $infoY + $lineHeight, "Năm:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $infoY + $lineHeight, $year, $infoValueColor, $fontPath);
                drawText($image, 6, $canChiX, $infoY + $lineHeight, "$lunarCan $lunarChi", $infoValueColor, $fontPath);

                // Tháng
                $month = $normalizedData['duong_lich']['month'] ?? '';
                $lunarMonth = $normalizedData['lunar']['month'] ?? '';
                $canChiThang = $normalizedData['can_chi_thang'] ?? '';
                drawText($image, 6, $labelX, $infoY + $lineHeight * 2, "Tháng:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $infoY + $lineHeight * 2, "$month ($lunarMonth)", $infoValueColor, $fontPath);
                drawText($image, 6, $canChiX, $infoY + $lineHeight * 2, $canChiThang, $infoValueColor, $fontPath);

                // Ngày
                $day = $normalizedData['duong_lich']['day'] ?? '';
                $lunarDay = $normalizedData['lunar']['day'] ?? '';
                $canChiNgay = $normalizedData['can_chi_ngay'] ?? '';
                drawText($image, 6, $labelX, $infoY + $lineHeight * 3, "Ngày:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $infoY + $lineHeight * 3, "$day ($lunarDay)", $infoValueColor, $fontPath);
                drawText($image, 6, $canChiX, $infoY + $lineHeight * 3, $canChiNgay, $infoValueColor, $fontPath);

                // Giờ
                $gioAmSinh = $normalizedData['gio_am_sinh_am'] ?? '';
                $gioAmSinhChi = $normalizedData['gio_am_sinh_chi_am'] ?? '';
                drawText($image, 6, $labelX, $infoY + $lineHeight * 4, "Giờ:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $infoY + $lineHeight * 4, $gioAmSinh, $infoValueColor, $fontPath);
                drawText($image, 6, $canChiX, $infoY + $lineHeight * 4, $gioAmSinhChi, $infoValueColor, $fontPath);

                // Năm xem
                $canChiNamXem = $normalizedData['can_chi_nam_xem'] ?? '';
                $namXem = $normalizedData['nam_xem'] ?? '';
                $tuoi = $normalizedData['tuoi'] ?? '';
                drawText($image, 6, $labelX, $infoY + $lineHeight * 5, "Năm xem:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $infoY + $lineHeight * 5, "$canChiNamXem ($namXem)", $infoValueColor, $fontPath);
                drawText($image, 6, $tuoiX, $infoY + $lineHeight * 5, "$tuoi tuổi", $infoValueColor, $fontPath);

                // Âm Dương - tăng khoảng cách và font size
                $amDuong = $laSo['info']['am_duong'] ?? '';
                $ketLuan = $laSo['info']['ket_luan'][0] ?? '';
                $amDuongY = $infoY + $lineHeight * 6 + 10; // Tăng khoảng cách 10px
                drawText($image, 7, $labelX, $amDuongY, "Âm Dương:", $infoLabelColor, $fontPath);
                drawText($image, 7, $valueX, $amDuongY, $amDuong, $infoValueColor, $fontPath);
                if ($ketLuan) {
                    drawText($image, 6, $valueX, $amDuongY + 18, $ketLuan, $infoValueColor, $fontPath);
                }

                // Bản Mệnh - điều chỉnh vị trí
                $baseY = $amDuongY + 40; // Khoảng cách từ âm dương
                $menh = $laSo['info']['menh'] ?? '';
                drawText($image, 6, $labelX, $baseY, "Bản Mệnh:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $baseY, $menh, $infoValueColor, $fontPath);

                // Cục - tăng khoảng cách và font size như âm dương
                $cuc = $laSo['info']['cuc'] ?? '';
                $cucMenhRelation = $laSo['info']['cuc_menh_relation'] ?? '';
                $cucY = $baseY + $lineHeight + 10; // Tăng khoảng cách 10px
                drawText($image, 7, $labelX, $cucY, "Cục:", $infoLabelColor, $fontPath);
                drawText($image, 7, $valueX, $cucY, $cuc, $infoValueColor, $fontPath);
                if ($cucMenhRelation) {
                    drawText($image, 6, $valueX, $cucY + 18, $cucMenhRelation, $infoValueColor, $fontPath);
                }

                // Chủ Mệnh - điều chỉnh vị trí theo cục
                $chuMenh = $laSo['info']['chu_menh'] ?? '';
                $chuMenhY = $cucY + 40; // Khoảng cách từ cục
                drawText($image, 6, $labelX, $chuMenhY, "Chủ Mệnh:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $chuMenhY, $chuMenh, $infoValueColor, $fontPath);

                // Chủ Thân
                $chuThan = $laSo['info']['chu_than'] ?? '';
                drawText($image, 6, $labelX, $chuMenhY + $lineHeight, "Chủ Thân:", $infoLabelColor, $fontPath);
                drawText($image, 6, $valueX, $chuMenhY + $lineHeight, $chuThan, $infoValueColor, $fontPath);

                // Copyright - tăng padding top, tăng size và căn giữa chính xác
                  if ($app_name === 'phonglich') {
        $copyright = "Bản quyền © PhongLich.com";
    } else {
        $copyright = "Bản quyền © phongthuydaicat.vn";
    }
             
                if ($fontPath && file_exists($fontPath)) {
                    $bbox = imagettfbbox(4, 0, $fontPath, $copyright);
                    $copyrightWidth = $bbox[4] - $bbox[0];
                } else {
                    $copyrightWidth = strlen($copyright) * 8;
                }
                // Căn giữa trong toàn bộ khu vực địa bàn 2x2 và tăng padding top - dịch sang trái chút
                $realCenterX = intval($x + $cellWidth);
                $copyrightY = $chuMenhY + $lineHeight * 2 + 30; // Tăng padding top từ 15 lên 30
                $copyrightX = intval($realCenterX - $copyrightWidth / 2) - 70; // Dịch sang trái 20px
                                drawText($image, 9, $copyrightX, $copyrightY, $copyright, $infoLabelColor, $fontPath);
                
                                // Vẽ vòng tuổi chi (vong_tuoi_chi) bên trong địa bàn
                                foreach ($gridOrder as $chi) {
                                    if ($chi === null) continue;
                
                                    $vongTuoi = $laSo['palaces'][$chi]['vong_tuoi_chi'] ?? '';
                                    if ($vongTuoi) {
                                        $anchorPoint = $getPalaceAnchorPoint($chi, $gridOrder, $cellWidth, $cellHeight, $startX, $startY);
                                        if ($anchorPoint) {
                                            $padding = 10;
                                            $textWidth = 0; $textHeight = 0;
                                            
                                            // Calculate text box size for accurate positioning
                                            if ($fontPath && file_exists($fontPath)) {
                                                $bbox = imagettfbbox(3 + 6, 0, $fontPath, $vongTuoi);
                                                $textWidth = $bbox[2] - $bbox[0];
                                                $textHeight = $bbox[1] - $bbox[7];
                                            } else {
                                                $textWidth = strlen($vongTuoi) * 8; $textHeight = 15;
                                            }
                
                                            $textX = $anchorPoint['x'] -1;
                                            $textY = $anchorPoint['y'];
                
                                            // Adjust position to be *inside* the địa bàn, offset from the anchor point
                                            switch ($chi) {
                                                case 'Tỵ':   // Palace TL, Anchor BR -> Text goes into TR of địa bàn from anchor
                                                    $textX += $padding;
                                                    $textY += $padding;
                                                    break;
                                                case 'Thân': // Palace TR, Anchor BL -> Text goes into TL of địa bàn from anchor
                                                    $textX -= ($textWidth + $padding);
                                                    $textY += $padding;
                                                    break;
                                                case 'Dần':  // Palace BL, Anchor TR -> Text goes into BR of địa bàn from anchor
                                                    $textX += $padding;
                                                    $textY -= ($textHeight + $padding);
                                                    break;
                                                case 'Hợi':  // Palace BR, Anchor TL -> Text goes into BL of địa bàn from anchor
                                                    $textX -= ($textWidth + $padding);
                                                    $textY -= ($textHeight + $padding);
                                                    break;
                                                case 'Ngọ':
                                                case 'Mùi':  // Palace Top, Anchor on Top edge of địa bàn
                                                    $textX -= $textWidth / 2;
                                                    $textY += $padding;
                                                    break;
                                                case 'Sửu':
                                                case 'Tý':   // Palace Bottom, Anchor on Bottom edge of địa bàn
                                                    $textX -= $textWidth / 2;
                                                    $textY -= ($textHeight + $padding);
                                                    break;
                                                case 'Thìn':
                                                case 'Mão':  // Palace Left, Anchor on Left edge of địa bàn
                                                    $textX += $padding;
                                                    $textY -= $textHeight / 2;
                                                    break;
                                                case 'Dậu':
                                                case 'Tuất': // Palace Right, Anchor on Right edge of địa bàn
                                                    $textX -= ($textWidth + $padding);
                                                    $textY -= $textHeight / 2;
                                                    break;
                                            }
                                            drawText($image, 6, $textX, $textY, $vongTuoi, $textGray, $fontPath);
                                        }
                                    }
                                }
                            }
                        } else {
            // Cung bình thường - không cần vẽ border riêng vì đã có grid lines

            if (isset($laSo['palaces'][$chi])) {
                $cung = $laSo['palaces'][$chi];
                $padding = 8; // Tăng padding để content rộng hơn
                $paddingTop = 12;

                // Header section với padding tăng để rộng hơn
                $headerTopPadding = 20; // Tăng top padding
                $headerY = $y + $headerTopPadding;

                // Content padding tăng
                $contentTopPadding = 15;

                // Can và Chi (header-left) với màu theo Ngũ Hành - tăng font
                $canCung = explode('.', $cung['can_chi_cung'] ?? '..')[0];
                $canInitial = mb_substr($canCung, 0, 1, 'UTF-8');
                $chiColor = $chiColorMap[$chi] ?? $black;
                drawText($image, 3, $x + $padding, $headerY, $canInitial . '.' . $chi, $chiColor, $fontPath);

                // Tên cung chức năng (center) - căn giữa hoàn toàn
                $cungName = $cung['cung_chuc_nang'] ?? '';
                // Tính toán chính xác để căn giữa trong toàn bộ cell
                if ($fontPath && file_exists($fontPath)) {
                    // Nếu có TTF font, tính width chính xác
                    $bbox = imagettfbbox(2 + 6, 0, $fontPath, $cungName);
                    $textWidth = $bbox[4] - $bbox[0];
                    $cungNameX = $x + ($cellWidth / 2) - ($textWidth / 2);
                } else {
                    // Fallback với ước tính
                    $cungNameWidth = strlen($cungName) * 7;
                    $cungNameX = $x + ($cellWidth / 2) - ($cungNameWidth / 2);
                }
                drawText($image, 3, intval($cungNameX), $headerY, $cungName, $black, $fontPath);

                // Đại vận (header-right) - tăng font
                $daiVan = $cung['dai_van'] ?? '';
                if ($daiVan) {
                    $daiVanWidth = strlen($daiVan) * 7;
                    drawText($image, 3, intval($x + $cellWidth - $daiVanWidth - $padding), $headerY, $daiVan, $thuyColor, $fontPath);
                }

                // Content section với khoảng cách tăng
                $contentStartY = $headerY + 20; // Tăng khoảng cách sau header
                $chinhTinhY = $contentStartY;

                // Chính tinh với font lớn hơn
                if (!empty($cung['chinh_tinh'])) {
                    foreach ($cung['chinh_tinh'] as $index => $sao) {
                        if ($index >= 2) break; // Giới hạn 2 sao chính
                        $saoName = $sao['name'] ?? '';
                        $bright = !empty($sao['bright']) ? '(' . $sao['bright'] . ')' : '';
                        $saoClass = $sao['class'] ?? '';
                        $saoColor = getSaoColor($saoClass, $kimColor, $mocColor, $thuyColor, $hoaColor, $thoColor, $black);

                        // Center align cho chính tinh - căn giữa chính xác hơn
                        $saoText = $saoName . $bright;
                        $saoTextWidth = strlen($saoText) * 7; // Tăng ước tính width để căn giữa chính xác với font size 4
                        $saoX = $x + ($cellWidth / 2) - ($saoTextWidth / 2);

                        drawText($image, 10, intval($saoX), $chinhTinhY + ($index * 32), $saoText, $saoColor, $fontPath);
                    }
                }

                // Content grid với khoảng cách tăng để rộng hơn
                $contentGridY = $contentStartY + 60; // Tăng space sau chính tinh để không bị sát
                $leftColumnX = $x + $padding;
                $rightColumnX = $x + ($cellWidth / 2) + $padding;
                $lineHeightSmall = 22; // Tăng line height để dễ đọc hơn

                // Left column: Phụ tinh cát
                $leftY = $contentGridY;
                if (!empty($cung['phu_tinh_cat'])) {
                    foreach ($cung['phu_tinh_cat'] as $index => $sao) {
                        if ($index >= 6) break; // Giới hạn để không tràn
                        $saoName = $sao['name'] ?? '';
                        $bright = !empty($sao['bright']) ? '(' . $sao['bright'] . ')' : '';
                        $saoClass = $sao['class'] ?? '';
                        $saoColor = getSaoColor($saoClass, $kimColor, $mocColor, $thuyColor, $hoaColor, $thoColor, $black);
                        drawText($image, 6, $leftColumnX, $leftY + ($index * $lineHeightSmall), $saoName . $bright, $saoColor, $fontPath);
                    }
                }

                // Right column: Phụ tinh sát + Special + Lưu tinh
                $rightY = $contentGridY;
                $rightIndex = 0;

                // Phụ tinh sát
                if (!empty($cung['phu_tinh_sat'])) {
                    foreach ($cung['phu_tinh_sat'] as $sao) {
                        if ($rightIndex >= 8) break;
                        $saoName = $sao['name'] ?? '';
                        $bright = !empty($sao['bright']) ? '(' . $sao['bright'] . ')' : '';
                        $saoClass = $sao['class'] ?? '';
                        $saoColor = getSaoColor($saoClass, $kimColor, $mocColor, $thuyColor, $hoaColor, $thoColor, $black);
                        drawText($image, 6, $rightColumnX, $rightY + ($rightIndex * $lineHeightSmall), $saoName . $bright, $saoColor, $fontPath);
                        $rightIndex++;
                    }
                }

                // Special sao (loại trừ Tuần/Triệt)
                if (!empty($cung['special'])) {
                    foreach ($cung['special'] as $sao) {
                        if (in_array($sao['name'], ['Tuần', 'Triệt'])) continue;
                        if ($rightIndex >= 8) break;
                        $saoName = $sao['name'] ?? '';
                        $bright = !empty($sao['bright']) ? '(' . $sao['bright'] . ')' : '';
                        $saoClass = $sao['class'] ?? '';
                        $saoColor = getSaoColor($saoClass, $kimColor, $mocColor, $thuyColor, $hoaColor, $thoColor, $black);
                        drawText($image, 6, $rightColumnX, $rightY + ($rightIndex * $lineHeightSmall), $saoName . $bright, $saoColor, $fontPath);
                        $rightIndex++;
                    }
                }

                // Lưu tinh (màu italic orange)
                if (!empty($cung['luu'])) {
                    foreach ($cung['luu'] as $sao) {
                        if ($rightIndex >= 8) break;
                        $saoName = $sao['name'] ?? '';
                        $bright = !empty($sao['bright']) ? '(' . $sao['bright'] . ')' : '';
                        $saoClass = $sao['class'] ?? '';
                        $saoColor = getSaoColor($saoClass, $kimColor, $mocColor, $thuyColor, $hoaColor, $thoColor, $orangeText); // #c2410c
                        drawText($image, 6, $rightColumnX, $rightY + ($rightIndex * $lineHeightSmall), $saoName . $bright, $saoColor, $fontPath);
                        $rightIndex++;
                    }
                }

                // Footer theo template (dòng 960-964)
                // .cung-footer { margin-top: auto; padding-top: 0.25rem; border-top: 1px solid #e5e7eb; font-size: 12px; color: #4b5563; }
                $footerY = intval($y + $cellHeight - 35); // Cách bottom 20px

                // Vẽ border-top cho footer
                imageline($image, $x + $padding, $footerY - 5, $x + $cellWidth - $padding, $footerY - 5, $footerBorderGray);

                // Left: DV chức năng - .text-primary (xanh dương)
                $dvChucNang = $cung['dv_chuc_nang'] ?? '';
                drawText($image, 2, $x + $padding, $footerY, $dvChucNang, $thuyColor, $fontPath);

                // Center: Vòng trang sinh - .text-dark (đen, font-weight: 700)
                $vongTrangSinh = $cung['vong_trang_sinh'] ?? '';
                if ($vongTrangSinh) {
                    $vtsWidth = strlen($vongTrangSinh) * 6;
                    $vtsX = $x + ($cellWidth / 2) - ($vtsWidth / 2);
                    drawText($image, 2, intval($vtsX), $footerY, $vongTrangSinh, $black, $fontPath);
                }

                // Right: Tháng âm - .text-success (xanh lá)
                $thangAm = $cung['thang_am'] ?? '';
                if ($thangAm) {
                    $thangAmText = "Th.$thangAm";
                    $thangAmWidth = strlen($thangAmText) * 6;
                    drawText($image, 2, intval($x + $cellWidth - $thangAmWidth - $padding), $footerY, $thangAmText, $mocColor, $fontPath);
                }
            }
        }
    }

    // Vẽ footer legend (không cần vẽ border vì đã có sẵn trong ảnh template)
    $legendY = $height - 100; // Vị trí gần cuối ảnh

    // Dòng 1: Chú thích độ sáng
    $legendText1 = "M: Miếu  V: Vượng  Đ: Đắc  B: Bình hòa  H: Hãm  ĐV: Đại vận  LN: Lưu niên  NL: Lưu nguyệt";
    drawText($image, 5, 10, $legendY + 10, $legendText1, $black, $fontPath);

    // Dòng 2: Màu Ngũ Hành
    $legend2Y = $legendY + 40; // Increased spacing
    drawText($image, 5, 10, $legend2Y, "Ngũ Hành:", $black, $fontPath); // Larger font size

    // Define common box dimensions and offsets
    $boxWidth = 40;
    $boxHeight = 20;
    $boxY1 = $legend2Y - 2;
    $boxY2 = $legend2Y - 2 + $boxHeight;
    $textFontSize = 3;
    $estimatedCharWidth = 7; // Average width for font size 3
    $estimatedTextHeight = 15; // Average height for font size 3

    $currentBoxX = 80;

    // Kim
    $kimText = "Kim"; $kimTextWidth = strlen($kimText) * $estimatedCharWidth;
    $kimBoxX1 = $currentBoxX; $kimBoxY1 = $legend2Y - 2;
    $kimBoxX2 = $kimBoxX1 + $boxWidth; $kimBoxY2 = $kimBoxY1 + $boxHeight;
    imagefilledrectangle($image, $kimBoxX1, $kimBoxY1, $kimBoxX2, $kimBoxY2, $kimColor);
    $kimTextX = intval($kimBoxX1 + ($boxWidth / 2) - ($kimTextWidth / 2) + 2);
    $kimTextY = intval($kimBoxY1 + ($boxHeight / 2) - ($estimatedTextHeight / 2) + 4);
    drawText($image, $textFontSize, $kimTextX, $kimTextY, $kimText, $white, $fontPath);
    $currentBoxX = $kimBoxX2 + 5; // 5px gap

    // Mộc
    $mocText = "Mộc"; $mocTextWidth = strlen($mocText) * $estimatedCharWidth;
    $mocBoxX1 = $currentBoxX; $mocBoxY1 = $legend2Y - 2;
    $mocBoxX2 = $mocBoxX1 + $boxWidth; $mocBoxY2 = $mocBoxY1 + $boxHeight;
    imagefilledrectangle($image, $mocBoxX1, $mocBoxY1, $mocBoxX2, $mocBoxY2, $mocColor);
        $mocTextX = intval($mocBoxX1 + ($boxWidth / 2) - ($mocTextWidth / 2) + 6);
        $mocTextY = intval($mocBoxY1 + ($boxHeight / 2) - ($estimatedTextHeight / 2) + 4);
    drawText($image, $textFontSize, $mocTextX, $mocTextY, $mocText, $white, $fontPath);
    $currentBoxX = $mocBoxX2 + 5;

    // Thủy
    $thuyText = "Thủy"; $thuyTextWidth = strlen($thuyText) * $estimatedCharWidth;
    $thuyBoxX1 = $currentBoxX; $thuyBoxY1 = $legend2Y - 2;
    $thuyBoxX2 = $thuyBoxX1 + $boxWidth; $thuyBoxY2 = $thuyBoxY1 + $boxHeight;
    imagefilledrectangle($image, $thuyBoxX1, $thuyBoxY1, $thuyBoxX2, $thuyBoxY2, $thuyColor);
        $thuyTextX = intval($thuyBoxX1 + ($boxWidth / 2) - ($thuyTextWidth / 2) + 6);
        $thuyTextY = intval($thuyBoxY1 + ($boxHeight / 2) - ($estimatedTextHeight / 2)+4);
    drawText($image, $textFontSize, $thuyTextX, $thuyTextY, $thuyText, $white, $fontPath);
    $currentBoxX = $thuyBoxX2 + 5;

    // Hỏa
    $hoaText = "Hỏa"; $hoaTextWidth = strlen($hoaText) * $estimatedCharWidth;
    $hoaBoxX1 = $currentBoxX; $hoaBoxY1 = $legend2Y - 2;
    $hoaBoxX2 = $hoaBoxX1 + $boxWidth; $hoaBoxY2 = $hoaBoxY1 + $boxHeight;
    imagefilledrectangle($image, $hoaBoxX1, $hoaBoxY1, $hoaBoxX2, $hoaBoxY2, $hoaColor);
        $hoaTextX = intval($hoaBoxX1 + ($boxWidth / 2) - ($hoaTextWidth / 2) + 6);
        $hoaTextY = intval($hoaBoxY1 + ($boxHeight / 2) - ($estimatedTextHeight / 2)+4);
    drawText($image, $textFontSize, $hoaTextX, $hoaTextY, $hoaText, $white, $fontPath);
    $currentBoxX = $hoaBoxX2 + 5;

    // Thổ
    $thoText = "Thổ"; $thoTextWidth = strlen($thoText) * $estimatedCharWidth;
    $thoBoxX1 = $currentBoxX; $thoBoxY1 = $legend2Y - 2;
    $thoBoxX2 = $thoBoxX1 + $boxWidth; $thoBoxY2 = $thoBoxY1 + $boxHeight;
    imagefilledrectangle($image, $thoBoxX1, $thoBoxY1, $thoBoxX2, $thoBoxY2, $thoColor);
    $thoTextX = intval($thoBoxX1 + ($boxWidth / 2) - ($thoTextWidth / 2)+6);
    $thoTextY = intval($thoBoxY1 + ($boxHeight / 2) - ($estimatedTextHeight / 2)+4);
    drawText($image, $textFontSize, $thoTextX, $thoTextY, $thoText, $white, $fontPath);

    // Copyright
    if ($app_name === 'phonglich') {
        drawText($image, 3, 10, $legendY + 70, "Bản quyền © PhongLich.com", $black, $fontPath);
    } else {
        drawText($image, 3, 10, $legendY + 70, "Bản quyền © phongthuydaicat.vn", $black, $fontPath);
    }

    // Tạo borderMap cho Tuần/Triệt markers (theo template)
    $borderMap = [
        'ngo-ty' => ['top' => 23.5, 'left' => 25, 'orientation' => 'vertical'],
        'mui-ngo' => ['top' => 23.5, 'left' => 50, 'orientation' => 'vertical'], //đã sửa
        'mui-than' => ['top' => 23.5, 'left' => 75, 'orientation' => 'vertical'],
        'dau-than' => ['top' => 23.43, 'left' => 87.5, 'orientation' => 'horizontal'],
        'dau-tuat' => ['top' => 50, 'left' => 87.5, 'orientation' => 'horizontal'],
        'hoi-tuat' => ['top' => 70, 'left' => 87.5, 'orientation' => 'horizontal'], //đã sửa
        'hoi-ty' => ['top' => 70, 'left' => 75, 'orientation' => 'vertical'],
        'suu-ty' => ['top' => 70, 'left' => 50, 'orientation' => 'vertical'],
        'dan-suu' => ['top' => 70, 'left' => 25, 'orientation' => 'vertical'],
        'dan-mao' => ['top' => 70, 'left' => 12.5, 'orientation' => 'horizontal'], //đã sửa
        'mao-thin' => ['top' => 47, 'left' => 12.5, 'orientation' => 'horizontal'],
        'thin-ty' => ['top' => 23.5, 'left' => 12.5, 'orientation' => 'horizontal'] //đã sửa
    ];

    // Hàm slugify cho palace names (inline version)
    $slugifyPalace = function ($text) {
        $map = [
            'Tý' => 'ty',
            'Sửu' => 'suu',
            'Dần' => 'dan',
            'Mão' => 'mao',
            'Thìn' => 'thin',
            'Tỵ' => 'ty',
            'Ngọ' => 'ngo',
            'Mùi' => 'mui',
            'Thân' => 'than',
            'Dậu' => 'dau',
            'Tuất' => 'tuat',
            'Hợi' => 'hoi'
        ];
        return $map[$text] ?? strtolower($text);
    };

    // Tạo keys cho Tuần và Triệt
    $tuanKey = null;
    if (count($tuanPalaces) === 2) {
        sort($tuanPalaces);
        $tuanKey = $slugifyPalace($tuanPalaces[0]) . '-' . $slugifyPalace($tuanPalaces[1]);
    }

    $trietKey = null;
    if (count($trietPalaces) === 2) {
        sort($trietPalaces);
        $trietKey = $slugifyPalace($trietPalaces[0]) . '-' . $slugifyPalace($trietPalaces[1]);
    }

    // Tạo finalPositions cho Tuần/Triệt
    $finalPositions = [];
    if ($tuanKey && $trietKey && $tuanKey === $trietKey) {
        // Trường hợp TRÙNG NHAU
        if (isset($borderMap[$tuanKey])) {
            $finalPositions['Triệt - Tuần'] = $borderMap[$tuanKey];
        }
    } else {
        // Trường hợp KHÁC NHAU
        if ($tuanKey && isset($borderMap[$tuanKey])) {
            $finalPositions['Tuần'] = $borderMap[$tuanKey];
        }
        if ($trietKey && isset($borderMap[$trietKey])) {
            $finalPositions['Triệt'] = $borderMap[$trietKey];
        }
    }

    // Vẽ Tuần/Triệt markers với vị trí cải thiện
    foreach ($finalPositions as $saoName => $position) {
        $markerX = intval(($position['left'] / 100) * $width);
        $markerY = intval(($position['top'] / 100) * $height);

        // Kích thước marker lớn hơn và nổi bật hơn
        $markerWidth = 70;  // Tăng từ 60 lên 80
        $markerHeight = 25; // Tăng từ 25 lên 30

        // Điều chỉnh vị trí dựa trên orientation để đặt chính giữa giữa 2 cung
        if ($position['orientation'] === 'vertical') {
            // Đặt marker giữa 2 cung theo chiều dọc
            $markerX = intval($markerX - $markerWidth / 2);
            $markerY = intval($markerY - $markerHeight / 2);
        } else {
            // Đặt marker giữa 2 cung theo chiều ngang
            $markerX = intval($markerX - $markerWidth / 2);
            $markerY = intval($markerY - $markerHeight / 2);
        }

        // Vẽ background với màu nổi bật hơn cho Triệt
        $markerBgColor = (strpos($saoName, 'Triệt') !== false) ? $black : $black;
        imagefilledrectangle(
            $image,
            $markerX,
            $markerY,
            intval($markerX + $markerWidth),
            intval($markerY + $markerHeight),
            $markerBgColor
        );

        // Vẽ border dày hơn
        for ($borderThick = 0; $borderThick < 2; $borderThick++) {
            imagerectangle(
                $image,
                $markerX - $borderThick,
                $markerY - $borderThick,
                intval($markerX + $markerWidth) + $borderThick,
                intval($markerY + $markerHeight) + $borderThick,
                $borderColor
            );
        }

        // Vẽ text màu trắng với font lớn hơn
        $textX = intval($markerX + $markerWidth / 2 - (strlen($saoName) * 2));
        $textY = intval($markerY + $markerHeight / 2 - 2);
        drawText($image, 3, $textX, $textY, $saoName, $white, $fontPath);
    }

    // Lưu ảnh
    if (!imagepng($image, $outputFile)) {
        throw new Exception("Không thể lưu file ảnh: $outputFile");
    }

    // Giải phóng bộ nhớ
    imagedestroy($image);
}


// --- TẠO ẢNH CHÍNH ---
$imageUrl = createImageIfNotExists(
    __DIR__ . '/templates/laso_display.phtml',
    'laso',
    $dataHash,
    $outputDir,
    ['normalizedData' => $normalizedData, 'laSo' => $laSo, 'app_name' => $validated['app_name']]
);
$time = $GLOBALS['render_time'];


// --- DỌN DẸP FILE CŨ ---
cleanupOldImages();

// --- TRẢ KẾT QUẢ ---
send_json_response([
    'success' => true,
    'message' => 'Tạo lá số thành công.',
    'create' => $time['created'] ? 'có tạo mới' : 'đã tồn tại',
    'time_create' => "Thời gian: {$time['ms']} ms ({$time['seconds']}s)",
    'data' => [
        'input_summary' => $normalizedData,
        'laso_details' => $laSo,
        'image_url' => $imageUrl,
    ]
], 200);
