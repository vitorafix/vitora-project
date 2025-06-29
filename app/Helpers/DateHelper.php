<?php

namespace App\Helpers;

use DateTime;

/**
 * Helper class for date conversions, specifically to Persian (Jalali) calendar.
 * کلاس کمکی برای تبدیل تاریخ، به ویژه به تقویم شمسی (جلالی).
 */
class DateHelper
{
    /**
     * Converts a Gregorian date (DateTime object or string) to a Persian (Jalali) date string.
     * تاریخ میلادی (شیء DateTime یا رشته) را به رشته تاریخ شمسی (جلالی) تبدیل می‌کند.
     *
     * @param string|DateTime $gregorianDate - The Gregorian date to convert.
     * @param string $format - The desired format for the Jalali date (e.g., 'Y/m/d', 'Y-m-d H:i:s').
     * @return string
     */
    public static function toJalali($gregorianDate, $format = 'Y/m/d')
    {
        // If the input is not a DateTime object, try to create one
        if (!($gregorianDate instanceof DateTime)) {
            try {
                $gregorianDate = new DateTime($gregorianDate);
            } catch (\Exception $e) {
                // Log the error or return an empty string/default value
                \Log::error("Invalid date format provided to toJalali: " . $e->getMessage());
                return 'تاریخ نامعتبر';
            }
        }

        $g_y = (int) $gregorianDate->format('Y');
        $g_m = (int) $gregorianDate->format('m');
        $g_d = (int) $gregorianDate->format('d');
        $g_h = (int) $gregorianDate->format('H');
        $g_i = (int) $gregorianDate->format('i');
        $g_s = (int) $gregorianDate->format('s');

        // Gregorian to Jalali conversion logic
        // This is a common algorithm for Gregorian to Jalali conversion
        $g_days_in_month = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [0, 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        // Check for leap year in Gregorian
        if ($g_y % 4 == 0 && ($g_y % 100 != 0 || $g_y % 400 == 0)) {
            $g_days_in_month[2] = 29;
        }

        $gy2 = $g_y - 1600;
        $gm2 = $g_m - 1;
        $gd2 = $g_d - 1;

        $g_day_no = 365 * $gy2 + floor(($gy2 + 3) / 4) - floor(($gy2 + 99) / 100) + floor(($gy2 + 399) / 400);
        for ($i = 0; $i < $gm2; $i++) {
            $g_day_no += $g_days_in_month[$i + 1];
        }
        $g_day_no += $gd2;

        $j_day_no = $g_day_no - 79;

        $j_np = floor($j_day_no / 12053);
        $j_day_no %= 12053;

        $j_gy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $j_gy += floor(($j_day_no - 1) / 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i + 1]; $i++) {
            $j_day_no -= $j_days_in_month[$i + 1];
        }
        $j_m = $i + 1;
        $j_d = $j_day_no + 1;

        // --- End of Gregorian to Jalali conversion logic ---

        $replace = [
            'Y' => $j_gy, // Jalali Year
            'y' => substr($j_gy, -2), // Last two digits of Jalali Year
            'm' => sprintf('%02d', $j_m), // Jalali Month (with leading zero)
            'M' => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'][$j_m - 1], // Jalali Month Name
            'd' => sprintf('%02d', $j_d), // Jalali Day (with leading zero)
            'H' => sprintf('%02d', $g_h), // Hour (Gregorian, assuming time is not converted)
            'i' => sprintf('%02d', $g_i), // Minute (Gregorian, assuming time is not converted)
            's' => sprintf('%02d', $g_s)  // Second (Gregorian, assuming time is not converted)
        ];

        $output = '';
        $chars = str_split($format);
        foreach ($chars as $char) {
            if (isset($replace[$char])) {
                $output .= $replace[$char];
            } else {
                $output .= $char;
            }
        }

        return $output;
    }
}
