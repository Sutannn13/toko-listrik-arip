<?php

namespace Tests\Feature;

use Tests\TestCase;

class BladeConflictMarkerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_no_conflict_markers_in_blade_files(): void
    {
        $bladeFiles = $this->getAllBladeFiles();
        $this->assertNotEmpty($bladeFiles, 'Expected to find Blade files in the project');

        $conflicts = [];

        foreach ($bladeFiles as $filePath) {
            $content = file_get_contents($filePath);

            if (preg_match('/<<<<<<<.*?\n/', $content)) {
                $conflicts[] = $filePath . ' (contains <<<<<<<)';
            }

            if (preg_match('/=======\n/', $content)) {
                $conflicts[] = $filePath . ' (contains =======)';
            }

            if (preg_match('/>>>>>>>.*?\n/', $content)) {
                $conflicts[] = $filePath . ' (contains >>>>>>>)';
            }
        }

        if (count($conflicts) > 0) {
            $message = "Found unresolved merge conflict markers in:\n" . implode("\n", $conflicts);
            $this->fail($message);
        }

        $this->assertEmpty($conflicts, 'No conflict markers should exist in any Blade files');
    }

    public function test_no_conflict_markers_in_app_source_files(): void
    {
        $conflicts = [];

        $phpFiles = glob(base_path('app/**/*.php'));
        foreach ($phpFiles as $filePath) {
            $content = file_get_contents($filePath);
            if (preg_match('/<<<<<<<|=======|>>>>>>>/', $content)) {
                $conflicts[] = $filePath;
            }
        }

        $this->assertEmpty($conflicts, 'No conflict markers should exist in app source files');
    }

    public function test_no_conflict_markers_in_config_files(): void
    {
        $conflicts = [];

        $configFiles = glob(base_path('config/**/*.php'));
        foreach ($configFiles as $filePath) {
            $content = file_get_contents($filePath);
            if (preg_match('/<<<<<<<|=======|>>>>>>>/', $content)) {
                $conflicts[] = $filePath;
            }
        }

        $this->assertEmpty($conflicts, 'No conflict markers should exist in config files');
    }

    private function getAllBladeFiles(): array
    {
        $bladeFiles = [];
        $resourcesPath = base_path('resources/views');

        if (! is_dir($resourcesPath)) {
            return $bladeFiles;
        }

        // Use glob with recursive pattern for cross-platform compatibility
        $pattern = $resourcesPath . '/**/*.blade.php';
        $globResults = glob($pattern, GLOB_NOSORT);

        if ($globResults === false) {
            return $bladeFiles;
        }

        foreach ($globResults as $filePath) {
            if (is_file($filePath)) {
                $bladeFiles[] = str_replace('\\', '/', $filePath);
            }
        }

        return $bladeFiles;
    }
}