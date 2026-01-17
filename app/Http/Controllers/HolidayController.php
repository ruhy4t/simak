<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    /**
     * Store a new holiday
     */
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'description' => 'required|string|max:255',
        ], [
            'date.required' => 'Tanggal wajib diisi.',
            'date.unique' => 'Tanggal ini sudah terdaftar.',
            'description.required' => 'Keterangan wajib diisi.',
        ]);

        Holiday::create([
            'date' => $request->date,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    /**
     * Update a holiday
     */
    public function update(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'date' => 'required|date|unique:holidays,date,' . $id,
            'description' => 'required|string|max:255',
        ], [
            'date.required' => 'Tanggal wajib diisi.',
            'date.unique' => 'Tanggal ini sudah terdaftar.',
            'description.required' => 'Keterangan wajib diisi.',
        ]);

        $holiday->update([
            'date' => $request->date,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    /**
     * Delete a holiday
     */
    public function destroy($id)
    {
        if (!in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}