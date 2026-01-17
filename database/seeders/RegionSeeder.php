<<<<<<< HEAD
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->log('Start seeding regional data...');

        try {
            // 1. Provinsi
            $this->log('Fetching Provinsi...');
            // Replace Http::get with native fetch to avoid Guzzle issues
            $provinces = $this->fetchJson('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');

            if (!$provinces) {
                throw new \Exception("Gagal mengambil data provinsi (Empty response)");
            }

            foreach ($provinces as $prov) {
                DB::table('ref_provinsi')->updateOrInsert(
                    ['id' => $prov['id']],
                    ['nama' => $this->formatName($prov['name']), 'created_at' => now(), 'updated_at' => now()]
                );
            }
            $this->log('Provinsi seeded: ' . count($provinces));

            // 2. Kabupaten/Kota
            // Use progress bar if available (CLI only)
            $bar = null;
            if (isset($this->command) && method_exists($this->command, 'getOutput')) {
                $output = $this->command->getOutput();
                if ($output) { // Ensure output object is not null
                    $bar = $output->createProgressBar(count($provinces));
                }
            }

            if ($bar)
                $bar->start();

            foreach ($provinces as $prov) {
                // Fetch Regencies for this Province
                $regencies = $this->fetchJson("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$prov['id']}.json");

                if ($regencies) {
                    foreach ($regencies as $reg) {
                        DB::table('ref_kabupaten')->updateOrInsert(
                            ['id' => $reg['id']],
                            [
                                'province_id' => $reg['province_id'],
                                'nama' => $this->formatName($reg['name']),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                    }
                }

                if ($bar)
                    $bar->advance();
            }

            if ($bar)
                $bar->finish();
            $this->log("\nSeeding completed!");

        } catch (\Exception $e) {
            // Log::error('Seeding region failed: ' . $e->getMessage());
            // Commented out Log to avoid dependency if Log failing too, but usually Log is safe.
            // Let's keep it simple.
            $this->log('Seeding failed: ' . $e->getMessage());
            // Re-throw to be caught by controller
            throw $e;
        }
    }

    /**
     * Safe log to command or file
     */
    private function log($message)
    {
        if (isset($this->command) && method_exists($this->command, 'info')) {
            $this->command->info($message);
        } else {
            // Fallback to error_log or Laravel Log if possible, but keep it safe
            // error_log("RegionSeeder: " . $message);
        }
    }

    /**
     * Native JSON fetcher to avoid Guzzle dependencies
     */
    private function fetchJson($url)
    {
        // 1. Try CURL (Most robust)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for some shared hosts with old certs
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result) {
                return json_decode($result, true);
            }
        }

        // 2. Try file_get_contents (Fallback)
        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
                "http" => [
                    "timeout" => 30
                ]
            ]);
            $result = @file_get_contents($url, false, $context);
            if ($result) {
                return json_decode($result, true);
            }
        }

        return null;
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
=======
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->log('Start seeding regional data...');

        try {
            // 1. Provinsi
            $this->log('Fetching Provinsi...');
            // Replace Http::get with native fetch to avoid Guzzle issues
            $provinces = $this->fetchJson('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');

            if (!$provinces) {
                throw new \Exception("Gagal mengambil data provinsi (Empty response)");
            }

            foreach ($provinces as $prov) {
                DB::table('ref_provinsi')->updateOrInsert(
                    ['id' => $prov['id']],
                    ['nama' => $this->formatName($prov['name']), 'created_at' => now(), 'updated_at' => now()]
                );
            }
            $this->log('Provinsi seeded: ' . count($provinces));

            // 2. Kabupaten/Kota
            // Use progress bar if available (CLI only)
            $bar = null;
            if (isset($this->command) && method_exists($this->command, 'getOutput')) {
                $output = $this->command->getOutput();
                if ($output) { // Ensure output object is not null
                    $bar = $output->createProgressBar(count($provinces));
                }
            }

            if ($bar)
                $bar->start();

            foreach ($provinces as $prov) {
                // Fetch Regencies for this Province
                $regencies = $this->fetchJson("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$prov['id']}.json");

                if ($regencies) {
                    foreach ($regencies as $reg) {
                        DB::table('ref_kabupaten')->updateOrInsert(
                            ['id' => $reg['id']],
                            [
                                'province_id' => $reg['province_id'],
                                'nama' => $this->formatName($reg['name']),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                    }
                }

                if ($bar)
                    $bar->advance();
            }

            if ($bar)
                $bar->finish();
            $this->log("\nSeeding completed!");

        } catch (\Exception $e) {
            // Log::error('Seeding region failed: ' . $e->getMessage());
            // Commented out Log to avoid dependency if Log failing too, but usually Log is safe.
            // Let's keep it simple.
            $this->log('Seeding failed: ' . $e->getMessage());
            // Re-throw to be caught by controller
            throw $e;
        }
    }

    /**
     * Safe log to command or file
     */
    private function log($message)
    {
        if (isset($this->command) && method_exists($this->command, 'info')) {
            $this->command->info($message);
        } else {
            // Fallback to error_log or Laravel Log if possible, but keep it safe
            // error_log("RegionSeeder: " . $message);
        }
    }

    /**
     * Native JSON fetcher to avoid Guzzle dependencies
     */
    private function fetchJson($url)
    {
        // 1. Try CURL (Most robust)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for some shared hosts with old certs
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result) {
                return json_decode($result, true);
            }
        }

        // 2. Try file_get_contents (Fallback)
        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
                "http" => [
                    "timeout" => 30
                ]
            ]);
            $result = @file_get_contents($url, false, $context);
            if ($result) {
                return json_decode($result, true);
            }
        }

        return null;
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
>>>>>>> 9466a07 (Upload project pertama kali)
