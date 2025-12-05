<?php
/**
 * File: cleanup_old_files.php
 * Xóa các file ảnh của những tháng trước đó
 */

/**
 * Xóa các file ảnh của những tháng trước đó
 * @param int $monthsToKeep Số tháng muốn giữ lại (mặc định = 1, chỉ giữ tháng hiện tại)
 * @param bool $dryRun Chỉ kiểm tra không xóa thật (mặc định = false)
 * @return array Thống kê kết quả
 */
function cleanupOldMonthFolders($monthsToKeep = 1, $dryRun = false)
{
    $storageDir = __DIR__ . '/public/storage';
    $stats = [
        'folders_checked' => 0,
        'folders_deleted' => 0,
        'files_deleted' => 0,
        'deleted_folders' => [],
        'errors' => []
    ];

    if (!is_dir($storageDir)) {
        $stats['errors'][] = "Thư mục storage không tồn tại: $storageDir";
        return $stats;
    }

    // Lấy ngày tháng năm hiện tại
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('n');

    // Tính toán tháng cắt (tháng nào trở về trước sẽ bị xóa)
    $cutoffMonth = $currentMonth - $monthsToKeep + 1;
    $cutoffYear = $currentYear;

    if ($cutoffMonth <= 0) {
        $cutoffYear--;
        $cutoffMonth = 12 + $cutoffMonth;
    }

    // Duyệt qua các app folders (phonglich, phongthuydaicat, etc.)
    $appFolders = glob($storageDir . '/*', GLOB_ONLYDIR);

    foreach ($appFolders as $appFolder) {
        $appName = basename($appFolder);

        // Duyệt qua các thư mục ngày_tháng_năm
        $dateFolders = glob($appFolder . '/*', GLOB_ONLYDIR);

        foreach ($dateFolders as $dateFolder) {
            $stats['folders_checked']++;
            $folderName = basename($dateFolder);

            // Parse folder name: day_month_year
            if (preg_match('/^(\d+)_(\d+)_(\d+)$/', $folderName, $matches)) {
                $folderDay = (int)$matches[1];
                $folderMonth = (int)$matches[2];
                $folderYear = (int)$matches[3];

                $shouldDelete = false;

                // Logic xóa: tháng/năm cũ hơn cutoff
                if ($folderYear < $cutoffYear) {
                    $shouldDelete = true;
                } elseif ($folderYear == $cutoffYear && $folderMonth < $cutoffMonth) {
                    $shouldDelete = true;
                }

                if ($shouldDelete) {
                    $stats['deleted_folders'][] = "$appName/$folderName";

                    if (!$dryRun) {
                        // Đếm files trước khi xóa
                        $files = glob($dateFolder . '/*.png');
                        $stats['files_deleted'] += count($files);

                        // Xóa toàn bộ thư mục và files
                        if (deleteDirectory($dateFolder)) {
                            $stats['folders_deleted']++;
                        } else {
                            $stats['errors'][] = "Không thể xóa thư mục: $appName/$folderName";
                        }
                    } else {
                        // Dry run - chỉ đếm files
                        $files = glob($dateFolder . '/*.png');
                        $stats['files_deleted'] += count($files);
                        $stats['folders_deleted']++;
                    }
                }
            }
        }
    }

    return $stats;
}

/**
 * Hàm helper để xóa thư mục và tất cả nội dung bên trong
 * @param string $dir Đường dẫn thư mục
 * @return bool
 */
function deleteDirectory($dir)
{
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            deleteDirectory($filePath);
        } else {
            @unlink($filePath);
        }
    }

    return @rmdir($dir);
}

// --- CHẠY TRỰC TIẾP ---
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header("Content-Type: application/json; charset=UTF-8");

    $monthsToKeep = (int)($_GET['months'] ?? 1);

    // Mặc định là xóa luôn
    $result = cleanupOldMonthFolders($monthsToKeep, false);

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>