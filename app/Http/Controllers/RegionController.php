<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        try {
            // List provinces with search and pagination
            $query = DB::table('ref_provinsi')->orderBy('nama');

            if ($request->has('search') && $request->filled('search')) {
                $search = $request->search;
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            }

            // Check if table exists safely
            try {
                if (DB::getSchemaBuilder()->hasTable('ref_provinsi')) {
                    $provinces = $query->paginate(10);
                } else {
                    throw new \Exception("Table not found");
                }
            } catch (\Throwable $e) {
                // Return empty paginator if table doesn't exist or other DB error
                $provinces = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
                $provinces->setPath($request->url());
            }

            return view('admin.regions.index', compact('provinces'));

        } catch (\Throwable $e) {
            // Debugging output for Verification
            return response('Error in RegionController: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        // Show regencies for a province
        $province = DB::table('ref_provinsi')->where('id', $id)->first();
        if (!$province)
            abort(404);

        $query = DB::table('ref_kabupaten')
            ->where('province_id', $id)
            ->orderBy('nama');

        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $regencies = $query->paginate(10);

        return view('admin.regions.show', compact('province', 'regencies'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type'); // provinsi, kabupaten, kecamatan, kelurahan

        $request->validate([
            'id' => 'required|numeric',
            'nama' => 'required|string|max:255',
            'type' => 'required|in:provinsi,kabupaten,kecamatan,kelurahan',
            'parent_id' => 'required_unless:type,provinsi'
        ]);

        try {
            if ($type === 'provinsi') {
                DB::table('ref_provinsi')->insert([
                    'id' => $request->id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($type === 'kabupaten') {
                DB::table('ref_kabupaten')->insert([
                    'id' => $request->id,
                    'province_id' => $request->parent_id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($type === 'kecamatan') {
                DB::table('ref_kecamatan')->insert([
                    'id' => $request->id,
                    'regency_id' => $request->parent_id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($type === 'kelurahan') {
                DB::table('ref_kelurahan')->insert([
                    'id' => $request->id,
                    'district_id' => $request->parent_id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return back()->with('success', 'Data berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');

        try {
            if ($type === 'provinsi')
                DB::table('ref_provinsi')->where('id', $id)->delete();
            elseif ($type === 'kabupaten')
                DB::table('ref_kabupaten')->where('id', $id)->delete();
            elseif ($type === 'kecamatan')
                DB::table('ref_kecamatan')->where('id', $id)->delete();
            elseif ($type === 'kelurahan')
                DB::table('ref_kelurahan')->where('id', $id)->delete();

            return back()->with('success', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function sync()
    {
        // Trigger seeding
        try {
            // Increase time limit if allowed
            if (function_exists('set_time_limit')) {
                @set_time_limit(600);
            }

            // FIX: Manually require the seeder file and Run it directly (Bypassing Artisan)
            $seederClass = 'Database\\Seeders\\RegionSeeder';

            if (!class_exists($seederClass)) {
                $seederPath = database_path('seeders/RegionSeeder.php');
                if (!file_exists($seederPath)) {
                    throw new \Exception("File RegionSeeder.php tidak ditemukan di: $seederPath");
                }
                require_once $seederPath;
            }

            if (!class_exists($seederClass)) {
                throw new \Exception("Class RegionSeeder tidak ditemukan setelah require file.");
            }

            // Instantiate and run directly
            $seeder = new $seederClass();

            // DO NOT set mock command. Let it be null so the Seeder knows it's not in CLI.

            $seeder->run();

            return back()->with('success', 'Data wilayah berhasil disinkronisasi.');
        } catch (\Throwable $e) {
            // Catch ALL errors including compilation errors if possible (though require catches parse errors differently)
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage() . ' on line ' . $e->getLine());
        }
    }
    /**
     * Format name to Title Case with abbreviation handling
     */
    private function formatName($name)
    {
        // Convert to Title Case
        $name = ucwords(strtolower($name));

        // Handle specific abbreviations
        $replacements = [
            'Dki ' => 'DKI ',
            'Di ' => 'DI ',
            'Diy ' => 'DIY ',
            'Nad ' => 'NAD ',
        ];

        foreach ($replacements as $search => $replace) {
            if (strpos($name, $search) === 0) {
                $name = str_replace($search, $replace, $name);
            }
        }

        return $name;
    }
}
