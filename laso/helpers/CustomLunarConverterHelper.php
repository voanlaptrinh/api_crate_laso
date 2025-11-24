<?php
// file: helpers/CustomLunarConverterHelper.php

/**
 * Class CustomLunarConverterHelper
 * Chuyển đổi ngày Dương lịch sang Âm lịch bằng PHP thuần.
 * Logic tính toán được giữ nguyên từ phiên bản gốc.
 */
class CustomLunarConverterHelper
{
    // --- HÀM CHÍNH ĐỂ API GỌI ---

    /**
     * Chuyển đổi một đối tượng DateTime (Dương lịch) sang mảng thông tin Âm lịch.
     * Đây là hàm DUY NHẤT mà file API cần gọi.
     *
     * @param DateTime $datetime Đối tượng DateTime chứa ngày giờ dương lịch.
     * @return array Mảng thông tin âm lịch.
     */
    public static function fromGregorian(\DateTime $datetime): array
    {
        // Sử dụng phương thức format() của DateTime thay vì thuộc tính của Carbon
        $dd = (int)$datetime->format('j');
        $mm = (int)$datetime->format('n');
        $yy = (int)$datetime->format('Y');
        $hour = (int)$datetime->format('G'); // 'G' = 24-hour format without leading zeros (0-23)

        // Múi giờ Việt Nam là +7
        $timeZone = 7.0;

        // Gọi hàm tính toán chính để lấy ngày tháng năm âm
        list($lunarDay, $lunarMonth, $lunarYear, $lunarLeap) = self::convertSolarToLunar($dd, $mm, $yy, $timeZone);

        // Xử lý trường hợp giờ Tý (23:00 - 23:59), giờ này thuộc về ngày âm của hôm sau.
        if ($hour == 23) {
            // Sử dụng clone và modify() để tạo ngày hôm sau, thay cho copy()->addDay() của Carbon
            $nextDay = (clone $datetime)->modify('+1 day');
            list($lunarDay, $lunarMonth, $lunarYear, $lunarLeap) = self::convertSolarToLunar(
                (int)$nextDay->format('j'),
                (int)$nextDay->format('n'),
                (int)$nextDay->format('Y'),
                $timeZone
            );
        }
        
        $chiGio = self::getChiGio($hour);
        $chiGioDisplay = self::getChiGioDisplay($hour);

        return [
            'year'     => $lunarYear,
            'month'    => $lunarMonth,
            'day'      => $lunarDay,
            'can'      => self::getCan($lunarYear), // Can/Chi được tính dựa trên NĂM ÂM LỊCH đã tính được
            'chi'      => self::getChi($lunarYear),
            'hour_chi' => $chiGio,
            'hour_chi_display' => $chiGioDisplay,
            'is_leap'  => ($lunarLeap == 1),
        ];
    }

    /**
     * Lấy tên Chi của giờ sinh.
     *
     * @param int $hour
     * @return string
     */
    private static function getChiGio(int $hour): string
    {
        $chi = ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'];

        // Trường hợp đặc biệt: 23h vẫn thuộc giờ Tý của ngày hôm sau theo cách tính canh giờ.
        if ($hour == 23) {
            return $chi[0]; // Tý
        }

        // Công thức tính cho các giờ còn lại (0h - 22h)
        return $chi[floor(($hour + 1) / 2)];
    }

    /**
     * Lấy tên Chi của giờ sinh có phân biệt Tý sớm/muộn cho hiển thị.
     *
     * @param int $hour
     * @return string
     */
    public static function getChiGioDisplay(int $hour): string
    {
        $chi = ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'];

        // Trường hợp đặc biệt cho giờ Tý
        if ($hour == 23) {
            return 'Tý sớm'; // 23:00-23:59
        } elseif ($hour == 0) {
            return 'Tý muộn'; // 00:00-00:59
        }

        // Công thức tính cho các giờ còn lại (1h - 22h)
        return $chi[floor(($hour + 1) / 2)];
    }

  

    // --- CÁC HÀM TÍNH TOÁN PHỤ TRỢ (PRIVATE) - GIỮ NGUYÊN KHÔNG THAY ĐỔI ---

    public static function convertSolarToLunar($dd, $mm, $yy, $timeZone)
    {
        $dayNumber = self::jdFromDate($dd, $mm, $yy);
        $k = floor(($dayNumber - 2415021.076998695) / 29.530588853);
        $monthStart = self::getNewMoonDay($k + 1, $timeZone);
        if ($monthStart > $dayNumber) {
            $monthStart = self::getNewMoonDay($k, $timeZone);
        }
        $a11 = self::getLunarMonth11($yy, $timeZone);
        $b11 = $a11;
        if ($a11 >= $monthStart) {
            $lunarYear = $yy;
            $a11 = self::getLunarMonth11($yy - 1, $timeZone);
        } else {
            $lunarYear = $yy + 1;
            $b11 = self::getLunarMonth11($yy + 1, $timeZone);
        }
        $lunarDay = $dayNumber - $monthStart + 1;
        $diff = floor(($monthStart - $a11) / 29);
        $lunarLeap = 0;
        $lunarMonth = $diff + 11;
        if ($b11 - $a11 > 365) {
            $leapMonthDiff = self::getLeapMonthOffset($a11, $timeZone);
            if ($diff >= $leapMonthDiff) {
                $lunarMonth = $diff + 10;
                if ($diff == $leapMonthDiff) {
                    $lunarLeap = 1;
                }
            }
        }
        if ($lunarMonth > 12) {
            $lunarMonth = $lunarMonth - 12;
        }
        if ($lunarMonth >= 11 && $diff < 4) {
            $lunarYear -= 1;
        }
        return [(int)$lunarDay, (int)$lunarMonth, (int)$lunarYear, (int)$lunarLeap];
    }

