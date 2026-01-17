<<<<<<< HEAD
<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $allowedDays = Setting::getAllowedDays();
        $enableHourRestriction = Setting::get('enable_hour_restriction', false);
        $workingHoursStart = Setting::get('working_hours_start', '08:00');
        $workingHoursEnd = Setting::get('working_hours_end', '16:00');
        $holidays = Holiday::orderBy('date', 'asc')->paginate(10);

        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return view('settings.index', compact(
            'allowedDays',
            'enableHourRestriction',
            'workingHoursStart',
            'workingHoursEnd',
            'dayNames',
            'holidays'
        ));
    }

    public function update(Request $request)
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // Get selected days (array of day numbers)
        $selectedDays = $request->input('allowed_days', []);
        Setting::set('allowed_days', $selectedDays);

        // Hour restriction toggle
        Setting::set('enable_hour_restriction', $request->has('enable_hour_restriction'));

        // Working hours
        Setting::set('working_hours_start', $request->input('working_hours_start', '08:00'));
        Setting::set('working_hours_end', $request->input('working_hours_end', '16:00'));

        Setting::clearCache();

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }
}
=======
<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $allowedDays = Setting::getAllowedDays();
        $enableHourRestriction = Setting::get('enable_hour_restriction', false);
        $workingHoursStart = Setting::get('working_hours_start', '08:00');
        $workingHoursEnd = Setting::get('working_hours_end', '16:00');
        $holidays = Holiday::orderBy('date', 'asc')->paginate(10);

        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return view('settings.index', compact(
            'allowedDays',
            'enableHourRestriction',
            'workingHoursStart',
            'workingHoursEnd',
            'dayNames',
            'holidays'
        ));
    }

    public function update(Request $request)
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // Get selected days (array of day numbers)
        $selectedDays = $request->input('allowed_days', []);
        Setting::set('allowed_days', $selectedDays);

        // Hour restriction toggle
        Setting::set('enable_hour_restriction', $request->has('enable_hour_restriction'));

        // Working hours
        Setting::set('working_hours_start', $request->input('working_hours_start', '08:00'));
        Setting::set('working_hours_end', $request->input('working_hours_end', '16:00'));

        Setting::clearCache();

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
