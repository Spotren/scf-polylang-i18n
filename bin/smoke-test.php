<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$pluginFile = $rootDir . '/scf-polylang-i18n.php';
$readmeFile = $rootDir . '/readme.txt';
$composerFile = $rootDir . '/composer.json';
$potFile = $rootDir . '/languages/scf-polylang-i18n.pot';

assertFileExists($pluginFile);
assertFileExists($readmeFile);
assertFileExists($composerFile);
assertFileExists($potFile);

$pluginVersion = readPluginHeader($pluginFile, 'Version');
$requiresPhp = readPluginHeader($pluginFile, 'Requires PHP');
$stableTag = readReadmeHeader($readmeFile, 'Stable tag');
$composer = json_decode((string) file_get_contents($composerFile), true);

assertSameValue($pluginVersion, $stableTag, 'Stable tag must match the plugin version.');
assertSameValue('>=8.0', (string) ($composer['require']['php'] ?? ''), 'composer.json PHP constraint must match plugin support.');
assertSameValue('8.0', $requiresPhp, 'Plugin header must declare the supported PHP floor.');

if (isset($argv[1])) {
    runtimeSmokeTest($argv[1], $pluginFile);
}

fwrite(STDOUT, "Smoke test passed.\n");

function runtimeSmokeTest(string $wordpressRoot, string $pluginFile): void
{
    $wpLoad = rtrim($wordpressRoot, '/\\') . '/wp-load.php';

    assertFileExists($wpLoad);

    require_once $wpLoad;
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $pluginBasename = plugin_basename($pluginFile);

    assertTrue(
        is_plugin_active($pluginBasename) || is_plugin_inactive($pluginBasename),
        'Plugin must be discoverable by WordPress.'
    );

    require_once $pluginFile;

    assertTrue(class_exists('Spotren\\ScfPolylangI18n\\App'), 'Plugin bootstrap class must autoload.');
    assertTrue(function_exists('get_option'), 'WordPress option API must be available.');
}

function readPluginHeader(string $pluginFile, string $headerName): string
{
    $contents = (string) file_get_contents($pluginFile);
    $pattern = '/^[ \\t\\/*#@]*' . preg_quote($headerName, '/') . ':(.*)$/mi';

    if (preg_match($pattern, $contents, $matches) !== 1) {
        fail('Missing plugin header: ' . $headerName);
    }

    return trim($matches[1]);
}

function readReadmeHeader(string $readmeFile, string $headerName): string
{
    $contents = (string) file_get_contents($readmeFile);
    $pattern = '/^' . preg_quote($headerName, '/') . ':(.*)$/mi';

    if (preg_match($pattern, $contents, $matches) !== 1) {
        fail('Missing readme header: ' . $headerName);
    }

    return trim($matches[1]);
}

function assertFileExists(string $path): void
{
    assertTrue(is_file($path), 'Expected file not found: ' . $path);
}

function assertSameValue(string $expected, string $actual, string $message): void
{
    assertTrue($expected === $actual, $message . ' Expected "' . $expected . '", got "' . $actual . '".');
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fail($message);
    }
}

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
