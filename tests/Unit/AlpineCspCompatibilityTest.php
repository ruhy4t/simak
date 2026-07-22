<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AlpineCspCompatibilityTest extends TestCase
{
    public function test_alpine_event_handlers_use_single_csp_compatible_expressions(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = $file->getContents();
            preg_match_all(
                '/(?<attribute>@(?:click|change|submit|input|keydown|keyup|blur|focus)(?:\.[\w-]+)*)\s*=\s*"(?<expression>.*?)"/s',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $location = $file->getRelativePathname().' ['.$match['attribute'].']';

                $this->assertStringNotContainsString(';', $match['expression'], $location);
                $this->assertDoesNotMatchRegularExpression('/\bif\s*\(/', $match['expression'], $location);
            }
        }
    }

    public function test_named_alpine_components_are_registered_before_startup(): void
    {
        $componentSource = File::get(resource_path('js/alpine-components.js'));
        $appSource = File::get(resource_path('js/app.js'));
        $registeredComponents = [];

        preg_match_all("/Alpine\\.data\\('(?<name>[A-Za-z_$][A-Za-z0-9_$]*)'/", $componentSource, $registered);
        foreach ($registered['name'] ?? [] as $name) {
            $registeredComponents[$name] = true;
        }

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all('/x-data\s*=\s*"(?<expression>.*?)"/s', $file->getContents(), $matches);
            foreach ($matches['expression'] ?? [] as $expression) {
                if (! preg_match('/^\s*(?<name>[A-Za-z_$][A-Za-z0-9_$]*)(?:\s*\(|\s*$)/', $expression, $named)) {
                    continue;
                }

                $this->assertArrayHasKey(
                    $named['name'],
                    $registeredComponents,
                    $file->getRelativePathname().' uses an unregistered Alpine component.'
                );
            }
        }

        $this->assertLessThan(
            strpos($appSource, 'Alpine.start()'),
            strpos($appSource, 'registerAlpineComponents(Alpine)')
        );
    }

    public function test_user_management_views_do_not_depend_on_inline_event_handlers(): void
    {
        foreach (['users/manage.blade.php', 'dinas/users.blade.php'] as $view) {
            $contents = File::get(resource_path('views/'.$view));

            $this->assertDoesNotMatchRegularExpression(
                '/\son(?:click|submit|input|change)\s*=/i',
                $contents,
                $view,
            );
            $this->assertStringNotContainsString('<script', strtolower($contents), $view);
        }
    }

    public function test_ajax_safe_views_do_not_depend_on_inline_event_handlers(): void
    {
        foreach (config('ajax_navigation.safe_views') as $view) {
            $contents = File::get(base_path($view));

            $this->assertDoesNotMatchRegularExpression(
                '/\son(?:click|submit|input|change)\s*=/i',
                $contents,
                $view,
            );
        }
    }
}