    private static function jdFromDate($dd, $mm, $yy)
    {
        $a = floor((14 - $mm) / 12);
        $y = $yy + 4800 - $a;
        $m = $mm + 12 * $a - 3;
        $jd = $dd + floor((153 * $m + 2) / 5) + 365 * $y + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;
        if ($jd < 2299161) {
            $jd = $dd + floor((153 * $m + 2) / 5) + 365 * $y + floor($y / 4) - 32083;
        }
        return $jd;
    }

    private static function getNewMoonDay($k, $timeZone)
    {
        $T = $k / 1236.85;
        $T2 = $T * $T;
        $T3 = $T2 * $T;
        $dr = M_PI / 180;
        $Jd1 = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $T2 - 0.000000155 * $T3;
        $Jd1 += 0.00033 * sin((166.56 + 132.87 * $T - 0.009173 * $T2) * $dr);
        $M = 359.2242 + 29.10535608 * $k - 0.0000333 * $T2 - 0.00000347 * $T3;
        $Mpr = 306.0253 + 385.81691806 * $k + 0.0107306 * $T2 + 0.00001236 * $T3;
        $F = 21.2964 + 390.67050646 * $k - 0.0016528 * $T2 - 0.00000239 * $T3;
        $C1 = (0.1734 - 0.000393 * $T) * sin($M * $dr) + 0.0021 * sin(2 * $dr * $M);
        $C1 += -0.4068 * sin($Mpr * $dr) + 0.0161 * sin($dr * 2 * $Mpr);
        $C1 += -0.0004 * sin($dr * 3 * $Mpr);
        $C1 += 0.0104 * sin($dr * 2 * $F) - 0.0051 * sin($dr * ($M + $Mpr));
        $C1 += -0.0074 * sin($dr * ($M - $Mpr)) + 0.0004 * sin($dr * (2 * $F + $M));
        $C1 += -0.0004 * sin($dr * (2 * $F - $M)) - 0.0006 * sin($dr * (2 * $F + $Mpr));
        $C1 += 0.0010 * sin($dr * (2 * $F - $Mpr)) + 0.0005 * sin($dr * (2 * $Mpr + $M));
        if ($T < -11) {
            $deltat = 0.001 + 0.000839 * $T + 0.0002261 * $T2 - 0.00000845 * $T3 - 0.000000081 * $T * $T3;
        } else {
            $deltat = -0.000278 + 0.000265 * $T + 0.000262 * $T2;
        }
        $JdNew = $Jd1 + $C1 - $deltat;
        return floor($JdNew + 0.5 + $timeZone / 24);
    }

    private static function getLunarMonth11($yy, $timeZone)
    {
        $off = self::jdFromDate(31, 12, $yy) - 2415021;
        $k = floor($off / 29.530588853);
        $nm = self::getNewMoonDay($k, $timeZone);
        $sunLong = self::getSunLongitude($nm, $timeZone);
        if ($sunLong >= 9) {
            $nm = self::getNewMoonDay($k - 1, $timeZone);
        }
        return $nm;
    }

    private static function getLeapMonthOffset($a11, $timeZone)
    {
        $k = floor(($a11 - 2415021.076998695) / 29.530588853 + 0.5);
        $last = 0;
        $i = 1;
        $arc = self::getSunLongitude(self::getNewMoonDay($k + $i, $timeZone), $timeZone);
        do {
            $last = $arc;
            $i++;
            $arc = self::getSunLongitude(self::getNewMoonDay($k + $i, $timeZone), $timeZone);
        } while ($arc != $last && $i < 14);
        return $i - 1;
    }

    private static function getSunLongitude($jdn, $timeZone)
    {
        $T = ($jdn - 2451545.5 - $timeZone / 24) / 36525;
        $T2 = $T * $T;
        $dr = M_PI / 180;
        $M = 357.52910 + 35999.05030 * $T - 0.0001559 * $T2 - 0.00000048 * $T * $T2;
        $L0 = 280.46645 + 36000.76983 * $T + 0.0003032 * $T2;
        $DL = (1.914600 - 0.004817 * $T - 0.000014 * $T2) * sin($dr * $M);
        $DL += (0.019993 - 0.000101 * $T) * sin($dr * 2 * $M) + 0.000290 * sin($dr * 3 * $M);
        $L = $L0 + $DL;
        $omega = 125.04 - 1934.136 * $T;
        $L = $L - 0.00569 - 0.00478 * sin($omega * $dr);
        $L = $L * $dr;
        $L = $L - M_PI * 2 * (floor($L / (M_PI * 2)));
        return floor($L / M_PI * 6);
    }
    
    private const THIEN_CAN = ['Canh', 'Tân', 'Nhâm', 'Quý', 'Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ'];
    private const DIA_CHI   = ['Thân', 'Dậu', 'Tuất', 'Hợi', 'Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi'];

    /**
     * Lấy Thiên Can của một năm.
     * @param int $year Năm (âm lịch hoặc dương lịch tùy theo hệ thống).
     * @return string Trả về tên Thiên Can.
     */
    public static function getCan(int $year): string
    {
        return self::THIEN_CAN[$year % 10];
    }

    /**
     * Lấy Địa Chi của một năm.
     * @param int $year Năm (âm lịch hoặc dương lịch tùy theo hệ thống).
     * @return string Trả về tên Địa Chi.
     */
    public static function getChi(int $year): string
    {
        return self::DIA_CHI[$year % 12];
    }
}