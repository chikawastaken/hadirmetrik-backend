<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Enforces the modular monolith rule: a module may only reach into another
 * module through its Contracts namespace. Infrastructure, Application and Http
 * classes are private to the module that owns them.
 *
 * Deliberately dependency-free (no arch-testing package) so it runs with the
 * existing PHPUnit setup.
 */
class ModuleBoundaryTest extends TestCase
{
    private const MODULE_ROOT = 'App\\Modules\\';

    public function test_modules_only_depend_on_other_modules_contracts(): void
    {
        $violations = [];

        foreach ($this->moduleFiles() as $file) {
            $source = (string) file_get_contents($file->getPathname());
            $currentModule = $this->moduleOf($source);

            if ($currentModule === null) {
                continue;
            }

            foreach ($this->importedModules($source) as $import) {
                [$module, $remainder] = $import;

                if ($module === $currentModule) {
                    continue;
                }

                if (! str_starts_with($remainder, '\\Contracts\\') && $remainder !== '\\Contracts') {
                    $violations[] = sprintf(
                        '%s imports %s\\%s (only %s\\Contracts may cross a module boundary)',
                        $file->getPathname(),
                        $module,
                        ltrim($remainder, '\\'),
                        $module,
                    );
                }
            }
        }

        $this->assertSame([], $violations, "Module boundary violations:\n".implode("\n", $violations));
    }

    /**
     * @return list<SplFileInfo>
     */
    private function moduleFiles(): array
    {
        $root = dirname(__DIR__, 2).'/app/Modules';
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function moduleOf(string $source): ?string
    {
        if (preg_match('/^namespace\s+'.preg_quote(self::MODULE_ROOT, '/').'([A-Za-z0-9_]+)/m', $source, $matches) !== 1) {
            return null;
        }

        return self::MODULE_ROOT.$matches[1];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function importedModules(string $source): array
    {
        preg_match_all('/^use\s+(App\\\\Modules\\\\[^;]+);/m', $source, $matches);

        $imports = [];

        foreach ($matches[1] as $import) {
            // App\Modules\<Module>\Rest...  ->  ["App\Modules\<Module>", "\Rest..."]
            if (preg_match('/^'.preg_quote(self::MODULE_ROOT, '/').'([A-Za-z0-9_]+)(.*)$/', $import, $parts) === 1) {
                $imports[] = [self::MODULE_ROOT.$parts[1], $parts[2]];
            }
        }

        return $imports;
    }
}
