<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;
use App\Models\Holiday;

/**
 * CheckWorkingDay Middleware
 * 
 * Restricts mutation submissions based on:
 * - Holidays (from holidays table)
 * - Allowed days (configurable via Settings)
 * - Working hours (if enabled in Settings)
 */
class CheckWorkingDay
{
    public function handle(Request $request, Closure $next): Response
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

        // Check if today is a holiday
        if (Holiday::isHoliday($today)) {
            $holidayName = Holiday::getDescription($today);
            return back()
                ->with('error', "Layanan tidak tersedia hari ini: {$holidayName}.")
                ->withInput();
        }

        // Get allowed days from settings
        $allowedDays = Setting::getAllowedDays();

        // Day names for error message
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];

        // Check if current day is allowed
        if (!in_array($dayOfWeek, $allowedDays)) {
            $allowedDayNames = array_map(fn($d) => $dayNames[$d], $allowedDays);
            $message = 'Pengajuan mutasi hanya dapat dilakukan pada hari: ' . implode(', ', $allowedDayNames) . '.';

            // Add hour info if enabled
            if (Setting::isHourRestrictionEnabled()) {
                $startTime = Setting::get('working_hours_start', '08:00');
                $endTime = Setting::get('working_hours_end', '16:00');
                $message .= " (Jam {$startTime} - {$endTime})";
            }

            return back()->with('error', $message)->withInput();
        }

        // Check working hours if enabled
        if (Setting::isHourRestrictionEnabled()) {
            $startTime = Setting::get('working_hours_start', '08:00');
            $endTime = Setting::get('working_hours_end', '16:00');
            $currentTime = $now->format('H:i');

            if ($currentTime < $startTime || $currentTime > $endTime) {
                return back()
                    ->with('error', "Pengajuan mutasi hanya dapat dilakukan pada jam {$startTime} - {$endTime}.")
                    ->withInput();
            }
        }

        return $next($request);
    }
}
