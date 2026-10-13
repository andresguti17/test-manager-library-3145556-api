<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Constitution Articles I–III as executable tests (T-19).
 */
class ArchitectureTest extends TestCase
{
    private static function appPath(string $sub = ''): string
    {
        return dirname(__DIR__, 2).'/app'.($sub === '' ? '' : '/'.$sub);
    }

    private function phpFiles(string $dir): array
    {
        $files = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    public function test_domain_depends_on_nothing(): void
    {
        $violations = [];
        foreach ($this->phpFiles(self::appPath('/Library/Domain')) as $file) {
            $src = file_get_contents($file);
            if (preg_match('/\b(Illuminate|Eloquent|Laravel|Symfony\\\\Component\\\\Http|Filament)\b/', $src, $m)) {
                $violations[] = "$file: {$m[0]}";
            }
        }
        $this->assertSame([], $violations, 'Article I: domain must not reference the framework');
    }

    public function test_application_never_references_adapters(): void
    {
        $violations = [];
        foreach ($this->phpFiles(self::appPath('/Library/Application')) as $file) {
            $src = file_get_contents($file);
            if (str_contains($src, 'Adapters')) {
                $violations[] = $file;
            }
        }
        $this->assertSame([], $violations, 'Article II: application must not reference adapters');
    }

    public function test_infrastructure_is_only_instantiated_in_the_composition_root(): void
    {
        $allowed = [
            'Library/Bootstrap/Composition/PortBindings.php',
            'Library/Bootstrap/LibraryServiceProvider.php',
        ];
        $violations = [];
        foreach ($this->phpFiles(self::appPath('')) as $file) {
            $norm = str_replace('\\', '/', $file);
            $skip = false;
            foreach ($allowed as $suffix) {
                if (str_ends_with($norm, $suffix)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }
            $src = file_get_contents($file);
            if (preg_match('/new\s+(Eloquent\w+|JwtIssuer|LaravelHashHasher|SystemClock|DiskCoverStorage|\w+Model)\s*\(/', $src, $m)) {
                $violations[] = "$file: {$m[0]}";
            }
        }
        $this->assertSame([], $violations, 'Article III: single composition point');
    }
}
