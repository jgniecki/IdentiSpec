<?php

declare(strict_types=1);

namespace IdentiSpec\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class CoreBoundaryTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function sourceFiles(): iterable
    {
        $sourceDirectory = dirname(__DIR__, 2) . '/src';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory));

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname() => [$file->getPathname()];
            }
        }
    }

    #[DataProvider('sourceFiles')]
    public function testProductionSourceHasNoNetworkTelemetryHiddenIoOrFrameworkCoupling(
        string $filename,
    ): void {
        $source = file_get_contents($filename);
        self::assertIsString($source);

        $forbiddenCalls = [
            'curl_exec', 'curl_init', 'fsockopen', 'pfsockopen', 'stream_socket_client',
            'file_get_contents', 'file_put_contents', 'fopen', 'fwrite', 'readfile',
            'exec', 'passthru', 'proc_open', 'shell_exec', 'system',
        ];

        foreach ($forbiddenCalls as $function) {
            self::assertDoesNotMatchRegularExpression(
                '/\\b' . preg_quote($function, '/') . '\\s*\\(/i',
                $source,
                sprintf('%s must not call %s().', $filename, $function),
            );
        }

        self::assertDoesNotMatchRegularExpression(
            '/\\b(?:Symfony|Laravel|Illuminate)\\\\/i',
            $source,
            sprintf('%s must remain framework-independent.', $filename),
        );
        self::assertDoesNotMatchRegularExpression(
            '/\\b(?:OpenTelemetry|Sentry|Datadog|NewRelic)\\b/i',
            $source,
            sprintf('%s must not emit telemetry.', $filename),
        );
    }

    public function testRuntimeDependenciesRemainLimitedToPhp(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($manifest);
        self::assertSame(['php' => '>=8.2.0'], $manifest['require'] ?? null);
    }
}
