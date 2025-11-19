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
// BƯỚC 2: SỬ DỤNG CLASS BROWSERSHOT
use Spatie\Browsershot\Browsershot;

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

// Hàm render template trong scope riêng (isolation)
function renderTemplateIsolated($templatePath, $variables = [])
{
    return (function ($__templatePath, $__vars) {
        extract($__vars, EXTR_SKIP);
        ob_start();
        include $__templatePath;
        return ob_get_clean();
    })($templatePath, $variables);
}

// Hàm tạo ảnh (dùng chung)
function createImageIfNotExists($templateFile, $prefix, $dataHash, $outputDir, $templateData = [])
{
    $fileName = "{$prefix}_{$dataHash}.png";
    $outputPngFile = $outputDir . '/' . $fileName;

    if (file_exists($outputPngFile)) {
        return generate_public_url($fileName);
    }

    try {
        // Render HTML từ template trong scope tách biệt
        $html = renderTemplateIsolated($templateFile, $templateData);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        Browsershot::html($html)
            ->timeout(60000)
            ->windowSize(1100, 1350)
            ->setNodeModulePath(__DIR__ . '/../')
            ->setChromePath('/var/www/.cache/puppeteer/chrome/linux-139.0.7258.68/chrome-linux64/chrome')
            ->addChromiumArguments(['no-sandbox', 'disable-setuid-sandbox'])
            ->save($outputPngFile);

        if (!file_exists($outputPngFile)) {
            throw new Exception("Không thể tạo file ảnh từ template {$templateFile}");
        }

        chmod($outputPngFile, 0755);
        return generate_public_url($fileName);
    } catch (Exception $e) {
        send_json_response([
        'success' => true,
        'message' => 'Lấy dữ liệu lá số thành công nhưng không thể tạo ảnh.',
        'error_image_generation' => $e->getMessage(),
    ]);
    }
}

// --- TẠO ẢNH CHÍNH ---
$imageUrl = createImageIfNotExists(
    __DIR__ . '/templates/laso_display.phtml',
    'laso',
    $dataHash,
    $outputDir,
    ['normalizedData' => $normalizedData, 'laSo' => $laSo]
);



// --- DỌN DẸP FILE CŨ ---
cleanupOldImages();

// --- TRẢ KẾT QUẢ ---
send_json_response([
    'success' => true,
    'message' => 'Tạo lá số thành công.',
    'data' => [
        'input_summary' => $normalizedData,
        'laso_details' => $laSo,
        'image_url' => $imageUrl,
    ]
], 200);


